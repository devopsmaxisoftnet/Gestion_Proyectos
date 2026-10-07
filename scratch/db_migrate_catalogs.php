<?php
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Create Catalogs
    $conn->exec("CREATE TABLE IF NOT EXISTS cat_especialidades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL UNIQUE,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB");

    $conn->exec("CREATE TABLE IF NOT EXISTS cat_tipos_profesional (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB");

    $conn->exec("CREATE TABLE IF NOT EXISTS cat_tipos_documento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL UNIQUE,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB");

    // 2. Insert Dummy Data for Catalogs
    $especialidades = ['Obras Viales', 'Edificaciones', 'Saneamiento', 'Electromecánica', 'Consultoría', 'Supervisión', 'Arquitectura'];
    $stmtE = $conn->prepare("INSERT IGNORE INTO cat_especialidades (nombre) VALUES (?)");
    foreach($especialidades as $e) $stmtE->execute([$e]);

    $tipos_prof = ['Ingeniero Civil', 'Arquitecto', 'Ingeniero Mecánico', 'Ingeniero Eléctrico', 'Ingeniero Sanitario', 'Técnico', 'Topógrafo', 'Administrador'];
    $stmtP = $conn->prepare("INSERT IGNORE INTO cat_tipos_profesional (nombre) VALUES (?)");
    foreach($tipos_prof as $t) $stmtP->execute([$t]);

    $tipos_doc = ['Testimonio de Constitución', 'Vigencia de Poder', 'RNP', 'Certificado ISO 9001', 'Certificado ISO 14001', 'Certificado ISO 45001', 'Declaración Jurada', 'Estatutos'];
    $stmtD = $conn->prepare("INSERT IGNORE INTO cat_tipos_documento (nombre) VALUES (?)");
    foreach($tipos_doc as $d) $stmtD->execute([$d]);

    // 3. Alter Existing Tables to use Catalogs
    // First, add new ID columns
    try { $conn->exec("ALTER TABLE empresas ADD COLUMN especialidad_id INT NULL AFTER especialidad"); } catch(Exception $e){}
    try { $conn->exec("ALTER TABLE profesionales ADD COLUMN especialidad_id INT NULL AFTER especialidad"); } catch(Exception $e){}
    try { $conn->exec("ALTER TABLE profesionales ADD COLUMN tipo_profesional_id INT NULL AFTER tipo_profesional"); } catch(Exception $e){}
    try { $conn->exec("ALTER TABLE empresa_documentos ADD COLUMN tipo_documento_id INT NULL AFTER tipo_documento"); } catch(Exception $e){}

    // Update data: set to 1 (default) or match
    $conn->exec("UPDATE empresas SET especialidad_id = 1 WHERE especialidad_id IS NULL");
    $conn->exec("UPDATE profesionales SET especialidad_id = 1 WHERE especialidad_id IS NULL");
    $conn->exec("UPDATE profesionales SET tipo_profesional_id = 1 WHERE tipo_profesional_id IS NULL");
    $conn->exec("UPDATE empresa_documentos SET tipo_documento_id = 1 WHERE tipo_documento_id IS NULL");

    // Add Foreign Keys
    try { $conn->exec("ALTER TABLE empresas ADD CONSTRAINT fk_emp_esp FOREIGN KEY (especialidad_id) REFERENCES cat_especialidades(id) ON DELETE SET NULL"); } catch(Exception $e){}
    try { $conn->exec("ALTER TABLE profesionales ADD CONSTRAINT fk_prof_esp FOREIGN KEY (especialidad_id) REFERENCES cat_especialidades(id) ON DELETE SET NULL"); } catch(Exception $e){}
    try { $conn->exec("ALTER TABLE profesionales ADD CONSTRAINT fk_prof_tipo FOREIGN KEY (tipo_profesional_id) REFERENCES cat_tipos_profesional(id) ON DELETE SET NULL"); } catch(Exception $e){}
    try { $conn->exec("ALTER TABLE empresa_documentos ADD CONSTRAINT fk_doc_tipo FOREIGN KEY (tipo_documento_id) REFERENCES cat_tipos_documento(id) ON DELETE SET NULL"); } catch(Exception $e){}

    echo "Migración completada exitosamente.\n";
} catch(PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
