-- ==============================================================
-- MIGRACIÓN: Core de Seguridad y Estructura Organizacional
-- Sprint 1 del ERP - Gestión de Proyectos
-- Fecha: 14-10-2026
-- ==============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- =====================================================
-- 1. TABLA: org_areas (Áreas organizacionales jerárquicas)
-- =====================================================
CREATE TABLE IF NOT EXISTS `org_areas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(150) NOT NULL,
    `codigo` VARCHAR(30) NOT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `parent_id` INT DEFAULT NULL COMMENT 'Área padre para jerarquía',
    `nivel` TINYINT(2) DEFAULT 0 COMMENT '0=Gerencia General, 1=Gerencia, 2=Sub-área, 3=Unidad',
    `icono` VARCHAR(50) DEFAULT 'fas fa-sitemap',
    `color` VARCHAR(20) DEFAULT '#6366f1',
    `orden` INT DEFAULT 0 COMMENT 'Orden de visualización',
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_areas_codigo` (`codigo`),
    FOREIGN KEY (`parent_id`) REFERENCES `org_areas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. TABLA: org_cargos (Cargos/puestos dentro de cada área)
-- =====================================================
CREATE TABLE IF NOT EXISTS `org_cargos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(150) NOT NULL,
    `codigo` VARCHAR(30) NOT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `area_id` INT NOT NULL COMMENT 'Área a la que pertenece el cargo',
    `nivel_jerarquico` TINYINT(2) DEFAULT 3 COMMENT '1=Director/CEO, 2=Gerente, 3=Jefe, 4=Coordinador, 5=Analista/Asistente, 6=Operativo',
    `puede_aprobar` TINYINT(1) DEFAULT 0 COMMENT '¿Puede aprobar OC, planillas, etc.?',
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_cargos_codigo` (`codigo`),
    FOREIGN KEY (`area_id`) REFERENCES `org_areas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. TABLA: permisos (Catálogo de permisos del sistema)
-- =====================================================
CREATE TABLE IF NOT EXISTS `permisos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `modulo` VARCHAR(80) NOT NULL COMMENT 'Nombre del módulo: oportunidades, contratos, compras, etc.',
    `accion` VARCHAR(50) NOT NULL COMMENT 'ver, crear, editar, eliminar, aprobar, exportar',
    `descripcion` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_permiso` (`modulo`, `accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. TABLA: cargo_permisos (N:M entre cargos y permisos)
-- =====================================================
CREATE TABLE IF NOT EXISTS `cargo_permisos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cargo_id` INT NOT NULL,
    `permiso_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_cargo_permiso` (`cargo_id`, `permiso_id`),
    FOREIGN KEY (`cargo_id`) REFERENCES `org_cargos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permiso_id`) REFERENCES `permisos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. TABLA: usuario_permisos_temp (Permisos temporales delegados)
-- =====================================================
CREATE TABLE IF NOT EXISTS `usuario_permisos_temp` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `permiso_id` INT NOT NULL,
    `area_id` INT DEFAULT NULL COMMENT 'Área específica del permiso temporal (NULL = global)',
    `otorgado_por` INT NOT NULL COMMENT 'Superadmin o gerente que delegó',
    `motivo` VARCHAR(255) DEFAULT NULL,
    `fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_fin` DATETIME NOT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permiso_id`) REFERENCES `permisos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`area_id`) REFERENCES `org_areas`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`otorgado_por`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. Campos cargo_id y org_area_id en usuarios
-- =====================================================
-- Nota: En MySQL 8.0 se asegura la existencia de columnas vía upgrade.php


-- =====================================================
-- 7. DATOS SEMILLA: Áreas organizacionales
-- =====================================================

