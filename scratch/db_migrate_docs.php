<?php
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 5. Documentos Empresa
    $conn->exec("CREATE TABLE IF NOT EXISTS empresa_documentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        nombre_documento VARCHAR(255) NOT NULL,
        tipo_documento VARCHAR(100),
        fecha_emision DATE,
        fecha_vencimiento DATE,
        archivo_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "Tabla empresa_documentos creada.\n";
} catch(PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
