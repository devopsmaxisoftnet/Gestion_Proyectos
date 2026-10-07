-- ====================================================================
-- LIMPIEZA DE DUPLICADOS EN LÍNEAS DE NEGOCIO Y ROLES
-- Elimina los registros repetidos y previene futuras duplicaciones
-- ====================================================================

-- 1. Actualizar cualquier referencia en convocatorias u oportunidades a los IDs canónicos
UPDATE convocatorias SET linea_negocio_id = 1 WHERE linea_negocio_id IN (4, 7, 10, 13, 16, 19, 22, 25);
UPDATE convocatorias SET linea_negocio_id = 2 WHERE linea_negocio_id IN (5, 8, 11, 14, 17, 20, 23, 26);
UPDATE convocatorias SET linea_negocio_id = 3 WHERE linea_negocio_id IN (6, 9, 12, 15, 18, 21, 24, 27);

UPDATE oportunidades SET linea_negocio_id = 1 WHERE linea_negocio_id IN (4, 7, 10, 13, 16, 19, 22, 25);
UPDATE oportunidades SET linea_negocio_id = 2 WHERE linea_negocio_id IN (5, 8, 11, 14, 17, 20, 23, 26);
UPDATE oportunidades SET linea_negocio_id = 3 WHERE linea_negocio_id IN (6, 9, 12, 15, 18, 21, 24, 27);

-- Actualizar cualquier referencia de usuarios a roles canónicos (1 al 5)
UPDATE usuarios SET rol_id = 1 WHERE rol_id > 5 AND rol_id % 5 = 1;
UPDATE usuarios SET rol_id = 2 WHERE rol_id > 5 AND rol_id % 5 = 2;
UPDATE usuarios SET rol_id = 3 WHERE rol_id > 5 AND rol_id % 5 = 3;
UPDATE usuarios SET rol_id = 4 WHERE rol_id > 5 AND rol_id % 5 = 4;
UPDATE usuarios SET rol_id = 5 WHERE rol_id > 5 AND rol_id % 5 = 0;

-- 2. Eliminar duplicados de lineas_negocio (conservando los IDs 1, 2, 3)
DELETE FROM lineas_negocio WHERE id > 3;

-- 3. Eliminar duplicados de roles (conservando los IDs 1, 2, 3, 4, 5)
DELETE FROM roles WHERE id > 5;

-- 4. Agregar restricción UNIQUE para que no se vuelvan a duplicar jamás
ALTER TABLE lineas_negocio ADD UNIQUE KEY uq_linea_nombre (nombre);
ALTER TABLE roles ADD UNIQUE KEY uq_rol_nombre (nombre);
