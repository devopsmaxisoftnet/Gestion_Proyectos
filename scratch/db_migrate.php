<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // 1. Renombrar proveedores a empresas
    $conn->exec("RENAME TABLE proveedores TO empresas");
    echo "Tabla proveedores renombrada a empresas.\n";
    
    // 2. Modificar columnas
    // empresas: id, razon_social, ruc, direccion, telefono, email, contacto_nombre, especialidad, calificacion, activo
    // Necesitamos: tipo_empresa (propia o externa), num_cuenta, representante, contacto_administrativo
    $conn->exec("ALTER TABLE empresas 
        ADD COLUMN tipo_empresa ENUM('propia', 'externa') DEFAULT 'externa' AFTER ruc,
        ADD COLUMN num_cuenta VARCHAR(50) AFTER tipo_empresa,
        ADD COLUMN representante VARCHAR(150) AFTER direccion,
        ADD COLUMN contacto_administrativo VARCHAR(150) AFTER representante
    ");
    echo "Columnas añadidas a la tabla empresas.\n";
    
    // 3. Crear tabla profesionales
    // debajo de la parte de empresas poner profesionales
    // El sistema debe detectar por empresa tipo de profesionales
    $conn->exec("CREATE TABLE IF NOT EXISTS profesionales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        tipo_profesional VARCHAR(100) NOT NULL,
        especialidad VARCHAR(150),
        telefono VARCHAR(50),
        email VARCHAR(150),
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "Tabla profesionales creada.\n";
    
    // 4. Bandeja de entrada en cada tipo de proceso en gestión de oportunidades
    // La gestión de oportunidades tiene "procesos", que en este sistema podrían ser las oportunidades o licitaciones mismas, o fases.
    // "Bandeja de entrada en cada tipo de proceso en gestion de oportunidades"
    $conn->exec("CREATE TABLE IF NOT EXISTS bandeja_entrada (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo_proceso ENUM('convocatoria', 'oportunidad', 'licitacion', 'propuesta', 'contrato') NOT NULL,
        referencia_id INT NOT NULL, -- ID del registro asociado
        titulo VARCHAR(255) NOT NULL,
        mensaje TEXT,
        estado ENUM('pendiente', 'en_proceso', 'completado') DEFAULT 'pendiente',
        asignado_a INT NULL, -- usuario_id
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    echo "Tabla bandeja_entrada creada.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
