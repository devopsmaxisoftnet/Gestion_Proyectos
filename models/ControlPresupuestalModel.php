<?php
/**
 * Control presupuestal por actividad (control de disponibilidad) + compromisos.
 * Gestión de Proyectos - Costeo Basado en Actividades
 *
 * Capas de cada actividad (en soles):
 *   Presupuesto   = tareas.costo_estimado
 *   Ejecutado     = consumo: salidas de almacén + tareo + subcontratos + compras ya recibidas/servicios
 *   Por recibir   = OC aprobadas aún no recepcionadas en almacén (compromiso firme; ya está dentro del costo real)
 *   En aprobación = OC pendientes de aprobación (compromiso potencial; NO está en el costo real)
 *   Por valorizar = subcontratos activos: monto contratado menos lo ya valorizado y aprobado (compromiso; NO está en el costo real)
 *   Disponible    = Presupuesto − costo real − En aprobación − Por valorizar
 *
 * Modos de control por proyecto:
 *   informar : solo tableros
 *   advertir : avisa al operar y registra el exceso (por defecto)
 *   bloquear : una operación que excede el presupuesto exige autorización con motivo de un usuario facultado
 */
require_once __DIR__ . '/TareaModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class ControlPresupuestalModel {

    public const MODOS = ['informar', 'advertir', 'bloquear'];
    private const EPS = 0.005;
    public const MOTIVO_MIN = 10;

    private TareaModel $tareas;

    public function __construct() {
        $this->tareas = new TareaModel();
    }

    public function disponible(): bool {
        $r = dbQueryOne(
            "SELECT COUNT(*) n FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('presupuesto_control', 'presupuesto_excesos')");
        return (int)($r['n'] ?? 0) === 2;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  CONFIGURACIÓN
    // ═════════════════════════════════════════════════════════════════════

    public function getConfig(int $proyectoId): array {
        $def = ['modo' => 'advertir', 'tolerancia' => 0.0, 'umbral' => 80.0, 'configurado' => false];
        if (!$this->disponible()) { return $def; }
        $r = dbQueryOne("SELECT modo, tolerancia_pct, umbral_alerta_pct FROM presupuesto_control WHERE proyecto_id = ?", [$proyectoId]);
        if (!$r) { return $def; }
        return ['modo' => $r['modo'], 'tolerancia' => (float)$r['tolerancia_pct'], 'umbral' => (float)$r['umbral_alerta_pct'], 'configurado' => true];
    }

    public function guardarConfig(int $proyectoId, string $modo, float $tolerancia, float $umbral, int $userId): void {
        if (!$this->disponible()) { throw new Exception('Falta aplicar la migración del control presupuestal (database/migration_costeo_actividades.sql).'); }
        if (!in_array($modo, self::MODOS, true)) { throw new Exception('Modo de control inválido.'); }
        if (!dbQueryOne("SELECT id FROM proyectos WHERE id = ?", [$proyectoId])) { throw new Exception('El proyecto no existe.'); }
        if ($tolerancia < 0 || $tolerancia > 50) { throw new Exception('La tolerancia debe estar entre 0 % y 50 %.'); }
        if ($umbral < 1 || $umbral > 100) { throw new Exception('El umbral de alerta debe estar entre 1 % y 100 %.'); }
        dbExecute(
            "INSERT INTO presupuesto_control (proyecto_id, modo, tolerancia_pct, umbral_alerta_pct, actualizado_por)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE modo = VALUES(modo), tolerancia_pct = VALUES(tolerancia_pct),
                                     umbral_alerta_pct = VALUES(umbral_alerta_pct), actualizado_por = VALUES(actualizado_por)",
            [$proyectoId, $modo, round($tolerancia, 2), round($umbral, 2), $userId ?: null]);
    }

    /** ¿El usuario en sesión puede autorizar un exceso? Administradores o quien tenga permiso de aprobar en el módulo. */
    public static function puedeAutorizar(string $modulo): bool {
        $u = $_SESSION['user'] ?? [];
        if (empty($u['id'])) { return false; }
        if (in_array($u['rol'] ?? '', ['superadmin', 'admin'], true)) { return true; }
        return PermisosHelper::puede('aprobar', $modulo);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  ESTADO DEL PROYECTO
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Saldo por valorizar de los subcontratos ACTIVOS por actividad (solo montos propios, sin hijas).
     * @return array<int,float> [tarea_id => monto]
     */
    public function subcontratosPorValorizar(int $proyectoId, ?int $excluirSubId = null): array {
        $r = dbQueryOne("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subcontrato_valorizaciones'");
        if ((int)($r['n'] ?? 0) === 0) { return []; }
        $sql = "SELECT s.tarea_id, COALESCE(SUM(GREATEST(s.monto - COALESCE(v.val, 0), 0)), 0) AS monto
                FROM subcontratos s
                LEFT JOIN (SELECT subcontrato_id, SUM(monto_bruto) AS val FROM subcontrato_valorizaciones
                           WHERE estado IN ('aprobada', 'pagada') GROUP BY subcontrato_id) v ON v.subcontrato_id = s.id
                WHERE s.proyecto_id = ? AND s.estado = 'activo' AND s.tarea_id IS NOT NULL";
        $params = [$proyectoId];
        if ($excluirSubId) { $sql .= " AND s.id <> ?"; $params[] = $excluirSubId; }
        $out = [];
        foreach (dbQueryAll($sql . " GROUP BY s.tarea_id", $params) as $x) { $out[(int)$x['tarea_id']] = (float)$x['monto']; }
        return $out;
    }

    /** Clasifica una actividad: sin_uso | ok | alerta | excedido | sin_presupuesto */
    public static function clasificar(float $presupuesto, float $usado, array $cfg): string {
        if ($presupuesto <= 0) { return $usado > self::EPS ? 'sin_presupuesto' : 'sin_uso'; }
        if ($usado > $presupuesto * (1 + $cfg['tolerancia'] / 100) + self::EPS) { return 'excedido'; }
        if ($usado > $presupuesto + self::EPS) { return 'alerta'; }   // sobre el presupuesto pero dentro de la tolerancia
        if ($usado >= $presupuesto * $cfg['umbral'] / 100) { return 'alerta'; }
        return $usado <= self::EPS ? 'sin_uso' : 'ok';
    }

    public static function claseUI(string $estado): string {
        return ['excedido' => 'danger', 'sin_presupuesto' => 'danger', 'alerta' => 'warn', 'ok' => 'ok', 'sin_uso' => 'neutral'][$estado] ?? 'neutral';
    }

    public static function etiquetaEstado(string $estado): string {
        return ['excedido' => 'Excedido', 'sin_presupuesto' => 'Sin presupuesto', 'alerta' => 'En alerta', 'ok' => 'En rango', 'sin_uso' => 'Sin uso'][$estado] ?? $estado;
    }

    /**
     * Estado de todas las actividades del proyecto en orden de árbol, con los compromisos acumulados por rama.
     *
     * @param int|null $excluirOcId OC cuyo monto pendiente no debe contarse (cuando se evalúa su propia aprobación)
     * @param int|null $excluirSubId subcontrato cuyo saldo por valorizar no debe contarse (cuando se edita o reactiva)
     * @return array{filas:array, totales:array, config:array, conteo:array}
     */
    public function getEstadoProyecto(int $proyectoId, ?int $excluirOcId = null, ?int $excluirSubId = null): array {
        $cfg = $this->getConfig($proyectoId);
        $arbol = $this->tareas->construirArbol($this->tareas->getAllByProyecto($proyectoId));

        $propioRec = $this->tareas->comprasDirectasPorTarea($proyectoId);
        $propioPend = [];
        $sql = "SELECT d.tarea_id, COALESCE(SUM(d.total), 0) AS monto
                FROM orden_compra_detalles d
                JOIN ordenes_compra oc ON oc.id = d.orden_compra_id
                WHERE oc.proyecto_id = ? AND oc.estado = 'pendiente_aprobacion' AND d.tarea_id IS NOT NULL";
        $params = [$proyectoId];
        if ($excluirOcId) { $sql .= " AND oc.id <> ?"; $params[] = $excluirOcId; }
        foreach (dbQueryAll($sql . " GROUP BY d.tarea_id", $params) as $r) { $propioPend[(int)$r['tarea_id']] = (float)$r['monto']; }

        // Subcontratos activos: lo contratado que todavía no se valoriza
        $propioVal = $this->subcontratosPorValorizar($proyectoId, $excluirSubId);

        // Acumulación hijas → padres (procesando de lo más profundo a la raíz)
        $rec = []; $pend = []; $val = []; $padre = []; $nivel = [];
        foreach ($arbol as $t) {
            $id = (int)$t['id'];
            $rec[$id] = $propioRec[$id] ?? 0.0; $pend[$id] = $propioPend[$id] ?? 0.0; $val[$id] = $propioVal[$id] ?? 0.0;
            $padre[$id] = !empty($t['parent_id']) ? (int)$t['parent_id'] : 0;
            $nivel[$id] = (int)$t['_nivel'];
        }
        $ids = array_keys($nivel);
        usort($ids, fn($a, $b) => $nivel[$b] <=> $nivel[$a]);
        foreach ($ids as $id) {
            if ($padre[$id] && isset($rec[$padre[$id]])) { $rec[$padre[$id]] += $rec[$id]; $pend[$padre[$id]] += $pend[$id]; $val[$padre[$id]] += $val[$id]; }
        }

        $filas = [];
        $tot = ['presupuesto' => 0.0, 'real' => 0.0, 'ejecutado' => 0.0, 'por_recibir' => 0.0, 'por_valorizar' => 0.0, 'pendiente' => 0.0, 'disponible' => 0.0];
        $conteo = ['excedido' => 0, 'alerta' => 0, 'sin_presupuesto' => 0, 'ok' => 0, 'sin_uso' => 0];
        foreach ($arbol as $t) {
            $id = (int)$t['id'];
            $p = round((float)$t['costo_estimado'], 2);
            $real = round((float)$t['costo_real'], 2);
            $pr = round(min($rec[$id], $real), 2);               // por recibir nunca supera el costo real
            $pe = round($pend[$id], 2);
            $pv = round($val[$id], 2);
            $ejec = round(max(0, $real - $pr), 2);
            $usado = $real + $pe + $pv;
            $estado = self::clasificar($p, $usado, $cfg);
            $esHoja = !$t['_tiene_hijos'];
            $filas[] = [
                'id' => $id, 'codigo' => $t['codigo'], 'nombre' => $t['nombre'], 'nivel' => (int)$t['_nivel'], 'es_hoja' => $esHoja,
                'presupuesto' => $p, 'real' => $real, 'ejecutado' => $ejec, 'por_recibir' => $pr, 'pendiente' => $pe, 'por_valorizar' => $pv,
                'disponible' => round($p - $usado, 2),
                'pct' => $p > 0 ? round($usado / $p * 100, 1) : null,
                'pct_ejec' => $p > 0 ? round($ejec / $p * 100, 2) : 0.0,
                'pct_rec' => $p > 0 ? round($pr / $p * 100, 2) : 0.0,
                'pct_pend' => $p > 0 ? round($pe / $p * 100, 2) : 0.0,
                'pct_val' => $p > 0 ? round($pv / $p * 100, 2) : 0.0,
                'estado' => $estado, 'clase' => self::claseUI($estado), 'etiqueta' => self::etiquetaEstado($estado),
            ];
            if ((int)($t['_nivel']) === 0) {
                $tot['presupuesto'] += $p; $tot['real'] += $real; $tot['ejecutado'] += $ejec; $tot['por_recibir'] += $pr; $tot['por_valorizar'] += $pv; $tot['pendiente'] += $pe;
            }
            if ($esHoja) { $conteo[$estado]++; }   // se cuentan las hojas: son donde se imputa el gasto
        }
        $tot['disponible'] = $tot['presupuesto'] - $tot['real'] - $tot['pendiente'] - $tot['por_valorizar'];
        foreach ($tot as $k => $v) { $tot[$k] = round($v, 2); }

        return ['filas' => $filas, 'totales' => $tot, 'config' => $cfg, 'conteo' => $conteo];
    }

    /**
     * Datos mínimos para la verificación en vivo de los formularios (OC y vale de salida).
     * 'usado' = costo real + OC en aprobación (sin contar $excluirOcId, la orden que se está editando).
     *
     * @return array{config:array, tareas:array<int,array{usado:float, presupuesto:float}>}
     */
    public function getDisponibilidad(int $proyectoId, ?int $excluirOcId = null, ?int $excluirSubId = null): array {
        $est = $this->getEstadoProyecto($proyectoId, $excluirOcId, $excluirSubId);
        $t = [];
        foreach ($est['filas'] as $f) {
            $t[$f['id']] = ['usado' => round($f['real'] + $f['pendiente'] + $f['por_valorizar'], 2), 'presupuesto' => $f['presupuesto']];
        }
        return ['config' => $est['config'], 'tareas' => $t];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  EVALUACIÓN PREVIA DE UNA OPERACIÓN
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Evalúa el impacto de cargar montos adicionales a actividades.
     *
     * @param array<int,float> $cargos [tarea_id => monto adicional]
     * @return array{modo:string, items:array, hay_exceso:bool, hay_alerta:bool, bloquea:bool, exceso_total:float}
     */
    public function evaluar(int $proyectoId, array $cargos, ?int $excluirOcId = null, ?int $excluirSubId = null): array {
        $est = $this->getEstadoProyecto($proyectoId, $excluirOcId, $excluirSubId);
        $cfg = $est['config'];
        $porId = array_column($est['filas'], null, 'id');
        $items = []; $hayExceso = false; $hayAlerta = false; $excesoTotal = 0.0;

        foreach ($cargos as $tareaId => $monto) {
            $monto = round((float)$monto, 2);
            if ($monto <= 0 || !isset($porId[(int)$tareaId])) { continue; }
            $f = $porId[(int)$tareaId];
            $antes = $f['real'] + $f['pendiente'] + $f['por_valorizar'];
            $proy = round($antes + $monto, 2);
            $estado = self::clasificar($f['presupuesto'], $proy, $cfg);
            $nivel = in_array($estado, ['excedido', 'sin_presupuesto'], true) ? 'exceso' : ($estado === 'alerta' ? 'alerta' : 'ok');
            $exceso = $nivel === 'exceso' ? round($proy - max(0, $f['presupuesto']), 2) : 0.0;
            if ($nivel === 'exceso') { $hayExceso = true; $excesoTotal += $exceso; }
            if ($nivel === 'alerta') { $hayAlerta = true; }
            $items[] = [
                'tarea_id' => (int)$tareaId, 'codigo' => $f['codigo'], 'nombre' => $f['nombre'],
                'presupuesto' => $f['presupuesto'], 'usado_previo' => round($antes, 2), 'monto' => $monto, 'proyectado' => $proy,
                'disponible_antes' => round($f['presupuesto'] - $antes, 2), 'disponible_despues' => round($f['presupuesto'] - $proy, 2),
                'pct_antes' => $f['presupuesto'] > 0 ? round($antes / $f['presupuesto'] * 100, 1) : null,
                'pct_despues' => $f['presupuesto'] > 0 ? round($proy / $f['presupuesto'] * 100, 1) : null,
                'nivel' => $nivel, 'exceso' => $exceso, 'sin_presupuesto' => $f['presupuesto'] <= 0,
            ];
        }
        usort($items, fn($a, $b) => [$b['nivel'] === 'exceso', $b['exceso']] <=> [$a['nivel'] === 'exceso', $a['exceso']]);

        return [
            'modo' => $cfg['modo'], 'tolerancia' => $cfg['tolerancia'], 'umbral' => $cfg['umbral'],
            'items' => $items, 'hay_exceso' => $hayExceso, 'hay_alerta' => $hayAlerta,
            'bloquea' => $hayExceso && $cfg['modo'] === 'bloquear', 'exceso_total' => round($excesoTotal, 2),
        ];
    }

    /** Evalúa la aprobación de una OC (sus líneas con actividad se suman por actividad). */
    public function evaluarOC(int $ocId): ?array {
        $oc = dbQueryOne("SELECT id, proyecto_id, estado FROM ordenes_compra WHERE id = ?", [$ocId]);
        if (!$oc) { return null; }
        $cargos = [];
        foreach (dbQueryAll("SELECT tarea_id, SUM(total) AS monto FROM orden_compra_detalles WHERE orden_compra_id = ? AND tarea_id IS NOT NULL GROUP BY tarea_id", [$ocId]) as $r) {
            $cargos[(int)$r['tarea_id']] = (float)$r['monto'];
        }
        return $this->evaluar((int)$oc['proyecto_id'], $cargos, $ocId);
    }

    /**
     * Evalúa un vale de salida: material × costo promedio, equipo × horas × tarifa (mismo precio que se usará al registrar).
     * @param array $items [['tarea_id','material_id','equipo_id','cantidad'], ...]
     */
    public function evaluarSalida(int $proyectoId, array $items): array {
        $cargos = [];
        foreach ($items as $it) {
            $tid = (int)($it['tarea_id'] ?? 0);
            $cant = (float)($it['cantidad'] ?? 0);
            if ($tid <= 0 || $cant <= 0) { continue; }
            $precio = 0.0;
            if (!empty($it['material_id'])) {
                $m = dbQueryOne("SELECT costo_promedio, precio_referencial FROM cat_materiales WHERE id = ?", [(int)$it['material_id']]);
                $precio = $m ? ((float)$m['costo_promedio'] > 0 ? (float)$m['costo_promedio'] : (float)$m['precio_referencial']) : 0.0;
            } elseif (!empty($it['equipo_id'])) {
                $e = dbQueryOne("SELECT costo_hora FROM cat_equipos WHERE id = ?", [(int)$it['equipo_id']]);
                $precio = $e ? (float)$e['costo_hora'] : 0.0;
            }
            $cargos[$tid] = ($cargos[$tid] ?? 0.0) + round($cant * round($precio, 2), 2);
        }
        return $this->evaluar($proyectoId, $cargos);
    }

    /**
     * Evalúa crear, editar o reactivar un subcontrato: lo que compromete es el monto contratado menos lo ya valorizado
     * (lo valorizado ya está en el costo real). $subId excluye el saldo actual del propio contrato.
     */
    public function evaluarSubcontrato(int $proyectoId, int $tareaId, float $monto, ?int $subId = null): array {
        $valorizado = 0.0;
        if ($subId) {
            $r = dbQueryOne("SELECT COALESCE(SUM(monto_bruto), 0) v FROM subcontrato_valorizaciones WHERE subcontrato_id = ? AND estado IN ('aprobada', 'pagada')", [$subId]);
            $valorizado = (float)($r['v'] ?? 0);
        }
        return $this->evaluar($proyectoId, $tareaId > 0 ? [$tareaId => max(0, $monto - $valorizado)] : [], null, $subId);
    }

    /** Texto corto para mensajes: «02.01 Zapatas (+S/ 1,200.00)». */
    public static function resumenExcesos(array $eval, int $max = 4): string {
        $txt = [];
        foreach ($eval['items'] as $i) {
            if ($i['nivel'] !== 'exceso') { continue; }
            $txt[] = $i['codigo'] . ' ' . $i['nombre'] . ($i['sin_presupuesto'] ? ' (sin presupuesto)' : ' (+S/ ' . number_format($i['exceso'], 2) . ')');
        }
        $extra = count($txt) - $max;
        return implode('; ', array_slice($txt, 0, $max)) . ($extra > 0 ? " y $extra más" : '');
    }

    /** Valida y normaliza el motivo de una autorización de exceso. Lanza Exception si no cumple. */
    public static function validarMotivo(?string $motivo): string {
        $m = trim(preg_replace('/\s+/', ' ', (string)$motivo));
        if (mb_strlen($m) < self::MOTIVO_MIN) {
            throw new Exception('Indica el motivo de la autorización del exceso (mínimo ' . self::MOTIVO_MIN . ' caracteres).');
        }
        return mb_substr($m, 0, 500);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  BITÁCORA DE EXCESOS
    // ═════════════════════════════════════════════════════════════════════

    /** Registra en la bitácora cada actividad que quedó por encima del presupuesto. Devuelve cuántas filas guardó. */
    public function registrarExcesos(int $proyectoId, array $eval, string $origen, ?int $origenId, string $ref, ?string $motivo, int $userId): int {
        if (!$this->disponible() || $eval['modo'] === 'informar' || !in_array($origen, ['oc', 'salida', 'subcontrato'], true)) { return 0; }
        $n = 0;
        foreach ($eval['items'] as $i) {
            if ($i['nivel'] !== 'exceso') { continue; }
            $t = dbQueryOne("SELECT codigo, nombre FROM tareas WHERE id = ?", [$i['tarea_id']]);
            dbExecute(
                "INSERT INTO presupuesto_excesos (proyecto_id, tarea_id, tarea_codigo, tarea_nombre, origen, origen_id, origen_ref, modo,
                                                  presupuesto, usado_previo, monto, exceso, motivo, autorizado_por)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$proyectoId, $i['tarea_id'], $t['codigo'] ?? $i['codigo'], mb_substr($t['nombre'] ?? $i['nombre'], 0, 255), $origen, $origenId,
                 mb_substr($ref, 0, 60), $eval['modo'], $i['presupuesto'], $i['usado_previo'], $i['monto'], $i['exceso'],
                 $motivo !== null && $motivo !== '' ? mb_substr($motivo, 0, 500) : null, $userId ?: null]);
            $n++;
        }
        return $n;
    }

    public function getExcesos(int $proyectoId, int $limite = 12): array {
        if (!$this->disponible()) { return []; }
        $limite = max(1, min(200, $limite));
        return dbQueryAll(
            "SELECT e.id, e.tarea_codigo, e.tarea_nombre, e.origen, e.origen_id, e.origen_ref, e.modo, e.presupuesto, e.usado_previo,
                    e.monto, e.exceso, e.motivo, e.created_at, u.nombre AS autorizado_por
             FROM presupuesto_excesos e LEFT JOIN usuarios u ON u.id = e.autorizado_por
             WHERE e.proyecto_id = ? ORDER BY e.id DESC LIMIT $limite", [$proyectoId]);
    }

    public function getResumenExcesos(int $proyectoId): array {
        if (!$this->disponible()) { return ['n' => 0, 'total' => 0.0]; }
        $r = dbQueryOne("SELECT COUNT(*) n, COALESCE(SUM(exceso), 0) total FROM presupuesto_excesos WHERE proyecto_id = ?", [$proyectoId]);
        return ['n' => (int)$r['n'], 'total' => round((float)$r['total'], 2)];
    }
}