-- Nivel 0: Gerencia General
INSERT INTO `org_areas` (`nombre`, `codigo`, `descripcion`, `parent_id`, `nivel`, `icono`, `color`, `orden`) VALUES
('Gerencia General', 'GG', 'Dirección ejecutiva corporativa (CEO)', NULL, 0, 'fas fa-crown', '#7c3aed', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Nivel 1: Gerencias
INSERT INTO `org_areas` (`nombre`, `codigo`, `descripcion`, `parent_id`, `nivel`, `icono`, `color`, `orden`) VALUES
('Administración y Finanzas', 'GAF', 'Gerencia de Administración y Finanzas', 
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GG') t), 1, 'fas fa-coins', '#0891b2', 2),
('Gerencia de Proyectos', 'GP', 'Gerencia de Proyectos y Operaciones', 
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GG') t), 1, 'fas fa-hard-hat', '#ea580c', 3)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Nivel 2: Sub-áreas de Administración y Finanzas
INSERT INTO `org_areas` (`nombre`, `codigo`, `descripcion`, `parent_id`, `nivel`, `icono`, `color`, `orden`) VALUES
('Tesorería', 'TES', 'Control de flujos de caja, ingresos y egresos',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GAF') t), 2, 'fas fa-money-bill-wave', '#0d9488', 1),
('Recursos Humanos', 'RRHH', 'Gestión de personal, planillas y tareos',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GAF') t), 2, 'fas fa-user-friends', '#0d9488', 2),
('Logística', 'LOG', 'Compras, almacenes y gestión de proveedores',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GAF') t), 2, 'fas fa-truck', '#0d9488', 3),
('Contabilidad', 'CONT', 'Registros contables, facturación y tributación',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GAF') t), 2, 'fas fa-calculator', '#0d9488', 4)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Nivel 2: Sub-áreas de Gerencia de Proyectos
INSERT INTO `org_areas` (`nombre`, `codigo`, `descripcion`, `parent_id`, `nivel`, `icono`, `color`, `orden`) VALUES
('Licitaciones', 'LIC', 'Gestión del ciclo de licitaciones públicas y privadas',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GP') t), 2, 'fas fa-gavel', '#c2410c', 1),
('Consultoría', 'CST', 'Supervisiones, expedientes técnicos y estudios',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GP') t), 2, 'fas fa-clipboard-check', '#c2410c', 2),
('Ejecuciones', 'EJEC', 'Ejecución de obras civiles e infraestructura',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'GP') t), 2, 'fas fa-building', '#c2410c', 3)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Nivel 3: Unidades dentro de Ejecuciones
INSERT INTO `org_areas` (`nombre`, `codigo`, `descripcion`, `parent_id`, `nivel`, `icono`, `color`, `orden`) VALUES
('Expedientes Técnicos', 'EXPT', 'Elaboración de expedientes técnicos de proyectos',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'EJEC') t), 3, 'fas fa-file-alt', '#9a3412', 1),
('Supervisiones', 'SUPV', 'Supervisión y control de obras en ejecución',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'EJEC') t), 3, 'fas fa-binoculars', '#9a3412', 2),
('Edificaciones', 'EDIF', 'Construcción de edificaciones e infraestructura vertical',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'EJEC') t), 3, 'fas fa-city', '#9a3412', 3),
('Viales', 'VIAL', 'Obras viales, carreteras y pavimentación',
    (SELECT id FROM (SELECT id FROM org_areas WHERE codigo = 'EJEC') t), 3, 'fas fa-road', '#9a3412', 4)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- =====================================================
-- 8. DATOS SEMILLA: Cargos
-- =====================================================
INSERT INTO `org_cargos` (`nombre`, `codigo`, `descripcion`, `area_id`, `nivel_jerarquico`, `puede_aprobar`) VALUES
-- Gerencia General
('Director General / CEO', 'CEO', 'Máxima autoridad ejecutiva',
    (SELECT id FROM org_areas WHERE codigo = 'GG'), 1, 1),
('Asistente de Gerencia', 'ASIST-GG', 'Soporte directo a Gerencia General',
    (SELECT id FROM org_areas WHERE codigo = 'GG'), 5, 0),

-- Administración y Finanzas
('Gerente de Administración y Finanzas', 'GER-AF', 'Responsable del área administrativa y financiera',
    (SELECT id FROM org_areas WHERE codigo = 'GAF'), 2, 1),

-- Gerencia de Proyectos
('Gerente de Proyectos', 'GER-PROY', 'Responsable de todas las operaciones de proyectos',
    (SELECT id FROM org_areas WHERE codigo = 'GP'), 2, 1),

-- Sub-áreas Administración
('Jefe de Tesorería', 'JEFE-TES', 'Responsable de tesorería y flujos de caja',
    (SELECT id FROM org_areas WHERE codigo = 'TES'), 3, 1),
('Jefe de RRHH', 'JEFE-RRHH', 'Responsable de recursos humanos y planillas',
    (SELECT id FROM org_areas WHERE codigo = 'RRHH'), 3, 1),
('Jefe de Logística', 'JEFE-LOG', 'Responsable de compras, almacenes y proveedores',
    (SELECT id FROM org_areas WHERE codigo = 'LOG'), 3, 1),
('Jefe de Contabilidad', 'JEFE-CONT', 'Responsable de registros contables',
    (SELECT id FROM org_areas WHERE codigo = 'CONT'), 3, 1),
('Asistente de Logística', 'ASIST-LOG', 'Soporte operativo en compras y almacenes',
    (SELECT id FROM org_areas WHERE codigo = 'LOG'), 5, 0),
('Asistente Contable', 'ASIST-CONT', 'Soporte en registros contables',
    (SELECT id FROM org_areas WHERE codigo = 'CONT'), 5, 0),

-- Sub-áreas Proyectos
('Jefe de Licitaciones', 'JEFE-LIC', 'Responsable del área de licitaciones',
    (SELECT id FROM org_areas WHERE codigo = 'LIC'), 3, 1),
('Jefe de Consultoría', 'JEFE-CST', 'Responsable del área de consultoría',
    (SELECT id FROM org_areas WHERE codigo = 'CST'), 3, 1),
('Jefe de Ejecuciones', 'JEFE-EJEC', 'Responsable del área de ejecución de obras',
    (SELECT id FROM org_areas WHERE codigo = 'EJEC'), 3, 1),
('Ingeniero de Proyectos', 'ING-PROY', 'Ingeniero asignado a proyectos de ejecución',
    (SELECT id FROM org_areas WHERE codigo = 'EJEC'), 5, 0),
('Coordinador de Expedientes', 'COORD-EXPT', 'Coordinador de elaboración de expedientes',
    (SELECT id FROM org_areas WHERE codigo = 'EXPT'), 4, 0),
('Supervisor de Obras', 'SUPV-OBRAS', 'Supervisor de campo para obras en ejecución',
    (SELECT id FROM org_areas WHERE codigo = 'SUPV'), 4, 0)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- =====================================================
-- 9. DATOS SEMILLA: Permisos del sistema
-- =====================================================
INSERT INTO `permisos` (`modulo`, `accion`, `descripcion`) VALUES
-- Módulos Fase 1
('convocatorias', 'ver', 'Ver listado y detalle de convocatorias'),
('convocatorias', 'crear', 'Registrar nuevas convocatorias'),
('convocatorias', 'editar', 'Modificar convocatorias existentes'),
('convocatorias', 'eliminar', 'Eliminar convocatorias'),
('oportunidades', 'ver', 'Ver listado y detalle de oportunidades'),
('oportunidades', 'crear', 'Registrar nuevas oportunidades'),
('oportunidades', 'editar', 'Modificar oportunidades existentes'),
('oportunidades', 'eliminar', 'Eliminar oportunidades'),
('oportunidades', 'aprobar', 'Aprobar o cambiar estado de oportunidades'),
('licitaciones', 'ver', 'Ver licitaciones'),
('licitaciones', 'crear', 'Crear licitaciones'),
('licitaciones', 'editar', 'Editar licitaciones'),
('licitaciones', 'eliminar', 'Eliminar licitaciones'),
('propuestas', 'ver', 'Ver propuestas'),
('propuestas', 'crear', 'Crear propuestas'),
('propuestas', 'editar', 'Editar propuestas'),
('propuestas', 'eliminar', 'Eliminar propuestas'),
('contratos', 'ver', 'Ver contratos'),
('contratos', 'crear', 'Crear contratos'),
('contratos', 'editar', 'Editar contratos'),
('contratos', 'eliminar', 'Eliminar contratos'),
('clientes', 'ver', 'Ver clientes'),
('clientes', 'crear', 'Crear clientes'),
('clientes', 'editar', 'Editar clientes'),
('clientes', 'eliminar', 'Eliminar clientes'),
('empresas', 'ver', 'Ver empresas propias'),
('empresas', 'crear', 'Crear empresas'),
('empresas', 'editar', 'Editar empresas'),
('empresas', 'eliminar', 'Eliminar empresas'),
('profesionales', 'ver', 'Ver catálogo de profesionales'),
('profesionales', 'crear', 'Registrar profesionales'),
('profesionales', 'editar', 'Editar profesionales'),
('profesionales', 'eliminar', 'Eliminar profesionales'),
('usuarios', 'ver', 'Ver usuarios del sistema'),
('usuarios', 'crear', 'Crear usuarios'),
('usuarios', 'editar', 'Editar usuarios'),
('usuarios', 'eliminar', 'Eliminar usuarios'),
('configuracion', 'ver', 'Ver configuración del sistema'),
('configuracion', 'editar', 'Modificar configuración del sistema'),
('organizacion', 'ver', 'Ver estructura organizacional'),
('organizacion', 'crear', 'Crear áreas y cargos'),
('organizacion', 'editar', 'Editar áreas y cargos'),
('organizacion', 'eliminar', 'Eliminar áreas y cargos'),

-- Módulos Fase 2
('proyectos', 'ver', 'Ver proyectos'),
('proyectos', 'crear', 'Crear proyectos'),
('proyectos', 'editar', 'Editar proyectos'),
('proyectos', 'eliminar', 'Eliminar proyectos'),
('edt', 'ver', 'Ver estructura de desglose de trabajo'),
('edt', 'crear', 'Crear tareas en el EDT'),
('edt', 'editar', 'Editar tareas del EDT'),
('edt', 'eliminar', 'Eliminar tareas del EDT'),

-- Módulos Fase 3
('compras', 'ver', 'Ver órdenes de compra'),
('compras', 'crear', 'Crear órdenes de compra'),
('compras', 'editar', 'Editar órdenes de compra'),
('compras', 'aprobar', 'Aprobar órdenes de compra'),
('compras', 'eliminar', 'Eliminar órdenes de compra'),
('almacenes', 'ver', 'Ver inventario y movimientos'),
('almacenes', 'crear', 'Registrar movimientos de almacén'),
('almacenes', 'editar', 'Editar movimientos'),
('personal', 'ver', 'Ver tareos y planillas'),
('personal', 'crear', 'Registrar asistencias y partes'),
('personal', 'editar', 'Editar registros de personal'),
('personal', 'aprobar', 'Aprobar planillas'),
('subcontratos', 'ver', 'Ver subcontratos'),
('subcontratos', 'crear', 'Crear subcontratos'),
('subcontratos', 'editar', 'Editar subcontratos'),

-- Módulos Fase 4
('valorizaciones', 'ver', 'Ver valorizaciones'),
('valorizaciones', 'crear', 'Crear valorizaciones'),
('valorizaciones', 'editar', 'Editar valorizaciones'),
('valorizaciones', 'aprobar', 'Aprobar valorizaciones'),
('facturacion', 'ver', 'Ver facturas'),
('facturacion', 'crear', 'Emitir facturas'),
('facturacion', 'editar', 'Editar facturas'),
('tesoreria', 'ver', 'Ver movimientos de tesorería'),
('tesoreria', 'crear', 'Registrar ingresos/egresos'),
('tesoreria', 'aprobar', 'Aprobar pagos'),
('cuentas', 'ver', 'Ver cuentas por cobrar/pagar'),

-- Módulo Fase 5
('analisis', 'ver', 'Ver dashboards de análisis y rentabilidad'),
('analisis', 'exportar', 'Exportar reportes de análisis'),

-- Datos Maestros
('proveedores', 'ver', 'Ver proveedores'),
('proveedores', 'crear', 'Crear proveedores'),
('proveedores', 'editar', 'Editar proveedores'),
('proveedores', 'eliminar', 'Eliminar proveedores'),
('materiales', 'ver', 'Ver catálogo de materiales'),
('materiales', 'crear', 'Crear materiales'),
('materiales', 'editar', 'Editar materiales'),
('equipos', 'ver', 'Ver catálogo de equipos'),
('equipos', 'crear', 'Crear equipos'),
('equipos', 'editar', 'Editar equipos'),
('centros_costo', 'ver', 'Ver centros de costo'),
('centros_costo', 'crear', 'Crear centros de costo'),
('centros_costo', 'editar', 'Editar centros de costo')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- =====================================================
-- 10. Asignar TODOS los permisos al cargo CEO
-- =====================================================
INSERT IGNORE INTO `cargo_permisos` (`cargo_id`, `permiso_id`)
SELECT 
    (SELECT id FROM org_cargos WHERE codigo = 'CEO') as cargo_id,
    p.id as permiso_id
FROM permisos p;

-- Asignar permisos de Gerente de Proyectos (sus módulos)
INSERT IGNORE INTO `cargo_permisos` (`cargo_id`, `permiso_id`)
SELECT 
    (SELECT id FROM org_cargos WHERE codigo = 'GER-PROY') as cargo_id,
    p.id as permiso_id
FROM permisos p
WHERE p.modulo IN ('convocatorias','oportunidades','licitaciones','propuestas','contratos','proyectos',
                   'clientes','profesionales','edt','compras','almacenes','subcontratos',
                   'valorizaciones','personal','analisis');

-- Asignar permisos de Gerente de Administración (sus módulos)
INSERT IGNORE INTO `cargo_permisos` (`cargo_id`, `permiso_id`)
SELECT 
    (SELECT id FROM org_cargos WHERE codigo = 'GER-AF') as cargo_id,
    p.id as permiso_id
FROM permisos p
WHERE p.modulo IN ('usuarios','configuracion','empresas','tesoreria','facturacion','cuentas',
                   'personal','compras','almacenes','proveedores','materiales','equipos',
                   'centros_costo','analisis','valorizaciones');

-- Asignar permisos de Jefe de Licitaciones
INSERT IGNORE INTO `cargo_permisos` (`cargo_id`, `permiso_id`)
SELECT 
    (SELECT id FROM org_cargos WHERE codigo = 'JEFE-LIC') as cargo_id,
    p.id as permiso_id
FROM permisos p
WHERE p.modulo IN ('convocatorias','oportunidades','licitaciones','propuestas','clientes','profesionales')
  AND p.accion IN ('ver','crear','editar');

COMMIT;
