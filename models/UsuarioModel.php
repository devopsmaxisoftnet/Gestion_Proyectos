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
            SELECT u.*, r.nombre as rol_nombre,
                   oc.nombre as cargo_nombre, oc.codigo as cargo_codigo, oc.nivel_jerarquico as cargo_nivel,
                   oa.nombre as area_nombre
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            LEFT JOIN org_cargos oc ON u.cargo_id = oc.id
            LEFT JOIN org_areas oa ON COALESCE(u.org_area_id, u.area_id) = oa.id
            WHERE $where
            ORDER BY u.nombre
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT u.*, r.nombre as rol_nombre,
                   oc.nombre as cargo_nombre, oc.codigo as cargo_codigo, oc.nivel_jerarquico as cargo_nivel,
                   oa.nombre as area_nombre
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            LEFT JOIN org_cargos oc ON u.cargo_id = oc.id
            LEFT JOIN org_areas oa ON COALESCE(u.org_area_id, u.area_id) = oa.id
            WHERE u.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        $cargoId = !empty($data['cargo_id']) ? (int)$data['cargo_id'] : null;
        $areaId = !empty($data['org_area_id']) ? (int)$data['org_area_id'] : (!empty($data['area_id']) ? (int)$data['area_id'] : null);
        
        // Determinar rol automáticamente según el cargo (Nivel 1 o CEO -> admin, resto -> usuario)
        $cargoInfo = $cargoId ? dbQueryOne("SELECT nivel_jerarquico, codigo FROM org_cargos WHERE id = ?", [$cargoId]) : null;
        $esAdminCargo = ($cargoInfo && ((int)$cargoInfo['nivel_jerarquico'] === 1 || $cargoInfo['codigo'] === 'CEO'));
        $rolEnum = $esAdminCargo ? 'admin' : 'usuario';
        $rolId = $esAdminCargo ? 1 : 2;
        $tipoUsuarioId = $esAdminCargo ? 1 : 2;

        $hashedPassword = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        
        dbExecute("
            INSERT INTO usuarios (nombre, apellido, email, password, password_hash, rol_id, area_id, org_area_id, cargo_id, tipo_usuario_id, acceso_aprobado, activo, rol)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?)
        ", [
            $data['nombre'], $data['apellido'], $data['email'],
            $hashedPassword,
            $hashedPassword,
            $rolId,
            $areaId,
            $areaId,
            $cargoId,
            $tipoUsuarioId,
            $rolEnum
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        $userActual = dbQueryOne("SELECT * FROM usuarios WHERE id = ?", [$id]);
        $emailActual = strtolower($userActual['email'] ?? '');
        $esCuentaRaiz = in_array($emailActual, ['devops.maxisoftnet@gmail.com', 'asconsultti@gmail.com']);

        if ($esCuentaRaiz) {
            // Protección estricta e irrevocable para las dos cuentas de Administrador Supremo
            $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = 'admin', rol_id = 1, cargo_id = 1, org_area_id = 1, area_id = 1, activo = 1 WHERE id = ?";
            dbExecute($sql, [$data['nombre'], $data['apellido'], $emailActual, $id]);
            return;
        }

        $cargoId = !empty($data['cargo_id']) ? (int)$data['cargo_id'] : ($userActual['cargo_id'] ?? null);
        $areaId = !empty($data['org_area_id']) ? (int)$data['org_area_id'] : (!empty($data['area_id']) ? (int)$data['area_id'] : ($userActual['org_area_id'] ?? $userActual['area_id'] ?? null));
        
        // Determinar rol automáticamente según el cargo (Nivel 1 o CEO -> admin, resto -> usuario)
        $cargoInfo = $cargoId ? dbQueryOne("SELECT nivel_jerarquico, codigo FROM org_cargos WHERE id = ?", [$cargoId]) : null;
        $esAdminCargo = ($cargoInfo && ((int)$cargoInfo['nivel_jerarquico'] === 1 || $cargoInfo['codigo'] === 'CEO'));
        $rolEnum = $esAdminCargo ? 'admin' : 'usuario';
        $rolId = $esAdminCargo ? 1 : 2;
        $tipoUsuarioId = $data['tipo_usuario_id'] ?? $userActual['tipo_usuario_id'] ?? null;
        
        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol_id = ?, area_id = ?, org_area_id = ?, cargo_id = ?, tipo_usuario_id = ?, activo = ?, rol = ?";
        $params = [
            $data['nombre'], 
            $data['apellido'], 
            $data['email'], 
            $rolId, 
            $areaId, 
            $areaId,
            $cargoId,
            $tipoUsuarioId, 
            $data['activo'] ?? $userActual['activo'] ?? 1,
            $rolEnum
        ];
        
        if (!empty($data['password'])) {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $sql .= ", password = ?, password_hash = ?";
            $params[] = $hashedPassword;
            $params[] = $hashedPassword;
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
            // Si la cuenta estaba pendiente de aprobación, activarla y aprobarla formalmente
            if (isset($user['acceso_aprobado']) && empty($user['acceso_aprobado'])) {
                dbExecute("UPDATE solicitudes_acceso SET estado = 'aprobada', updated_at = NOW() WHERE usuario_id = ? AND estado = 'pendiente'", [$id]);
                return dbExecute("UPDATE usuarios SET activo = 1, acceso_aprobado = 1 WHERE id = ?", [$id]);
            }
            $nuevoEstado = $user['activo'] ? 0 : 1;
            return dbExecute("UPDATE usuarios SET activo = ? WHERE id = ?", [$nuevoEstado, $id]);
        }
        return false;
    }
    
    public function getRoles() {
        return dbQuery("SELECT * FROM roles ORDER BY id");
    }
    
    public function getAreas() {
        return dbQuery("SELECT * FROM org_areas WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
    }

    public function getOrgAreas() {
        return dbQuery("SELECT id, nombre, codigo, icono, color FROM org_areas WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
    }

    public function getOrgCargos() {
        return dbQuery("
            SELECT c.id, c.nombre, c.codigo, c.nivel_jerarquico, c.puede_aprobar, c.area_id, a.nombre as area_nombre 
            FROM org_cargos c 
            JOIN org_areas a ON c.area_id = a.id 
            WHERE c.activo = 1 
            ORDER BY c.nivel_jerarquico ASC, c.nombre ASC
        ");
    }
    
    public function getTiposUsuario() {
        return dbQuery("SELECT * FROM tipos_usuario ORDER BY nivel_acceso DESC");
    }
    
    public function getLineasNegocio() {
        return dbQuery("SELECT * FROM lineas_negocio WHERE activo = 1 ORDER BY nombre");
    }
}
