<?php
/**
 * Modelo: Cliente
 */
class ClienteModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (razon_social LIKE ? OR ruc LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        if (!empty($filtros['tipo'])) {
            $where .= " AND tipo = ?";
            $params[] = $filtros['tipo'];
        }
        
        return dbQuery("SELECT * FROM clientes WHERE $where ORDER BY razon_social", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("SELECT * FROM clientes WHERE id = ?", [$id]);
    }
    
    public function create($data) {
        dbExecute("
            INSERT INTO clientes (razon_social, ruc, direccion, telefono, email, contacto_nombre,
            contacto_cargo, contacto_telefono, contacto_email, tipo, departamento, provincia, distrito)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['razon_social'], $data['ruc'], $data['direccion'] ?? '',
            $data['telefono'] ?? '', $data['email'] ?? '', $data['contacto_nombre'] ?? '',
            $data['contacto_cargo'] ?? '', $data['contacto_telefono'] ?? '',
            $data['contacto_email'] ?? '', $data['tipo'] ?? 'publico',
            $data['departamento'] ?? '', $data['provincia'] ?? '', $data['distrito'] ?? ''
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        return dbExecute("
            UPDATE clientes SET
            razon_social = ?, ruc = ?, direccion = ?, telefono = ?, email = ?,
            contacto_nombre = ?, contacto_cargo = ?, contacto_telefono = ?,
            contacto_email = ?, tipo = ?, departamento = ?, provincia = ?, distrito = ?
            WHERE id = ?
        ", [
            $data['razon_social'], $data['ruc'], $data['direccion'] ?? '',
            $data['telefono'] ?? '', $data['email'] ?? '', $data['contacto_nombre'] ?? '',
            $data['contacto_cargo'] ?? '', $data['contacto_telefono'] ?? '',
            $data['contacto_email'] ?? '', $data['tipo'] ?? 'publico',
            $data['departamento'] ?? '', $data['provincia'] ?? '', $data['distrito'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM clientes WHERE id = ?", [$id]);
    }
}
