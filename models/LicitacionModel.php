<?php
/**
 * Modelo: Licitacion
 */
class LicitacionModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $where .= " AND l.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (o.nombre LIKE ? OR l.codigo LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        
        return dbQuery("
            SELECT l.*, o.nombre as oportunidad_nombre, o.codigo as oportunidad_codigo,
                   c.razon_social as entidad, ln.nombre as linea,
                   u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM licitaciones l
            JOIN oportunidades o ON l.oportunidad_id = o.id
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN lineas_negocio ln ON o.linea_negocio_id = ln.id
            LEFT JOIN usuarios u ON l.responsable_id = u.id
            WHERE $where
            ORDER BY l.fecha_presentacion ASC
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT l.*, o.nombre as oportunidad_nombre, o.codigo as oportunidad_codigo,
                   c.razon_social as entidad, ln.nombre as linea,
                   u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM licitaciones l
            JOIN oportunidades o ON l.oportunidad_id = o.id
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN lineas_negocio ln ON o.linea_negocio_id = ln.id
            LEFT JOIN usuarios u ON l.responsable_id = u.id
            WHERE l.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        $codigo = 'LIC-' . date('Y') . '-' . str_pad(dbQueryOne("SELECT COUNT(*)+1 as n FROM licitaciones")['n'], 3, '0', STR_PAD_LEFT);
        dbExecute("
            INSERT INTO licitaciones (codigo, oportunidad_id, responsable_id, fecha_presentacion, monto_ofertado, estado)
            VALUES (?, ?, ?, ?, ?, 'en_preparacion')
        ", [$codigo, $data['oportunidad_id'], $data['responsable_id'] ?: null, $data['fecha_presentacion'], $data['monto_ofertado']]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        return dbExecute("
            UPDATE licitaciones SET
            oportunidad_id = ?, responsable_id = ?, fecha_presentacion = ?, monto_ofertado = ?,
            estado = ?, observaciones = ?
            WHERE id = ?
        ", [
            $data['oportunidad_id'], $data['responsable_id'] ?: null,
            $data['fecha_presentacion'], $data['monto_ofertado'],
            $data['estado'], $data['observaciones'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM licitaciones WHERE id = ?", [$id]);
    }
}
