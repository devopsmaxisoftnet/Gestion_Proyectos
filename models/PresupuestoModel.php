<?php
/**
 * Presupuesto por insumos de cada actividad del WBS (APU: materiales, mano de obra, equipos, subcontratos).
 * Gestión de Proyectos - Costeo Basado en Actividades
 *
 * Reglas:
 *   - Solo las actividades HOJA llevan insumos: el presupuesto de un padre es la suma de sus hijas.
 *   - Si una hoja tiene insumos, tareas.costo_estimado = SUM(parcial) (lo sincroniza TareaModel).
 *   - Cada insumo se compara con el consumo real que ya imputan compras, almacén y tareo:
 *       material   → material_id  (salidas de almacén + compras directas)
 *       equipo     → equipo_id    (salidas en horas + alquileres por OC)
 *       mano_obra  → tipo_labor   (horas del tareo)
 *       subcontrato→ sin clave    (se compara a nivel de categoría)
 */
require_once __DIR__ . '/TareaModel.php';

class PresupuestoModel {

    public const TIPOS = [
        'material'    => ['label' => 'Materiales',    'unidad' => 'und'],
        'mano_obra'   => ['label' => 'Mano de obra',  'unidad' => 'h'],
        'equipo'      => ['label' => 'Equipos',       'unidad' => 'h'],
        'subcontrato' => ['label' => 'Subcontratos',  'unidad' => 'glb'],
    ];
    public const LABORES = ['Operativo', 'Supervisión', 'Técnico', 'Mantenimiento', 'Apoyo'];
    private const MAX_LINEAS = 300;

    private TareaModel $tareas;

    public function __construct() {
        $this->tareas = new TareaModel();
    }

    public function disponible(): bool {
        return (bool)dbQueryOne(
            "SELECT 1 AS x FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tarea_insumos'"
        );
    }

    // ═════════════════════════════════════════════════════════════════════
    //  LECTURA
    // ═════════════════════════════════════════════════════════════════════

