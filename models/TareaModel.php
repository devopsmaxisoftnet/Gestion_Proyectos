<?php
/**
 * Modelo de WBS / Tareas de Proyecto (Fase 2: Planificación y EDT)
 * Costeo Basado en Actividades (ABC): cada tarea es un mini Centro de Costo.
 * Gestión de Proyectos - ERP Core
 *
 * Reglas de costeo (costo_real de una actividad):
 *   + Compras directas : líneas de OC (aprobada/recibida) imputadas a la tarea
 *                        que NO ingresaron a almacén (evita doble conteo con las salidas).
 *   + Subcontratos     : valorizaciones APROBADAS o PAGADAS (monto bruto) de los subcontratos imputados a la tarea.
 *                        Lo contratado y aún sin valorizar es un compromiso (ver ControlPresupuestalModel).
 *   + Almacén          : salidas a obra imputadas a la tarea (materiales / equipos).
 *   + Mano de obra     : SUM(asistencias.costo_calculado) de la tarea.
 *   + Sub-actividades  : costo_real de cada hija (roll-up).
 *
 * Presupuesto (costo_estimado): manual en una hoja, SUM(tarea_insumos.parcial) si la hoja tiene
 * presupuesto por insumos (ver PresupuestoModel), y la suma de las hijas en un padre.
 */
require_once __DIR__ . '/../config/database.php';

class TareaModel {

    /** Caché de columnas por tabla durante la petición */
    private static array $columnasCache = [];

    // ═════════════════════════════════════════════════════════════════════
    //  INTROSPECCIÓN DE ESQUEMA (tolera migraciones parciales)
    // ═════════════════════════════════════════════════════════════════════

