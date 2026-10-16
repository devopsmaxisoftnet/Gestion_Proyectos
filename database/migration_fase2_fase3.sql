-- ==============================================================
-- MIGRACIÓN: Módulos de Planificación y Ejecución (Fase 2 y 3)
-- ERP Gestión de Proyectos
-- Tablas: proveedores, tareas (WBS), asistencias (RRHH), ordenes_compra (Logística)
-- ==============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 0. TABLA: proveedores (Catálogo de Proveedores de Bienes y Servicios)
CREATE TABLE IF NOT EXISTS `proveedores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `razon_social` VARCHAR(255) NOT NULL,
    `ruc` VARCHAR(20) DEFAULT NULL,
    `direccion` TEXT DEFAULT NULL,
    `telefono` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `contacto_nombre` VARCHAR(150) DEFAULT NULL,
    `especialidad` VARCHAR(255) DEFAULT NULL,
    `calificacion` DECIMAL(3,2) DEFAULT 5.00,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. TABLA: tareas (WBS / EDT por Proyecto - Fase 2)
CREATE TABLE IF NOT EXISTS `tareas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proyecto_id` INT NOT NULL,
    `codigo` VARCHAR(50) NOT NULL COMMENT 'Código EDT/WBS ej: EDT-1.01',
    `nombre` VARCHAR(255) NOT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `fecha_inicio` DATE NOT NULL,
    `fecha_fin` DATE NOT NULL,
    `progreso` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Porcentaje de avance 0.00 a 100.00',
    `estado` ENUM('pendiente', 'en_proceso', 'completada', 'bloqueada') DEFAULT 'pendiente',
    `prioridad` ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    `responsable_id` INT DEFAULT NULL COMMENT 'Usuario asignado',
    `costo_estimado` DECIMAL(14,2) DEFAULT 0.00,
    `costo_real` DECIMAL(14,2) DEFAULT 0.00,
    `orden` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tareas_proyecto` (`proyecto_id`),
    INDEX `idx_tareas_estado` (`estado`),
    FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`responsable_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA: asistencias (Tareo de Personal por Centro de Costo / Proyecto - RRHH)
CREATE TABLE IF NOT EXISTS `asistencias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proyecto_id` INT NOT NULL COMMENT 'Centro de costo del tareo',
    `profesional_id` INT NOT NULL COMMENT 'Personal de la empresa',
    `fecha` DATE NOT NULL,
    `horas_normales` DECIMAL(4,2) NOT NULL DEFAULT 8.00,
    `horas_extras` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    `tipo_labor` VARCHAR(100) DEFAULT 'Operativo',
    `observaciones` TEXT DEFAULT NULL,
    `registrado_por` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_asistencias_proyecto` (`proyecto_id`),
    INDEX `idx_asistencias_profesional` (`profesional_id`),
    INDEX `idx_asistencias_fecha` (`fecha`),
    UNIQUE KEY `uk_asistencia_diaria` (`proyecto_id`, `profesional_id`, `fecha`),
    FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`profesional_id`) REFERENCES `profesionales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`registrado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA: ordenes_compra (Logística y Compras por Proyecto - Fase 3)
CREATE TABLE IF NOT EXISTS `ordenes_compra` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) NOT NULL UNIQUE,
    `proyecto_id` INT NOT NULL,
    `proveedor_id` INT DEFAULT NULL,
    `proveedor_nombre` VARCHAR(255) DEFAULT NULL,
    `fecha_emision` DATE NOT NULL,
    `fecha_entrega` DATE DEFAULT NULL,
    `subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `igv` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `moneda` VARCHAR(10) DEFAULT 'PEN',
    `condicion_pago` VARCHAR(100) DEFAULT 'Crédito 30 días',
    `lugar_entrega` VARCHAR(255) DEFAULT NULL,
    `estado` ENUM('borrador', 'pendiente_aprobacion', 'aprobada', 'rechazada', 'recibida', 'anulada') DEFAULT 'borrador',
    `aprobado_por` INT DEFAULT NULL,
    `fecha_aprobacion` DATETIME DEFAULT NULL,
    `motivo_rechazo` TEXT DEFAULT NULL,
    `observaciones` TEXT DEFAULT NULL,
    `solicitado_por` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_oc_proyecto` (`proyecto_id`),
    INDEX `idx_oc_proveedor` (`proveedor_id`),
    INDEX `idx_oc_estado` (`estado`),
    FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`solicitado_por`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA: orden_compra_detalles (Ítems y Materiales)
