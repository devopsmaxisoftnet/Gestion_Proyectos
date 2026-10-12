<?php
/**
 * Modelo: Contrato
 */
class ContratoModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $where .= " AND ct.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['tipo_gestion'])) {
            $where .= " AND ct.tipo_gestion = ?";
            $params[] = $filtros['tipo_gestion'];
        }
        if (!empty($filtros['anio'])) {
            $where .= " AND ct.anio = ?";
            $params[] = (int)$filtros['anio'];
        }
        if (!empty($filtros['busqueda'])) {
            $term = "%{$filtros['busqueda']}%";
            $where .= " AND (ct.objeto LIKE ? OR ct.codigo LIKE ? OR ct.nombre_corto LIKE ? OR ct.consultor LIKE ? OR ct.cui LIKE ? OR c.razon_social LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        
        return dbQuery("
            SELECT ct.*, c.razon_social as cliente_nombre,
                   u.nombre as resp_nombre, u.apellido as resp_apellido,
                   COALESCE(SUM(p.monto_facturado), 0) as total_facturado,
                   COALESCE(SUM(p.monto_ingreso_cuenta), 0) as total_ingreso,
                   COALESCE(SUM(p.monto_retenido), 0) as total_retenido,
                   COUNT(p.id) as cant_pagos
            FROM contratos ct
            LEFT JOIN clientes c ON ct.cliente_id = c.id
            LEFT JOIN usuarios u ON ct.responsable_id = u.id
            LEFT JOIN contrato_pagos p ON ct.id = p.contrato_id
            WHERE $where
            GROUP BY ct.id
            ORDER BY ct.anio DESC, ct.id ASC
        ", $params);
    }
    
    public function getById($id) {
        $ct = dbQueryOne("
            SELECT ct.*, c.razon_social as cliente_nombre,
                   u.nombre as resp_nombre, u.apellido as resp_apellido,
                   o.nombre as oportunidad_nombre, o.codigo as oportunidad_codigo
            FROM contratos ct
            LEFT JOIN clientes c ON ct.cliente_id = c.id
            LEFT JOIN usuarios u ON ct.responsable_id = u.id
            LEFT JOIN oportunidades o ON ct.oportunidad_id = o.id
            WHERE ct.id = ?
        ", [$id]);
        
        if ($ct) {
            $ct['pagos'] = dbQuery("
                SELECT * FROM contrato_pagos 
                WHERE contrato_id = ? 
                ORDER BY id ASC
            ", [$id]);

            // Totales de pagos para este contrato
            $totales = dbQueryOne("
                SELECT 
                    COALESCE(SUM(monto_facturado), 0) as sum_facturado,
                    COALESCE(SUM(monto_ingreso_cuenta), 0) as sum_ingreso,
                    COALESCE(SUM(monto_retenido), 0) as sum_retenido,
                    COALESCE(SUM(utilidad), 0) as sum_utilidad,
                    COUNT(*) as total_pagos
                FROM contrato_pagos
                WHERE contrato_id = ?
            ", [$id]);

            $ct['metricas'] = $totales ?: [];
        }
        return $ct;
    }

    public function getTotalesGenerales($filtros = []) {
        $whereCt = "1=1";
        $paramsCt = [];
        if (!empty($filtros['anio'])) {
            $whereCt .= " AND anio = ?";
            $paramsCt[] = (int)$filtros['anio'];
        }
        if (!empty($filtros['tipo_gestion'])) {
            $whereCt .= " AND tipo_gestion = ?";
            $paramsCt[] = $filtros['tipo_gestion'];
        }

        $totalesContratos = dbQueryOne("
            SELECT 
                COUNT(*) as total_contratos,
                COUNT(CASE WHEN tipo_gestion = 'propia' THEN 1 END) as total_propios,
                COUNT(CASE WHEN tipo_gestion = 'externa' THEN 1 END) as total_externos,
                COUNT(CASE WHEN anio = 2026 THEN 1 END) as total_2026,
                COUNT(CASE WHEN anio = 2025 THEN 1 END) as total_2025,
                COALESCE(SUM(monto_contrato), 0) as total_contratado
            FROM contratos
            WHERE $whereCt
        ", $paramsCt);

        // Totales de pagos para los contratos filtrados
        $totalesPagos = dbQueryOne("
            SELECT 
                COALESCE(SUM(p.monto_facturado), 0) as total_facturado,
                COALESCE(SUM(p.monto_ingreso_cuenta), 0) as total_ingreso,
                COALESCE(SUM(p.monto_retenido), 0) as total_retenido,
                COALESCE(SUM(p.utilidad), 0) as total_utilidad
            FROM contrato_pagos p
            INNER JOIN contratos ct ON p.contrato_id = ct.id
            WHERE $whereCt
        ", $paramsCt);

        return array_merge($totalesContratos ?: [], $totalesPagos ?: []);
    }
    
    public function create($data) {
        $codigo = !empty($data['codigo']) ? trim($data['codigo']) : ('CONT-' . date('Y') . '-' . str_pad(dbQueryOne("SELECT COUNT(*)+1 as n FROM contratos")['n'], 3, '0', STR_PAD_LEFT));
        $monto = !empty($data['monto_contrato']) ? (float)$data['monto_contrato'] : 0.00;
        $plazo = !empty($data['plazo_dias']) ? (int)$data['plazo_dias'] : 0;
        $utilidad = !empty($data['monto_utilidad']) ? (float)$data['monto_utilidad'] : 0.00;
        $anio = !empty($data['anio']) ? (int)$data['anio'] : date('Y');

        dbExecute("
            INSERT INTO contratos (
                codigo, anio, oportunidad_id, cliente_id, responsable_id, objeto, nombre_corto, cui, bases, estado_licitacion,
                monto_contrato, moneda, fecha_firma, fecha_inicio, fecha_fin, plazo_dias, estado,
                tipo_contrato, tipo_gestion, consultor, empresa_factura, empresas_asociadas,
                representante, tipo_servicio, clasificacion, responsable_texto, monto_utilidad, observaciones
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PEN', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $codigo, $anio, $data['oportunidad_id'] ?: null, $data['cliente_id'] ?: null,
            $data['responsable_id'] ?: null, $data['objeto'] ?? '', $data['nombre_corto'] ?? '', $data['cui'] ?? '',
            $data['bases'] ?? '', $data['estado_licitacion'] ?? 'SUSCRITO',
            $monto, $data['fecha_firma'] ?: null, $data['fecha_inicio'] ?: null,
            $data['fecha_fin'] ?: null, $plazo, $data['estado'] ?? 'en_proceso',
            $data['tipo_contrato'] ?? ($data['tipo_servicio'] ?? 'Servicio'),
            $data['tipo_gestion'] ?? 'propia', $data['consultor'] ?? '', $data['empresa_factura'] ?? '',
            $data['empresas_asociadas'] ?? '', $data['representante'] ?? '', $data['tipo_servicio'] ?? '',
            $data['clasificacion'] ?? '', $data['responsable_texto'] ?? '', $utilidad, $data['observaciones'] ?? ''
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        $monto = !empty($data['monto_contrato']) ? (float)$data['monto_contrato'] : 0.00;
        $plazo = !empty($data['plazo_dias']) ? (int)$data['plazo_dias'] : 0;
        $utilidad = !empty($data['monto_utilidad']) ? (float)$data['monto_utilidad'] : 0.00;
        $anio = !empty($data['anio']) ? (int)$data['anio'] : 2026;

        return dbExecute("
            UPDATE contratos SET
                codigo = ?, anio = ?, oportunidad_id = ?, cliente_id = ?, responsable_id = ?, objeto = ?,
                nombre_corto = ?, cui = ?, bases = ?, estado_licitacion = ?, monto_contrato = ?, fecha_firma = ?, fecha_inicio = ?,
                fecha_fin = ?, plazo_dias = ?, estado = ?, tipo_contrato = ?, tipo_gestion = ?,
                consultor = ?, empresa_factura = ?, empresas_asociadas = ?, representante = ?,
                tipo_servicio = ?, clasificacion = ?, responsable_texto = ?, monto_utilidad = ?, observaciones = ?
            WHERE id = ?
        ", [
            $data['codigo'] ?? '', $anio, $data['oportunidad_id'] ?: null, $data['cliente_id'] ?: null,
            $data['responsable_id'] ?: null, $data['objeto'] ?? '', $data['nombre_corto'] ?? '',
            $data['cui'] ?? '', $data['bases'] ?? '', $data['estado_licitacion'] ?? 'SUSCRITO',
            $monto, $data['fecha_firma'] ?: null, $data['fecha_inicio'] ?: null,
            $data['fecha_fin'] ?: null, $plazo, $data['estado'] ?? 'en_proceso',
            $data['tipo_contrato'] ?? ($data['tipo_servicio'] ?? 'Servicio'),
            $data['tipo_gestion'] ?? 'propia', $data['consultor'] ?? '', $data['empresa_factura'] ?? '',
            $data['empresas_asociadas'] ?? '', $data['representante'] ?? '', $data['tipo_servicio'] ?? '',
            $data['clasificacion'] ?? '', $data['responsable_texto'] ?? '', $utilidad, $data['observaciones'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM contratos WHERE id = ?", [$id]);
    }

    public function agregarPago($contratoId, $pago) {
        return dbExecute("
            INSERT INTO contrato_pagos (
                contrato_id, numero_pago, fecha_emision, nro_factura,
                monto_facturado, estado, fecha_pago, monto_ingreso_cuenta,
                monto_retenido, depositado_contratista, utilidad, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $contratoId,
            $pago['numero_pago'] ?? '',
            $pago['fecha_emision'] ?: null,
            $pago['nro_factura'] ?? '',
            floatval($pago['monto_facturado'] ?? 0),
            $pago['estado'] ?? 'PAGADA',
            $pago['fecha_pago'] ?: null,
            floatval($pago['monto_ingreso_cuenta'] ?? 0),
            floatval($pago['monto_retenido'] ?? 0),
            floatval($pago['depositado_contratista'] ?? 0),
            floatval($pago['utilidad'] ?? 0),
            $pago['observaciones'] ?? ''
        ]);
    }

    public function eliminarPago($pagoId) {
        return dbExecute("DELETE FROM contrato_pagos WHERE id = ?", [$pagoId]);
    }
}
