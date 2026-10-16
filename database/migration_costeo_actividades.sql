-- ==============================================================
-- MIGRACIÓN FASE 0: Costeo Basado en Actividades (ABC) - WBS / Compras / Almacén / Tareo
-- ERP Gestión de Proyectos
--
-- IDEMPOTENTE: cada cambio verifica information_schema antes de ejecutarse, por lo que
-- puede correrse varias veces o sobre bases con migraciones parciales (MySQL 8 no soporta
-- "ADD COLUMN IF NOT EXISTS").
--
-- Reglas del archivo (para que database/upgrade.php y el runner lo procesen bien):
--   * una sentencia por bloque, terminada en ';' al final de línea
--   * sin ';' dentro de literales ni comentarios
-- ==============================================================

SET @db = DATABASE();

-- --------------------------------------------------------------
-- 1. PRESUPUESTOS (WBS): sub-actividades jerárquicas en tareas
-- --------------------------------------------------------------
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tareas` ADD COLUMN `parent_id` int DEFAULT NULL COMMENT ''Tarea padre para sub-actividades WBS'' AFTER `proyecto_id`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tareas' AND COLUMN_NAME = 'parent_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tareas` ADD CONSTRAINT `fk_tareas_parent` FOREIGN KEY (`parent_id`) REFERENCES `tareas` (`id`) ON DELETE CASCADE',
    'SELECT 1') FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tareas' AND CONSTRAINT_NAME = 'fk_tareas_parent');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- --------------------------------------------------------------
-- 2. COMPRAS: imputar cada línea de OC a una actividad y a un ítem de catálogo
-- --------------------------------------------------------------
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `orden_compra_detalles` ADD COLUMN `tarea_id` int DEFAULT NULL COMMENT ''Actividad WBS a la que se carga la compra'' AFTER `orden_compra_id`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'orden_compra_detalles' AND COLUMN_NAME = 'tarea_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `orden_compra_detalles` ADD CONSTRAINT `fk_ocdet_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE SET NULL',
    'SELECT 1') FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'orden_compra_detalles' AND CONSTRAINT_NAME = 'fk_ocdet_tarea');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `orden_compra_detalles` ADD COLUMN `material_id` int DEFAULT NULL',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'orden_compra_detalles' AND COLUMN_NAME = 'material_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `orden_compra_detalles` ADD COLUMN `equipo_id` int DEFAULT NULL',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'orden_compra_detalles' AND COLUMN_NAME = 'equipo_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Subcontratos
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `subcontratos` ADD COLUMN `tarea_id` int DEFAULT NULL COMMENT ''Actividad WBS a la que pertenece el subcontrato'' AFTER `proyecto_id`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'subcontratos' AND COLUMN_NAME = 'tarea_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `subcontratos` ADD CONSTRAINT `fk_subcontrato_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE SET NULL',
    'SELECT 1') FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'subcontratos' AND CONSTRAINT_NAME = 'fk_subcontrato_tarea');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- --------------------------------------------------------------
