<?php
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Renombrar
    $conn->exec("RENAME TABLE proveedores TO empresas");
    echo "Tabla proveedores renombrada a empresas.\n";
    
    // 2. Columnas
    $conn->exec("ALTER TABLE empresas 
        ADD COLUMN tipo_empresa ENUM('propia', 'externa') DEFAULT 'externa' AFTER ruc,
        ADD COLUMN num_cuenta VARCHAR(50) AFTER tipo_empresa,
        ADD COLUMN representante VARCHAR(150) AFTER direccion,
        ADD COLUMN contacto_administrativo VARCHAR(150) AFTER representante");
    echo "Columnas añadidas a empresas.\n";
    
    // 3. Profesionales
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
    
    // 4. Bandeja
    $conn->exec("CREATE TABLE IF NOT EXISTS bandeja_entrada (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo_proceso ENUM('convocatoria', 'oportunidad', 'licitacion', 'propuesta', 'contrato') NOT NULL,
        referencia_id INT NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        mensaje TEXT,
        estado ENUM('pendiente', 'en_proceso', 'completado') DEFAULT 'pendiente',
        asignado_a INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "Tabla bandeja_entrada creada.\n";
    
} catch(PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
