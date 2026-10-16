<?php
/**
 * Modelo: Empresa
 */
class EmpresaModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (e.razon_social LIKE ? OR e.ruc LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        
        return dbQuery("
            SELECT e.*, c.nombre as especialidad_nombre 
            FROM empresas e 
            LEFT JOIN cat_especialidades c ON e.especialidad_id = c.id 
            WHERE $where 
            ORDER BY e.razon_social
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("SELECT * FROM empresas WHERE id = ?", [$id]);
    }
    
    public function create($data) {
        require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';
        $drive_id = !empty($data['drive_folder_id']) ? GoogleDriveHelper::extractFolderId($data['drive_folder_id']) : null;

        dbExecute("
            INSERT INTO empresas (razon_social, ruc, tipo_empresa, num_cuenta, direccion, representante, contacto_administrativo, telefono, email, especialidad_id, drive_folder_id, drive_email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['razon_social'], $data['ruc'] ?? '', 
            $data['tipo_empresa'] ?? 'externa',
            $data['num_cuenta'] ?? '',
            $data['direccion'] ?? '',
            $data['representante'] ?? '',
            $data['contacto_administrativo'] ?? '',
            $data['telefono'] ?? '', $data['email'] ?? '',
            !empty($data['especialidad_id']) ? $data['especialidad_id'] : null,
            $drive_id,
            $data['drive_email'] ?? null
        ]);
        $newId = dbLastId();

        // Crear subcarpeta correspondiente en Google Drive
        try {
            $helper = new GoogleDriveHelper();
            $helper->getItemFolder('Documentos', $data['razon_social']);
        } catch (\Exception $e) {}

        return $newId;
    }
    
    public function update($id, $data) {
        require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';
        $drive_id = !empty($data['drive_folder_id']) ? GoogleDriveHelper::extractFolderId($data['drive_folder_id']) : null;

        return dbExecute("
            UPDATE empresas SET
            razon_social = ?, ruc = ?, tipo_empresa = ?, num_cuenta = ?, direccion = ?, 
            representante = ?, contacto_administrativo = ?, telefono = ?, email = ?,
            especialidad_id = ?, drive_folder_id = ?, drive_email = ?
            WHERE id = ?
        ", [
            $data['razon_social'], $data['ruc'] ?? '', 
            $data['tipo_empresa'] ?? 'externa',
            $data['num_cuenta'] ?? '',
            $data['direccion'] ?? '',
            $data['representante'] ?? '',
            $data['contacto_administrativo'] ?? '',
            $data['telefono'] ?? '', $data['email'] ?? '',
            !empty($data['especialidad_id']) ? $data['especialidad_id'] : null,
            $drive_id,
            $data['drive_email'] ?? null,
            $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM empresas WHERE id = ?", [$id]);
    }

    // ─── PROFESIONALES ─────────────────────────────────────────────

    public function getProfesionales($empresa_id) {
        return dbQuery("
            SELECT p.*, e.nombre as especialidad_nombre, t.nombre as tipo_nombre 
            FROM profesionales p 
            LEFT JOIN cat_especialidades e ON p.especialidad_id = e.id 
            LEFT JOIN cat_tipos_profesional t ON p.tipo_profesional_id = t.id 
            WHERE p.empresa_id = ? 
            ORDER BY p.nombre
        ", [$empresa_id]);
    }

    public function linkProfesional($empresa_id, $profesional_id) {
        return dbExecute("UPDATE profesionales SET empresa_id = ? WHERE id = ?", [$empresa_id, $profesional_id]);
    }

    public function unlinkProfesional($id) {
        return dbExecute("UPDATE profesionales SET empresa_id = NULL WHERE id = ?", [$id]);
    }

    // ─── DOCUMENTOS ─────────────────────────────────────────────

    public function getDocumentos($empresa_id) {
        return dbQuery("
            SELECT d.*, t.nombre as tipo_nombre 
            FROM empresa_documentos d 
            LEFT JOIN cat_tipos_documento t ON d.tipo_documento_id = t.id 
            WHERE d.empresa_id = ? 
            ORDER BY d.fecha_vencimiento ASC
        ", [$empresa_id]);
    }

    public function createDocumento($empresa_id, $data) {
        $archivo_path = null;
        if (!empty($_FILES['archivo_documento']['name'])) {
            $upload_dir = 'uploads/documentos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            require_once __DIR__ . '/../helpers/FileSecurityHelper.php';
            require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';

            $upDoc = FileSecurityHelper::validateAndUpload($_FILES['archivo_documento'], $upload_dir, [
                'prefix' => 'emp_' . $empresa_id
            ]);

            if ($upDoc['success']) {
                $archivo_path = $upDoc['path'];
                try {
                    $emp = $this->getById($empresa_id);
                    $empName = !empty($emp['razon_social']) ? $emp['razon_social'] : ('Empresa_' . $empresa_id);
                    GoogleDriveHelper::uploadSystemFile($upDoc['path'], $upDoc['orig_name'], 'Documentos', $empName);
                } catch (\Exception $e) {}
            } else {
                error_log("Upload documento empresa rechazado: " . $upDoc['error']);
            }
        }

        dbExecute("
            INSERT INTO empresa_documentos (empresa_id, nombre_documento, tipo_documento_id, fecha_emision, fecha_vencimiento, archivo_path)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $empresa_id, 
            $data['nombre_documento'], 
            !empty($data['tipo_documento_id']) ? $data['tipo_documento_id'] : null, 
            !empty($data['fecha_emision']) ? $data['fecha_emision'] : null, 
            !empty($data['fecha_vencimiento']) ? $data['fecha_vencimiento'] : null,
            $archivo_path
        ]);
        return dbLastId();
    }

    public function deleteDocumento($id) {
        return dbExecute("DELETE FROM empresa_documentos WHERE id = ?", [$id]);
    }

    // ─── CATALOGOS ─────────────────────────────────────────────
    public function getCatEspecialidades() {
        return dbQuery("SELECT * FROM cat_especialidades WHERE activo = 1 ORDER BY nombre ASC");
    }
    public function getCatTiposProfesional() {
        return dbQuery("SELECT * FROM cat_tipos_profesional WHERE activo = 1 ORDER BY nombre ASC");
    }
    public function getCatTiposDocumento() {
        return dbQuery("SELECT * FROM cat_tipos_documento WHERE activo = 1 ORDER BY nombre ASC");
    }
}
