<?php
/**
 * Modelo: Cliente
 */
class ClienteModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (c.razon_social LIKE ? OR c.ruc LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        if (!empty($filtros['tipo'])) {
            $where .= " AND c.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        
        return dbQuery("
            SELECT c.*, 
                   cd.nombre as departamento_nombre,
                   cp.nombre as provincia_nombre,
                   cc.nombre as cargo_nombre
            FROM clientes c
            LEFT JOIN cat_departamentos cd ON c.departamento_id = cd.id
            LEFT JOIN cat_provincias cp ON c.provincia_id = cp.id
            LEFT JOIN cat_cargos_cliente cc ON c.contacto_cargo_id = cc.id
            WHERE $where 
            ORDER BY c.razon_social
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("SELECT * FROM clientes WHERE id = ?", [$id]);
    }
    
    public function create($data) {
        $razon = trim($data['razon_social'] ?? '');
        if (!empty($razon)) {
            $existente = dbQueryOne("SELECT id FROM clientes WHERE LOWER(TRIM(razon_social)) = LOWER(?) LIMIT 1", [$razon]);
            if ($existente) {
                return (int)$existente['id'];
            }
        }
        
        dbExecute("
            INSERT INTO clientes (razon_social, ruc, direccion, telefono, email, contacto_nombre,
            contacto_cargo_id, contacto_telefono, contacto_email, tipo, departamento_id, provincia_id, distrito_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['razon_social'], $data['ruc'], $data['direccion'] ?? '',
            $data['telefono'] ?? '', $data['email'] ?? '', $data['contacto_nombre'] ?? '',
            empty($data['contacto_cargo_id']) ? null : $data['contacto_cargo_id'], 
            $data['contacto_telefono'] ?? '',
            $data['contacto_email'] ?? '', $data['tipo'] ?? 'publico',
            empty($data['departamento_id']) ? null : $data['departamento_id'], 
            empty($data['provincia_id']) ? null : $data['provincia_id'], 
            empty($data['distrito_id']) ? null : $data['distrito_id']
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        return dbExecute("
            UPDATE clientes SET
            razon_social = ?, ruc = ?, direccion = ?, telefono = ?, email = ?,
            contacto_nombre = ?, contacto_cargo_id = ?, contacto_telefono = ?,
            contacto_email = ?, tipo = ?, departamento_id = ?, provincia_id = ?, distrito_id = ?
            WHERE id = ?
        ", [
            $data['razon_social'], $data['ruc'], $data['direccion'] ?? '',
            $data['telefono'] ?? '', $data['email'] ?? '', $data['contacto_nombre'] ?? '',
            empty($data['contacto_cargo_id']) ? null : $data['contacto_cargo_id'], 
            $data['contacto_telefono'] ?? '',
            $data['contacto_email'] ?? '', $data['tipo'] ?? 'publico',
            empty($data['departamento_id']) ? null : $data['departamento_id'], 
            empty($data['provincia_id']) ? null : $data['provincia_id'], 
            empty($data['distrito_id']) ? null : $data['distrito_id'], 
            $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM clientes WHERE id = ?", [$id]);
    }
}