-- 3. LOGÍSTICA Y CATÁLOGOS: consumo de almacén por actividad y control de stock
-- --------------------------------------------------------------
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos_detalles` ADD COLUMN `tarea_id` int DEFAULT NULL COMMENT ''Actividad WBS que consume el material/equipo'' AFTER `movimiento_id`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos_detalles' AND COLUMN_NAME = 'tarea_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos_detalles` ADD CONSTRAINT `fk_almdet_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE SET NULL',
    'SELECT 1') FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos_detalles' AND CONSTRAINT_NAME = 'fk_almdet_tarea');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Columnas que el costeo exige en los detalles de almacén (ya existen en la BD actual; se verifican)
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos_detalles` ADD COLUMN `material_id` int DEFAULT NULL',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos_detalles' AND COLUMN_NAME = 'material_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos_detalles` ADD COLUMN `equipo_id` int DEFAULT NULL',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos_detalles' AND COLUMN_NAME = 'equipo_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos_detalles` ADD COLUMN `cantidad` decimal(10,2) NOT NULL DEFAULT 0.00',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos_detalles' AND COLUMN_NAME = 'cantidad');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos_detalles` ADD COLUMN `precio_unitario` decimal(12,2) NOT NULL DEFAULT 0.00',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos_detalles' AND COLUMN_NAME = 'precio_unitario');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Catálogo de materiales: precio referencial y stock
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `cat_materiales` ADD COLUMN `precio_referencial` decimal(12,2) DEFAULT 0.00 AFTER `categoria`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cat_materiales' AND COLUMN_NAME = 'precio_referencial');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `cat_materiales` ADD COLUMN `stock_actual` decimal(10,2) DEFAULT 0.00 AFTER `precio_referencial`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cat_materiales' AND COLUMN_NAME = 'stock_actual');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- --------------------------------------------------------------
-- 4. RECURSOS HUMANOS (TAREO): tarifas por hora e imputación de horas por actividad
-- --------------------------------------------------------------
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `profesionales` ADD COLUMN `costo_hora` decimal(10,2) DEFAULT 25.00 COMMENT ''Costo hora-hombre normal'' AFTER `email`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'profesionales' AND COLUMN_NAME = 'costo_hora');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `profesionales` ADD COLUMN `costo_hora_extra` decimal(10,2) DEFAULT 35.00 COMMENT ''Costo hora-hombre extra'' AFTER `costo_hora`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'profesionales' AND COLUMN_NAME = 'costo_hora_extra');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `asistencias` ADD COLUMN `tarea_id` int DEFAULT NULL COMMENT ''Actividad WBS donde trabajó el personal'' AFTER `proyecto_id`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'asistencias' AND COLUMN_NAME = 'tarea_id');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `asistencias` ADD COLUMN `costo_calculado` decimal(12,2) DEFAULT 0.00 COMMENT ''(horas_normales * costo_hora) + (horas_extras * costo_hora_extra)'' AFTER `horas_extras`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'asistencias' AND COLUMN_NAME = 'costo_calculado');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Nueva clave única (se crea ANTES de eliminar la anterior para no dejar la tabla sin índice)
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `asistencias` ADD UNIQUE KEY `uk_asistencia_diaria_tarea` (`proyecto_id`, `profesional_id`, `fecha`, `tarea_id`)',
    'SELECT 1') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'asistencias' AND INDEX_NAME = 'uk_asistencia_diaria_tarea');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) > 0,
    'ALTER TABLE `asistencias` DROP INDEX `uk_asistencia_diaria`',
    'SELECT 1') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'asistencias' AND INDEX_NAME = 'uk_asistencia_diaria');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `asistencias` ADD CONSTRAINT `fk_asistencia_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE SET NULL',
    'SELECT 1') FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'asistencias' AND CONSTRAINT_NAME = 'fk_asistencia_tarea');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- --------------------------------------------------------------
-- 5. ALMACÉN (Paso 3): valorización, alertas de stock y anulación de movimientos
-- --------------------------------------------------------------
-- Costo promedio ponderado móvil: las salidas se valorizan con él
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `cat_materiales` ADD COLUMN `costo_promedio` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT ''Costo promedio ponderado movil'' AFTER `stock_actual`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cat_materiales' AND COLUMN_NAME = 'costo_promedio');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Materiales existentes: el costo inicial es el precio referencial
UPDATE `cat_materiales` SET `costo_promedio` = `precio_referencial` WHERE `costo_promedio` = 0 AND `precio_referencial` > 0;

-- Umbral para la alerta de stock crítico (0 = sin alerta)
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `cat_materiales` ADD COLUMN `stock_minimo` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `costo_promedio`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cat_materiales' AND COLUMN_NAME = 'stock_minimo');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Los movimientos no se borran: se anulan (revierte stock y costo)
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos` ADD COLUMN `estado` enum(''registrado'',''anulado'') NOT NULL DEFAULT ''registrado'' AFTER `observaciones`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos' AND COLUMN_NAME = 'estado');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos` ADD COLUMN `motivo_anulacion` varchar(255) DEFAULT NULL AFTER `estado`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos' AND COLUMN_NAME = 'motivo_anulacion');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `almacen_movimientos` ADD INDEX `idx_almacen_tipo_fecha` (`tipo_movimiento`, `fecha`)',
    'SELECT 1') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'almacen_movimientos' AND INDEX_NAME = 'idx_almacen_tipo_fecha');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- --------------------------------------------------------------
-- 6. PRESUPUESTO POR INSUMOS (APU): detalle del presupuesto de cada actividad hoja
--    La suma de los parciales pasa a ser tareas.costo_estimado
--    material -> material_id | equipo -> equipo_id | mano_obra -> tipo_labor (igual que el tareo)
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tarea_insumos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tarea_id` int NOT NULL,
  `tipo` enum('material','mano_obra','equipo','subcontrato') COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_id` int DEFAULT NULL,
  `equipo_id` int DEFAULT NULL,
  `tipo_labor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'und',
  `cantidad` decimal(14,3) NOT NULL DEFAULT 0.000,
  `precio_unitario` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `parcial` decimal(14,2) NOT NULL DEFAULT 0.00,
  `orden` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tarea_insumos_tarea` (`tarea_id`, `tipo`),
  KEY `idx_tarea_insumos_material` (`material_id`),
  KEY `idx_tarea_insumos_equipo` (`equipo_id`),
  CONSTRAINT `fk_tarea_insumos_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tarea_insumos_material` FOREIGN KEY (`material_id`) REFERENCES `cat_materiales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tarea_insumos_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `cat_equipos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 7. CONTROL PRESUPUESTAL: configuración por proyecto y bitácora de excesos autorizados
