<?php
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Modificar contratos.estado a VARCHAR(60)
    dbExecute("ALTER TABLE contratos MODIFY COLUMN estado VARCHAR(60) NOT NULL DEFAULT 'en_elaboracion'");
    echo "✓ contratos.estado asegurado como VARCHAR(60)\n";

    // 2. Agregar columnas para archivos de propuesta si no existen
    $cols = dbQuery("SHOW COLUMNS FROM propuestas");
    $colNames = array_column($cols, 'Field');
    
    if (!in_array('archivo_tecnica', $colNames)) {
        dbExecute("ALTER TABLE propuestas ADD COLUMN archivo_tecnica VARCHAR(255) NULL AFTER propuesta_tecnica");
        echo "✓ Columna archivo_tecnica agregada a propuestas\n";
    }
    if (!in_array('archivo_economica', $colNames)) {
        dbExecute("ALTER TABLE propuestas ADD COLUMN archivo_economica VARCHAR(255) NULL AFTER propuesta_economica");
        echo "✓ Columna archivo_economica agregada a propuestas\n";
    }

    // 3. Corregir estados vacíos en contratos existentes
    dbExecute("UPDATE contratos SET estado = 'vigente' WHERE id = 29");
    dbExecute("UPDATE contratos SET estado = 'en_elaboracion' WHERE id = 30");
    dbExecute("UPDATE contratos SET estado = 'vigente' WHERE id = 31");
    echo "✓ Estados corregidos en contratos iniciales\n";

    // 4. Asegurar contrato vinculado para Oportunidad 78 (Miraflores)
    $op78 = dbQueryOne("SELECT * FROM oportunidades WHERE id = 78");
    if ($op78) {
        $c78 = dbQueryOne("SELECT id, codigo FROM contratos WHERE oportunidad_id = 78 AND objeto LIKE '%Miraflores%'");
        if (!$c78) {
            // Obtener primer cliente
            $cli = dbQueryOne("SELECT id FROM clientes LIMIT 1");
            $cliId = $cli ? $cli['id'] : null;
            dbExecute("INSERT INTO contratos (codigo, oportunidad_id, cliente_id, objeto, monto_contrato, moneda, fecha_firma, fecha_inicio, fecha_fin, plazo_dias, estado, tipo_contrato)
                       VALUES ('CONT-2026-078', 78, ?, 'Construcción de pistas y veredas en Miraflores - Tramo Principal', 1850000.00, 'PEN', CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 240 DAY), 240, 'en_elaboracion', 'Licitación Pública')",
                       [$cliId]);
            echo "✓ Contrato CONT-2026-078 generado exitosamente para Oportunidad 78!\n";
        } else {
            dbExecute("UPDATE contratos SET objeto = 'Construcción de pistas y veredas en Miraflores - Tramo Principal', monto_contrato = 1850000.00, estado = 'en_elaboracion' WHERE id = ?", [$c78['id']]);
            echo "✓ Contrato existente para Oportunidad 78 actualizado correctamente.\n";
        }
    }

    // 5. Poblar catálogo de especialidades con una lista completa y realista de ingeniería y licitaciones
    $nuevasEspecialidades = [
        'Ingeniería Residente de Obra',
        'Especialista en Seguridad, Salud en el Trabajo y Medio Ambiente (SSOMA)',
        'Especialista en Mecánica de Suelos, Geotecnia y Cimentaciones',
        'Especialista en Costos, Presupuestos y Valorizaciones',
        'Especialista en Estructuras y Obras de Concreto Armado',
        'Especialista en Pavimentos, Asfalto y Mezclas en Caliente',
        'Especialista en Redes de Agua Potable, Alcantarillado y Saneamiento',
        'Especialista en Instalaciones Electromecánicas y Alta Tensión',
        'Especialista en Contrataciones del Estado y Licitaciones OSCE',
        'Especialista en Topografía, Geodesia y Fotogrametría con Dron',
        'Coordinador y Modelador BIM / VDC',
        'Especialista Ambiental, DIA, EIA y Gestión de Residuos',
        'Especialista en Hidrología, Hidráulica y Drenaje Pluvial',
        'Especialista en Arqueología y Planes de Monitoreo Arqueológico (PMA)',
        'Especialista en Puentes, Viaductos y Pasos a Desnivel',
        'Especialista en Control y Aseguramiento de Calidad (QA/QC)',
        'Asesor Legal en Derecho Administrativo y Arbitrajes de Obra',
        'Especialista en Geología y Estabilidad de Taludes y Rocas',
        'Especialista en Mantenimiento Rutinario y Periódico de Carreteras',
        'Especialista en Tránsito, Semaforización e Impacto Vial',
        'Especialista en Arquitectura Hospitalaria y Centros de Salud',
        'Especialista en Redes de Gas Natural y Ductos Industriales',
        'Especialista en Telecomunicaciones, Fibra Óptica y Cableado Estructurado',
        'Especialista en Climatización, HVAC y Ventilación Mecánica',
        'Especialista en Gestión del Riesgo de Desastres y Defensa Civil (INDECI)',
        'Especialista en Iluminación y Eficiencia Energética',
        'Especialista en Sistemas de Agua Contra Incendios (ACI)',
        'Especialista en Obras Portuarias, Marítimas y Dragado',
        'Especialista en Túneles y Obras Subterráneas',
        'Especialista en Supervisión de Obras de Edificación Pública',
        'Especialista en Seguridad Estructural y Vulnerabilidad Sísmica',
        'Consultor Senior en Dirección de Proyectos (PMP)',
        'Especialista en Ensayos de Laboratorio de Mecánica de Materiales'
    ];

    $stmtCheck = getConnection()->prepare("SELECT id FROM cat_especialidades WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))");
    $stmtIns = getConnection()->prepare("INSERT INTO cat_especialidades (nombre, activo) VALUES (?, 1)");

    $insertCount = 0;
    foreach ($nuevasEspecialidades as $esp) {
        $stmtCheck->execute([$esp]);
        if (!$stmtCheck->fetch()) {
            $stmtIns->execute([$esp]);
            $insertCount++;
        }
    }
    echo "✓ Se agregaron $insertCount nuevas especialidades al catálogo cat_especialidades!\n";

    // Total en el catálogo
    $totalEsp = dbQueryOne("SELECT COUNT(*) as t FROM cat_especialidades");
    echo "✓ Total actual en cat_especialidades: " . $totalEsp['t'] . " especialidades.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
