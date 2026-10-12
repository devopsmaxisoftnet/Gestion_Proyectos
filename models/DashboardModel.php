<?php
/**
 * Modelo: Dashboard
 * Consultas para el panel de resumen
 */
class DashboardModel {
    
    public function getOportunidadesActivas() {
        return dbQueryOne("SELECT COUNT(*) as total FROM oportunidades WHERE estado NOT IN ('cerrada','no_participar','no_adjudicada')");
    }
    
    public function getLicitacionesCount() {
        return dbQueryOne("SELECT COUNT(*) as total FROM licitaciones");
    }
    
    public function getPropuestasCount() {
        return dbQueryOne("SELECT COUNT(*) as total, SUM(monto_propuesta) as monto_total FROM propuestas WHERE estado IN ('enviada','aceptada')");
    }
    
    public function getContratosVigentes() {
        return dbQueryOne("SELECT COUNT(*) as total, SUM(monto_contrato) as monto_total FROM contratos WHERE estado NOT IN ('resuelto','anulado','liquidado')");
    }
    
    public function getValorPortafolio() {
        return dbQueryOne("SELECT SUM(monto_estimado) as total FROM oportunidades WHERE estado NOT IN ('cerrada','no_participar','no_adjudicada')");
    }
    
    public function getCarteraPorLinea() {
        return dbQuery("
            SELECT ln.id, ln.nombre, ln.descripcion, ln.icono, ln.color,
                   COUNT(o.id) as num_oportunidades,
                   COALESCE(SUM(o.monto_estimado), 0) as valor_estimado
            FROM lineas_negocio ln
            LEFT JOIN oportunidades o ON o.linea_negocio_id = ln.id AND o.estado NOT IN ('cerrada','no_participar')
            WHERE ln.activo = 1
            GROUP BY ln.id
            ORDER BY ln.id
        ");
    }
    
    public function getOportunidadesPorLinea($lineaId, $limit = 3) {
        return dbQuery("
            SELECT o.codigo, o.nombre, o.estado
            FROM oportunidades o
            WHERE o.linea_negocio_id = ? AND o.estado NOT IN ('cerrada','no_participar')
            ORDER BY o.created_at DESC
            LIMIT $limit
        ", [$lineaId]);
    }
    
    public function getOportunidadesPrioritarias($limit = 5) {
        return dbQuery("
            SELECT o.*, c.razon_social as entidad, ln.nombre as linea
            FROM oportunidades o
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN lineas_negocio ln ON o.linea_negocio_id = ln.id
            WHERE o.estado NOT IN ('cerrada','no_participar','no_adjudicada')
            ORDER BY o.fecha_limite ASC
            LIMIT $limit
        ");
    }
    
    public function getFlujoComercial() {
        $flujo = [];
        $flujo['detectadas'] = dbQueryOne("SELECT COUNT(*) as total FROM convocatorias")['total'];
        $flujo['en_evaluacion'] = dbQueryOne("SELECT COUNT(*) as total FROM oportunidad_evaluaciones")['total'];
        $flujo['en_propuesta'] = dbQueryOne("SELECT COUNT(*) as total FROM propuestas")['total'];
        $flujo['adjudicadas'] = dbQueryOne("SELECT COUNT(*) as total FROM oportunidades WHERE estado = 'adjudicada'")['total'];
        return $flujo;
    }
    
    public function getProximosVencimientos($dias = 10) {
        return dbQuery("
            SELECT o.codigo, o.nombre, o.fecha_limite
            FROM oportunidades o
            WHERE o.fecha_limite BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND o.estado NOT IN ('cerrada','no_participar','no_adjudicada','adjudicada')
            ORDER BY o.fecha_limite ASC
        ", [$dias]);
    }
    
    public function getLicitacionesEnPreparacion() {
        return dbQueryOne("SELECT COUNT(*) as total FROM licitaciones WHERE estado = 'en_preparacion'")['total'];
    }
    
    public function getEvaluacionPortafolio() {
        $total = dbQueryOne("SELECT COUNT(*) as total FROM oportunidades")['total'];
        $evaluadas = dbQueryOne("SELECT COUNT(*) as total FROM oportunidad_evaluaciones WHERE aprobado_gerencia = 1")['total'];
        return $total > 0 ? round(($evaluadas / $total) * 100) : 0;
    }
}
