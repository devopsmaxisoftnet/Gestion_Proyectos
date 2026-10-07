<?php
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables
    $conn->exec("CREATE TABLE IF NOT EXISTS cat_departamentos (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) UNIQUE)");
    $conn->exec("CREATE TABLE IF NOT EXISTS cat_provincias (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) UNIQUE)");
    $conn->exec("CREATE TABLE IF NOT EXISTS cat_distritos (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) UNIQUE)");
    $conn->exec("CREATE TABLE IF NOT EXISTS cat_cargos_cliente (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) UNIQUE)");

    // Insert departments (Peru)
    $deps = ['Amazonas', 'Ancash', 'Apurimac', 'Arequipa', 'Ayacucho', 'Cajamarca', 'Callao', 'Cusco', 'Huancavelica', 'Huanuco', 'Ica', 'Junin', 'La Libertad', 'Lambayeque', 'Lima', 'Loreto', 'Madre de Dios', 'Moquegua', 'Pasco', 'Piura', 'Puno', 'San Martin', 'Tacna', 'Tumbes', 'Ucayali'];
    $stmt = $conn->prepare("INSERT IGNORE INTO cat_departamentos (nombre) VALUES (?)");
    foreach($deps as $d) $stmt->execute([$d]);

    // Insert some common cargos
    $cargos = ['Gerente General', 'Gerente de Proyectos', 'Jefe de Logística', 'Administrador', 'Ingeniero Residente', 'Alcalde', 'Gobernador', 'Director', 'Supervisor'];
    $stmt = $conn->prepare("INSERT IGNORE INTO cat_cargos_cliente (nombre) VALUES (?)");
    foreach($cargos as $c) $stmt->execute([$c]);

    // Alter table clientes
    // First safely rename columns if they are strings to avoid data loss, or just add new FK columns.
    // Let's add new columns _id
    try {
        $conn->exec("ALTER TABLE clientes ADD COLUMN departamento_id INT DEFAULT NULL");
        $conn->exec("ALTER TABLE clientes ADD COLUMN provincia_id INT DEFAULT NULL");
        $conn->exec("ALTER TABLE clientes ADD COLUMN distrito_id INT DEFAULT NULL");
        $conn->exec("ALTER TABLE clientes ADD COLUMN contacto_cargo_id INT DEFAULT NULL");
    } catch(Exception $e) {
        // columns might already exist
    }

    echo "Catalogs created and seeded. Clientes table altered.\n";
} catch(PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
