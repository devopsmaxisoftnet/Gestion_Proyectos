<?php
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
    $stmt = $conn->query('SHOW CREATE TABLE empresas');
    echo "=== EMPRESAS ===\n" . $stmt->fetchColumn(1) . "\n\n";
    $stmt = $conn->query('SHOW CREATE TABLE profesionales');
    echo "=== PROFESIONALES ===\n" . $stmt->fetchColumn(1) . "\n\n";
    $stmt = $conn->query('SHOW CREATE TABLE empresa_documentos');
    echo "=== DOCUMENTOS ===\n" . $stmt->fetchColumn(1) . "\n\n";
    $stmt = $conn->query('SHOW CREATE TABLE clientes');
    echo "=== CLIENTES ===\n" . $stmt->fetchColumn(1) . "\n\n";
} catch(Exception $e) {
    echo $e->getMessage();
}
