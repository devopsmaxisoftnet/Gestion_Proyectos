<?php
class ProfesionalModel {
    
    public function getEspecialidades() {
        return dbQuery("SELECT * FROM cat_especialidades WHERE activo = 1 ORDER BY nombre ASC");
    }

    public function getTiposProfesional() {
        return dbQuery("SELECT * FROM cat_tipos_profesional WHERE activo = 1 ORDER BY nombre ASC");
    }

    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (p.nombre LIKE ? OR p.especialidad LIKE ? OR p.tipo_profesional LIKE ? OR p.email LIKE ? OR p.telefono LIKE ?)";
            $term = "%{$filtros['busqueda']}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filtros['especialidad_id'])) {
            $where .= " AND p.especialidad_id = ?";
            $params[] = intval($filtros['especialidad_id']);
        }

        if (!empty($filtros['empresa_id'])) {
            $where .= " AND p.empresa_id = ?";
            $params[] = intval($filtros['empresa_id']);
        }
        
        return dbQuery("
            SELECT p.*, e.razon_social as empresa_nombre, ce.nombre as especialidad_catalogo
            FROM profesionales p
            LEFT JOIN empresas e ON p.empresa_id = e.id
            LEFT JOIN cat_especialidades ce ON p.especialidad_id = ce.id
            WHERE $where
            ORDER BY p.nombre ASC
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT p.*, e.razon_social as empresa_nombre, ce.nombre as especialidad_catalogo
            FROM profesionales p
            LEFT JOIN empresas e ON p.empresa_id = e.id
            LEFT JOIN cat_especialidades ce ON p.especialidad_id = ce.id
            WHERE p.id = ?
        ", [$id]);
    }
    
    public function getByEmpresa($empresaId) {
        return dbQuery("SELECT * FROM profesionales WHERE empresa_id = ? ORDER BY nombre ASC", [$empresaId]);
    }
    
    public function create($data) {
        $espId = !empty($data['especialidad_id']) ? intval($data['especialidad_id']) : null;
        $especialidadNombre = $data['especialidad'] ?? '';
        
        if ($espId) {
            $espRow = dbQueryOne("SELECT nombre FROM cat_especialidades WHERE id = ?", [$espId]);
            if ($espRow) {
                $especialidadNombre = $espRow['nombre'];
            }
        }

        $tipoId = !empty($data['tipo_profesional_id']) ? intval($data['tipo_profesional_id']) : null;
        $tipoNombre = $data['tipo_profesional'] ?? '';
        if ($tipoId) {
            $tipoRow = dbQueryOne("SELECT nombre FROM cat_tipos_profesional WHERE id = ?", [$tipoId]);
            if ($tipoRow) {
                $tipoNombre = $tipoRow['nombre'];
            }
        }

        return dbExecute("
            INSERT INTO profesionales (nombre, tipo_profesional, tipo_profesional_id, especialidad, especialidad_id, telefono, email, empresa_id, activo, costo_hora, costo_hora_extra)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['nombre'],
            $tipoNombre,
            $tipoId,
            $especialidadNombre,
            $espId,
            $data['telefono'] ?? null,
            $data['email'] ?? null,
            !empty($data['empresa_id']) ? $data['empresa_id'] : null,
            isset($data['activo']) ? intval($data['activo']) : 1,
            $this->tarifa($data['costo_hora'] ?? null, 25.00),
            $this->tarifa($data['costo_hora_extra'] ?? null, 35.00)
        ]);
    }

    /**
     * Tarifa hora-hombre: número >= 0 con 2 decimales. Vacío/ausente = valor por defecto.
     */
    private function tarifa($v, float $defecto): float {
        if ($v === null || $v === '') { return $defecto; }
        if (!is_numeric($v) || (float)$v < 0 || (float)$v > 99999) { return $defecto; }
        return round((float)$v, 2);
    }

    public function update($id, $data) {
        $espId = !empty($data['especialidad_id']) ? intval($data['especialidad_id']) : null;
        $especialidadNombre = $data['especialidad'] ?? '';
        
        if ($espId) {
            $espRow = dbQueryOne("SELECT nombre FROM cat_especialidades WHERE id = ?", [$espId]);
            if ($espRow) {
                $especialidadNombre = $espRow['nombre'];
            }
        }

        $tipoId = !empty($data['tipo_profesional_id']) ? intval($data['tipo_profesional_id']) : null;
        $tipoNombre = $data['tipo_profesional'] ?? '';
        if ($tipoId) {
            $tipoRow = dbQueryOne("SELECT nombre FROM cat_tipos_profesional WHERE id = ?", [$tipoId]);
            if ($tipoRow) {
                $tipoNombre = $tipoRow['nombre'];
            }
        }

        $actual = dbQueryOne("SELECT costo_hora, costo_hora_extra FROM profesionales WHERE id = ?", [$id]) ?: ['costo_hora' => 25, 'costo_hora_extra' => 35];

        return dbExecute("
            UPDATE profesionales SET
            nombre = ?, tipo_profesional = ?, tipo_profesional_id = ?, especialidad = ?, especialidad_id = ?,
            telefono = ?, email = ?, empresa_id = ?, activo = ?, costo_hora = ?, costo_hora_extra = ?
            WHERE id = ?
        ", [
            $data['nombre'],
            $tipoNombre,
            $tipoId,
            $especialidadNombre,
            $espId,
            $data['telefono'] ?? null,
            $data['email'] ?? null,
            !empty($data['empresa_id']) ? $data['empresa_id'] : null,
            isset($data['activo']) ? intval($data['activo']) : 1,
            $this->tarifa($data['costo_hora'] ?? null, (float)$actual['costo_hora']),
            $this->tarifa($data['costo_hora_extra'] ?? null, (float)$actual['costo_hora_extra']),
            $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM profesionales WHERE id = ?", [$id]);
    }
}
