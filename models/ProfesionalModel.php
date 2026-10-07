<?php
class ProfesionalModel {
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (nombre LIKE ? OR especialidad LIKE ? OR tipo_profesional LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        
        return dbQuery("SELECT * FROM profesionales WHERE $where ORDER BY nombre ASC", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("SELECT * FROM profesionales WHERE id = ?", [$id]);
    }
    
    public function getByEmpresa($empresaId) {
        return dbQuery("SELECT * FROM profesionales WHERE empresa_id = ? ORDER BY nombre ASC", [$empresaId]);
    }
    
    public function create($data) {
        return dbExecute("
            INSERT INTO profesionales (nombre, tipo_profesional, especialidad, telefono, email, empresa_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $data['nombre'], $data['tipo_profesional'] ?? null,
            $data['especialidad'] ?? null, $data['telefono'] ?? null,
            $data['email'] ?? null, !empty($data['empresa_id']) ? $data['empresa_id'] : null
        ]);
    }
    
    public function update($id, $data) {
        return dbExecute("
            UPDATE profesionales SET
            nombre = ?, tipo_profesional = ?, especialidad = ?, telefono = ?, email = ?, empresa_id = ?
            WHERE id = ?
        ", [
            $data['nombre'], $data['tipo_profesional'] ?? null,
            $data['especialidad'] ?? null, $data['telefono'] ?? null,
            $data['email'] ?? null, !empty($data['empresa_id']) ? $data['empresa_id'] : null, $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM profesionales WHERE id = ?", [$id]);
    }
}