    private function columnas(string $tabla): array {
        if (!isset(self::$columnasCache[$tabla])) {
            $rows = dbQueryAll(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$tabla]
            );
            self::$columnasCache[$tabla] = array_map('strtolower', array_column($rows, 'COLUMN_NAME'));
        }
        return self::$columnasCache[$tabla];
    }

    private function tieneColumna(string $tabla, string $columna): bool {
        return in_array(strtolower($columna), $this->columnas($tabla), true);
    }

    /**
     * Verifica que la migración FASE 0 esté aplicada. Devuelve la lista de elementos faltantes.
     */
    public function getEsquemaFaltante(): array {
        $requeridos = [
            ['tareas', 'parent_id'],
            ['orden_compra_detalles', 'tarea_id'],
            ['subcontratos', 'tarea_id'],
            ['almacen_movimientos_detalles', 'tarea_id'],
            ['almacen_movimientos', 'estado'],
            ['cat_materiales', 'costo_promedio'],
            ['cat_materiales', 'stock_minimo'],
            ['asistencias', 'tarea_id'],
            ['asistencias', 'costo_calculado'],
            ['profesionales', 'costo_hora'],
            ['profesionales', 'costo_hora_extra'],
            ['tarea_insumos', 'tarea_id'],
            ['subcontrato_valorizaciones', 'monto_bruto'],
        ];
        $faltan = [];
        foreach ($requeridos as [$tabla, $col]) {
            if (!$this->tieneColumna($tabla, $col)) {
                $faltan[] = "$tabla.$col";
            }
        }
        return $faltan;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  CONSULTAS BÁSICAS
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Obtiene todas las tareas WBS asignadas a un proyecto con sus responsables
     */
    public function getAllByProyecto(int $proyectoId, array $filtros = []): array {
        $where = "t.proyecto_id = ?";
        $params = [$proyectoId];

        if (!empty($filtros['estado'])) {
            $where .= " AND t.estado = ?";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['prioridad'])) {
            $where .= " AND t.prioridad = ?";
            $params[] = $filtros['prioridad'];
        }

        if (!empty($filtros['busqueda'])) {
            $where .= " AND (t.nombre LIKE ? OR t.codigo LIKE ? OR t.descripcion LIKE ?)";
            $term = "%" . trim($filtros['busqueda']) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql = "SELECT t.*,
                       u.nombre as resp_nombre, u.email as resp_email,
                       DATEDIFF(t.fecha_fin, t.fecha_inicio) as duracion_dias
                FROM tareas t
                LEFT JOIN usuarios u ON t.responsable_id = u.id
                WHERE $where
                ORDER BY t.orden ASC, t.codigo ASC, t.fecha_inicio ASC";

        return dbQueryAll($sql, $params);
    }

    /**
     * Obtiene una tarea específica por su ID
     */
    public function getById(int $id): ?array {
        $sql = "SELECT t.*,
                       p.nombre as proyecto_nombre, p.codigo as proyecto_codigo,
                       u.nombre as resp_nombre, u.email as resp_email,
                       DATEDIFF(t.fecha_fin, t.fecha_inicio) as duracion_dias
                FROM tareas t
                JOIN proyectos p ON t.proyecto_id = p.id
                LEFT JOIN usuarios u ON t.responsable_id = u.id
                WHERE t.id = ? LIMIT 1";

        return dbQueryOne($sql, [$id]) ?: null;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  ÁRBOL WBS
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Convierte la lista plana en una lista ordenada en profundidad (DFS) con metadatos:
     *   _nivel (0 = raíz), _tiene_hijos, _ancestros (ids separados por coma).
     * Los nodos cuyo padre no está en el conjunto (p.ej. por filtros) se tratan como raíces.
     */
    public function construirArbol(array $tareas): array {
        $porId = [];
        foreach ($tareas as $t) {
            $porId[(int)$t['id']] = $t;
        }

        $hijos = [];
        $raices = [];
        foreach ($porId as $id => $t) {
            $pid = (int)($t['parent_id'] ?? 0);
            if ($pid > 0 && $pid !== $id && isset($porId[$pid])) {
                $hijos[$pid][] = $id;
            } else {
                $raices[] = $id;
            }
        }

        $resultado = [];
        $visitados = [];
        $recorrer = function (int $id, int $nivel, array $ancestros) use (&$recorrer, &$resultado, &$visitados, $porId, $hijos) {
            if (isset($visitados[$id])) { return; }
            $visitados[$id] = true;
            $nodo = $porId[$id];
            $nodo['_nivel'] = $nivel;
            $nodo['_tiene_hijos'] = !empty($hijos[$id]);
            $nodo['_ancestros'] = implode(',', $ancestros);
            $resultado[] = $nodo;
            foreach ($hijos[$id] ?? [] as $hijoId) {
                $recorrer($hijoId, $nivel + 1, array_merge($ancestros, [$id]));
            }
        };

        foreach ($raices as $rid) {
            $recorrer($rid, 0, []);
        }
        // Nodos en ciclos (datos corruptos): se muestran como raíces para no perderlos
        foreach (array_keys($porId) as $id) {
            if (!isset($visitados[$id])) {
                $recorrer($id, 0, []);
            }
        }

        return $resultado;
    }

    /**
     * Semáforo de salud financiera: Verde < 80 %, Ámbar 80-100 %, Rojo > 100 %.
     */
    public static function calcularSalud(float $real, float $estimado): array {
        if ($estimado <= 0) {
            if ($real > 0) {
                return ['pct' => 100.0, 'clase' => 'danger', 'label' => 'Sin presupuesto', 'color' => '#dc2626'];
            }
            return ['pct' => 0.0, 'clase' => 'neutral', 'label' => 'Sin presupuesto', 'color' => '#94a3b8'];
        }
        $pct = round(($real / $estimado) * 100, 1);
        if ($pct > 100) {
            return ['pct' => $pct, 'clase' => 'danger', 'label' => 'Sobrecosto', 'color' => '#dc2626'];
        }
        if ($pct >= 80) {
            return ['pct' => $pct, 'clase' => 'warn', 'label' => 'En riesgo', 'color' => '#d97706'];
        }
        return ['pct' => $pct, 'clase' => 'ok', 'label' => 'Saludable', 'color' => '#16a34a'];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  CRUD
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Crea una nueva tarea / entregable / sub-actividad WBS
     */
    public function create(array $data): int {
        $proyectoId = (int)($data['proyecto_id'] ?? 0);
        $codigo = trim($data['codigo'] ?? '');
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $fechaInicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : date('Y-m-d');
        $fechaFin = !empty($data['fecha_fin']) ? $data['fecha_fin'] : date('Y-m-d', strtotime('+7 days'));
        $progreso = isset($data['progreso']) ? max(0, min(100, (float)$data['progreso'])) : 0.00;
        $estado = !empty($data['estado']) ? $data['estado'] : 'pendiente';
        $prioridad = !empty($data['prioridad']) ? $data['prioridad'] : 'media';
        $responsableId = !empty($data['responsable_id']) ? (int)$data['responsable_id'] : null;
        $costoEstimado = isset($data['costo_estimado']) ? max(0, (float)$data['costo_estimado']) : 0.00;
        $orden = isset($data['orden']) ? (int)$data['orden'] : 0;
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;

        if (!$proyectoId || empty($nombre)) {
            throw new Exception("El ID de proyecto y el nombre de la tarea son obligatorios.");
        }
        if ($fechaFin < $fechaInicio) {
            throw new Exception("La fecha de fin no puede ser anterior a la fecha de inicio.");
        }
        if ($parentId !== null) {
            $this->validarPadre(null, $parentId, $proyectoId);
        }

        if (empty($codigo)) {
            $count = dbQueryOne("SELECT COUNT(*) as c FROM tareas WHERE proyecto_id = ?", [$proyectoId]);
            $next = ((int)($count['c'] ?? 0)) + 1;
            $codigo = 'EDT-' . str_pad($next, 2, '0', STR_PAD_LEFT);
        }

        // Auto-estado según progreso
        $estado = $this->normalizarEstado($progreso, $estado);

        $campos = ['proyecto_id', 'codigo', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin',
                   'progreso', 'estado', 'prioridad', 'responsable_id', 'costo_estimado', 'costo_real', 'orden'];
        $valores = [$proyectoId, $codigo, $nombre, $descripcion, $fechaInicio, $fechaFin,
                    $progreso, $estado, $prioridad, $responsableId, $costoEstimado, 0.00, $orden];

        if ($this->tieneColumna('tareas', 'parent_id')) {
            $campos[] = 'parent_id';
            $valores[] = $parentId;
        }

        $sql = "INSERT INTO tareas (" . implode(', ', $campos) . ")
                VALUES (" . implode(', ', array_fill(0, count($campos), '?')) . ")";
        $tareaId = dbInsert($sql, $valores);

        // El costo_real es siempre calculado; propaga el roll-up del presupuesto hacia el padre
        $this->recalcularCostoRealTarea($tareaId);
        return $tareaId;
    }

    /**
     * Actualiza una tarea WBS existente.
     * costo_real NO se edita manualmente: se recalcula desde compras, almacén y tareo.
     */
    public function update(int $id, array $data): bool {
        $actual = $this->getById($id);
        if (!$actual) {
            throw new Exception("La tarea no existe.");
        }

        $codigo = trim($data['codigo'] ?? '');
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $fechaInicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : date('Y-m-d');
        $fechaFin = !empty($data['fecha_fin']) ? $data['fecha_fin'] : date('Y-m-d');
        $progreso = isset($data['progreso']) ? max(0, min(100, (float)$data['progreso'])) : 0.00;
        $estado = !empty($data['estado']) ? $data['estado'] : 'pendiente';
        $prioridad = !empty($data['prioridad']) ? $data['prioridad'] : 'media';
        $responsableId = !empty($data['responsable_id']) ? (int)$data['responsable_id'] : null;
        $costoEstimado = isset($data['costo_estimado']) ? max(0, (float)$data['costo_estimado']) : 0.00;
        $orden = isset($data['orden']) ? (int)$data['orden'] : 0;

        if ($nombre === '' || $codigo === '') {
            throw new Exception("El código y el nombre de la tarea son obligatorios.");
        }
        if ($fechaFin < $fechaInicio) {
            throw new Exception("La fecha de fin no puede ser anterior a la fecha de inicio.");
        }

        $estado = $this->normalizarEstado($progreso, $estado);

        $set = "codigo = ?, nombre = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?,
                progreso = ?, estado = ?, prioridad = ?, responsable_id = ?,
                costo_estimado = ?, orden = ?";
        $params = [$codigo, $nombre, $descripcion, $fechaInicio, $fechaFin,
                   $progreso, $estado, $prioridad, $responsableId, $costoEstimado, $orden];

        $padreAnterior = $actual['parent_id'] ?? null;
        $padreNuevo = $padreAnterior;
        if ($this->tieneColumna('tareas', 'parent_id') && array_key_exists('parent_id', $data)) {
            $padreNuevo = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            if ($padreNuevo !== null) {
                $this->validarPadre($id, $padreNuevo, (int)$actual['proyecto_id']);
            }
            $set .= ", parent_id = ?";
            $params[] = $padreNuevo;
        }

        $params[] = $id;
        dbExecute("UPDATE tareas SET $set WHERE id = ?", $params);

        // Recalcular la tarea (y su cadena de ancestros). Si cambió de padre, también el padre anterior.
        $this->recalcularCostoRealTarea($id);
        if ($padreAnterior && (int)$padreAnterior !== (int)$padreNuevo) {
            $this->recalcularCostoRealTarea((int)$padreAnterior);
        }

        return true;
    }

    /**
     * Actualiza rápidamente el avance y estado de una tarea (AJAX)
     */
    public function updateProgreso(int $id, float $progreso, ?string $estado = null): bool {
        $progreso = max(0, min(100, $progreso));
        $estado = $this->normalizarEstado($progreso, $estado);

        dbExecute("UPDATE tareas SET progreso = ?, estado = ? WHERE id = ?", [$progreso, $estado, $id]);

        $tarea = $this->getById($id);
        if ($tarea && !empty($tarea['proyecto_id'])) {
            $this->sincronizarAvanceProyecto((int)$tarea['proyecto_id']);
        }

        return true;
    }

    /**
     * Elimina una tarea WBS (las sub-actividades se eliminan en cascada por la FK).
     */
    public function delete(int $id): bool {
        $tarea = $this->getById($id);
        if (!$tarea) {
            return false;
        }
        $proyectoId = (int)$tarea['proyecto_id'];
        $padreId = !empty($tarea['parent_id']) ? (int)$tarea['parent_id'] : null;

        dbExecute("DELETE FROM tareas WHERE id = ?", [$id]);

        if ($padreId) {
            $this->recalcularCostoRealTarea($padreId); // actualiza ancestros y proyecto
        } else {
            $this->sincronizarAvanceProyecto($proyectoId);
        }

        return true;
    }

    /**
     * Cuenta cuántas sub-actividades (descendientes) tiene una tarea.
     */
    public function contarDescendientes(int $id): int {
        if (!$this->tieneColumna('tareas', 'parent_id')) { return 0; }
        $row = dbQueryOne(
            "WITH RECURSIVE d AS (
                SELECT id FROM tareas WHERE parent_id = ?
                UNION ALL
                SELECT t.id FROM tareas t JOIN d ON t.parent_id = d.id
             ) SELECT COUNT(*) AS c FROM d",
            [$id]
        );
        return (int)($row['c'] ?? 0);
    }

    /**
     * Estado automático según avance (conserva 'bloqueada').
     */
    private function normalizarEstado(float $progreso, ?string $estado): string {
        $validos = ['pendiente', 'en_proceso', 'completada', 'bloqueada'];
        $estado = in_array($estado, $validos, true) ? $estado : null;

        if ($estado === 'bloqueada') { return 'bloqueada'; }
        if ($progreso >= 100) { return 'completada'; }
        if ($progreso > 0 && ($estado === null || $estado === 'pendiente')) { return 'en_proceso'; }
        return $estado ?? 'pendiente';
    }

    /**
     * Valida que el padre exista, sea del mismo proyecto y no genere ciclos.
     */
    private function validarPadre(?int $tareaId, int $padreId, int $proyectoId): void {
        if (!$this->tieneColumna('tareas', 'parent_id')) {
            throw new Exception("Falta la columna tareas.parent_id: ejecuta la migración FASE 0.");
        }
        if ($tareaId !== null && $padreId === $tareaId) {
            throw new Exception("Una actividad no puede ser su propia actividad padre.");
        }
        $padre = dbQueryOne("SELECT id, proyecto_id, codigo FROM tareas WHERE id = ?", [$padreId]);
        if (!$padre || (int)$padre['proyecto_id'] !== $proyectoId) {
            throw new Exception("La actividad padre no existe o pertenece a otro proyecto.");
        }
        if ($this->sumaInsumos($padreId) !== null) {
            throw new Exception("La actividad {$padre['codigo']} tiene presupuesto por insumos y no puede tener sub-actividades. "
                . "Elimina sus insumos primero (el presupuesto de un padre es la suma de sus hijas).");
        }
        if ($tareaId !== null) {
            // El padre no puede ser descendiente de la propia tarea
            $cur = $padreId;
            for ($i = 0; $i < 50 && $cur; $i++) {
                $row = dbQueryOne("SELECT parent_id FROM tareas WHERE id = ?", [$cur]);
                $cur = (int)($row['parent_id'] ?? 0);
                if ($cur === $tareaId) {
                    throw new Exception("No se puede mover una actividad dentro de una de sus propias sub-actividades.");
                }
            }
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    //  MOTOR DE COSTEO ABC
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Costos DIRECTOS (sin sub-actividades) agrupados por tarea.
     * Se filtra por una tarea concreta o por todas las de un proyecto.
     *
     * @return array<int, array{materiales:float,equipos:float,mano_obra:float,subcontratos:float,horas:float}>
     */
    private function consultarCostosDirectos(?int $tareaId, ?int $proyectoId, bool $global = false): array {
        if ($tareaId === null && $proyectoId === null && !$global) { return []; }

        $vacio = ['materiales' => 0.0, 'equipos' => 0.0, 'mano_obra' => 0.0, 'subcontratos' => 0.0, 'horas' => 0.0];
        $out = [];
        $acum = function (int $tid, string $clave, float $monto) use (&$out, $vacio) {
            if (!isset($out[$tid])) { $out[$tid] = $vacio; }
            $out[$tid][$clave] += $monto;
        };

        // Filtro de alcance reutilizable: columna de tarea según el alias de cada tabla
        $alcance = function (string $col) use ($tareaId, $proyectoId, $global): array {
            if ($global) { return ["$col IS NOT NULL", []]; }   // todo el portafolio
            return $tareaId !== null
                ? ["$col = ?", [$tareaId]]
                : ["$col IN (SELECT id FROM tareas WHERE proyecto_id = ?)", [$proyectoId]];
        };

        // 1) Compras directas: líneas de OC aprobada/recibida, descontando la parte ya recepcionada
        //    en almacén (esa parte llega a las actividades por las salidas, no por la compra).
        if ($this->tieneColumna('orden_compra_detalles', 'tarea_id')) {
            [$w, $p] = $alcance('d.tarea_id');
            [$joinsRecep, $factor] = $this->sqlRecepcion();
            // Las líneas de alquiler de equipos (equipo_id) cuentan en la categoría Equipos, el resto en Materiales
            $esEq = $this->tieneColumna('orden_compra_detalles', 'equipo_id') ? '(d.equipo_id IS NOT NULL)' : '0';
            $rows = dbQueryAll(
                "SELECT d.tarea_id, $esEq AS es_eq, COALESCE(SUM(d.total * $factor), 0) AS monto
                 FROM orden_compra_detalles d
                 JOIN ordenes_compra oc ON oc.id = d.orden_compra_id
                 $joinsRecep
                 WHERE d.tarea_id IS NOT NULL AND $w
                   AND oc.estado IN ('aprobada', 'recibida')
                 GROUP BY d.tarea_id, $esEq", $p);
            foreach ($rows as $r) { $acum((int)$r['tarea_id'], (int)$r['es_eq'] === 1 ? 'equipos' : 'materiales', (float)$r['monto']); }
        }

        // 2) Subcontratos: lo valorizado y aprobado (si la migración de valorizaciones no está aplicada, el monto contratado)
        if ($this->tieneColumna('subcontratos', 'tarea_id')) {
            [$w, $p] = $alcance('s.tarea_id');
            if ($this->tieneColumna('subcontrato_valorizaciones', 'monto_bruto')) {
                $rows = dbQueryAll(
                    "SELECT s.tarea_id, COALESCE(SUM(v.monto_bruto), 0) AS monto
                     FROM subcontrato_valorizaciones v
                     JOIN subcontratos s ON s.id = v.subcontrato_id
                     WHERE s.tarea_id IS NOT NULL AND $w AND v.estado IN ('aprobada', 'pagada')
                     GROUP BY s.tarea_id", $p);
            } else {
                $rows = dbQueryAll(
                    "SELECT s.tarea_id, COALESCE(SUM(s.monto), 0) AS monto
                     FROM subcontratos s
                     WHERE s.tarea_id IS NOT NULL AND $w AND s.estado IN ('activo', 'finalizado')
                     GROUP BY s.tarea_id", $p);
            }
            foreach ($rows as $r) { $acum((int)$r['tarea_id'], 'subcontratos', (float)$r['monto']); }
        }

        // 3) Salidas de almacén a obra
        $exprCosto = $this->exprCostoAlmacen('det');
        if ($exprCosto !== null) {
            [$w, $p] = $alcance('det.tarea_id');
            $esEquipo = $this->tieneColumna('almacen_movimientos_detalles', 'equipo_id')
                ? "CASE WHEN det.equipo_id IS NOT NULL THEN $exprCosto ELSE 0 END" : "0";
            $rows = dbQueryAll(
                "SELECT det.tarea_id,
                        COALESCE(SUM($exprCosto), 0) AS total,
                        COALESCE(SUM($esEquipo), 0)  AS equipos
                 FROM almacen_movimientos_detalles det
                 JOIN almacen_movimientos am ON am.id = det.movimiento_id AND am.tipo_movimiento = 'salida'" . $this->movActivo('am') . "
                 WHERE det.tarea_id IS NOT NULL AND $w
                 GROUP BY det.tarea_id", $p);
            foreach ($rows as $r) {
                $tid = (int)$r['tarea_id'];
                $acum($tid, 'equipos', (float)$r['equipos']);
                $acum($tid, 'materiales', (float)$r['total'] - (float)$r['equipos']);
            }
        }

        // 4) Mano de obra (tareo)
        if ($this->tieneColumna('asistencias', 'tarea_id') && $this->tieneColumna('asistencias', 'costo_calculado')) {
            [$w, $p] = $alcance('a.tarea_id');
            $rows = dbQueryAll(
                "SELECT a.tarea_id, COALESCE(SUM(a.costo_calculado), 0) AS monto,
                        COALESCE(SUM(a.horas_normales + a.horas_extras), 0) AS horas
                 FROM asistencias a
                 WHERE a.tarea_id IS NOT NULL AND $w
                 GROUP BY a.tarea_id", $p);
            foreach ($rows as $r) {
                $acum((int)$r['tarea_id'], 'mano_obra', (float)$r['monto']);
                $acum((int)$r['tarea_id'], 'horas', (float)$r['horas']);
            }
        }

        return $out;
    }

    /** Ignora los movimientos de almacén anulados (columna `estado` de la migración del Paso 3). */
    private function movActivo(string $alias): string {
        return $this->tieneColumna('almacen_movimientos', 'estado') ? " AND $alias.estado = 'registrado'" : '';
    }

    /**
     * Fragmentos SQL para saber qué parte de cada línea de OC ya ingresó a almacén.
     * La recepción se compara por (orden, material): si se recibió el 40 % de las unidades de un
     * material, el 40 % del importe de sus líneas deja de ser compra directa. Las líneas sin
     * material de catálogo (servicios, texto libre, equipos) nunca pasan por almacén → factor 1.
     *
     * @return array{0:string,1:string} [JOINs sobre alias d (detalle OC), expresión del factor 0..1 a computar]
     */
    private function sqlRecepcion(): array {
        $t = 'almacen_movimientos_detalles';
        if (!$this->tieneColumna($t, 'material_id') || !$this->tieneColumna('orden_compra_detalles', 'material_id')) {
            return ['', '1'];
        }
        $joins = "LEFT JOIN (SELECT am.orden_compra_id, rd.material_id, SUM(rd.cantidad) AS rec
                             FROM almacen_movimientos am
                             JOIN almacen_movimientos_detalles rd ON rd.movimiento_id = am.id
                             WHERE am.tipo_movimiento = 'ingreso' AND rd.material_id IS NOT NULL" . $this->movActivo('am') . "
                             GROUP BY am.orden_compra_id, rd.material_id) rcb
                       ON rcb.orden_compra_id = d.orden_compra_id AND rcb.material_id = d.material_id
                  LEFT JOIN (SELECT orden_compra_id, material_id, SUM(cantidad) AS ord
                             FROM orden_compra_detalles WHERE material_id IS NOT NULL
                             GROUP BY orden_compra_id, material_id) ord
                       ON ord.orden_compra_id = d.orden_compra_id AND ord.material_id = d.material_id";
        $factor = "(1 - COALESCE(LEAST(1, rcb.rec / NULLIF(ord.ord, 0)), 0))";
        return [$joins, $factor];
    }

    /**
     * Expresión SQL del costo de una línea de movimiento de almacén según las columnas disponibles.
     * Devuelve null si la tabla no existe o no tiene datos de costo/cantidad utilizables.
     */
    private function exprCostoAlmacen(string $alias): ?string {
        $t = 'almacen_movimientos_detalles';
        if (!$this->tieneColumna($t, 'tarea_id') || !$this->tieneColumna($t, 'movimiento_id')) { return null; }
        if ($this->tieneColumna($t, 'costo_total'))     { return "COALESCE($alias.costo_total, 0)"; }
        if (!$this->tieneColumna($t, 'cantidad'))       { return null; }
        if ($this->tieneColumna($t, 'costo_unitario'))  { return "COALESCE($alias.cantidad * $alias.costo_unitario, 0)"; }
        if ($this->tieneColumna($t, 'precio_unitario')) { return "COALESCE($alias.cantidad * $alias.precio_unitario, 0)"; }
        return null;
    }

    /**
     * MÉTODO MAESTRO: recalcula el costo_real de una actividad (y de toda su cadena de ancestros)
     * sumando compras, subcontratos, almacén, tareo y el costo de sus sub-actividades.
     * Si la actividad tiene hijas, su costo_estimado se sincroniza como suma de las hijas.
     * Finalmente sincroniza el avance y el monto ejecutado del proyecto.
     *
     * @return float costo_real resultante de la actividad
     */
    public function recalcularCostoRealTarea(int $tareaId): float {
        $tarea = dbQueryOne("SELECT id, proyecto_id, parent_id FROM tareas WHERE id = ?", [$tareaId]);
        if (!$tarea) { return 0.0; }
        $proyectoId = (int)$tarea['proyecto_id'];

        $resultado = 0.0;
        $actual = $tareaId;
        $primera = true;
        for ($nivel = 0; $nivel < 50 && $actual; $nivel++) {
            $costoActual = $this->recalcularNodo($actual);
            if ($primera) { $resultado = $costoActual; $primera = false; }
            $fila = dbQueryOne("SELECT parent_id FROM tareas WHERE id = ?", [$actual]);
            $actual = (int)($fila['parent_id'] ?? 0);
        }

        $this->sincronizarAvanceProyecto($proyectoId);
        return $resultado;
    }

    /**
     * Recalcula un único nodo: directos + hijas. No sube a ancestros ni toca el proyecto.
     */
    private function recalcularNodo(int $tareaId, ?array $directosPrecalculados = null): float {
        $directos = $directosPrecalculados ?? ($this->consultarCostosDirectos($tareaId, null)[$tareaId] ?? []);
        $propio = ($directos['materiales'] ?? 0) + ($directos['equipos'] ?? 0)
                + ($directos['mano_obra'] ?? 0) + ($directos['subcontratos'] ?? 0);

        $hijas = ['real' => 0.0, 'est' => 0.0, 'n' => 0];
        if ($this->tieneColumna('tareas', 'parent_id')) {
            $r = dbQueryOne("SELECT COUNT(*) n, COALESCE(SUM(costo_real),0) real_, COALESCE(SUM(costo_estimado),0) est
                             FROM tareas WHERE parent_id = ?", [$tareaId]);
            $hijas = ['real' => (float)$r['real_'], 'est' => (float)$r['est'], 'n' => (int)$r['n']];
        }

        $total = round($propio + $hijas['real'], 2);
        if ($hijas['n'] > 0) {
            dbExecute("UPDATE tareas SET costo_real = ?, costo_estimado = ? WHERE id = ?", [$total, round($hijas['est'], 2), $tareaId]);
        } elseif (($insumos = $this->sumaInsumos($tareaId)) !== null) {
            // Hoja con presupuesto por insumos: el presupuesto es la suma de sus parciales
            dbExecute("UPDATE tareas SET costo_real = ?, costo_estimado = ? WHERE id = ?", [$total, $insumos, $tareaId]);
        } else {
            dbExecute("UPDATE tareas SET costo_real = ? WHERE id = ?", [$total, $tareaId]);
        }
        return $total;
    }

    /**
     * Suma de los parciales del presupuesto por insumos de una actividad, o null si no tiene insumos
     * (en ese caso el presupuesto es el monto manual de la actividad).
     */
    public function sumaInsumos(int $tareaId): ?float {
        if (!$this->tieneColumna('tarea_insumos', 'tarea_id')) { return null; }
        $r = dbQueryOne("SELECT COUNT(*) n, COALESCE(SUM(parcial), 0) s FROM tarea_insumos WHERE tarea_id = ?", [$tareaId]);
        return (int)($r['n'] ?? 0) > 0 ? round((float)$r['s'], 2) : null;
    }

    /**
     * Compras directas aprobadas AÚN POR RECIBIR en almacén, por actividad (solo montos propios, sin hijas).
     * Es la parte del costo real que todavía es un compromiso firme (OC aprobada) y no un consumo.
     *
     * @return array<int,float> [tarea_id => monto]
     */
    public function comprasDirectasPorTarea(int $proyectoId): array {
        if (!$this->tieneColumna('orden_compra_detalles', 'tarea_id')) { return []; }
        [$joinsRecep, $factor] = $this->sqlRecepcion();
        $out = [];
        foreach (dbQueryAll(
            "SELECT d.tarea_id, COALESCE(SUM(d.total * $factor), 0) AS monto
             FROM orden_compra_detalles d
             JOIN ordenes_compra oc ON oc.id = d.orden_compra_id
             $joinsRecep
             WHERE d.tarea_id IN (SELECT id FROM tareas WHERE proyecto_id = ?)
               AND oc.estado IN ('aprobada', 'recibida')
             GROUP BY d.tarea_id", [$proyectoId]) as $r) {
            $out[(int)$r['tarea_id']] = (float)$r['monto'];
        }
        return $out;
    }
    /**
     * Consumo REAL de un conjunto de actividades, desagregado por insumo (cantidad y costo).
     * Usa exactamente las mismas reglas que consultarCostosDirectos, así los totales por categoría coinciden.
     *
     * Claves: 'm:<material_id>' | 'e:<equipo_id>' | 'l:<tipo_labor>' | 'libre' (compra sin catálogo) | 'sub' (subcontratos)
     * @param int[] $tareaIds
     * @return array<int, array<string, array<string, array{qty:float, costo:float}>>>  [tarea][tipo][clave]
     */
    public function consultarRealPorClave(array $tareaIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $tareaIds))));
        if (empty($ids)) { return []; }
        $in = implode(',', array_fill(0, count($ids), '?'));

        $out = [];
        $acum = function (int $tid, string $tipo, string $clave, float $qty, float $costo) use (&$out) {
            $x = &$out[$tid][$tipo][$clave];
            if (!isset($x)) { $x = ['qty' => 0.0, 'costo' => 0.0]; }
            $x['qty'] += $qty; $x['costo'] += $costo;
        };

        // 1) Compras directas (descontando lo ya recepcionado en almacén)
        if ($this->tieneColumna('orden_compra_detalles', 'tarea_id')) {
            [$joinsRecep, $factor] = $this->sqlRecepcion();
            $mat = $this->tieneColumna('orden_compra_detalles', 'material_id') ? 'd.material_id' : 'NULL';
            $eq = $this->tieneColumna('orden_compra_detalles', 'equipo_id') ? 'd.equipo_id' : 'NULL';
            $rows = dbQueryAll(
                "SELECT d.tarea_id, $mat AS material_id, $eq AS equipo_id,
                        COALESCE(SUM(d.cantidad * $factor), 0) AS qty, COALESCE(SUM(d.total * $factor), 0) AS monto
                 FROM orden_compra_detalles d
                 JOIN ordenes_compra oc ON oc.id = d.orden_compra_id
                 $joinsRecep
                 WHERE d.tarea_id IN ($in) AND oc.estado IN ('aprobada', 'recibida')
                 GROUP BY d.tarea_id, $mat, $eq", $ids);
            foreach ($rows as $r) {
                if (!empty($r['equipo_id']))        { $acum((int)$r['tarea_id'], 'equipo', 'e:' . $r['equipo_id'], (float)$r['qty'], (float)$r['monto']); }
                elseif (!empty($r['material_id']))  { $acum((int)$r['tarea_id'], 'material', 'm:' . $r['material_id'], (float)$r['qty'], (float)$r['monto']); }
                else                                { $acum((int)$r['tarea_id'], 'material', 'libre', (float)$r['qty'], (float)$r['monto']); }
            }
        }

        // 2) Subcontratos (valorizaciones aprobadas o pagadas)
        if ($this->tieneColumna('subcontratos', 'tarea_id')) {
            if ($this->tieneColumna('subcontrato_valorizaciones', 'monto_bruto')) {
                $rows = dbQueryAll(
                    "SELECT s.tarea_id, COALESCE(SUM(v.monto_bruto), 0) AS monto
                     FROM subcontrato_valorizaciones v JOIN subcontratos s ON s.id = v.subcontrato_id
                     WHERE s.tarea_id IN ($in) AND v.estado IN ('aprobada', 'pagada') GROUP BY s.tarea_id", $ids);
            } else {
                $rows = dbQueryAll(
                    "SELECT tarea_id, COALESCE(SUM(monto), 0) AS monto FROM subcontratos
                     WHERE tarea_id IN ($in) AND estado IN ('activo', 'finalizado') GROUP BY tarea_id", $ids);
            }
            foreach ($rows as $r) { $acum((int)$r['tarea_id'], 'subcontrato', 'sub', 1.0, (float)$r['monto']); }
        }

        // 3) Salidas de almacén (materiales en unidades, equipos en horas)
        $exprCosto = $this->exprCostoAlmacen('det');
        if ($exprCosto !== null && $this->tieneColumna('almacen_movimientos_detalles', 'cantidad')) {
            $mat = $this->tieneColumna('almacen_movimientos_detalles', 'material_id') ? 'det.material_id' : 'NULL';
            $eq = $this->tieneColumna('almacen_movimientos_detalles', 'equipo_id') ? 'det.equipo_id' : 'NULL';
            $rows = dbQueryAll(
                "SELECT det.tarea_id, $mat AS material_id, $eq AS equipo_id,
                        COALESCE(SUM(det.cantidad), 0) AS qty, COALESCE(SUM($exprCosto), 0) AS monto
                 FROM almacen_movimientos_detalles det
                 JOIN almacen_movimientos am ON am.id = det.movimiento_id AND am.tipo_movimiento = 'salida'" . $this->movActivo('am') . "
                 WHERE det.tarea_id IN ($in)
                 GROUP BY det.tarea_id, $mat, $eq", $ids);
            foreach ($rows as $r) {
                if (!empty($r['equipo_id']))        { $acum((int)$r['tarea_id'], 'equipo', 'e:' . $r['equipo_id'], (float)$r['qty'], (float)$r['monto']); }
                elseif (!empty($r['material_id']))  { $acum((int)$r['tarea_id'], 'material', 'm:' . $r['material_id'], (float)$r['qty'], (float)$r['monto']); }
                else                                { $acum((int)$r['tarea_id'], 'material', 'libre', (float)$r['qty'], (float)$r['monto']); }
            }
        }

        // 4) Mano de obra por tipo de labor (horas)
        if ($this->tieneColumna('asistencias', 'tarea_id') && $this->tieneColumna('asistencias', 'costo_calculado')) {
            $rows = dbQueryAll(
                "SELECT tarea_id, COALESCE(NULLIF(TRIM(tipo_labor), ''), 'Operativo') AS labor,
                        COALESCE(SUM(horas_normales + horas_extras), 0) AS horas, COALESCE(SUM(costo_calculado), 0) AS monto
                 FROM asistencias WHERE tarea_id IN ($in) GROUP BY tarea_id, labor", $ids);
            foreach ($rows as $r) { $acum((int)$r['tarea_id'], 'mano_obra', 'l:' . $r['labor'], (float)$r['horas'], (float)$r['monto']); }
        }

        return $out;
    }

    /**
     * Recalcula TODO el proyecto de abajo hacia arriba (hojas → raíces) y sincroniza el proyecto.
     * Usa 4 consultas agrupadas en lugar de N+1.
     *
     * @return array{tareas:int, total_real:float}
     */
    public function recalcularCostoProyecto(int $proyectoId): array {
        $tareas = dbQueryAll("SELECT id, parent_id FROM tareas WHERE proyecto_id = ?", [$proyectoId]);
        if (empty($tareas)) {
            $this->sincronizarAvanceProyecto($proyectoId);
            return ['tareas' => 0, 'total_real' => 0.0];
        }

        $directos = $this->consultarCostosDirectos(null, $proyectoId);

        // Calcular profundidad de cada nodo para procesar de más profundo a más superficial
        $padre = [];
        foreach ($tareas as $t) { $padre[(int)$t['id']] = (int)($t['parent_id'] ?? 0); }
        $profundidad = [];
        foreach (array_keys($padre) as $id) {
            $d = 0; $cur = $id;
            while (!empty($padre[$cur]) && isset($padre[$padre[$cur]]) && $d < 50) { $cur = $padre[$cur]; $d++; }
            $profundidad[$id] = $d;
        }
        arsort($profundidad);

        $pdo = getConnection();
        $pdo->beginTransaction();
        try {
            foreach (array_keys($profundidad) as $id) {
                $this->recalcularNodo($id, $directos[$id] ?? []);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $this->sincronizarAvanceProyecto($proyectoId);
        $row = dbQueryOne("SELECT monto_ejecutado FROM proyectos WHERE id = ?", [$proyectoId]);
        return ['tareas' => count($tareas), 'total_real' => (float)($row['monto_ejecutado'] ?? 0)];
    }

    /**
     * Sincroniza en `proyectos`: avance (promedio de actividades hoja) y monto ejecutado
     * (suma de actividades raíz, que ya acumulan a sus hijas).
     */
    private function sincronizarAvanceProyecto(int $proyectoId): void {
        if ($this->tieneColumna('tareas', 'parent_id')) {
            $res = dbQueryOne(
                "SELECT
                    (SELECT AVG(h.progreso) FROM tareas h
                      WHERE h.proyecto_id = ?
                        AND NOT EXISTS (SELECT 1 FROM tareas c WHERE c.parent_id = h.id)) AS prom,
                    (SELECT SUM(r.costo_real) FROM tareas r
                      WHERE r.proyecto_id = ? AND r.parent_id IS NULL) AS costo",
                [$proyectoId, $proyectoId]
            );
        } else {
            $res = dbQueryOne("SELECT AVG(progreso) AS prom, SUM(costo_real) AS costo FROM tareas WHERE proyecto_id = ?", [$proyectoId]);
        }
        $prom = round((float)($res['prom'] ?? 0), 2);
        $costoReal = (float)($res['costo'] ?? 0);

        dbExecute("UPDATE proyectos SET avance_porcentaje = ?, monto_ejecutado = ? WHERE id = ?", [
            $prom, $costoReal, $proyectoId
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  RESÚMENES, GRÁFICOS Y DESGLOSE
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Resumen de métricas del WBS para el proyecto (presupuesto/costo sobre raíces para no duplicar roll-ups).
     */
    public function getResumenProyecto(int $proyectoId): array {
        $tieneParent = $this->tieneColumna('tareas', 'parent_id');
        $sql = "SELECT
                    COUNT(*) as total_tareas,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'bloqueada' THEN 1 ELSE 0 END) as bloqueadas
                FROM tareas WHERE proyecto_id = ?";
        $row = dbQueryOne($sql, [$proyectoId]) ?: [];

        if ($tieneParent) {
            $m = dbQueryOne(
                "SELECT
                    COALESCE(AVG(CASE WHEN NOT EXISTS (SELECT 1 FROM tareas c WHERE c.parent_id = t.id) THEN t.progreso END), 0) AS promedio_avance,
                    COALESCE(SUM(CASE WHEN t.parent_id IS NULL THEN t.costo_estimado END), 0) AS total_estimado,
                    COALESCE(SUM(CASE WHEN t.parent_id IS NULL THEN t.costo_real END), 0) AS total_real
                 FROM tareas t WHERE t.proyecto_id = ?", [$proyectoId]) ?: [];
        } else {
            $m = dbQueryOne("SELECT COALESCE(AVG(progreso),0) AS promedio_avance, COALESCE(SUM(costo_estimado),0) AS total_estimado,
                                    COALESCE(SUM(costo_real),0) AS total_real FROM tareas WHERE proyecto_id = ?", [$proyectoId]) ?: [];
        }

        $totalEst = (float)($m['total_estimado'] ?? 0);
        $totalReal = (float)($m['total_real'] ?? 0);

        return [
            'total_tareas' => (int)($row['total_tareas'] ?? 0),
            'completadas' => (int)($row['completadas'] ?? 0),
            'en_proceso' => (int)($row['en_proceso'] ?? 0),
            'pendientes' => (int)($row['pendientes'] ?? 0),
            'bloqueadas' => (int)($row['bloqueadas'] ?? 0),
            'promedio_avance' => round((float)($m['promedio_avance'] ?? 0), 2),
            'total_estimado' => $totalEst,
            'total_real' => $totalReal,
            'desviacion' => $totalEst - $totalReal,
            'salud' => self::calcularSalud($totalReal, $totalEst),
        ];
    }

    /**
     * Costos directos del proyecto por categoría (para la dona) y por tarea (para tablas/gráficos).
     *
     * @return array{totales: array<string,float>, por_tarea: array<int,array>}
     */
    /**
     * Costos directos por categoría de TODO el portafolio (dashboards de fases 2 y 5).
     */
    public function getCostosDirectosGlobal(): array {
        $totales = ['materiales' => 0.0, 'equipos' => 0.0, 'mano_obra' => 0.0, 'subcontratos' => 0.0, 'horas' => 0.0];
        foreach ($this->consultarCostosDirectos(null, null, true) as $d) {
            foreach ($totales as $k => $_) { $totales[$k] += $d[$k]; }
        }
        return $totales;
    }

    public function getCostosDirectosProyecto(int $proyectoId): array {
        $porTarea = $this->consultarCostosDirectos(null, $proyectoId);
        $totales = ['materiales' => 0.0, 'equipos' => 0.0, 'mano_obra' => 0.0, 'subcontratos' => 0.0, 'horas' => 0.0];
        foreach ($porTarea as $d) {
            foreach ($totales as $k => $_) { $totales[$k] += $d[$k]; }
        }
        return ['totales' => $totales, 'por_tarea' => $porTarea];
    }

    /**
     * Detalle de gastos de una actividad (propios, sin sub-actividades) para el modal de desglose.
     */
    public function getDesgloseTarea(int $tareaId): array {
        $detalle = ['compras' => [], 'subcontratos' => [], 'almacen' => [], 'tareo' => [], 'hijas' => []];

        if ($this->tieneColumna('orden_compra_detalles', 'tarea_id')) {
            [$joinsRecep, $factor] = $this->sqlRecepcion();
            $detalle['compras'] = dbQueryAll(
                "SELECT oc.codigo AS oc_codigo, oc.estado, oc.fecha_emision,
                        COALESCE(pv.razon_social, oc.proveedor_nombre) AS proveedor,
                        d.descripcion, d.unidad_medida, d.cantidad, d.precio_unitario, d.total,
                        (oc.estado IN ('aprobada','recibida') AND $factor > 0) AS computa,
                        IF(oc.estado IN ('aprobada','recibida'), ROUND(d.total * $factor, 2), 0) AS monto_computa,
                        ROUND((1 - $factor) * 100) AS pct_almacen
                 FROM orden_compra_detalles d
                 JOIN ordenes_compra oc ON oc.id = d.orden_compra_id
                 $joinsRecep
                 LEFT JOIN proveedores pv ON pv.id = oc.proveedor_id
                 WHERE d.tarea_id = ?
                 ORDER BY oc.fecha_emision DESC, d.id DESC", [$tareaId]);
        }

        if ($this->tieneColumna('subcontratos', 'tarea_id')) {
            $conVal = $this->tieneColumna('subcontrato_valorizaciones', 'monto_bruto');
            $valorizado = $conVal
                ? "COALESCE((SELECT SUM(v.monto_bruto) FROM subcontrato_valorizaciones v WHERE v.subcontrato_id = s.id AND v.estado IN ('aprobada','pagada')), 0)"
                : "IF(s.estado IN ('activo','finalizado'), s.monto, 0)";
            $detalle['subcontratos'] = dbQueryAll(
                "SELECT s.id, s.codigo, s.servicio, s.monto, s.estado, s.fecha_inicio, s.fecha_fin,
                        pv.razon_social AS proveedor,
                        $valorizado AS valorizado,
                        IF(s.estado = 'activo', GREATEST(s.monto - $valorizado, 0), 0) AS por_valorizar,
                        ($valorizado > 0) AS computa
                 FROM subcontratos s
                 LEFT JOIN proveedores pv ON pv.id = s.proveedor_id
                 WHERE s.tarea_id = ?
                 ORDER BY s.id DESC", [$tareaId]);
        }

        $exprCosto = $this->exprCostoAlmacen('det');
        if ($exprCosto !== null) {
            $t = 'almacen_movimientos_detalles';
            $join = '';
            $nombre = "'Ítem de almacén'";
            if ($this->tieneColumna($t, 'material_id') && $this->tieneColumna('cat_materiales', 'nombre')) {
                $join .= " LEFT JOIN cat_materiales cm ON cm.id = det.material_id";
                $nombre = "cm.nombre";
            }
            if ($this->tieneColumna($t, 'equipo_id') && $this->tieneColumna('cat_equipos', 'nombre')) {
                $join .= " LEFT JOIN cat_equipos ce ON ce.id = det.equipo_id";
                $nombre = ($nombre === "cm.nombre") ? "COALESCE(cm.nombre, ce.nombre)" : "ce.nombre";
            }
            $cantidad = $this->tieneColumna($t, 'cantidad') ? 'det.cantidad' : 'NULL';
            $detalle['almacen'] = dbQueryAll(
                "SELECT am.fecha, am.guia_remision, $nombre AS item, $cantidad AS cantidad, $exprCosto AS costo
                 FROM almacen_movimientos_detalles det
                 JOIN almacen_movimientos am ON am.id = det.movimiento_id AND am.tipo_movimiento = 'salida'" . $this->movActivo('am') . "
                 $join
                 WHERE det.tarea_id = ?
                 ORDER BY am.fecha DESC, det.id DESC", [$tareaId]);
        }

        if ($this->tieneColumna('asistencias', 'tarea_id') && $this->tieneColumna('asistencias', 'costo_calculado')) {
            $detalle['tareo'] = dbQueryAll(
                "SELECT a.fecha, pr.nombre AS profesional, a.horas_normales, a.horas_extras, a.costo_calculado AS costo
                 FROM asistencias a
                 JOIN profesionales pr ON pr.id = a.profesional_id
                 WHERE a.tarea_id = ?
                 ORDER BY a.fecha DESC, pr.nombre ASC", [$tareaId]);
        }

        if ($this->tieneColumna('tareas', 'parent_id')) {
            $detalle['hijas'] = dbQueryAll(
                "SELECT id, codigo, nombre, costo_estimado, costo_real FROM tareas WHERE parent_id = ? ORDER BY orden, codigo", [$tareaId]);
        }

        $directos = $this->consultarCostosDirectos($tareaId, null)[$tareaId]
            ?? ['materiales' => 0.0, 'equipos' => 0.0, 'mano_obra' => 0.0, 'subcontratos' => 0.0, 'horas' => 0.0];

        return ['directos' => $directos, 'detalle' => $detalle];
    }

    /**
     * Lista de proyectos disponibles para el selector WBS
     */
    public function getProyectos(): array {
        return dbQueryAll("SELECT id, codigo, nombre, estado, avance_porcentaje FROM proyectos ORDER BY fecha_inicio DESC, nombre ASC");
    }

    /**
     * Lista de usuarios disponibles para asignar responsabilidades
     */
    public function getUsuarios(): array {
        return dbQueryAll("SELECT id, nombre, email, rol FROM usuarios WHERE activo = 1 ORDER BY nombre ASC");
    }
}
