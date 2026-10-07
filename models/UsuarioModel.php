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
        $rolEnum = in_array((int)($data['rol_id'] ?? 0), [1, 5]) ? 'admin' : 'usuario';
        dbExecute("
            INSERT INTO usuarios (nombre, apellido, email, password, rol_id, area_id, tipo_usuario_id, acceso_aprobado, rol)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
        ", [
            $data['nombre'], $data['apellido'], $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT), 
            $data['rol_id'] ?: null,
            $data['area_id'] ?: null,
            $data['tipo_usuario_id'] ?: null,
            $rolEnum
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        $rolEnum = in_array((int)($data['rol_id'] ?? 0), [1, 5]) ? 'admin' : 'usuario';
        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol_id = ?, area_id = ?, tipo_usuario_id = ?, activo = ?, rol = ?";
        $params = [
            $data['nombre'], 
            $data['apellido'], 
            $data['email'], 
            $data['rol_id'] ?: null, 
            $data['area_id'] ?: null, 
            $data['tipo_usuario_id'] ?: null, 
            $data['activo'] ?? 1,
            $rolEnum
        ];
        
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
    
    public function toggleStatus($id) {
        $user = $this->getById($id);
        if ($user) {
            $nuevoEstado = $user['activo'] ? 0 : 1;
            return dbExecute("UPDATE usuarios SET activo = ? WHERE id = ?", [$nuevoEstado, $id]);
        }
        return false;
    }
    
    public function getRoles() {
        return dbQuery("SELECT * FROM roles ORDER BY id");
    }
    
    public function getAreas() {
        return dbQuery("SELECT * FROM areas WHERE activo = 1 ORDER BY nombre");
    }
    
    public function getTiposUsuario() {
        return dbQuery("SELECT * FROM tipos_usuario ORDER BY nivel_acceso DESC");
    }
    
    public function getLineasNegocio() {
        return dbQuery("SELECT * FROM lineas_negocio WHERE activo = 1 ORDER BY nombre");
    }
}
