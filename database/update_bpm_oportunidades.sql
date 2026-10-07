
-- =====================================================
-- MIGRACIÓN BPMN: Gestión de Oportunidades
-- Ajustes para cumplir con el diagrama de flujo ASCONSULT
-- =====================================================

-- 1. Actualizar ENUM de estados en oportunidades y agregar campos de flujo
ALTER TABLE oportunidades
    MODIFY COLUMN estado ENUM(
        'identificada',
        'evaluacion_requisitos',
        'viabilidad_preliminar',
        'evaluacion_especializada',
        'aprobacion_gerencia',
        'preparacion_propuesta',
        'presentada',
        'adjudicada',
        'no_adjudicada',
        'descartada'
    ) DEFAULT 'identificada',
    ADD COLUMN origen ENUM('licitacion_publica', 'invitacion_privada', 'cliente_directo', 'empresas_propias', 'otras') DEFAULT 'licitacion_publica' AFTER linea_negocio_id,
    ADD COLUMN requiere_evaluacion_especializada TINYINT(1) DEFAULT 0 AFTER origen,
    ADD COLUMN motivo_descarte TEXT AFTER estado;

-- 2. Crear tabla para las evaluaciones de la oportunidad (3FN)
CREATE TABLE IF NOT EXISTS oportunidad_evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oportunidad_id INT NOT NULL,
    -- Evaluación Licitaciones (Requisitos)
    req_bases_tdr TINYINT(1) DEFAULT 0,
    req_legales TINYINT(1) DEFAULT 0,
    req_cronograma TINYINT(1) DEFAULT 0,
    -- Evaluación Proyectos (Viabilidad)
    viabilidad_alcance TEXT,
    viabilidad_plazo TEXT,
    viabilidad_riesgos TEXT,
    -- Evaluación Especializada (Consultoría / Finanzas)
    informe_tecnico TEXT,
    informe_economico TEXT,
    -- Aprobación Gerencia
    aprobado_gerencia TINYINT(1) DEFAULT 0,
    comentarios_gerencia TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (oportunidad_id) REFERENCES oportunidades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Crear tabla de seguimiento de propuestas (Fase 4 BPMN)
CREATE TABLE IF NOT EXISTS oportunidad_seguimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    oportunidad_id INT NOT NULL,
    fecha_presentacion DATETIME,
    constancia_presentacion VARCHAR(255),
    existen_observaciones TINYINT(1) DEFAULT 0,
    detalle_observaciones TEXT,
    subsanaciones_presentadas TINYINT(1) DEFAULT 0,
    acta_adjudicacion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (oportunidad_id) REFERENCES oportunidades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Modificar Licitaciones para reflejar la relación
-- En el BPMN, la Preparación de Propuesta es parte de la Oportunidad (antes de adjudicar).
-- La tabla 'licitaciones' actual parece redundante si todo es oportunidad.
-- Pero para mantener la estructura, aseguramos que licitaciones se use en la fase 'preparacion_propuesta'.
