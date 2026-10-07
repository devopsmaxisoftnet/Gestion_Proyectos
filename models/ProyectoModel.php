<?php
/**
 * Modelo: Proyecto
 */
class ProyectoModel {
    
    public function getAll($filtros = []) {
        $where = "1=1";
        $params = [];
        
        if (!empty($filtros['estado'])) {
            $where .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $where .= " AND (p.nombre LIKE ? OR p.codigo LIKE ?)";
            $params[] = "%{$filtros['busqueda']}%";
            $params[] = "%{$filtros['busqueda']}%";
        }
        
        return dbQuery("
            SELECT p.*, ct.codigo as contrato_codigo, ct.monto_contrato,
                   u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM proyectos p
            LEFT JOIN contratos ct ON p.contrato_id = ct.id
            LEFT JOIN usuarios u ON p.responsable_id = u.id
            WHERE $where
            ORDER BY p.fecha_inicio DESC
        ", $params);
    }
    
    public function getById($id) {
        return dbQueryOne("
            SELECT p.*, ct.codigo as contrato_codigo, ct.monto_contrato, ct.objeto as contrato_objeto,
                   u.nombre as resp_nombre, u.apellido as resp_apellido
            FROM proyectos p
            LEFT JOIN contratos ct ON p.contrato_id = ct.id
            LEFT JOIN usuarios u ON p.responsable_id = u.id
            WHERE p.id = ?
        ", [$id]);
    }
    
    public function create($data) {
        $codigo = 'PROY-' . date('Y') . '-' . str_pad(dbQueryOne("SELECT COUNT(*)+1 as n FROM proyectos")['n'], 3, '0', STR_PAD_LEFT);
        $fechaInicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
        $fechaFin = !empty($data['fecha_fin_planificada']) ? $data['fecha_fin_planificada'] : null;
        $montoPres = !empty($data['monto_presupuesto']) ? (float)$data['monto_presupuesto'] : 0.00;
        dbExecute("
            INSERT INTO proyectos (codigo, contrato_id, nombre, responsable_id, fecha_inicio, 
            fecha_fin_planificada, monto_presupuesto, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'planificacion')
        ", [
            $codigo, $data['contrato_id'] ?: null, $data['nombre'],
            $data['responsable_id'] ?: null, $fechaInicio,
            $fechaFin, $montoPres
        ]);
        return dbLastId();
    }
    
    public function update($id, $data) {
        $fechaInicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
        $fechaFin = !empty($data['fecha_fin_planificada']) ? $data['fecha_fin_planificada'] : null;
        $avance = !empty($data['avance_porcentaje']) ? (float)$data['avance_porcentaje'] : 0.00;
        $montoPres = !empty($data['monto_presupuesto']) ? (float)$data['monto_presupuesto'] : 0.00;
        $montoEjec = !empty($data['monto_ejecutado']) ? (float)$data['monto_ejecutado'] : 0.00;
        return dbExecute("
            UPDATE proyectos SET
            contrato_id = ?, nombre = ?, responsable_id = ?, fecha_inicio = ?,
            fecha_fin_planificada = ?, avance_porcentaje = ?, monto_presupuesto = ?,
            monto_ejecutado = ?, estado = ?, observaciones = ?
            WHERE id = ?
        ", [
            $data['contrato_id'] ?: null, $data['nombre'],
            $data['responsable_id'] ?: null, $fechaInicio,
            $fechaFin, $avance,
            $montoPres, $montoEjec,
            $data['estado'], $data['observaciones'] ?? '', $id
        ]);
    }
    
    public function delete($id) {
        return dbExecute("DELETE FROM proyectos WHERE id = ?", [$id]);
    }
    
    public function getValorizaciones($proyectoId) {
        return dbQuery("SELECT * FROM valorizaciones WHERE proyecto_id = ? ORDER BY numero", [$proyectoId]);
    }
}
