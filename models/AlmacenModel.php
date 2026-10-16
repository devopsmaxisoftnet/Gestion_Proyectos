<?php
/**
 * Modelo de Logística y Almacén (Fase 3)
 *
 * - Ingresos: recepcionan ítems de una OC aprobada → suben stock_actual y recalculan el costo
 *   promedio ponderado móvil del material.
 * - Salidas (vales a obra): cada línea exige una actividad del WBS (tarea_id); descuentan stock,
 *   se valorizan al costo promedio vigente y disparan TareaModel::recalcularCostoRealTarea().
 * - Los movimientos no se borran: se anulan, revirtiendo stock y costo.
 *
 * Todas las escrituras son transaccionales y bloquean las filas de stock (SELECT ... FOR UPDATE)
 * para impedir stock negativo o recepciones duplicadas por concurrencia.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TareaModel.php';

class AlmacenModel {

    /** Tolerancia para comparar cantidades decimales(10,2) */
    private const EPS = 0.004;

    // ═════════════════════════════════════════════════════════════════════
    //  CONSULTAS
    // ═════════════════════════════════════════════════════════════════════

    /** Subconsulta: cantidad ya recibida por (OC, material), ignorando ingresos anulados */
    private function sqlRecibido(): string {
        return "(SELECT am.orden_compra_id, d.material_id, SUM(d.cantidad) AS rec
                 FROM almacen_movimientos am
                 JOIN almacen_movimientos_detalles d ON d.movimiento_id = am.id
                 WHERE am.tipo_movimiento = 'ingreso' AND am.estado = 'registrado' AND d.material_id IS NOT NULL
                 GROUP BY am.orden_compra_id, d.material_id)";
    }

    /** Subconsulta: cantidad ordenada por (OC, material) */
    private function sqlOrdenado(): string {
        return "(SELECT orden_compra_id, material_id, SUM(cantidad) AS ord, SUM(total) AS monto
                 FROM orden_compra_detalles WHERE material_id IS NOT NULL
                 GROUP BY orden_compra_id, material_id)";
    }

    public function getKpis(): array {
        $inv = dbQueryOne("SELECT
                    COALESCE(SUM(stock_actual * costo_promedio), 0) AS valor,
                    SUM(CASE WHEN stock_minimo > 0 AND stock_actual <= stock_minimo THEN 1 ELSE 0 END) AS criticos,
                    SUM(CASE WHEN stock_minimo > 0 AND stock_actual <= 0 THEN 1 ELSE 0 END) AS agotados,
                    COUNT(*) AS total_items
                FROM cat_materiales WHERE activo = 1") ?: [];

        $mes = dbQueryAll("SELECT am.tipo_movimiento AS tipo, COUNT(*) AS movs,
                                  COALESCE(SUM((SELECT SUM(d.cantidad * d.precio_unitario) FROM almacen_movimientos_detalles d WHERE d.movimiento_id = am.id)), 0) AS monto
                           FROM almacen_movimientos am
                           WHERE am.estado = 'registrado' AND am.fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                           GROUP BY am.tipo_movimiento");
        $porTipo = ['ingreso' => ['movs' => 0, 'monto' => 0.0], 'salida' => ['movs' => 0, 'monto' => 0.0]];
        foreach ($mes as $r) { $porTipo[$r['tipo']] = ['movs' => (int)$r['movs'], 'monto' => (float)$r['monto']]; }

        return [
            'valor_inventario' => (float)($inv['valor'] ?? 0),
            'items_activos' => (int)($inv['total_items'] ?? 0),
            'criticos' => (int)($inv['criticos'] ?? 0),
            'agotados' => (int)($inv['agotados'] ?? 0),
            'por_recepcionar' => count($this->getOrdenesRecepcionables()),
            'ingresos_mes' => $porTipo['ingreso'],
            'salidas_mes' => $porTipo['salida'],
        ];
    }

    /**
     * Monto y cantidad de movimientos por mes (últimos N meses, incluye meses sin movimiento).
     */
    public function getMovimientosPorMes(int $meses = 6): array {
        $meses = max(1, min(24, $meses));
        $rows = dbQueryAll("SELECT DATE_FORMAT(am.fecha, '%Y-%m') AS mes, am.tipo_movimiento AS tipo, COUNT(*) AS movs,
                                   COALESCE(SUM((SELECT SUM(d.cantidad * d.precio_unitario) FROM almacen_movimientos_detalles d WHERE d.movimiento_id = am.id)), 0) AS monto
                            FROM almacen_movimientos am
                            WHERE am.estado = 'registrado'
                              AND am.fecha >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL " . ($meses - 1) . " MONTH)
                            GROUP BY mes, tipo");
        $nombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $out = ['labels' => [], 'ingreso_monto' => [], 'salida_monto' => [], 'ingreso_movs' => [], 'salida_movs' => []];
        $idx = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -$i months");
            $key = date('Y-m', $ts);
            $idx[$key] = count($out['labels']);
            $out['labels'][] = $nombres[(int)date('n', $ts)] . ' ' . date('y', $ts);
            foreach (['ingreso_monto', 'salida_monto', 'ingreso_movs', 'salida_movs'] as $k) { $out[$k][] = 0; }
        }
        foreach ($rows as $r) {
            if (!isset($idx[$r['mes']])) { continue; }
            $out[$r['tipo'] . '_monto'][$idx[$r['mes']]] = round((float)$r['monto'], 2);
            $out[$r['tipo'] . '_movs'][$idx[$r['mes']]] = (int)$r['movs'];
        }
        return $out;
    }

    /** Materiales activos en o bajo su stock mínimo, los más urgentes primero */
    public function getStockCritico(int $limite = 10): array {
        $limite = max(1, min(50, $limite));
        return dbQueryAll("SELECT id, codigo, nombre, unidad_medida, stock_actual, stock_minimo, costo_promedio
                           FROM cat_materiales
                           WHERE activo = 1 AND stock_minimo > 0 AND stock_actual <= stock_minimo
                           ORDER BY (stock_actual / stock_minimo) ASC, nombre ASC
                           LIMIT $limite");
    }

    public function getInventario(): array {
        $rows = dbQueryAll("SELECT id, codigo, nombre, unidad_medida, categoria, stock_actual, stock_minimo, costo_promedio,
                                   precio_referencial, activo, (stock_actual * costo_promedio) AS valor
                            FROM cat_materiales ORDER BY activo DESC, nombre ASC");
        foreach ($rows as &$r) {
            $stock = (float)$r['stock_actual']; $min = (float)$r['stock_minimo'];
            $r['alerta'] = $stock <= 0 && $min > 0 ? 'agotado' : ($min > 0 && $stock <= $min ? 'critico' : 'ok');
        }
        return $rows;
    }

    public function getMovimientos(array $filtros = []): array {
        $where = "1=1";
        $params = [];
        if (in_array($filtros['tipo'] ?? '', ['ingreso', 'salida'], true)) {
            $where .= " AND am.tipo_movimiento = ?"; $params[] = $filtros['tipo'];
        }
        if (in_array($filtros['estado'] ?? '', ['registrado', 'anulado'], true)) {
            $where .= " AND am.estado = ?"; $params[] = $filtros['estado'];
        }
        if (!empty($filtros['proyecto_id'])) {
            $where .= " AND am.proyecto_id = ?"; $params[] = (int)$filtros['proyecto_id'];
        }
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (am.guia_remision LIKE ? OR oc.codigo LIKE ? OR p.nombre LIKE ?)";
            $t = '%' . trim($filtros['busqueda']) . '%';
            array_push($params, $t, $t, $t);
        }

        return dbQueryAll("SELECT am.*, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                                  oc.codigo AS oc_codigo, u.nombre AS registrado_nombre,
                                  (SELECT COUNT(*) FROM almacen_movimientos_detalles d WHERE d.movimiento_id = am.id) AS lineas,
                                  (SELECT COALESCE(SUM(d.cantidad * d.precio_unitario), 0) FROM almacen_movimientos_detalles d WHERE d.movimiento_id = am.id) AS monto
                           FROM almacen_movimientos am
                           JOIN proyectos p ON p.id = am.proyecto_id
                           LEFT JOIN ordenes_compra oc ON oc.id = am.orden_compra_id
                           LEFT JOIN usuarios u ON u.id = am.registrado_por
                           WHERE $where
                           ORDER BY am.fecha DESC, am.id DESC
                           LIMIT 300", $params);
    }

    public function getMovimiento(int $id): ?array {
        return dbQueryOne("SELECT am.*, p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                                  oc.codigo AS oc_codigo, oc.estado AS oc_estado, u.nombre AS registrado_nombre
                           FROM almacen_movimientos am
                           JOIN proyectos p ON p.id = am.proyecto_id
                           LEFT JOIN ordenes_compra oc ON oc.id = am.orden_compra_id
                           LEFT JOIN usuarios u ON u.id = am.registrado_por
                           WHERE am.id = ?", [$id]) ?: null;
    }

    public function getDetallesMovimiento(int $id): array {
        return dbQueryAll("SELECT d.*, (d.cantidad * d.precio_unitario) AS subtotal,
                                  t.codigo AS tarea_codigo, t.nombre AS tarea_nombre,
                                  m.codigo AS material_codigo, m.nombre AS material_nombre, m.unidad_medida,
                                  e.codigo AS equipo_codigo, e.nombre AS equipo_nombre
                           FROM almacen_movimientos_detalles d
                           LEFT JOIN tareas t ON t.id = d.tarea_id
                           LEFT JOIN cat_materiales m ON m.id = d.material_id
                           LEFT JOIN cat_equipos e ON e.id = d.equipo_id
                           WHERE d.movimiento_id = ? ORDER BY d.id", [$id]);
    }

    /** OC aprobadas con al menos un material pendiente de recepción */
    public function getOrdenesRecepcionables(): array {
        return dbQueryAll("SELECT oc.id, oc.codigo, oc.fecha_emision, oc.fecha_entrega, oc.moneda,
                                  p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                                  COALESCE(pv.razon_social, oc.proveedor_nombre) AS proveedor,
                                  COUNT(*) AS items_pendientes,
                                  SUM(x.ord - COALESCE(r.rec, 0)) AS unidades_pendientes
                           FROM ordenes_compra oc
                           JOIN proyectos p ON p.id = oc.proyecto_id
                           LEFT JOIN proveedores pv ON pv.id = oc.proveedor_id
                           JOIN " . $this->sqlOrdenado() . " x ON x.orden_compra_id = oc.id
                           LEFT JOIN " . $this->sqlRecibido() . " r ON r.orden_compra_id = oc.id AND r.material_id = x.material_id
                           WHERE oc.estado = 'aprobada' AND x.ord - COALESCE(r.rec, 0) > " . self::EPS . "
                           GROUP BY oc.id, oc.codigo, oc.fecha_emision, oc.fecha_entrega, oc.moneda,
                                    p.codigo, p.nombre, pv.razon_social, oc.proveedor_nombre
                           ORDER BY oc.fecha_entrega IS NULL, oc.fecha_entrega ASC, oc.id ASC");
    }

    /**
     * Materiales de una OC con lo ordenado, recibido y pendiente. Si $bloquear, lee dentro de la
     * transacción actual (los datos de la OC ya están bloqueados por quien llama).
     */
    public function getPendientesOC(int $ocId): array {
        $rows = dbQueryAll("SELECT m.id AS material_id, m.codigo, m.nombre, m.unidad_medida, m.stock_actual,
                                   x.ord AS ordenado, COALESCE(r.rec, 0) AS recibido, x.monto
                            FROM " . $this->sqlOrdenado() . " x
                            JOIN cat_materiales m ON m.id = x.material_id
                            LEFT JOIN " . $this->sqlRecibido() . " r ON r.orden_compra_id = x.orden_compra_id AND r.material_id = x.material_id
                            WHERE x.orden_compra_id = ?
                            ORDER BY m.nombre", [$ocId]);
        foreach ($rows as &$r) {
            $r['ordenado'] = (float)$r['ordenado'];
            $r['recibido'] = (float)$r['recibido'];
            $r['pendiente'] = max(0.0, round($r['ordenado'] - $r['recibido'], 2));
            $r['precio_unitario'] = $r['ordenado'] > 0 ? round((float)$r['monto'] / $r['ordenado'], 4) : 0.0;
        }
        return $rows;
    }

    public function getOrdenResumen(int $ocId): ?array {
        return dbQueryOne("SELECT oc.id, oc.codigo, oc.estado, oc.moneda, oc.proyecto_id,
                                  p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                                  COALESCE(pv.razon_social, oc.proveedor_nombre) AS proveedor
                           FROM ordenes_compra oc
                           JOIN proyectos p ON p.id = oc.proyecto_id
                           LEFT JOIN proveedores pv ON pv.id = oc.proveedor_id
                           WHERE oc.id = ?", [$ocId]) ?: null;
    }

    public function getProyectos(): array {
        return dbQueryAll("SELECT id, codigo, nombre FROM proyectos ORDER BY fecha_inicio DESC, nombre ASC");
    }

    /** Catálogo para el vale de salida: materiales con stock disponible y equipos activos */
    public function getCatalogoSalida(): array {
        return [
            'materiales' => dbQueryAll("SELECT id, codigo, nombre, unidad_medida, stock_actual,
                                               IF(costo_promedio > 0, costo_promedio, precio_referencial) AS costo
                                        FROM cat_materiales WHERE activo = 1 ORDER BY nombre"),
            'equipos' => dbQueryAll("SELECT id, codigo, nombre, costo_hora AS costo FROM cat_equipos WHERE activo = 1 ORDER BY nombre"),
        ];
    }

    public function getKardex(int $materialId): array {
        return dbQueryAll("SELECT am.id AS movimiento_id, am.fecha, am.tipo_movimiento, am.guia_remision, am.estado,
                                  oc.codigo AS oc_codigo, p.codigo AS proyecto_codigo, t.codigo AS tarea_codigo,
                                  d.cantidad, d.precio_unitario
                           FROM almacen_movimientos_detalles d
                           JOIN almacen_movimientos am ON am.id = d.movimiento_id
                           JOIN proyectos p ON p.id = am.proyecto_id
                           LEFT JOIN ordenes_compra oc ON oc.id = am.orden_compra_id
                           LEFT JOIN tareas t ON t.id = d.tarea_id
                           WHERE d.material_id = ?
                           ORDER BY am.fecha DESC, am.id DESC LIMIT 200", [$materialId]);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  ESCRITURA
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Recepciona en almacén ítems de una OC aprobada.
     *
     * @param array $items  [['material_id' => int, 'cantidad' => float], ...]
     * @return int id del movimiento
     */
    public function registrarIngreso(int $ocId, array $data, array $items, int $userId): int {
        $fecha = $this->fechaValida($data['fecha'] ?? '');
        $guia = mb_substr(trim((string)($data['guia_remision'] ?? '')), 0, 100) ?: null;
        $obs = trim((string)($data['observaciones'] ?? '')) ?: null;

        // Agrupa por material (una misma línea repetida suma)
        $pedido = [];
        foreach ($items as $it) {
            $mid = (int)($it['material_id'] ?? 0);
            $cant = round((float)($it['cantidad'] ?? 0), 2);
            if ($mid > 0 && $cant > 0) { $pedido[$mid] = round(($pedido[$mid] ?? 0) + $cant, 2); }
        }
        if (empty($pedido)) {
            throw new Exception('Indica la cantidad recibida de al menos un material.');
        }

        $conn = getConnection();
        $conn->beginTransaction();
        try {
            // Bloquea la OC: dos recepciones simultáneas no pueden superar lo pendiente
            $oc = dbQueryOne("SELECT id, proyecto_id, estado FROM ordenes_compra WHERE id = ? FOR UPDATE", [$ocId]);
            if (!$oc) { throw new Exception('La orden de compra no existe.'); }
            if ($oc['estado'] !== 'aprobada') {
                throw new Exception('Solo se pueden recepcionar órdenes aprobadas (estado actual: ' . str_replace('_', ' ', $oc['estado']) . ').');
            }

            $pend = [];
            foreach ($this->getPendientesOC($ocId) as $p) { $pend[(int)$p['material_id']] = $p; }

            foreach ($pedido as $mid => $cant) {
                if (!isset($pend[$mid])) {
                    throw new Exception('Uno de los materiales no pertenece a la orden de compra.');
                }
                if ($cant > $pend[$mid]['pendiente'] + self::EPS) {
                    throw new Exception("Se intenta recibir {$cant} de {$pend[$mid]['nombre']}, pero solo hay {$pend[$mid]['pendiente']} pendientes.");
                }
            }

            dbExecute("INSERT INTO almacen_movimientos (proyecto_id, orden_compra_id, tipo_movimiento, fecha, guia_remision, observaciones, registrado_por)
                       VALUES (?, ?, 'ingreso', ?, ?, ?, ?)", [(int)$oc['proyecto_id'], $ocId, $fecha, $guia, $obs, $userId ?: null]);
            $movId = (int)$conn->lastInsertId();

            ksort($pedido); // orden estable de bloqueo → sin deadlocks
            foreach ($pedido as $mid => $cant) {
                $mat = dbQueryOne("SELECT stock_actual, costo_promedio, precio_referencial FROM cat_materiales WHERE id = ? FOR UPDATE", [$mid]);
                $precio = $pend[$mid]['precio_unitario'];
                $stock = (float)$mat['stock_actual'];
                $avg = (float)$mat['costo_promedio'] > 0 ? (float)$mat['costo_promedio'] : (float)$mat['precio_referencial'];

                dbExecute("UPDATE cat_materiales SET stock_actual = ?, costo_promedio = ? WHERE id = ?",
                    [round($stock + $cant, 2), $this->promedioAlIngresar($stock, $avg, $cant, $precio), $mid]);
                dbExecute("INSERT INTO almacen_movimientos_detalles (movimiento_id, tarea_id, material_id, equipo_id, cantidad, precio_unitario)
                           VALUES (?, NULL, ?, NULL, ?, ?)", [$movId, $mid, $cant, round($precio, 2)]);
            }

            // ¿Quedó todo recibido? entonces la OC pasa a "recibida"
            $completa = true;
            foreach ($this->getPendientesOC($ocId) as $p) {
                if ($p['pendiente'] > self::EPS) { $completa = false; break; }
            }
            if ($completa) {
                dbExecute("UPDATE ordenes_compra SET estado = 'recibida' WHERE id = ? AND estado = 'aprobada'", [$ocId]);
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        // La parte recepcionada deja de ser compra directa: se refresca el WBS
        $this->recalcularProyecto((int)$oc['proyecto_id']);
        return $movId;
    }

    /**
     * Registra un vale de salida a obra. Cada línea exige actividad (tarea_id) del proyecto.
     *
     * @param array $items [['tarea_id'=>int, 'material_id'=>?int, 'equipo_id'=>?int, 'cantidad'=>float], ...]
     * @return int id del movimiento
     */
    public function registrarSalida(int $proyectoId, array $data, array $items, int $userId): int {
        if (!dbQueryOne("SELECT id FROM proyectos WHERE id = ?", [$proyectoId])) {
            throw new Exception('Selecciona un proyecto válido.');
        }
        $fecha = $this->fechaValida($data['fecha'] ?? '');
        $obs = trim((string)($data['observaciones'] ?? '')) ?: null;
        $vale = mb_substr(trim((string)($data['guia_remision'] ?? '')), 0, 100);

        // ── Normalización y validación de líneas ──
        $lineas = [];
        foreach ($items as $n => $it) {
            $pos = $n + 1;
            $tareaId = (int)($it['tarea_id'] ?? 0);
            $mid = !empty($it['material_id']) ? (int)$it['material_id'] : null;
            $eid = !empty($it['equipo_id']) ? (int)$it['equipo_id'] : null;
            $cant = round((float)($it['cantidad'] ?? 0), 2);

            if (!$mid && !$eid && $cant <= 0 && $tareaId <= 0) { continue; } // fila vacía
            if ($tareaId <= 0) { throw new Exception("Línea $pos: la actividad del WBS es obligatoria."); }
            if (($mid && $eid) || (!$mid && !$eid)) { throw new Exception("Línea $pos: elige un material o un equipo."); }
            if ($cant <= 0) { throw new Exception("Línea $pos: la cantidad debe ser mayor a 0."); }

            $lineas[] = ['tarea_id' => $tareaId, 'material_id' => $mid, 'equipo_id' => $eid, 'cantidad' => $cant, 'pos' => $pos];
        }
        if (empty($lineas)) { throw new Exception('Agrega al menos un ítem al vale de salida.'); }

        $tareaIds = array_values(array_unique(array_column($lineas, 'tarea_id')));
        $in = implode(',', array_fill(0, count($tareaIds), '?'));
        $validas = array_column(dbQueryAll("SELECT id FROM tareas WHERE proyecto_id = ? AND id IN ($in)", array_merge([$proyectoId], $tareaIds)), 'id');
        foreach ($lineas as $l) {
            if (!in_array((string)$l['tarea_id'], array_map('strval', $validas), true)) {
                throw new Exception("Línea {$l['pos']}: la actividad no pertenece al proyecto del vale.");
            }
        }

        $conn = getConnection();
        $conn->beginTransaction();
        try {
            // Bloquea los materiales involucrados (orden por id) y valida el stock agregado
            $porMaterial = [];
            foreach ($lineas as $l) {
                if ($l['material_id']) { $porMaterial[$l['material_id']] = round(($porMaterial[$l['material_id']] ?? 0) + $l['cantidad'], 2); }
            }
            ksort($porMaterial);
            $mats = [];
            foreach ($porMaterial as $mid => $total) {
                $m = dbQueryOne("SELECT id, nombre, unidad_medida, stock_actual, costo_promedio, precio_referencial, activo
                                 FROM cat_materiales WHERE id = ? FOR UPDATE", [$mid]);
                if (!$m || !$m['activo']) { throw new Exception('Uno de los materiales no existe o está inactivo.'); }
                if ($total > (float)$m['stock_actual'] + self::EPS) {
                    throw new Exception("Stock insuficiente de {$m['nombre']}: solicitado " . number_format($total, 2) . ", disponible " . number_format((float)$m['stock_actual'], 2) . " {$m['unidad_medida']}.");
                }
                $mats[$mid] = $m;
            }
            $equipos = [];
            foreach ($lineas as $l) {
                if ($l['equipo_id'] && !isset($equipos[$l['equipo_id']])) {
                    $e = dbQueryOne("SELECT id, costo_hora, activo FROM cat_equipos WHERE id = ?", [$l['equipo_id']]);
                    if (!$e || !$e['activo']) { throw new Exception('Uno de los equipos no existe o está inactivo.'); }
                    $equipos[$l['equipo_id']] = $e;
                }
            }

            if ($vale === '') { $vale = $this->siguienteVale($fecha); }
            dbExecute("INSERT INTO almacen_movimientos (proyecto_id, orden_compra_id, tipo_movimiento, fecha, guia_remision, observaciones, registrado_por)
                       VALUES (?, NULL, 'salida', ?, ?, ?, ?)", [$proyectoId, $fecha, $vale, $obs, $userId ?: null]);
            $movId = (int)$conn->lastInsertId();

            foreach ($lineas as $l) {
                if ($l['material_id']) {
                    $m = $mats[$l['material_id']];
                    $precio = (float)$m['costo_promedio'] > 0 ? (float)$m['costo_promedio'] : (float)$m['precio_referencial'];
                } else {
                    $precio = (float)$equipos[$l['equipo_id']]['costo_hora'];   // horas-equipo × tarifa
                }
                dbExecute("INSERT INTO almacen_movimientos_detalles (movimiento_id, tarea_id, material_id, equipo_id, cantidad, precio_unitario)
                           VALUES (?, ?, ?, ?, ?, ?)", [$movId, $l['tarea_id'], $l['material_id'], $l['equipo_id'], $l['cantidad'], round($precio, 2)]);
            }
            foreach ($porMaterial as $mid => $total) {
                dbExecute("UPDATE cat_materiales SET stock_actual = stock_actual - ? WHERE id = ?", [$total, $mid]);
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        // El consumo ya forma parte del costo real de cada actividad
        $this->recalcularTareas($tareaIds);
        return $movId;
    }

    /**
     * Anula un movimiento revirtiendo stock y costo promedio.
     * Un ingreso solo puede anularse si el stock actual aún cubre lo ingresado.
     */
    public function anular(int $movId, string $motivo): void {
        $motivo = mb_substr(trim($motivo), 0, 255);
        if ($motivo === '') { throw new Exception('Indica el motivo de la anulación.'); }

        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $mov = dbQueryOne("SELECT id, proyecto_id, orden_compra_id, tipo_movimiento, estado FROM almacen_movimientos WHERE id = ? FOR UPDATE", [$movId]);
            if (!$mov) { throw new Exception('El movimiento no existe.'); }
            if ($mov['estado'] !== 'registrado') { throw new Exception('El movimiento ya fue anulado.'); }

            $detalles = dbQueryAll("SELECT material_id, tarea_id, cantidad, precio_unitario FROM almacen_movimientos_detalles WHERE movimiento_id = ? ORDER BY material_id", [$movId]);
            $tareaIds = [];

            foreach ($detalles as $d) {
                if ($d['tarea_id']) { $tareaIds[] = (int)$d['tarea_id']; }
                if (!$d['material_id']) { continue; } // equipos: sin stock

                $m = dbQueryOne("SELECT nombre, stock_actual, costo_promedio, precio_referencial FROM cat_materiales WHERE id = ? FOR UPDATE", [$d['material_id']]);
                $stock = (float)$m['stock_actual'];
                $avg = (float)$m['costo_promedio'] > 0 ? (float)$m['costo_promedio'] : (float)$m['precio_referencial'];
                $cant = (float)$d['cantidad'];
                $precio = (float)$d['precio_unitario'];

                if ($mov['tipo_movimiento'] === 'salida') {
                    // El material vuelve al stock al mismo costo con que salió
                    dbExecute("UPDATE cat_materiales SET stock_actual = ?, costo_promedio = ? WHERE id = ?",
                        [round($stock + $cant, 2), $this->promedioAlIngresar($stock, $avg, $cant, $precio), $d['material_id']]);
                } else {
                    $restante = round($stock - $cant, 2);
                    if ($restante < -self::EPS) {
                        throw new Exception("No se puede anular el ingreso: parte de {$m['nombre']} ya fue consumida (stock {$stock}, ingresado {$cant}). Anula primero las salidas.");
                    }
                    $nuevoAvg = $restante > self::EPS ? max(0, round((($stock * $avg) - ($cant * $precio)) / $restante, 4)) : $avg;
                    dbExecute("UPDATE cat_materiales SET stock_actual = ?, costo_promedio = ? WHERE id = ?",
                        [max(0, $restante), $nuevoAvg, $d['material_id']]);
                }
            }

            dbExecute("UPDATE almacen_movimientos SET estado = 'anulado', motivo_anulacion = ? WHERE id = ?", [$motivo, $movId]);
            if ($mov['tipo_movimiento'] === 'ingreso' && $mov['orden_compra_id']) {
                dbExecute("UPDATE ordenes_compra SET estado = 'aprobada' WHERE id = ? AND estado = 'recibida'", [$mov['orden_compra_id']]);
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        if ($mov['tipo_movimiento'] === 'salida') {
            $this->recalcularTareas($tareaIds);
        } else {
            $this->recalcularProyecto((int)$mov['proyecto_id']);
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    //  INTERNOS
    // ═════════════════════════════════════════════════════════════════════

    /** Costo promedio ponderado móvil tras un ingreso */
    private function promedioAlIngresar(float $stock, float $avg, float $cant, float $precio): float {
        $total = $stock + $cant;
        if ($total <= 0) { return round($precio, 4); }
        return round((($stock * $avg) + ($cant * $precio)) / $total, 4);
    }

    private function fechaValida(string $f): string {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        if (!$d || $d->format('Y-m-d') !== $f) { return date('Y-m-d'); }
        if ($f > date('Y-m-d', strtotime('+1 day'))) { throw new Exception('La fecha del movimiento no puede ser futura.'); }
        return $f;
    }

    private function siguienteVale(string $fecha): string {
        $anio = date('Y', strtotime($fecha));
        $r = dbQueryOne("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(guia_remision, '-', -1) AS UNSIGNED)), 0) AS m
                         FROM almacen_movimientos WHERE tipo_movimiento = 'salida' AND guia_remision LIKE ?", ["VS-$anio-%"]);
        return 'VS-' . $anio . '-' . str_pad(((int)$r['m']) + 1, 4, '0', STR_PAD_LEFT);
    }

    /** Recalcula el costo real de cada actividad afectada (también sincroniza proyecto y ancestros). */
    private function recalcularTareas(array $tareaIds): void {
        $tm = new TareaModel();
        foreach (array_unique(array_map('intval', $tareaIds)) as $tid) {
            try { $tm->recalcularCostoRealTarea($tid); }
            catch (Throwable $e) { error_log("AlmacenModel: no se pudo recalcular la actividad $tid: " . $e->getMessage()); }
        }
    }

    private function recalcularProyecto(int $proyectoId): void {
        try { (new TareaModel())->recalcularCostoProyecto($proyectoId); }
        catch (Throwable $e) { error_log("AlmacenModel: no se pudo recalcular el proyecto $proyectoId: " . $e->getMessage()); }
    }
}
