-- ==============================================================
-- MIGRACIÓN: Fase 3 (Almacenes y Subcontratos), Fase 4 (Tesorería),
-- Catálogos Maestros (Materiales y Equipos) y Ampliación del Organigrama
-- ERP Gestión de Proyectos
-- ==============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. ACTUALIZACIÓN DEL ORGANIGRAMA (Áreas y Roles faltantes según ASCONSULT)
INSERT INTO `org_areas` (`nombre`, `codigo`, `descripcion`, `parent_id`, `nivel`, `icono`, `color`, `orden`) 
VALUES 
('TI', 'TI', 'Infraestructura, soporte e integraciones', 1, 1, 'fas fa-desktop', '#475569', 4),
('Empresas Propias', 'EMP-PROP', 'Desarrollo e inversión en proyectos propios', 1, 1, 'fas fa-city', '#ca8a04', 5)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `descripcion` = VALUES(`descripcion`);

-- Alinear jerarquía de Consultoría (Expedientes, Supervisiones, Edificaciones, Viales cuelgan de Consultoría)
UPDATE `org_areas` SET `parent_id` = (SELECT id FROM (SELECT id FROM `org_areas` WHERE `codigo` = 'CST') as t) WHERE `codigo` IN ('EXPT', 'SUPV', 'EDIF', 'VIAL');

-- Insertar Área Especialidad: Estudios (bajo Consultoría)
INSERT INTO `org_areas` (`nombre`, `codigo`, `descripcion`, `parent_id`, `nivel`, `icono`, `color`, `orden`)
VALUES ('Estudios', 'EST', 'Estudios de preinversión, perfiles y diseño técnico', (SELECT id FROM (SELECT id FROM `org_areas` WHERE `codigo` = 'CST') as t), 3, 'fas fa-chart-pie', '#0284c7', 3)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Cargos operativos para Ejecuciones, TI y Empresas Propias
INSERT INTO `org_cargos` (`nombre`, `codigo`, `descripcion`, `area_id`, `nivel_jerarquico`, `puede_aprobar`) 
VALUES 
('Residente de Obra', 'RES-OBRA', 'Dirección en campo del proyecto', (SELECT id FROM (SELECT id FROM `org_areas` WHERE `codigo` = 'EJEC') as t), 4, 0),
('Administrador de Obra', 'ADM-OBRA', 'Gestión operativa de obra', (SELECT id FROM (SELECT id FROM `org_areas` WHERE `codigo` = 'EJEC') as t), 5, 0),
('Jefe de TI', 'JEFE-TI', 'Infraestructura, redes, soporte y seguridad digital', (SELECT id FROM (SELECT id FROM `org_areas` WHERE `codigo` = 'TI') as t), 3, 1),
('Responsable de Negocios Propios', 'RESP-PROP', 'Desarrollo, inversión y seguimiento de proyectos propios', (SELECT id FROM (SELECT id FROM `org_areas` WHERE `codigo` = 'EMP-PROP') as t), 3, 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `descripcion` = VALUES(`descripcion`);

-- 2. DATOS MAESTROS FALTANTES
CREATE TABLE IF NOT EXISTS `cat_materiales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `unidad_medida` varchar(20) DEFAULT 'UND',
  `categoria` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_material_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cat_equipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL COMMENT 'Pesado, Liviano, Herramientas',
  `costo_hora` decimal(10,2) DEFAULT '0.00',
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_equipo_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. FASE 3: EJECUCIÓN Y OPERACIÓN (Almacén y Subcontratos)
CREATE TABLE IF NOT EXISTS `almacen_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyecto_id` int NOT NULL,
  `orden_compra_id` int DEFAULT NULL,
  `tipo_movimiento` enum('ingreso','salida') NOT NULL,
  `fecha` date NOT NULL,
  `guia_remision` varchar(100) DEFAULT NULL,
  `observaciones` text,
  `registrado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_almacen_proyecto` (`proyecto_id`),
  KEY `idx_almacen_oc` (`orden_compra_id`),
  CONSTRAINT `fk_almacen_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_almacen_oc` FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subcontratos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyecto_id` int NOT NULL,
  `proveedor_id` int NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `servicio` varchar(255) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activo','finalizado','suspendido') DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subcontrato_codigo` (`codigo`),
  KEY `idx_subcontrato_proyecto` (`proyecto_id`),
  KEY `idx_subcontrato_proveedor` (`proveedor_id`),
  CONSTRAINT `fk_subcontrato_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subcontrato_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. FASE 4: CONTROL ECONÓMICO Y FINANCIERO (Tesorería)
CREATE TABLE IF NOT EXISTS `tesoreria_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyecto_id` int DEFAULT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'Transferencia',
  `comprobante` varchar(100) DEFAULT NULL,
  `registrado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tesoreria_proyecto` (`proyecto_id`),
  CONSTRAINT `fk_tesoreria_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
