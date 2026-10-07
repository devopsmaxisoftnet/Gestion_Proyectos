-- =====================================================
-- MIGRACIÓN: Sistema de Gestión de Usuarios
-- Áreas, Tipos de usuario y Solicitudes de acceso
-- Ejecutar en phpMyAdmin sobre la BD gestion_comercial
-- =====================================================

-- =====================================================
-- TABLA: areas (áreas de la empresa)
-- =====================================================
CREATE TABLE IF NOT EXISTS areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    icono VARCHAR(50) DEFAULT 'folder',
    color VARCHAR(20) DEFAULT '#6C5CE7',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: tipos_usuario (tipos: jefatura aerea, asistente)
-- =====================================================
CREATE TABLE IF NOT EXISTS tipos_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    nivel_acceso INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: solicitudes_acceso
-- Registra las peticiones de los colaboradores
-- =====================================================
CREATE TABLE IF NOT EXISTS solicitudes_acceso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    mensaje TEXT,
    estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
    tipo_usuario_id INT DEFAULT NULL,
    area_id INT DEFAULT NULL,
    revisado_por INT DEFAULT NULL,
    fecha_revision DATETIME DEFAULT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_usuario_id) REFERENCES tipos_usuario(id) ON DELETE SET NULL,
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
    FOREIGN KEY (revisado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Modificar tabla usuarios: agregar campos nuevos
-- =====================================================
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS tipo_usuario_id INT DEFAULT NULL AFTER rol_id,
    ADD COLUMN IF NOT EXISTS area_id INT DEFAULT NULL AFTER tipo_usuario_id,
    ADD COLUMN IF NOT EXISTS acceso_aprobado TINYINT(1) DEFAULT 0 AFTER area_id;

-- Agregar llaves foráneas (ignorar error si ya existen)
ALTER TABLE usuarios
    ADD CONSTRAINT fk_usuario_tipo FOREIGN KEY (tipo_usuario_id) REFERENCES tipos_usuario(id) ON DELETE SET NULL;

ALTER TABLE usuarios
    ADD CONSTRAINT fk_usuario_area FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Áreas de la empresa
INSERT INTO areas (nombre, descripcion, icono, color) VALUES
('Contabilidad y Finanzas', 'Gestión contable, presupuestos y estados financieros', 'calculator', '#6C5CE7'),
('Logística y Compras', 'Adquisiciones, inventarios y cadena de suministro', 'truck', '#00B894'),
('Proyectos', 'Ejecución y supervisión de proyectos', 'project-diagram', '#E17055'),
('Administración', 'Gestión administrativa general', 'building', '#0984E3');

-- Tipos de usuario
INSERT INTO tipos_usuario (nombre, descripcion, nivel_acceso) VALUES
('Jefatura de Área', 'Jefe o director de un área específica con permisos elevados', 3),
('Asistente', 'Personal de apoyo con permisos básicos dentro de su área', 1);

-- Marcar usuarios existentes como aprobados (superadmin siempre tiene acceso)
UPDATE usuarios SET acceso_aprobado = 1 WHERE rol = 'superadmin';
UPDATE usuarios SET acceso_aprobado = 1 WHERE rol = 'admin';

-- =====================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================
