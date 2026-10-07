<?php
$conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = file_get_contents(__DIR__ . '/../database/update_bpm_oportunidades.sql');
try {
    $conn->exec($sql);
    echo "SQL ejecutado correctamente.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
