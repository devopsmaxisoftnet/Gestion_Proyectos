<?php
/**
 * Seed de datos de prueba para todos los módulos
 */
try {
    require __DIR__ . '/../config/database.php';
    $conn = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ─── CLIENTES ──────────────────────────────────────────────────────────────
    $conn->exec("
        INSERT IGNORE INTO clientes (razon_social, ruc, tipo, email, telefono, activo)
        VALUES
        ('Municipalidad Distrital de Miraflores', '20501234567', 'publico', 'licitaciones@miraflores.gob.pe', '014467000', 1),
        ('Hospital Nacional Edgardo Rebagliati Martins', '20131370645', 'publico', 'logistica@rebagliati.gob.pe', '012659400', 1),
        ('Constructora Inmobiliaria Omega SAC', '20601234560', 'privado', 'contratos@omega.pe', '014430000', 1),
        ('Minera Cerro Verde SAC', '20100177390', 'privado', 'compras@cerroverde.com.pe', '054283000', 1)
    ");
    echo "✓ Clientes insertados\n";

    // ─── EMPRESAS ──────────────────────────────────────────────────────────────
    $conn->exec("
        INSERT IGNORE INTO empresas (razon_social, ruc, tipo_empresa, email, telefono, activo, direccion, representante)
        VALUES
        ('GPC Ingeniería y Construcción SAC', '20456789012', 'propia', 'admin@gpc.pe', '014429800', 1, 'Av. Javier Prado Este 1234, San Isidro', 'Carlos Medina García'),
        ('TechBuild Asociados SRL', '20512345678', 'propia', 'info@techbuild.pe', '014100200', 1, 'Calle Los Ingenieros 456, Surco', 'Ana Torres Reyes'),
        ('Consorcio Lima Norte SAC', '20601111222', 'externa', 'gerencia@limanorte.pe', '017899900', 1, 'Jr. Carabaya 789, Cercado de Lima', 'Roberto Huamán Flores'),
        ('ITAL Constructores EIRL', '20401234561', 'externa', 'obras@italconstructores.pe', '015551234', 1, 'Av. Industrial 321, Ate', 'Giuseppe Moretti Díaz')
    ");
    echo "✓ Empresas insertadas\n";

    // ─── CONVOCATORIAS ────────────────────────────────────────────────────────
    $conn->exec("
        INSERT IGNORE INTO convocatorias (codigo, nombre, entidad, monto_referencial, fecha_publicacion, fecha_limite_oferta, estado, departamento, fuente, observaciones)
        VALUES
        ('CONV-2026-001', 'Construcción de Pistas y Veredas - Miraflores', 'Municipalidad Distrital de Miraflores', 1850000.00, '2026-09-01', '2026-10-15', 'detectada', 'Lima', 'SEACE', 'Rehabilitación de 12 km de vías urbanas en el distrito.'),
        ('CONV-2026-002', 'Remodelación de Pabellón Norte del Hospital Rebagliati', 'ESSALUD', 3200000.00, '2026-09-10', '2026-10-30', 'en_revision', 'Lima', 'SEACE', 'Trabajos de arquitectura, instalaciones y acabados.'),
        ('CONV-2026-003', 'Mantenimiento de Carreteras Región Arequipa', 'MTC - Ministerio de Transportes', 900000.00, '2026-08-15', '2026-09-30', 'descartada', 'Arequipa', 'Portal MTC', 'Bacheo y señalización de 80 km de carreteras.'),
        ('CONV-2026-004', 'Suministro e Instalación de Sistema Solar Fotovoltaico', 'Gobierno Regional Ica', 650000.00, '2026-09-20', '2026-11-05', 'detectada', 'Ica', 'SEACE', 'Sistema de 500 kW para sede gubernamental.')
    ");
    echo "✓ Convocatorias insertadas\n";

    // ─── OPORTUNIDADES ────────────────────────────────────────────────────────
    // Check if oportunidades table exists
    $tables = $conn->query("SHOW TABLES LIKE 'oportunidades'")->fetchColumn();
    if ($tables) {
        // Check columns
        $cols = [];
        foreach($conn->query('DESCRIBE oportunidades') as $r) $cols[] = $r['Field'];
        echo "  Columnas oportunidades: " . implode(', ', $cols) . "\n";
        
        if (in_array('nombre', $cols) && in_array('estado', $cols)) {
            $conn->exec("
                INSERT IGNORE INTO oportunidades (nombre, monto_estimado, estado)
                VALUES
                ('Oportunidad Pistas Miraflores', 1850000.00, 'nueva'),
                ('Oportunidad Remodelación Rebagliati', 3200000.00, 'en_evaluacion'),
                ('Oportunidad Solar Ica', 650000.00, 'nueva')
            ");
            echo "✓ Oportunidades insertadas\n";
        }
    }

    // ─── LICITACIONES ─────────────────────────────────────────────────────────
    // Need oportunidad_id
    $opId = $conn->query("SELECT id FROM oportunidades LIMIT 1")->fetchColumn();
    if ($opId) {
        $conn->exec("
            INSERT IGNORE INTO licitaciones (codigo, oportunidad_id, monto_ofertado, estado, fecha_presentacion)
            VALUES
            ('LIC-2026-001', $opId, 1750000.00, 'en_preparacion', '2026-10-15'),
            ('LIC-2026-002', $opId, 420000.00, 'presentada', '2026-09-25')
        ");
        echo "✓ Licitaciones insertadas\n";
    }

    // ─── PROPUESTAS ───────────────────────────────────────────────────────────
    $tabProp = $conn->query("SHOW TABLES LIKE 'propuestas'")->fetchColumn();
    if ($tabProp) {
        $cols = [];
        foreach($conn->query('DESCRIBE propuestas') as $r) $cols[] = $r['Field'];
        echo "  Columnas propuestas: " . implode(', ', $cols) . "\n";
    }

    // ─── CONTRATOS ────────────────────────────────────────────────────────────
    $tabCont = $conn->query("SHOW TABLES LIKE 'contratos'")->fetchColumn();
    if ($tabCont) {
        $cols = [];
        foreach($conn->query('DESCRIBE contratos') as $r) $cols[] = $r['Field'];
        echo "  Columnas contratos: " . implode(', ', $cols) . "\n";
        
        $clienteId = $conn->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($clienteId && in_array('nombre', $cols)) {
            $conn->exec("
                INSERT IGNORE INTO contratos (nombre, cliente_id, monto, estado)
                VALUES
                ('Contrato Pistas Miraflores 2026', $clienteId, 1750000.00, 'activo'),
                ('Contrato Remodelación Rebagliati', $clienteId, 3100000.00, 'borrador'),
                ('Contrato Mantenimiento Carreteras AQP', $clienteId, 880000.00, 'activo')
            ");
            echo "✓ Contratos insertados\n";
        }
    }

    echo "\n✅ Seed de datos de prueba completado correctamente!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
