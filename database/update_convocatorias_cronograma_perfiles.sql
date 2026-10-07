-- ====================================================================
-- ACTUALIZACIÓN DE BD: CRONOGRAMA Y PERFILES DE CONVOCATORIAS
-- Cumplimiento de requerimientos Ing. Palomino y Modelo de Integración
-- ====================================================================

-- 1. Agregar campos de cronograma y duración a la tabla convocatorias si no existen
SET @dbname = DATABASE();

-- fecha_consultas
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'convocatorias' AND COLUMN_NAME = 'fecha_consultas') > 0,
  'SELECT 1',
  'ALTER TABLE convocatorias ADD COLUMN fecha_consultas DATE NULL AFTER fecha_publicacion'
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- fecha_buena_pro
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'convocatorias' AND COLUMN_NAME = 'fecha_buena_pro') > 0,
  'SELECT 1',
  'ALTER TABLE convocatorias ADD COLUMN fecha_buena_pro DATE NULL AFTER fecha_limite_oferta'
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- duracion_dias
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'convocatorias' AND COLUMN_NAME = 'duracion_dias') > 0,
  'SELECT 1',
  'ALTER TABLE convocatorias ADD COLUMN duracion_dias INT NULL AFTER monto_referencial'
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- cantidad_profesionales
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'convocatorias' AND COLUMN_NAME = 'cantidad_profesionales') > 0,
  'SELECT 1',
  'ALTER TABLE convocatorias ADD COLUMN cantidad_profesionales INT DEFAULT 0 AFTER requisitos_profesionales'
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 2. Tabla de catálogo de perfiles profesionales disponibles
CREATE TABLE IF NOT EXISTS `catalogo_perfiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL UNIQUE,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar perfiles por defecto si la tabla está vacía
INSERT IGNORE INTO `catalogo_perfiles` (`nombre`) VALUES
('Ingeniero Residente'),
('Especialista en Suelos y Pavimentos'),
('Especialista en Estructuras'),
('Especialista en Seguridad y Salud en el Trabajo'),
('Especialista Ambiental'),
('Especialista en Costos, Presupuestos y Valorizaciones'),
('Especialista en Instalaciones Sanitarias'),
('Especialista en Instalaciones Eléctricas y Electromecánicas'),
('Arquitecto'),
('Arqueólogo / Monitoreo Arqueológico'),
('Especialista en Geología y Geotecnia'),
('Especialista en Hidrología e Hidráulica'),
('Administrador de Contrato');


-- 3. Tabla de asignación de perfiles por convocatoria
CREATE TABLE IF NOT EXISTS `convocatoria_perfiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `convocatoria_id` INT NOT NULL,
  `perfil_nombre` VARCHAR(255) NOT NULL,
  `tipo_personal` VARCHAR(100) DEFAULT 'Profesional clave',
  `cantidad` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_convocatoria` (`convocatoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
