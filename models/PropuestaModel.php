<?php
/**
 * Modelo: Propuesta
 */
class PropuestaModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $where .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (o.nombre LIKE ? OR p.codigo LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        
        return dbQuery("
            SELECT p.*, l.codigo as licitacion_codigo, o.nombre as oportunidad_nombre,
                   o.codigo as oportunidad_codigo, c.razon_social as entidad,
                   u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM propuestas p
            JOIN licitaciones l ON p.licitacion_id = l.id
            JOIN oportunidades o ON l.oportunidad_id = o.id
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN usuarios u ON p.responsable_id = u.id
            WHERE $where
            ORDER BY p.created_at DESC
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT p.*, l.codigo as licitacion_codigo, o.nombre as oportunidad_nombre,
                   c.razon_social as entidad, u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM propuestas p
            JOIN licitaciones l ON p.licitacion_id = l.id
            JOIN oportunidades o ON l.oportunidad_id = o.id
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN usuarios u ON p.responsable_id = u.id
            WHERE p.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        $codigo = 'PROP-' . date('Y') . '-' . str_pad(dbQueryOne("SELECT COUNT(*)+1 as n FROM propuestas")['n'], 3, '0', STR_PAD_LEFT);
        dbExecute("
            INSERT INTO propuestas (codigo, licitacion_id, responsable_id, monto_propuesta, estado)
            VALUES (?, ?, ?, ?, 'borrador')
        ", [$codigo, $data['licitacion_id'], $data['responsable_id'] ?: null, $data['monto_propuesta']]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        return dbExecute("
            UPDATE propuestas SET
            licitacion_id = ?, responsable_id = ?, monto_propuesta = ?, fecha_envio = ?,
            estado = ?, observaciones = ?
            WHERE id = ?
        ", [
            $data['licitacion_id'], $data['responsable_id'] ?: null,
            $data['monto_propuesta'], $data['fecha_envio'] ?: null,
            $data['estado'], $data['observaciones'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM propuestas WHERE id = ?", [$id]);
    }
}