    /** Insumos presupuestados de una actividad, en el orden en que se capturaron. */
    public function getInsumos(int $tareaId): array {
        if (!$this->disponible()) { return []; }
        $rows = dbQueryAll(
            "SELECT id, tipo, material_id, equipo_id, tipo_labor, descripcion, unidad, cantidad, precio_unitario, parcial
             FROM tarea_insumos WHERE tarea_id = ? ORDER BY orden, id", [$tareaId]);
        foreach ($rows as &$r) {
            $r['id'] = (int)$r['id'];
            $r['material_id'] = $r['material_id'] !== null ? (int)$r['material_id'] : null;
            $r['equipo_id'] = $r['equipo_id'] !== null ? (int)$r['equipo_id'] : null;
            $r['cantidad'] = (float)$r['cantidad'];
            $r['precio_unitario'] = (float)$r['precio_unitario'];
            $r['parcial'] = (float)$r['parcial'];
        }
        return $rows;
    }

    /**
     * Cantidad de insumos y total presupuestado por actividad de un proyecto.
     * @return array<int, array{n:int, total:float}>
     */
    public function getResumenPorTarea(int $proyectoId): array {
        if (!$this->disponible()) { return []; }
        $out = [];
        foreach (dbQueryAll(
            "SELECT i.tarea_id, COUNT(*) n, COALESCE(SUM(i.parcial), 0) total
             FROM tarea_insumos i JOIN tareas t ON t.id = i.tarea_id
             WHERE t.proyecto_id = ? GROUP BY i.tarea_id", [$proyectoId]) as $r) {
            $out[(int)$r['tarea_id']] = ['n' => (int)$r['n'], 'total' => (float)$r['total']];
        }
        return $out;
    }

    /** Catálogos para el autocompletado del formulario (solo activos). */
    public function getCatalogos(): array {
        return [
            'materiales' => array_map(fn($m) => [
                'id' => (int)$m['id'], 'codigo' => $m['codigo'], 'nombre' => $m['nombre'], 'unidad' => $m['unidad_medida'],
                'precio' => round((float)($m['costo_promedio'] > 0 ? $m['costo_promedio'] : $m['precio_referencial']), 4),
            ], dbQueryAll("SELECT id, codigo, nombre, unidad_medida, precio_referencial, costo_promedio FROM cat_materiales WHERE activo = 1 ORDER BY nombre")),
            'equipos' => array_map(fn($e) => [
                'id' => (int)$e['id'], 'codigo' => $e['codigo'], 'nombre' => $e['nombre'], 'unidad' => 'h',
                'precio' => round((float)$e['costo_hora'], 4),
            ], dbQueryAll("SELECT id, codigo, nombre, costo_hora FROM cat_equipos WHERE activo = 1 ORDER BY nombre")),
            'labores' => self::LABORES,
        ];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  ESCRITURA
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Reemplaza TODO el presupuesto por insumos de una actividad hoja y recalcula su costo estimado
     * (y el de sus ancestros). Una lista vacía quita el detalle y la actividad vuelve a presupuesto manual.
     *
     * @param array $lineas [['tipo','material_id','equipo_id','tipo_labor','descripcion','unidad','cantidad','precio_unitario'], ...]
     * @return array{lineas:int, total:float}
     */
    public function guardarInsumos(int $tareaId, array $lineas): array {
        if (!$this->disponible()) {
            throw new Exception('Falta la tabla tarea_insumos: ejecuta la migración (database/migration_costeo_actividades.sql).');
        }
        $tarea = dbQueryOne("SELECT id, codigo, proyecto_id FROM tareas WHERE id = ?", [$tareaId]);
        if (!$tarea) { throw new Exception('La actividad no existe.'); }
        if ($this->tareas->contarDescendientes($tareaId) > 0) {
            throw new Exception("La actividad {$tarea['codigo']} tiene sub-actividades: el presupuesto por insumos se registra en las actividades hoja.");
        }
        if (count($lineas) > self::MAX_LINEAS) {
            throw new Exception('Máximo ' . self::MAX_LINEAS . ' insumos por actividad.');
        }

        $limpias = $this->validarLineas($lineas);

        $pdo = getConnection();
        $pdo->beginTransaction();
        try {
            dbExecute("DELETE FROM tarea_insumos WHERE tarea_id = ?", [$tareaId]);
            foreach ($limpias as $i => $l) {
                dbExecute(
                    "INSERT INTO tarea_insumos (tarea_id, tipo, material_id, equipo_id, tipo_labor, descripcion, unidad, cantidad, precio_unitario, parcial, orden)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$tareaId, $l['tipo'], $l['material_id'], $l['equipo_id'], $l['tipo_labor'], $l['descripcion'], $l['unidad'],
                     $l['cantidad'], $l['precio_unitario'], $l['parcial'], $i]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Sincroniza presupuesto de la hoja, de sus ancestros y del proyecto
        $this->tareas->recalcularCostoRealTarea($tareaId);

        return ['lineas' => count($limpias), 'total' => round(array_sum(array_column($limpias, 'parcial')), 2)];
    }

    /** Valida y normaliza las líneas; lanza Exception con el número de fila que falla. */
    private function validarLineas(array $lineas): array {
        $out = [];
        $vistos = [];
        $n = 0;
        foreach ($lineas as $raw) {
            $n++;
            if (!is_array($raw)) { continue; }
            $tipo = (string)($raw['tipo'] ?? '');
            if (!isset(self::TIPOS[$tipo])) { throw new Exception("Fila $n: tipo de insumo inválido."); }

            $cant = $this->numero($raw['cantidad'] ?? 0);
            $precio = $this->numero($raw['precio_unitario'] ?? 0);
            if ($cant === null || $cant <= 0) { throw new Exception("Fila $n: la cantidad debe ser mayor a 0."); }
            if ($precio === null || $precio < 0) { throw new Exception("Fila $n: el precio unitario no puede ser negativo."); }
            if ($cant > 99999999 || $precio > 99999999) { throw new Exception("Fila $n: cantidad o precio fuera de rango."); }

            $desc = trim((string)($raw['descripcion'] ?? ''));
            $unidad = mb_substr(trim((string)($raw['unidad'] ?? '')), 0, 20);
            $materialId = null; $equipoId = null; $labor = null;

            if ($tipo === 'material' && !empty($raw['material_id'])) {
                $m = dbQueryOne("SELECT id, nombre, unidad_medida FROM cat_materiales WHERE id = ?", [(int)$raw['material_id']]);
                if (!$m) { throw new Exception("Fila $n: el material seleccionado no existe en el catálogo."); }
                $materialId = (int)$m['id'];
                $desc = $m['nombre'];
                if ($unidad === '') { $unidad = (string)$m['unidad_medida']; }
            } elseif ($tipo === 'equipo' && !empty($raw['equipo_id'])) {
                $e = dbQueryOne("SELECT id, nombre FROM cat_equipos WHERE id = ?", [(int)$raw['equipo_id']]);
                if (!$e) { throw new Exception("Fila $n: el equipo seleccionado no existe en el catálogo."); }
                $equipoId = (int)$e['id'];
                $desc = $e['nombre'];
                $unidad = 'h';
            } elseif ($tipo === 'mano_obra') {
                $labor = trim((string)($raw['tipo_labor'] ?? ''));
                if (!in_array($labor, self::LABORES, true)) { throw new Exception("Fila $n: selecciona el tipo de labor (el mismo que se usa en el tareo)."); }
                if ($desc === '') { $desc = $labor; }
                $unidad = 'h';
            }

            if ($desc === '') { throw new Exception("Fila $n: indica la descripción del insumo."); }
            if ($unidad === '') { $unidad = self::TIPOS[$tipo]['unidad']; }

            // Un mismo material/equipo/labor no puede repetirse: la comparación con el real es por clave
            $clave = $materialId ? "m:$materialId" : ($equipoId ? "e:$equipoId" : ($labor !== null ? "l:$labor" : null));
            if ($clave !== null) {
                if (isset($vistos[$clave])) { throw new Exception("Fila $n: «{$desc}» está repetido. Suma las cantidades en una sola fila."); }
                $vistos[$clave] = true;
            }

            $out[] = [
                'tipo' => $tipo, 'material_id' => $materialId, 'equipo_id' => $equipoId, 'tipo_labor' => $labor,
                'descripcion' => mb_substr($desc, 0, 255), 'unidad' => $unidad,
                'cantidad' => round($cant, 3), 'precio_unitario' => round($precio, 4),
                'parcial' => round($cant * $precio, 2),
            ];
        }
        return $out;
    }

    private function numero($v): ?float {
        if (is_string($v)) { $v = str_replace(',', '', trim($v)); }
        return is_numeric($v) ? (float)$v : null;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  PRESUPUESTO vs REAL
    // ═════════════════════════════════════════════════════════════════════

    /** Semáforo de ejecución: verde < 80 %, ámbar 80-100 %, rojo > 100 %. Sin presupuesto y con gasto = rojo. */
    private function semaforo(float $plan, float $real): array {
        if ($plan <= 0) {
            return $real > 0 ? ['clase' => 'danger', 'pct' => null, 'label' => 'No presupuestado'] : ['clase' => 'neutral', 'pct' => 0.0, 'label' => 'Sin consumo'];
        }
        $pct = round($real / $plan * 100, 1);
        if ($real <= 0) { return ['clase' => 'neutral', 'pct' => 0.0, 'label' => 'Sin consumo']; }
        if ($pct > 100) { return ['clase' => 'danger', 'pct' => $pct, 'label' => 'Excedido']; }
        if ($pct >= 80) { return ['clase' => 'warn', 'pct' => $pct, 'label' => 'En el límite']; }
        return ['clase' => 'ok', 'pct' => $pct, 'label' => 'En rango'];
    }

    /** Semáforo para una categoría: conserva el nombre (label) y expone el estado aparte. */
    private function semCategoria(float $plan, float $real): array {
        $s = $this->semaforo($plan, $real);
        return ['clase' => $s['clase'], 'pct' => $s['pct'], 'estado' => $s['label']];
    }

    /** Nombres legibles de las claves de consumo no presupuestado (material/equipo del catálogo). */
    private function nombresClaves(array $claves): array {
        $mat = []; $eq = [];
        foreach ($claves as $c) {
            if (str_starts_with($c, 'm:')) { $mat[] = (int)substr($c, 2); }
            elseif (str_starts_with($c, 'e:')) { $eq[] = (int)substr($c, 2); }
        }
        $nombres = [];
        if ($mat) {
            $in = implode(',', array_fill(0, count($mat), '?'));
            foreach (dbQueryAll("SELECT id, nombre, unidad_medida FROM cat_materiales WHERE id IN ($in)", $mat) as $r) {
                $nombres['m:' . $r['id']] = [$r['nombre'], $r['unidad_medida']];
            }
        }
        if ($eq) {
            $in = implode(',', array_fill(0, count($eq), '?'));
            foreach (dbQueryAll("SELECT id, nombre FROM cat_equipos WHERE id IN ($in)", $eq) as $r) {
                $nombres['e:' . $r['id']] = [$r['nombre'], 'h'];
            }
        }
        return $nombres;
    }

    /**
     * Compara presupuesto y consumo de UNA actividad (solo propios; las hijas se comparan en cada hoja).
     *
     * @return array{lineas:array, categorias:array, plan:float, real:float}
     */
    public function getComparativo(int $tareaId): array {
        $r = $this->calcular([$tareaId]);
        return $r['por_tarea'][$tareaId] ?? $this->vacio();
    }

    private function vacio(): array {
        $cats = [];
        foreach (self::TIPOS as $k => $t) { $cats[$k] = ['label' => $t['label'], 'plan' => 0.0, 'real' => 0.0] + $this->semCategoria(0, 0); }
        return ['lineas' => [], 'categorias' => $cats, 'plan' => 0.0, 'real' => 0.0];
    }

    /**
     * Comparativo de todo el proyecto: por categoría, cobertura del presupuesto detallado y mayores desviaciones.
     */
    public function getResumenProyecto(int $proyectoId): array {
        $tareas = dbQueryAll(
            "SELECT t.id, t.codigo, t.nombre, t.costo_estimado,
                    NOT EXISTS (SELECT 1 FROM tareas c WHERE c.parent_id = t.id) AS es_hoja
             FROM tareas t WHERE t.proyecto_id = ?", [$proyectoId]);
        $ids = array_map(fn($t) => (int)$t['id'], $tareas);
        $calc = $this->calcular($ids);

        $cats = $this->vacio()['categorias'];
        $planTotal = 0.0; $realTotal = 0.0; $realSinDetalle = 0.0;
        foreach ($calc['por_tarea'] as $tid => $d) {
            // Comparación homogénea: solo actividades con presupuesto detallado (el resto se informa aparte)
            if (!isset($calc['plan_por_tarea'][$tid])) { $realSinDetalle += $d['real']; continue; }
            foreach ($d['categorias'] as $k => $c) { $cats[$k]['plan'] += $c['plan']; $cats[$k]['real'] += $c['real']; }
        }
        foreach ($cats as $k => &$c) {
            $c['plan'] = round($c['plan'], 2); $c['real'] = round($c['real'], 2);
            $c = array_merge($c, $this->semCategoria($c['plan'], $c['real']));
            $planTotal += $c['plan']; $realTotal += $c['real'];
        }
        unset($c);

        // Cobertura: cuánto del presupuesto de las hojas está desglosado en insumos
        $conInsumos = 0.0; $sinDesglosar = 0.0; $hojasConIns = 0; $hojas = 0;
        foreach ($tareas as $t) {
            if (!(int)$t['es_hoja']) { continue; }
            $hojas++;
            if (isset($calc['plan_por_tarea'][(int)$t['id']])) { $conInsumos += $calc['plan_por_tarea'][(int)$t['id']]; $hojasConIns++; }
            else { $sinDesglosar += (float)$t['costo_estimado']; }
        }
        $baseCobertura = $conInsumos + $sinDesglosar;

        // Mayores desviaciones de insumos (sobrecosto absoluto) entre todas las actividades
        $desv = [];
        $codigos = array_column($tareas, null, 'id');
        foreach ($calc['por_tarea'] as $tid => $d) {
            foreach ($d['lineas'] as $l) {
                if ($l['real'] === null || $l['sem'] === null || in_array($l['clave'], ['libre', 'sub'], true) || $l['real'] <= $l['plan'] + 0.005) { continue; }
                $desv[] = [
                    'tarea_id' => (int)$tid, 'actividad' => ($codigos[$tid]['codigo'] ?? '') . ' · ' . ($codigos[$tid]['nombre'] ?? ''),
                    'tipo' => $l['tipo'], 'insumo' => $l['descripcion'], 'plan' => $l['plan'], 'real' => $l['real'],
                    'exceso' => round($l['real'] - $l['plan'], 2), 'presupuestado' => $l['plan'] > 0,
                ];
            }
        }
        usort($desv, fn($a, $b) => $b['exceso'] <=> $a['exceso']);

        return [
            'categorias' => $cats,
            'plan' => round($planTotal, 2), 'real' => round($realTotal, 2), 'real_sin_detalle' => round($realSinDetalle, 2),
            'cobertura' => [
                'con_insumos' => round($conInsumos, 2), 'sin_desglosar' => round($sinDesglosar, 2),
                'pct' => $baseCobertura > 0 ? round($conInsumos / $baseCobertura * 100, 1) : 0.0,
                'hojas' => $hojas, 'hojas_con_insumos' => $hojasConIns,
            ],
            'desviaciones' => array_slice($desv, 0, 8),
            'n_excedidos' => count($desv),
        ];
    }

    /**
     * Núcleo: construye el comparativo plan/real para una lista de actividades.
     *
     * @param int[] $tareaIds
     * @return array{por_tarea: array<int,array>, plan_por_tarea: array<int,float>}
     */
    private function calcular(array $tareaIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $tareaIds))));
        if (empty($ids) || !$this->disponible()) { return ['por_tarea' => [], 'plan_por_tarea' => []]; }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $plan = [];
        foreach (dbQueryAll(
            "SELECT tarea_id, tipo, material_id, equipo_id, tipo_labor, descripcion, unidad, cantidad, precio_unitario, parcial
             FROM tarea_insumos WHERE tarea_id IN ($in) ORDER BY orden, id", $ids) as $r) {
            $plan[(int)$r['tarea_id']][] = $r;
        }
        $realMap = $this->tareas->consultarRealPorClave($ids);

        // Nombres de los consumos no presupuestados (una sola consulta para todas las actividades)
        $clavesLibres = [];
        foreach ($realMap as $tid => $porTipo) {
            foreach ($porTipo as $claves) { foreach (array_keys($claves) as $c) { $clavesLibres[$c] = true; } }
        }
        $nombres = $this->nombresClaves(array_keys($clavesLibres));

        $porTarea = []; $planPorTarea = [];
        foreach ($ids as $tid) {
            if (!isset($plan[$tid]) && !isset($realMap[$tid])) { continue; }
            $d = $this->vacio();
            $real = $realMap[$tid] ?? [];
            $usadas = [];   // [tipo][clave] consumidas por alguna línea presupuestada

            foreach ($plan[$tid] ?? [] as $l) {
                $tipo = $l['tipo'];
                $clave = !empty($l['material_id']) ? 'm:' . $l['material_id']
                       : (!empty($l['equipo_id']) ? 'e:' . $l['equipo_id']
                       : ($tipo === 'mano_obra' ? 'l:' . $l['tipo_labor'] : null));
                $rq = null; $rc = null;
                if ($clave !== null) {
                    $rq = round((float)($real[$tipo][$clave]['qty'] ?? 0), 3);
                    $rc = round((float)($real[$tipo][$clave]['costo'] ?? 0), 2);
                    $usadas[$tipo][$clave] = true;
                }
                $p = (float)$l['parcial'];
                $d['lineas'][] = [
                    'tipo' => $tipo, 'descripcion' => $l['descripcion'], 'unidad' => $l['unidad'], 'clave' => $clave,
                    'plan_qty' => (float)$l['cantidad'], 'plan_precio' => (float)$l['precio_unitario'], 'plan' => $p,
                    'real_qty' => $rq, 'real' => $rc,
                    'sem' => $clave !== null ? $this->semaforo($p, (float)$rc) : null,
                    'no_presupuestado' => false,
                ];
                $d['categorias'][$tipo]['plan'] += $p;
                $planPorTarea[$tid] = ($planPorTarea[$tid] ?? 0.0) + $p;
            }

            // Consumo real que no corresponde a ninguna línea presupuestada
            foreach (['material', 'equipo', 'mano_obra', 'subcontrato'] as $tipo) {
                foreach ($real[$tipo] ?? [] as $clave => $v) {
                    $costo = round($v['costo'], 2);
                    $d['categorias'][$tipo]['real'] += $costo;
                    if (isset($usadas[$tipo][$clave])) { continue; }
                    if ($clave === 'libre')      { $desc = 'Compras sin vincular a un material del catálogo'; $u = ''; $np = false; }
                    elseif ($clave === 'sub')    { $desc = 'Subcontratos imputados a la actividad'; $u = ''; $np = false; }
                    elseif (str_starts_with($clave, 'l:')) { $desc = 'Mano de obra «' . substr($clave, 2) . '» no presupuestada'; $u = 'h'; $np = true; }
                    else { [$desc, $u] = $nombres[$clave] ?? ['Ítem #' . substr($clave, 2), '']; $np = true; }
                    // Si la categoría tiene líneas sin clave (texto libre / subcontratos) lo "sin vincular" no es una desviación
                    $d['lineas'][] = [
                        'tipo' => $tipo, 'descripcion' => $desc, 'unidad' => $u, 'clave' => $clave,
                        'plan_qty' => 0.0, 'plan_precio' => 0.0, 'plan' => 0.0,
                        'real_qty' => round($v['qty'], 3), 'real' => $costo,
                        'sem' => $np ? $this->semaforo(0, $costo) : ['clase' => 'neutral', 'pct' => null, 'label' => 'Sin vincular'],
                        'no_presupuestado' => $np,
                    ];
                }
            }

            foreach ($d['categorias'] as $k => &$c) {
                $c['plan'] = round($c['plan'], 2); $c['real'] = round($c['real'], 2);
                $c = array_merge($c, $this->semCategoria($c['plan'], $c['real']));
                $d['plan'] += $c['plan']; $d['real'] += $c['real'];
            }
            unset($c);
            $d['plan'] = round($d['plan'], 2); $d['real'] = round($d['real'], 2);
            $porTarea[$tid] = $d;
        }
        return ['por_tarea' => $porTarea, 'plan_por_tarea' => $planPorTarea];
    }
}
