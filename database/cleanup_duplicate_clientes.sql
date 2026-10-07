-- ==============================================================================
-- LIMPIEZA Y DEDUPLICACIÓN DE CLIENTES / ENTIDADES EN BASE DE DATOS
-- Base de datos: gestion_comercial
-- ==============================================================================

USE `gestion_comercial`;

-- 1. Remapear claves foráneas en tablas dependientes hacia el ID canónico (mínimo)
UPDATE convocatorias c
JOIN clientes old_c ON c.cliente_id = old_c.id
JOIN (
    SELECT MIN(id) AS canonical_id, razon_social
    FROM clientes
    GROUP BY razon_social
) can ON old_c.razon_social = can.razon_social
SET c.cliente_id = can.canonical_id
WHERE c.cliente_id != can.canonical_id;

UPDATE oportunidades o
JOIN clientes old_c ON o.cliente_id = old_c.id
JOIN (
    SELECT MIN(id) AS canonical_id, razon_social
    FROM clientes
    GROUP BY razon_social
) can ON old_c.razon_social = can.razon_social
SET o.cliente_id = can.canonical_id
WHERE o.cliente_id != can.canonical_id;

UPDATE contratos ct
JOIN clientes old_c ON ct.cliente_id = old_c.id
JOIN (
    SELECT MIN(id) AS canonical_id, razon_social
    FROM clientes
    GROUP BY razon_social
) can ON old_c.razon_social = can.razon_social
SET ct.cliente_id = can.canonical_id
WHERE ct.cliente_id != can.canonical_id;

-- 2. Eliminar registros duplicados de clientes (conservando únicamente el menor ID por razón social)
DELETE c1 FROM clientes c1
INNER JOIN clientes c2
WHERE c1.id > c2.id AND c1.razon_social = c2.razon_social;

-- 3. Crear índice único para prevenir duplicados en el futuro
ALTER TABLE clientes ADD CONSTRAINT uq_clientes_razon_social UNIQUE (razon_social);
