<?php
/**
 * Modelo: Convocatoria
 */
class ConvocatoriaModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $where .= " AND c.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (c.nombre LIKE ? OR c.entidad LIKE ? OR c.codigo LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        if (!empty($filtros['linea_negocio_id'])) {
            $where .= " AND c.linea_negocio_id = ?";
            $params[] = $filtros['linea_negocio_id'];
        }
        if (!empty($filtros['departamento'])) {
            $where .= " AND c.departamento = ?";
            $params[] = $filtros['departamento'];
        }
        
        return dbQuery("
            SELECT c.*, ln.nombre as linea_negocio
            FROM convocatorias c
            LEFT JOIN lineas_negocio ln ON c.linea_negocio_id = ln.id
            WHERE $where
            ORDER BY c.fecha_publicacion DESC
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT c.*, ln.nombre as linea_negocio, cl.razon_social as cliente_nombre
            FROM convocatorias c
            LEFT JOIN lineas_negocio ln ON c.linea_negocio_id = ln.id
            LEFT JOIN clientes cl ON c.cliente_id = cl.id
            WHERE c.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        $codigo = 'CONV-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        dbExecute("
            INSERT INTO convocatorias (codigo, nombre, entidad, cliente_id, linea_negocio_id, tipo_gestion, 
            monto_referencial, fecha_publicacion, fecha_limite_oferta, plazo_ejecucion, requisitos_profesionales,
            departamento, fuente, estado, detectada_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'detectada', ?)
        ", [
            $codigo, $data['nombre'], $data['entidad'], $data['cliente_id'] ?: null,
            $data['linea_negocio_id'] ?: null, $data['tipo_gestion'], $data['monto_referencial'],
            $data['fecha_publicacion'], $data['fecha_limite_oferta'], $data['plazo_ejecucion'],
            $data['requisitos_profesionales'], $data['departamento'], $data['fuente'] ?? 'Manual',
            $data['detectada_por'] ?? 1
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        return dbExecute("
            UPDATE convocatorias SET
            nombre = ?, entidad = ?, cliente_id = ?, linea_negocio_id = ?, tipo_gestion = ?,
            monto_referencial = ?, fecha_publicacion = ?, fecha_limite_oferta = ?, plazo_ejecucion = ?,
            requisitos_profesionales = ?, departamento = ?, estado = ?, observaciones = ?
            WHERE id = ?
        ", [
            $data['nombre'], $data['entidad'], $data['cliente_id'] ?: null,
            $data['linea_negocio_id'] ?: null, $data['tipo_gestion'], $data['monto_referencial'],
            $data['fecha_publicacion'], $data['fecha_limite_oferta'], $data['plazo_ejecucion'],
            $data['requisitos_profesionales'], $data['departamento'], $data['estado'],
            $data['observaciones'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM convocatorias WHERE id = ?", [$id]);
    }
    
    public function evaluar($id, $estado) {
        return dbExecute("UPDATE convocatorias SET estado = ? WHERE id = ?", [$estado, $id]);
    }
    
    public function convertirAOportunidad($id) {
        $conv = $this->getById($id);
        if (!$conv) return false;
        
        $codigo = 'OP-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        dbExecute("
            INSERT INTO oportunidades (codigo, nombre, convocatoria_id, cliente_id, linea_negocio_id, 
            monto_estimado, fecha_limite, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'en_evaluacion')
        ", [
            $codigo, $conv['nombre'], $id, $conv['cliente_id'],
            $conv['linea_negocio_id'], $conv['monto_referencial'], $conv['fecha_limite_oferta']
        ]);
        
        $this->evaluar($id, 'convertida');
        return dbLastId();
    }
    
    public function getStats() {
        return [
            'detectadas' => dbQueryOne("SELECT COUNT(*) as t FROM convocatorias WHERE estado = 'detectada'")['t'],
            'convertidas' => dbQueryOne("SELECT COUNT(*) as t FROM convocatorias WHERE estado = 'convertida'")['t'],
        ];
    }
}
