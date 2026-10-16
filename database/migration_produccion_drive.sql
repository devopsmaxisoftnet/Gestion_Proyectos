-- ==============================================================
-- SCRIPT DE MIGRACIÓN PARA BASE DE DATOS EN PRODUCCIÓN
-- Base de datos: mnxsrokm_comercial
-- Fecha: 13-10-2026
-- Objetivo: Agregar campos de Google Drive, vinculación y configuración
-- ==============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- 1. Actualizar tabla `empresas`
ALTER TABLE `empresas`
  ADD COLUMN IF NOT EXISTS `drive_folder_id` varchar(255) DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `drive_email` varchar(150) DEFAULT NULL AFTER `drive_folder_id`,
  ADD COLUMN IF NOT EXISTS `drive_vinculado` tinyint(1) DEFAULT 0 AFTER `drive_email`;

-- 2. Actualizar tabla `usuarios`
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `empresa_id` int(11) DEFAULT NULL AFTER `password_hash`,
  ADD COLUMN IF NOT EXISTS `drive_folder_id` varchar(255) DEFAULT NULL AFTER `empresa_id`,
  ADD COLUMN IF NOT EXISTS `drive_email` varchar(150) DEFAULT NULL AFTER `drive_folder_id`,
  ADD COLUMN IF NOT EXISTS `drive_vinculado` tinyint(1) DEFAULT 0 AFTER `drive_email`;

-- 3. Crear tabla `configuracion` si no existe
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Insertar parámetros globales de Google Drive en `configuracion`
INSERT INTO `configuracion` (`clave`, `valor`) VALUES
  ('drive_vinculado', '1'),
  ('drive_folder_id', '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr'),
  ('drive_email', 'devops.maxisoftnet@gmail.com')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

-- 5. Vincular las empresas propias existentes a Google Drive para evitar bloqueos
UPDATE `empresas`
SET
  `drive_vinculado` = 1,
  `drive_folder_id` = '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr',
  `drive_email` = 'devops.maxisoftnet@gmail.com'
WHERE `tipo_empresa` = 'propia';

-- 6. Actualizar usuarios existentes para que tengan acceso inmediato al sistema sin ser bloqueados
UPDATE `usuarios`
SET
  `drive_vinculado` = 1,
  `drive_folder_id` = '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr',
  `drive_email` = 'devops.maxisoftnet@gmail.com'
WHERE `drive_vinculado` = 0 OR `drive_vinculado` IS NULL;

COMMIT;