--    modo: informar (solo tableros) | advertir (avisa y registra) | bloquear (exige autorización con motivo)
-- --------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `presupuesto_control` (
  `proyecto_id` int NOT NULL,
  `modo` enum('informar','advertir','bloquear') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'advertir',
  `tolerancia_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `umbral_alerta_pct` decimal(5,2) NOT NULL DEFAULT 80.00,
  `actualizado_por` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`proyecto_id`),
  CONSTRAINT `fk_presupuesto_control_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `presupuesto_excesos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyecto_id` int NOT NULL,
  `tarea_id` int DEFAULT NULL,
  `tarea_codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarea_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origen` enum('oc','salida') COLLATE utf8mb4_unicode_ci NOT NULL,
  `origen_id` int DEFAULT NULL,
  `origen_ref` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'advertir',
  `presupuesto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `usado_previo` decimal(14,2) NOT NULL DEFAULT 0.00,
  `monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `exceso` decimal(14,2) NOT NULL DEFAULT 0.00,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `autorizado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_excesos_proyecto` (`proyecto_id`, `created_at`),
  KEY `idx_excesos_tarea` (`tarea_id`),
  CONSTRAINT `fk_excesos_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_excesos_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_excesos_usuario` FOREIGN KEY (`autorizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 8. SUBCONTRATOS: contrato con adelanto y retención + valorizaciones del subcontratista
--    costo real de la actividad = valorizaciones APROBADAS o PAGADAS (monto_bruto del periodo)
--    por valorizar = monto contratado - valorizado, solo contratos activos (compromiso en el control presupuestal)
-- --------------------------------------------------------------
SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `subcontratos` ADD COLUMN `adelanto_monto` decimal(15,2) NOT NULL DEFAULT 0.00 AFTER `monto`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'subcontratos' AND COLUMN_NAME = 'adelanto_monto');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `subcontratos` ADD COLUMN `retencion_pct` decimal(5,2) NOT NULL DEFAULT 5.00 AFTER `adelanto_monto`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'subcontratos' AND COLUMN_NAME = 'retencion_pct');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `subcontratos` ADD COLUMN `observaciones` text DEFAULT NULL AFTER `estado`',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'subcontratos' AND COLUMN_NAME = 'observaciones');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `subcontratos` ADD COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'subcontratos' AND COLUMN_NAME = 'updated_at');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COLUMN_TYPE LIKE '%anulado%',
    'SELECT 1',
    'ALTER TABLE `subcontratos` MODIFY COLUMN `estado` enum(''activo'',''finalizado'',''suspendido'',''anulado'') NOT NULL DEFAULT ''activo''')
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'subcontratos' AND COLUMN_NAME = 'estado');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS `subcontrato_valorizaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subcontrato_id` int NOT NULL,
  `numero` int NOT NULL,
  `periodo_desde` date DEFAULT NULL,
  `periodo_hasta` date DEFAULT NULL,
  `avance_acumulado_pct` decimal(6,2) NOT NULL DEFAULT 0.00,
  `monto_bruto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bruto_acumulado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amortizacion_adelanto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `retencion` decimal(15,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(15,2) NOT NULL DEFAULT 0.00,
  `neto_pagar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `estado` enum('borrador','aprobada','pagada','anulada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `nro_factura` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registrado_por` int DEFAULT NULL,
  `aprobado_por` int DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subval_numero` (`subcontrato_id`, `numero`),
  KEY `idx_subval_estado` (`estado`),
  CONSTRAINT `fk_subval_subcontrato` FOREIGN KEY (`subcontrato_id`) REFERENCES `subcontratos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subval_registrado` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_subval_aprobado` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La bitácora de excesos también registra los subcontratos
SET @s = (SELECT IF(COLUMN_TYPE LIKE '%subcontrato%',
    'SELECT 1',
    'ALTER TABLE `presupuesto_excesos` MODIFY COLUMN `origen` enum(''oc'',''salida'',''subcontrato'') NOT NULL')
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'presupuesto_excesos' AND COLUMN_NAME = 'origen');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Permisos del módulo (el catálogo ya trae ver, crear y editar)
INSERT INTO `permisos` (`modulo`, `accion`, `descripcion`)
SELECT 'subcontratos', 'aprobar', 'Aprobar valorizaciones de subcontratistas y autorizar excesos'
WHERE NOT EXISTS (SELECT 1 FROM `permisos` WHERE `modulo` = 'subcontratos' AND `accion` = 'aprobar');

INSERT INTO `permisos` (`modulo`, `accion`, `descripcion`)
SELECT 'subcontratos', 'eliminar', 'Anular subcontratos y valorizaciones'
WHERE NOT EXISTS (SELECT 1 FROM `permisos` WHERE `modulo` = 'subcontratos' AND `accion` = 'eliminar');