CREATE TABLE IF NOT EXISTS `orden_compra_detalles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `orden_compra_id` INT NOT NULL,
    `descripcion` VARCHAR(255) NOT NULL,
    `unidad_medida` VARCHAR(20) DEFAULT 'UND',
    `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `precio_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (`orden_compra_id`) REFERENCES `ordenes_compra`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PERMISOS DEL SISTEMA: Registrar módulos en catálogo de permisos
INSERT IGNORE INTO `permisos` (`modulo`, `accion`, `descripcion`) VALUES
('tareas', 'ver', 'Ver WBS y lista de tareas de proyectos'),
('tareas', 'crear', 'Crear tareas y entregables WBS'),
('tareas', 'editar', 'Actualizar avance y datos de tareas'),
('tareas', 'eliminar', 'Eliminar tareas WBS'),
('asistencias', 'ver', 'Ver registros de tareo y horas de personal'),
('asistencias', 'crear', 'Registrar tareos diarios por proyecto'),
('asistencias', 'editar', 'Modificar horas y asistencias de personal'),
('asistencias', 'eliminar', 'Eliminar registros de asistencia'),
('compras', 'ver', 'Ver órdenes de compra y solicitudes'),
('compras', 'crear', 'Generar nuevas órdenes de compra'),
('compras', 'editar', 'Modificar órdenes de compra en borrador'),
('compras', 'eliminar', 'Anular o eliminar órdenes de compra'),
('compras', 'aprobar', 'Aprobar órdenes de compra (Logística / Gerencia)');

-- 6. ASIGNAR PERMISO DE APROBAR COMPRAS AL ROL / CARGO DE LOGÍSTICA Y GERENCIA
-- Gerente General (cargo 1), Gerente Adm y Finanzas (cargo 3), Gerente de Proyectos (cargo 4), Jefe de Logística (cargo 7)
INSERT IGNORE INTO `cargo_permisos` (`cargo_id`, `permiso_id`)
SELECT c.id, p.id 
FROM `org_cargos` c
CROSS JOIN `permisos` p
WHERE p.modulo IN ('tareas', 'asistencias', 'compras')
  AND (
      (p.modulo = 'compras' AND p.accion = 'aprobar' AND c.id IN (1, 3, 4, 7)) OR
      (p.modulo = 'compras' AND c.id IN (1, 3, 4, 7, 9)) OR
      (p.modulo = 'tareas' AND c.id IN (1, 3, 4, 13, 14, 15, 16)) OR
      (p.modulo = 'asistencias' AND c.id IN (1, 3, 4, 6, 13, 14, 16))
  );

-- 7. Datos de prueba iniciales para proveedores si está vacía
INSERT IGNORE INTO `proveedores` (`id`, `razon_social`, `ruc`, `direccion`, `telefono`, `email`, `contacto_nombre`, `especialidad`, `calificacion`, `activo`)
VALUES 
(1, 'ACEROS Y MATERIALES DEL SUR S.A.C.', '20556789123', 'Av. Industrial 450, Lima', '01-4455667', 'ventas@acerosdelsur.com.pe', 'Carlos Mendoza', 'Acero corrugado, perfiles y estructuras metálicas', 4.80, 1),
(2, 'CONCRETOS PREMEZCLADOS DEL PERU S.A.', '20448912345', 'Carretera Central Km 18, Ate', '01-3344556', 'pedidos@concretospremezclados.pe', 'Diana Vargas', 'Concreto premezclado y agregados', 4.90, 1),
(3, 'FERRETERIA INDUSTRIAL Y CONSTRUCCION E.I.R.L.', '20601234567', 'Av. Argentina 1220, Callao', '01-5566778', 'contacto@ferreteriaic.com', 'Jorge Salazar', 'Ferretería pesada, tuberías y accesorios PVC', 4.50, 1);

COMMIT;
