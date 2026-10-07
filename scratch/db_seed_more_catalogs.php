<?php
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // More Especialidades
    $especialidades = ['Hidráulica', 'Geotecnia', 'Estructuras', 'Telecomunicaciones', 'Minería', 'Petróleo y Gas', 'Medio Ambiente', 'Seguridad y Salud Ocupacional', 'Mantenimiento Vial', 'Obras de Arte', 'Movimiento de Tierras', 'BIM / VDC', 'Control de Calidad', 'Gestión de Proyectos', 'Urbanismo', 'Puentes'];
    $stmtE = $conn->prepare("INSERT IGNORE INTO cat_especialidades (nombre) VALUES (?)");
    foreach($especialidades as $e) $stmtE->execute([$e]);

    // More Tipos Profesional
    $tipos_prof = ['Ingeniero Agrícola', 'Ingeniero Forestal', 'Ingeniero Ambiental', 'Ingeniero Industrial', 'Ingeniero Químico', 'Especialista en Suelos', 'Especialista en Costos', 'Gerente de Proyecto', 'Asistente de Obra', 'Prevencionista', 'Abogado', 'Contador'];
    $stmtP = $conn->prepare("INSERT IGNORE INTO cat_tipos_profesional (nombre) VALUES (?)");
    foreach($tipos_prof as $t) $stmtP->execute([$t]);

    // More Tipos Documento
    $tipos_doc = ['Declaración Jurada de Domicilio', 'Ficha RUC', 'DNI del Representante Legal', 'Carta Fianza', 'Póliza CAR', 'Póliza TREC', 'Póliza SCTR', 'Certificado de Trabajo', 'Título Profesional', 'Colegiatura', 'Certificado de Habilidad', 'Declaración Anual Sunat', 'Estados Financieros'];
    $stmtD = $conn->prepare("INSERT IGNORE INTO cat_tipos_documento (nombre) VALUES (?)");
    foreach($tipos_doc as $d) $stmtD->execute([$d]);

    echo "Más opciones añadidas a los catálogos.\n";
} catch(PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
