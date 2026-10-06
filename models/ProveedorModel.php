<?php
/**
 * Modelo: Proveedor
 */
class ProveedorModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (razon_social LIKE ? OR ruc LIKE ? OR especialidad LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        
        return dbQuery("SELECT * FROM proveedores WHERE $where ORDER BY razon_social", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("SELECT * FROM proveedores WHERE id = ?", [$id]);
    }
    
    public function create($data) {
        dbExecute("
            INSERT INTO proveedores (razon_social, ruc, direccion, telefono, email, contacto_nombre, especialidad)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['razon_social'], $data['ruc'], $data['direccion'] ?? '',
            $data['telefono'] ?? '', $data['email'] ?? '',
            $data['contacto_nombre'] ?? '', $data['especialidad'] ?? ''
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        return dbExecute("
            UPDATE proveedores SET
            razon_social = ?, ruc = ?, direccion = ?, telefono = ?, email = ?,
            contacto_nombre = ?, especialidad = ?
            WHERE id = ?
        ", [
            $data['razon_social'], $data['ruc'], $data['direccion'] ?? '',
            $data['telefono'] ?? '', $data['email'] ?? '',
            $data['contacto_nombre'] ?? '', $data['especialidad'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM proveedores WHERE id = ?", [$id]);
    }
}
