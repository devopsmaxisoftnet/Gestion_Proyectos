<?php
require __DIR__ . '/../config/database.php';
$conn = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get IDs
$opId = $conn->query("SELECT id FROM oportunidades LIMIT 1")->fetchColumn();
$licId = $conn->query("SELECT id FROM licitaciones LIMIT 1")->fetchColumn();
$clienteId = $conn->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();

echo "opId=$opId, licId=$licId, clienteId=$clienteId\n";

// PROPUESTAS
if ($licId) {
    $conn->exec("
        INSERT IGNORE INTO propuestas (codigo, licitacion_id, monto_propuesta, fecha_envio, estado, propuesta_tecnica)
        VALUES
        ('PROP-2026-001', $licId, 1720000.00, '2026-10-10', 'enviada', 'Propuesta técnica completa con alcance y cronograma.'),
        ('PROP-2026-002', $licId, 1680000.00, '2026-10-12', 'borrador', 'Versión preliminar de propuesta técnica.'),
        ('PROP-2026-003', $licId, 405000.00, '2026-09-20', 'aceptada', 'Propuesta técnica aprobada por el cliente.')
    ");
    echo "✓ Propuestas insertadas\n";
}

// CONTRATOS
if ($opId && $clienteId) {
    $conn->exec("
        INSERT IGNORE INTO contratos (codigo, oportunidad_id, cliente_id, objeto, monto_contrato, moneda, fecha_firma, fecha_inicio, fecha_fin, plazo_dias, estado, tipo_contrato)
        VALUES
        ('CONT-2026-001', $opId, $clienteId, 'Construcción de pistas y veredas en Miraflores - Tramo 1', 1750000.00, 'PEN', '2026-10-20', '2026-11-01', '2027-05-01', 180, 'vigente', 'Obra'),
        ('CONT-2026-002', $opId, $clienteId, 'Remodelación de ambientes Pabellón Norte Rebagliati', 3100000.00, 'PEN', '2026-11-01', '2026-11-15', '2027-08-15', 270, 'borrador', 'Obra'),
        ('CONT-2026-003', $opId, $clienteId, 'Mantenimiento vial carretera Arequipa Km 40-120', 880000.00, 'PEN', '2026-09-15', '2026-10-01', '2027-01-31', 120, 'vigente', 'Servicio')
    ");
    echo "✓ Contratos insertados\n";
}

echo "\n✅ Seed adicional completado!\n";
