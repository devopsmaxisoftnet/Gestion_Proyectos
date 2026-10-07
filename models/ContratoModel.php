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
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (ct.objeto LIKE ? OR ct.codigo LIKE ? OR c.razon_social LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        
        return dbQuery("
            SELECT ct.*, c.razon_social as cliente_nombre,
                   u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM contratos ct
            LEFT JOIN clientes c ON ct.cliente_id = c.id
            LEFT JOIN usuarios u ON ct.responsable_id = u.id
            WHERE $where
            ORDER BY ct.fecha_firma DESC
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT ct.*, c.razon_social as cliente_nombre,
                   u.nombre as resp_nombre, u.apellido as resp_apellido,
                   o.nombre as oportunidad_nombre, o.codigo as oportunidad_codigo
            FROM contratos ct
            LEFT JOIN clientes c ON ct.cliente_id = c.id
            LEFT JOIN usuarios u ON ct.responsable_id = u.id
            LEFT JOIN oportunidades o ON ct.oportunidad_id = o.id
            WHERE ct.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        $codigo = 'CONT-' . date('Y') . '-' . str_pad(dbQueryOne("SELECT COUNT(*)+1 as n FROM contratos")['n'], 3, '0', STR_PAD_LEFT);
        $monto = !empty($data['monto_contrato']) ? (float)$data['monto_contrato'] : 0.00;
        $plazo = !empty($data['plazo_dias']) ? (int)$data['plazo_dias'] : 0;
        dbExecute("
            INSERT INTO contratos (codigo, oportunidad_id, cliente_id, responsable_id, objeto, monto_contrato,
            fecha_firma, fecha_inicio, fecha_fin, plazo_dias, estado, tipo_contrato)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_elaboracion', ?)
        ", [
            $codigo, $data['oportunidad_id'] ?: null, $data['cliente_id'] ?: null,
            $data['responsable_id'] ?: null, $data['objeto'], $monto,
            $data['fecha_firma'] ?: null, $data['fecha_inicio'] ?: null,
            $data['fecha_fin'] ?: null, $plazo, $data['tipo_contrato'] ?? ''
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        $monto = !empty($data['monto_contrato']) ? (float)$data['monto_contrato'] : 0.00;
        $plazo = !empty($data['plazo_dias']) ? (int)$data['plazo_dias'] : 0;
        return dbExecute("
            UPDATE contratos SET
            oportunidad_id = ?, cliente_id = ?, responsable_id = ?, objeto = ?, monto_contrato = ?,
            fecha_firma = ?, fecha_inicio = ?, fecha_fin = ?, plazo_dias = ?, estado = ?,
            tipo_contrato = ?, observaciones = ?
            WHERE id = ?
        ", [
            $data['oportunidad_id'] ?: null, $data['cliente_id'] ?: null,
            $data['responsable_id'] ?: null, $data['objeto'], $monto,
            $data['fecha_firma'] ?: null, $data['fecha_inicio'] ?: null,
            $data['fecha_fin'] ?: null, $plazo, $data['estado'],
            $data['tipo_contrato'] ?? '', $data['observaciones'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM contratos WHERE id = ?", [$id]);
    }
}
