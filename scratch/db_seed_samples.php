<?php
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Seed Clientes
    $clientes = [
        ['razon_social' => 'Ministerio de Transportes y Comunicaciones', 'ruc' => '20131379944', 'tipo' => 'publico'],
        ['razon_social' => 'Sedapal', 'ruc' => '20100152356', 'tipo' => 'publico'],
        ['razon_social' => 'Minera Antamina', 'ruc' => '20336214312', 'tipo' => 'privado']
    ];
    $stmtC = $conn->prepare("INSERT IGNORE INTO clientes (razon_social, ruc, tipo) VALUES (?, ?, ?)");
    foreach($clientes as $c) $stmtC->execute([$c['razon_social'], $c['ruc'], $c['tipo']]);

    // Seed Empresas (Consorcios / Socios)
    $stmtE = $conn->prepare("INSERT INTO empresas (razon_social, ruc, tipo_empresa, especialidad_id) VALUES (?, ?, 'externa', 1)");
    $stmtE->execute(['Constructora ABC S.A.C.', '20555666777']);
    $stmtE->execute(['Ingenieros del Perú EIRL', '20444555888']);
    
    // Seed Convocatorias (Dummy)
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS convocatorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(100),
            entidad_id INT,
            objeto TEXT,
            estado VARCHAR(50) DEFAULT 'Detectada',
            monto_estimado DECIMAL(15,2) DEFAULT 0,
            fecha_publicacion DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $stmtConv = $conn->prepare("INSERT INTO convocatorias (codigo, entidad_id, objeto, estado, monto_estimado, fecha_publicacion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtConv->execute(['LP-001-2026-MTC', 1, 'Mejoramiento de la Carretera Central tramo X', 'Detectada', 50000000.00, date('Y-m-d')]);
        $stmtConv->execute(['CP-005-2026-SEDAPAL', 2, 'Renovación de redes de agua potable sector 5', 'Detectada', 12000000.00, date('Y-m-d')]);
    } catch(Exception $e){}

    echo "Datos de prueba insertados.\n";
} catch(PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
