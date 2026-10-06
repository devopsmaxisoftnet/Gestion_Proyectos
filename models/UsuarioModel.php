<?php
/**
 * Modelo: Usuario
 */
class UsuarioModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        if (!empty($filtros['rol_id'])) {
            $where .= " AND u.rol_id = ?";
            $params[] = $filtros['rol_id'];
        }
        
        return dbQuery("
            SELECT u.*, r.nombre as rol_nombre
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            WHERE $where
            ORDER BY u.nombre
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT u.*, r.nombre as rol_nombre
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            WHERE u.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        dbExecute("
            INSERT INTO usuarios (nombre, apellido, email, password, rol_id)
            VALUES (?, ?, ?, ?, ?)
        ", [
            $data['nombre'], $data['apellido'], $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT), $data['rol_id']
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol_id = ?, activo = ?";
        $params = [$data['nombre'], $data['apellido'], $data['email'], $data['rol_id'], $data['activo'] ?? 1];
        
        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        return dbExecute($sql, $params);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM usuarios WHERE id = ?", [$id]);
    }
    
    public function getRoles() {
        return dbQuery("SELECT * FROM roles ORDER BY id");
    }
    
    public function getLineasNegocio() {
        return dbQuery("SELECT * FROM lineas_negocio WHERE activo = 1 ORDER BY nombre");
    }
}
