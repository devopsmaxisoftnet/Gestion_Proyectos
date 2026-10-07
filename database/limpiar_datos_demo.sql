-- ==========================================================
-- SCRIPT PARA LIMPIAR DATOS FAKE Y USAR CUENTAS REALES
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Limpiar transacciones
TRUNCATE TABLE valorizaciones;
TRUNCATE TABLE seguimientos;
TRUNCATE TABLE documentos;
TRUNCATE TABLE proyectos;
TRUNCATE TABLE contratos;
TRUNCATE TABLE propuestas;
TRUNCATE TABLE licitaciones;
TRUNCATE TABLE oportunidades;
TRUNCATE TABLE convocatorias;
TRUNCATE TABLE solicitudes_acceso;

-- 2. Limpiar clientes y proveedores (opcional, limpiar para empezar de cero)
TRUNCATE TABLE clientes;
TRUNCATE TABLE proveedores;

-- 3. Eliminar usuarios fake (Jorge, María, Carlos, Ana, Luis)
-- Según el dump, el admin tiene el ID 11 (Guillermo Lima) y asconsultti tendrá otro.
-- Borramos explícitamente a los 5 falsos por su email.
DELETE FROM usuarios WHERE email IN (
    'jorge.palomino@empresa.com',
    'maria.gutierrez@empresa.com',
    'carlos.mendoza@empresa.com',
    'ana.torres@empresa.com',
    'luis.vargas@empresa.com'
);

SET FOREIGN_KEY_CHECKS = 1;
