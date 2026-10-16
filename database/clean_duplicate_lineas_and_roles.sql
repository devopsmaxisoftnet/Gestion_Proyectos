-- ====================================================================
-- LIMPIEZA DE DUPLICADOS EN LÍNEAS DE NEGOCIO Y ROLES
-- Elimina los registros repetidos y previene futuras duplicaciones
-- ====================================================================

-- 1. Asegurar la existencia y nombres canónicos de las 3 líneas de negocio
INSERT INTO lineas_negocio (id, nombre, descripcion, icono, color, activo)
VALUES 
(1, 'Negocios propios', 'Iniciativas de inversión originadas y evaluadas por la empresa.', 'building', '#6C5CE7', 1),
(2, 'Consultoría', 'Supervisión, expedientes técnicos, estudios y entregables especializados.', 'clipboard-check', '#00B894', 1),
(3, 'Ejecución de obra', 'Contratos orientados a construcción, producción y entrega de obra.', 'hard-hat', '#E17055', 1)
ON DUPLICATE KEY UPDATE 
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    icono = VALUES(icono),
    color = VALUES(color),
    activo = VALUES(activo);

-- 2. Actualizar cualquier referencia en convocatorias u oportunidades a los IDs canónicos
UPDATE convocatorias SET linea_negocio_id = 1 WHERE linea_negocio_id IN (4, 7, 10, 13, 16, 19, 22, 25);
UPDATE convocatorias SET linea_negocio_id = 2 WHERE linea_negocio_id IN (5, 8, 11, 14, 17, 20, 23, 26);
UPDATE convocatorias SET linea_negocio_id = 3 WHERE linea_negocio_id IN (6, 9, 12, 15, 18, 21, 24, 27);

-- Mapear oportunidades de Ejecución de obra al ID 3
UPDATE oportunidades SET linea_negocio_id = 3 
WHERE nombre LIKE '%EJECUCION%' 
   OR nombre LIKE '%LLAMELLIN%' 
   OR nombre LIKE '%JULIO GOMEZ%' 
   OR nombre LIKE '%SOR LILIANA%'
   OR linea_negocio_id IN (3, 6, 9, 12, 15, 18, 21, 24, 27);

-- Mapear convocatorias/oportunidades residuales a Consultoría (ID 2) o Negocios propios (ID 1)
UPDATE oportunidades SET linea_negocio_id = 1 WHERE linea_negocio_id IN (1, 4, 7, 10, 13, 16, 19, 22, 25);
UPDATE oportunidades SET linea_negocio_id = 2 WHERE linea_negocio_id NOT IN (1, 3);

-- Actualizar cualquier referencia de usuarios a roles canónicos (1 al 5)
UPDATE usuarios SET rol_id = 1 WHERE rol_id > 5 AND rol_id % 5 = 1;
UPDATE usuarios SET rol_id = 2 WHERE rol_id > 5 AND rol_id % 5 = 2;
UPDATE usuarios SET rol_id = 3 WHERE rol_id > 5 AND rol_id % 5 = 3;
UPDATE usuarios SET rol_id = 4 WHERE rol_id > 5 AND rol_id % 5 = 4;
UPDATE usuarios SET rol_id = 5 WHERE rol_id > 5 AND rol_id % 5 = 0;

-- 3. Eliminar duplicados de lineas_negocio (conservando los IDs 1, 2, 3)
DELETE FROM lineas_negocio WHERE id > 3;

-- 4. Eliminar duplicados de roles (conservando los IDs 1, 2, 3, 4, 5)
DELETE FROM roles WHERE id > 5;
