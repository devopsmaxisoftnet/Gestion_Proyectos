<?php
/**
 * Modelo de Logística y Compras (Fase 3: Órdenes de Compra y Suministros)
 * Costeo ABC: cada línea de la OC se imputa a una actividad (tarea_id) y opcionalmente a un
 * ítem de catálogo (material_id / equipo_id). Todo cambio que pueda alterar el costo de las
 * actividades dispara TareaModel::recalcularCostoProyecto() para mantener el WBS al día.
 *
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/TareaModel.php';

class CompraModel {

    /** Estados en los que una OC aún puede editarse */
    public const ESTADOS_EDITABLES = ['borrador', 'pendiente_aprobacion', 'rechazada'];

    /** Estados que computan como compra real */
    private const ESTADOS_COSTO = "'aprobada','recibida'";

    // ═════════════════════════════════════════════════════════════════════
    //  CONSULTAS
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Obtiene el listado de Órdenes de Compra con filtros
     */
    public function getAll(array $filtros = []): array {
        $where = "1=1";
        $params = [];

        if (!empty($filtros['proyecto_id'])) {
            $where .= " AND oc.proyecto_id = ?";
            $params[] = (int)$filtros['proyecto_id'];
        }

        if (!empty($filtros['estado'])) {
            $where .= " AND oc.estado = ?";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['proveedor_id'])) {
            $where .= " AND oc.proveedor_id = ?";
            $params[] = (int)$filtros['proveedor_id'];
        }

        if (!empty($filtros['busqueda'])) {
            $where .= " AND (oc.codigo LIKE ? OR oc.proveedor_nombre LIKE ? OR p.nombre LIKE ?)";
            $term = "%" . trim($filtros['busqueda']) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql = "SELECT oc.*,
                       p.nombre as proyecto_nombre, p.codigo as proyecto_codigo,
                       pv.ruc as proveedor_ruc, pv.telefono as proveedor_telefono,
                       us.nombre as solicitante_nombre,
                       ua.nombre as aprobador_nombre,
                       (SELECT COUNT(*) FROM orden_compra_detalles d WHERE d.orden_compra_id = oc.id) as total_items,
                       (SELECT COUNT(*) FROM orden_compra_detalles d WHERE d.orden_compra_id = oc.id AND d.tarea_id IS NULL) as items_sin_actividad
                FROM ordenes_compra oc
                JOIN proyectos p ON oc.proyecto_id = p.id
                LEFT JOIN proveedores pv ON oc.proveedor_id = pv.id
                LEFT JOIN usuarios us ON oc.solicitado_por = us.id
                LEFT JOIN usuarios ua ON oc.aprobado_por = ua.id
                WHERE $where
                ORDER BY oc.fecha_emision DESC, oc.id DESC";

        return dbQueryAll($sql, $params);
    }

    /**
     * Obtiene una orden de compra por su ID
     */
    public function getById(int $id): ?array {
        $sql = "SELECT oc.*,
                       p.nombre as proyecto_nombre, p.codigo as proyecto_codigo,
                       pv.ruc as proveedor_ruc, pv.direccion as proveedor_direccion,
                       pv.telefono as proveedor_telefono, pv.email as proveedor_email,
                       us.nombre as solicitante_nombre, us.email as solicitante_email,
                       ua.nombre as aprobador_nombre, ua.email as aprobador_email,
                       EXISTS (SELECT 1 FROM almacen_movimientos am
                               WHERE am.orden_compra_id = oc.id AND am.tipo_movimiento = 'ingreso' AND am.estado = 'registrado') as tiene_ingreso_almacen
                FROM ordenes_compra oc
                JOIN proyectos p ON oc.proyecto_id = p.id
                LEFT JOIN proveedores pv ON oc.proveedor_id = pv.id
                LEFT JOIN usuarios us ON oc.solicitado_por = us.id
                LEFT JOIN usuarios ua ON oc.aprobado_por = ua.id
                WHERE oc.id = ? LIMIT 1";

        return dbQueryOne($sql, [$id]) ?: null;
    }

    /**
     * Ítems de una orden, con la actividad imputada y el ítem de catálogo
     */
    public function getDetalles(int $ordenId): array {
        $sql = "SELECT d.*,
                       t.codigo AS tarea_codigo, t.nombre AS tarea_nombre,
                       m.codigo AS material_codigo, e.codigo AS equipo_codigo
                FROM orden_compra_detalles d
                LEFT JOIN tareas t ON t.id = d.tarea_id
                LEFT JOIN cat_materiales m ON m.id = d.material_id
                LEFT JOIN cat_equipos e ON e.id = d.equipo_id
                WHERE d.orden_compra_id = ?
                ORDER BY d.id ASC";
        return dbQueryAll($sql, [$ordenId]);
    }

    /**
     * Actividades de un proyecto en orden de árbol WBS (para el selector de la OC).
     * Cada fila incluye _nivel y _tiene_hijos; la UI muestra por defecto solo las hojas.
     */
    public function getTareasProyecto(int $proyectoId, ?int $excluirOcId = null, ?int $excluirSubId = null): array {
        $tareaModel = new TareaModel();
        $arbol = $tareaModel->construirArbol($tareaModel->getAllByProyecto($proyectoId));
        require_once __DIR__ . '/ControlPresupuestalModel.php';
        $disp = (new ControlPresupuestalModel())->getDisponibilidad($proyectoId, $excluirOcId, $excluirSubId)['tareas'];
        return array_map(fn($t) => [
            'usado' => $disp[(int)$t['id']]['usado'] ?? (float)$t['costo_real'],
            'id' => (int)$t['id'],
            'codigo' => $t['codigo'],
            'nombre' => $t['nombre'],
            'nivel' => (int)$t['_nivel'],
            'es_hoja' => !$t['_tiene_hijos'],
            'estado' => $t['estado'],
            'costo_estimado' => (float)$t['costo_estimado'],
            'costo_real' => (float)$t['costo_real'],
        ], $arbol);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  ESCRITURA (con recálculo automático del WBS)
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Registra una nueva Orden de Compra con sus detalles en una transacción PDO atómica
     */
    public function create(array $data, array $detalles, int $solicitadoPor): int {
        $proyectoId = (int)($data['proyecto_id'] ?? 0);
        $cab = $this->prepararCabecera($data);
        [$items, $subtotal] = $this->normalizarItems($detalles, $proyectoId);

        $anio = date('Y', strtotime($cab['fecha_emision']));
        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        $conn = getConnection();
        $conn->beginTransaction();
        try {
            // Correlativo robusto: máximo existente del año + 1 (COUNT(*) repite códigos tras eliminar órdenes)
            $row = dbQueryOne("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)), 0) AS m
                               FROM ordenes_compra WHERE codigo LIKE ?", ["OC-$anio-%"]);
            $codigo = 'OC-' . $anio . '-' . str_pad(((int)$row['m']) + 1, 4, '0', STR_PAD_LEFT);

            $estado = !empty($data['estado']) && in_array($data['estado'], ['borrador', 'pendiente_aprobacion'], true)
                ? $data['estado'] : 'pendiente_aprobacion';

            $stmt = $conn->prepare("INSERT INTO ordenes_compra (codigo, proyecto_id, proveedor_id, proveedor_nombre,
                                        fecha_emision, fecha_entrega, subtotal, igv, total,
                                        moneda, condicion_pago, lugar_entrega, estado, observaciones, solicitado_por)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $codigo, $proyectoId, $cab['proveedor_id'], $cab['proveedor_nombre'],
                $cab['fecha_emision'], $cab['fecha_entrega'], $subtotal, $igv, $total,
                $cab['moneda'], $cab['condicion_pago'], $cab['lugar_entrega'], $estado, $cab['observaciones'], $solicitadoPor
            ]);
            $ordenId = (int)$conn->lastInsertId();

            $this->insertarItems($conn, $ordenId, $items);
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        $this->recalcularProyectos([$proyectoId]);
        return $ordenId;
    }

    /**
     * Edita una OC (solo borrador / pendiente / rechazada). Una OC rechazada que se corrige
     * vuelve a "pendiente_aprobacion". Reemplaza todas las líneas.
     */
    public function update(int $id, array $data, array $detalles): void {
        $actual = dbQueryOne("SELECT proyecto_id, estado FROM ordenes_compra WHERE id = ?", [$id]);
        if (!$actual) {
            throw new Exception('La orden de compra no existe.');
        }
        if (!in_array($actual['estado'], self::ESTADOS_EDITABLES, true)) {
            throw new Exception('Solo se pueden editar órdenes en borrador, pendientes o rechazadas.');
        }

        $proyectoId = (int)($data['proyecto_id'] ?? 0);
        $cab = $this->prepararCabecera($data);
        [$items, $subtotal] = $this->normalizarItems($detalles, $proyectoId);

        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);
        $nuevoEstado = $actual['estado'] === 'rechazada' ? 'pendiente_aprobacion' : $actual['estado'];

        $conn = getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("UPDATE ordenes_compra SET
                        proyecto_id = ?, proveedor_id = ?, proveedor_nombre = ?, fecha_emision = ?, fecha_entrega = ?,
                        subtotal = ?, igv = ?, total = ?, moneda = ?, condicion_pago = ?, lugar_entrega = ?,
                        observaciones = ?, estado = ?,
                        motivo_rechazo = NULL, aprobado_por = NULL, fecha_aprobacion = NULL
                    WHERE id = ?");
            $stmt->execute([
                $proyectoId, $cab['proveedor_id'], $cab['proveedor_nombre'], $cab['fecha_emision'], $cab['fecha_entrega'],
                $subtotal, $igv, $total, $cab['moneda'], $cab['condicion_pago'], $cab['lugar_entrega'],
                $cab['observaciones'], $nuevoEstado, $id
            ]);

            $conn->prepare("DELETE FROM orden_compra_detalles WHERE orden_compra_id = ?")->execute([$id]);
            $this->insertarItems($conn, $id, $items);
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        // Si se cambió de proyecto, hay que refrescar ambos WBS
        $this->recalcularProyectos([(int)$actual['proyecto_id'], $proyectoId]);
    }

    /**
     * Aprobar (solo desde pendiente_aprobacion). Devuelve false si el estado no lo permite.
     */
    public function aprobar(int $id, int $aprobadoPor): bool {
        return $this->transicion($id, ['pendiente_aprobacion'], 'aprobada',
            "aprobado_por = ?, fecha_aprobacion = NOW(), motivo_rechazo = NULL", [$aprobadoPor]);
    }

    /**
     * Rechazar con motivo (solo desde pendiente_aprobacion).
     */
    public function rechazar(int $id, string $motivo, int $rechazadoPor): bool {
        return $this->transicion($id, ['pendiente_aprobacion'], 'rechazada',
            "motivo_rechazo = ?, aprobado_por = ?, fecha_aprobacion = NOW()", [$motivo, $rechazadoPor]);
    }

    /**
     * Anular. No se puede anular una OC ya recibida ni una con ingresos a almacén.
     */
    public function anular(int $id): bool {
        if ($this->tieneIngresoAlmacen($id)) {
            throw new Exception('La orden ya tiene ingresos a almacén y no puede anularse.');
        }
        return $this->transicion($id, ['borrador', 'pendiente_aprobacion', 'aprobada', 'rechazada'], 'anulada');
    }

    /**
     * Eliminar. No se elimina una OC con movimientos de almacén asociados (se perdería la trazabilidad).
     */
    public function delete(int $id): bool {
        $oc = dbQueryOne("SELECT proyecto_id FROM ordenes_compra WHERE id = ?", [$id]);
        if (!$oc) { return false; }
        if ($this->tieneIngresoAlmacen($id)) {
            throw new Exception('La orden tiene movimientos de almacén asociados; anúlala en lugar de eliminarla.');
        }

        dbExecute("DELETE FROM ordenes_compra WHERE id = ?", [$id]);
        $this->recalcularProyectos([(int)$oc['proyecto_id']]);
        return true;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  KPIs Y GRÁFICOS
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Resumen de compras y estado financiero del módulo
     */
    public function getResumen(?int $proyectoId = null): array {
        $where = "1=1";
        $params = [];
        if ($proyectoId) {
            $where .= " AND proyecto_id = ?";
            $params[] = $proyectoId;
        }

        $row = dbQueryOne("SELECT
                    COUNT(*) as total_ordenes,
                    SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as total_aprobadas,
                    SUM(CASE WHEN estado = 'pendiente_aprobacion' THEN 1 ELSE 0 END) as total_pendientes,
                    SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as total_rechazadas,
                    COALESCE(SUM(CASE WHEN estado IN (" . self::ESTADOS_COSTO . ") THEN total ELSE 0 END), 0) as monto_aprobado,
                    COALESCE(SUM(CASE WHEN estado = 'pendiente_aprobacion' THEN total ELSE 0 END), 0) as monto_pendiente,
                    COALESCE(SUM(total), 0) as monto_general
                FROM ordenes_compra
                WHERE $where", $params) ?: [];

        // Compras ya aprobadas cuyas líneas no están imputadas a ninguna actividad (no llegan al WBS)
        $wSin = $proyectoId ? "AND oc.proyecto_id = ?" : "";
        $sin = dbQueryOne("SELECT COALESCE(SUM(d.total), 0) AS monto, COUNT(*) AS lineas
                           FROM orden_compra_detalles d
                           JOIN ordenes_compra oc ON oc.id = d.orden_compra_id
                           WHERE d.tarea_id IS NULL AND oc.estado IN (" . self::ESTADOS_COSTO . ") $wSin",
                          $proyectoId ? [$proyectoId] : []) ?: [];

        return [
            'total_ordenes' => (int)($row['total_ordenes'] ?? 0),
            'total_aprobadas' => (int)($row['total_aprobadas'] ?? 0),
            'total_pendientes' => (int)($row['total_pendientes'] ?? 0),
            'total_rechazadas' => (int)($row['total_rechazadas'] ?? 0),
            'monto_aprobado' => (float)($row['monto_aprobado'] ?? 0),
            'monto_pendiente' => (float)($row['monto_pendiente'] ?? 0),
            'monto_general' => (float)($row['monto_general'] ?? 0),
            'monto_sin_actividad' => (float)($sin['monto'] ?? 0),
            'lineas_sin_actividad' => (int)($sin['lineas'] ?? 0),
        ];
    }

    /**
     * Cantidad y monto de órdenes por estado (gráfico "Compras por Estado").
     */
    public function getComprasPorEstado(?int $proyectoId = null): array {
        $where = $proyectoId ? "WHERE proyecto_id = ?" : "";
        $rows = dbQueryAll("SELECT estado, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS monto
                            FROM ordenes_compra $where GROUP BY estado", $proyectoId ? [$proyectoId] : []);
        $porEstado = [];
        foreach ($rows as $r) {
            $porEstado[$r['estado']] = ['cantidad' => (int)$r['cantidad'], 'monto' => (float)$r['monto']];
        }
        return $porEstado;
    }

    /**
     * Actividades con mayor gasto en compras aprobadas/recibidas (gasto neto, sin IGV).
     */
    public function getTopActividades(?int $proyectoId = null, int $limite = 8): array {
        $w = $proyectoId ? "AND oc.proyecto_id = ?" : "";
        $limite = max(1, min(50, $limite));
        $rows = dbQueryAll("SELECT t.id, t.codigo, t.nombre, p.codigo AS proyecto_codigo, t.costo_estimado,
                                   SUM(d.total) AS gasto, COUNT(*) AS lineas
                            FROM orden_compra_detalles d
                            JOIN ordenes_compra oc ON oc.id = d.orden_compra_id
                            JOIN tareas t ON t.id = d.tarea_id
                            JOIN proyectos p ON p.id = t.proyecto_id
                            WHERE oc.estado IN (" . self::ESTADOS_COSTO . ") $w
                            GROUP BY t.id, t.codigo, t.nombre, p.codigo, t.costo_estimado
                            ORDER BY gasto DESC
                            LIMIT $limite", $proyectoId ? [$proyectoId] : []);
        return array_map(fn($r) => [
            'id' => (int)$r['id'],
            'codigo' => $r['codigo'],
            'nombre' => $r['nombre'],
            'proyecto_codigo' => $r['proyecto_codigo'],
            'costo_estimado' => (float)$r['costo_estimado'],
            'gasto' => (float)$r['gasto'],
            'lineas' => (int)$r['lineas'],
        ], $rows);
    }

    public function getProveedores(): array {
        return dbQueryAll("SELECT * FROM proveedores WHERE activo = 1 ORDER BY razon_social ASC");
    }

    public function getProyectos(): array {
        return dbQueryAll("SELECT id, codigo, nombre, estado FROM proyectos ORDER BY fecha_inicio DESC, nombre ASC");
    }

    /** Materiales y equipos activos para el selector de ítems de la OC */
    public function getCatalogoItems(): array {
        return [
            'materiales' => dbQueryAll("SELECT id, codigo, nombre, unidad_medida, precio_referencial
                                        FROM cat_materiales WHERE activo = 1 ORDER BY nombre ASC"),
            'equipos' => dbQueryAll("SELECT id, codigo, nombre, costo_hora
                                     FROM cat_equipos WHERE activo = 1 ORDER BY nombre ASC"),
        ];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  INTERNOS
    // ═════════════════════════════════════════════════════════════════════

    private function tieneIngresoAlmacen(int $ordenId): bool {
        $r = dbQueryOne("SELECT 1 AS x FROM almacen_movimientos WHERE orden_compra_id = ? AND tipo_movimiento = 'ingreso' AND estado = 'registrado' LIMIT 1", [$ordenId]);
        return (bool)$r;
    }

    /**
     * Cambio de estado atómico (UPDATE condicionado al estado de origen) + recálculo del WBS.
     */
    private function transicion(int $id, array $desde, string $nuevo, string $setExtra = '', array $paramsExtra = []): bool {
        $oc = dbQueryOne("SELECT proyecto_id FROM ordenes_compra WHERE id = ?", [$id]);
        if (!$oc) { return false; }

        $in = implode(',', array_fill(0, count($desde), '?'));
        $set = "estado = ?" . ($setExtra !== '' ? ", $setExtra" : '');
        $stmt = getConnection()->prepare("UPDATE ordenes_compra SET $set WHERE id = ? AND estado IN ($in)");
        $stmt->execute(array_merge([$nuevo], $paramsExtra, [$id], $desde));

        if ($stmt->rowCount() === 0) { return false; }

        $this->recalcularProyectos([(int)$oc['proyecto_id']]);
        return true;
    }

    /**
     * Dispara el recálculo del WBS. Un fallo aquí no debe deshacer la operación de compras
     * (ya confirmada): se registra en el log y el usuario puede usar "Recalcular costos" en el WBS.
     */
    private function recalcularProyectos(array $proyectoIds): void {
        $tareaModel = new TareaModel();
        foreach (array_unique(array_filter(array_map('intval', $proyectoIds))) as $pid) {
            try {
                $tareaModel->recalcularCostoProyecto($pid);
            } catch (Throwable $e) {
                error_log("CompraModel: no se pudo recalcular costos del proyecto $pid: " . $e->getMessage());
            }
        }
    }

    private function prepararCabecera(array $data): array {
        $proyectoId = (int)($data['proyecto_id'] ?? 0);
        if ($proyectoId <= 0 || !dbQueryOne("SELECT id FROM proyectos WHERE id = ?", [$proyectoId])) {
            throw new Exception('Debes vincular la Orden de Compra a un proyecto válido.');
        }

        $proveedorId = !empty($data['proveedor_id']) ? (int)$data['proveedor_id'] : null;
        $proveedorNombre = trim($data['proveedor_nombre'] ?? '');
        if ($proveedorId) {
            $pv = dbQueryOne("SELECT razon_social FROM proveedores WHERE id = ?", [$proveedorId]);
            if (!$pv) { throw new Exception('El proveedor seleccionado no existe.'); }
            $proveedorNombre = $pv['razon_social']; // fuente de verdad: el catálogo, no el campo oculto del navegador
        }

        $fechaEmision = !empty($data['fecha_emision']) ? $data['fecha_emision'] : date('Y-m-d');
        $fechaEntrega = !empty($data['fecha_entrega']) ? $data['fecha_entrega'] : null;
        if ($fechaEntrega !== null && $fechaEntrega < $fechaEmision) {
            throw new Exception('La fecha de entrega no puede ser anterior a la fecha de emisión.');
        }

        return [
            'proveedor_id' => $proveedorId,
            'proveedor_nombre' => $proveedorNombre,
            'fecha_emision' => $fechaEmision,
            'fecha_entrega' => $fechaEntrega,
            'moneda' => ($data['moneda'] ?? 'PEN') === 'USD' ? 'USD' : 'PEN',
            'condicion_pago' => !empty($data['condicion_pago']) ? trim($data['condicion_pago']) : 'Crédito 30 días',
            'lugar_entrega' => !empty($data['lugar_entrega']) ? trim($data['lugar_entrega']) : null,
            'observaciones' => !empty($data['observaciones']) ? trim($data['observaciones']) : null,
        ];
    }

    /**
     * Valida y calcula las líneas: la actividad debe pertenecer al proyecto de la OC y el ítem
     * de catálogo debe existir. Una línea no puede ser material y equipo a la vez.
     *
     * @return array{0: array, 1: float} [líneas, subtotal]
     */
    private function normalizarItems(array $detalles, int $proyectoId): array {
        // Actividades válidas del proyecto, en una sola consulta
        $tareaIds = array_values(array_unique(array_filter(array_map(fn($d) => (int)($d['tarea_id'] ?? 0), $detalles))));
        $tareasValidas = [];
        if ($tareaIds) {
            $in = implode(',', array_fill(0, count($tareaIds), '?'));
            foreach (dbQueryAll("SELECT id FROM tareas WHERE proyecto_id = ? AND id IN ($in)", array_merge([$proyectoId], $tareaIds)) as $r) {
                $tareasValidas[(int)$r['id']] = true;
            }
        }

        $items = [];
        $subtotal = 0.0;
        foreach ($detalles as $d) {
            $materialId = !empty($d['material_id']) ? (int)$d['material_id'] : null;
            $equipoId = !empty($d['equipo_id']) ? (int)$d['equipo_id'] : null;
            if ($materialId && $equipoId) {
                throw new Exception('Una línea no puede ser material y equipo a la vez.');
            }

            $descripcion = trim($d['descripcion'] ?? '');
            if ($materialId) {
                $cat = dbQueryOne("SELECT nombre FROM cat_materiales WHERE id = ?", [$materialId]);
                if (!$cat) { throw new Exception('Uno de los materiales seleccionados no existe en el catálogo.'); }
                if ($descripcion === '') { $descripcion = $cat['nombre']; }
            } elseif ($equipoId) {
                $cat = dbQueryOne("SELECT nombre FROM cat_equipos WHERE id = ?", [$equipoId]);
                if (!$cat) { throw new Exception('Uno de los equipos seleccionados no existe en el catálogo.'); }
                if ($descripcion === '') { $descripcion = $cat['nombre']; }
            }
            if ($descripcion === '') { continue; }

            $tareaId = !empty($d['tarea_id']) ? (int)$d['tarea_id'] : null;
            if ($tareaId !== null && !isset($tareasValidas[$tareaId])) {
                throw new Exception('Una de las actividades seleccionadas no pertenece al proyecto de la orden.');
            }

            $cantidad = max(0.01, (float)($d['cantidad'] ?? 1));
            $precio = max(0, (float)($d['precio_unitario'] ?? 0));
            $total = round($cantidad * $precio, 2);
            $subtotal += $total;

            $items[] = [
                'tarea_id' => $tareaId,
                'material_id' => $materialId,
                'equipo_id' => $equipoId,
                'descripcion' => mb_substr($descripcion, 0, 255),
                'unidad_medida' => !empty($d['unidad_medida']) ? mb_substr(strtoupper(trim($d['unidad_medida'])), 0, 20) : 'UND',
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'total' => $total,
            ];
        }

        if (empty($items)) {
            throw new Exception('Debes agregar al menos un ítem con descripción.');
        }
        return [$items, round($subtotal, 2)];
    }

    private function insertarItems(PDO $conn, int $ordenId, array $items): void {
        $stmt = $conn->prepare("INSERT INTO orden_compra_detalles
                (orden_compra_id, tarea_id, material_id, equipo_id, descripcion, unidad_medida, cantidad, precio_unitario, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($items as $i) {
            $stmt->execute([$ordenId, $i['tarea_id'], $i['material_id'], $i['equipo_id'], $i['descripcion'],
                            $i['unidad_medida'], $i['cantidad'], $i['precio_unitario'], $i['total']]);
        }
    }
}
