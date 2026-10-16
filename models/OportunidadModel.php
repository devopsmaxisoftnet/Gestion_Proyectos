<?php
/**
 * Modelo: Oportunidad
 */
class OportunidadModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $where .= " AND o.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (o.nombre LIKE ? OR c.razon_social LIKE ? OR o.codigo LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        if (!empty($filtros['linea_negocio_id'])) {
            $where .= " AND o.linea_negocio_id = ?";
            $params[] = $filtros['linea_negocio_id'];
        }
        
        return dbQuery("
            SELECT o.*, c.razon_social as entidad, ln.nombre as linea,
                   u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM oportunidades o
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN lineas_negocio ln ON o.linea_negocio_id = ln.id
            LEFT JOIN usuarios u ON o.responsable_id = u.id
            WHERE $where
            ORDER BY o.fecha_limite ASC
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT o.*, c.razon_social as entidad, ln.nombre as linea,
                   u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM oportunidades o
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN lineas_negocio ln ON o.linea_negocio_id = ln.id
            LEFT JOIN usuarios u ON o.responsable_id = u.id
            WHERE o.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        $codigo = 'OP-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $monto = !empty($data['monto_estimado']) ? (float)$data['monto_estimado'] : 0.00;
        $fechaLimite = !empty($data['fecha_limite']) ? $data['fecha_limite'] : null;
        dbExecute("
            INSERT INTO oportunidades (codigo, nombre, cliente_id, linea_negocio_id, origen, responsable_id,
            monto_estimado, fecha_limite, estado, prioridad, descripcion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'identificada', ?, ?)
        ", [
            $codigo, $data['nombre'], $data['cliente_id'] ?: null,
            $data['linea_negocio_id'] ?: null, $data['origen'] ?? 'licitacion_publica',
            $data['responsable_id'] ?: null,
            $monto, $fechaLimite, $data['prioridad'] ?? 'media',
            $data['descripcion'] ?? ''
        ]);
        $newId = dbLastId();

        // Crear subcarpeta correspondiente en Google Drive
        try {
            require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';
            $helper = new GoogleDriveHelper();
            $helper->getItemFolder('Oportunidades', $codigo);
        } catch (\Exception $e) {}

        return $newId;
    }
    
    public function update($id, $data) {
        $monto = !empty($data['monto_estimado']) ? (float)$data['monto_estimado'] : 0.00;
        $fechaLimite = !empty($data['fecha_limite']) ? $data['fecha_limite'] : null;
        return dbExecute("
            UPDATE oportunidades SET
            nombre = ?, cliente_id = ?, linea_negocio_id = ?, origen = ?, responsable_id = ?,
            monto_estimado = ?, fecha_limite = ?, estado = ?, prioridad = ?, descripcion = ?, observaciones = ?
            WHERE id = ?
        ", [
            $data['nombre'], $data['cliente_id'] ?: null, $data['linea_negocio_id'] ?: null,
            $data['origen'] ?? 'licitacion_publica',
            $data['responsable_id'] ?: null, $monto, $fechaLimite,
            $data['estado'], $data['prioridad'] ?? 'media', $data['descripcion'] ?? '',
            $data['observaciones'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM oportunidades WHERE id = ?", [$id]);
    }
    
    public function cambiarEstado($id, $estado) {
        return dbExecute("UPDATE oportunidades SET estado = ? WHERE id = ?", [$estado, $id]);
    }

    // -- Nuevas funciones para tracking BPMN --
    public function getEvaluacion($oportunidad_id) {
        return dbQueryOne("SELECT * FROM oportunidad_evaluaciones WHERE oportunidad_id = ?", [$oportunidad_id]);
    }

    public function initEvaluacion($oportunidad_id) {
        $exists = $this->getEvaluacion($oportunidad_id);
        if (!$exists) {
            dbExecute("INSERT INTO oportunidad_evaluaciones (oportunidad_id) VALUES (?)", [$oportunidad_id]);
        }
    }

    public function updateEvaluacion($oportunidad_id, $data) {
        $this->initEvaluacion($oportunidad_id);
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        if (empty($fields)) return true;
        
        $params[] = $oportunidad_id;
        $sql = "UPDATE oportunidad_evaluaciones SET " . implode(', ', $fields) . " WHERE oportunidad_id = ?";
        return dbExecute($sql, $params);
    }
}
