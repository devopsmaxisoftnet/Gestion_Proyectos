<?php
require_once __DIR__ . '/../config/database.php';

$existingCols = [];
foreach(dbQuery("DESCRIBE oportunidad_evaluaciones") as $c) {
    $existingCols[] = $c['Field'];
}

$colsToAdd = [
    'req_condiciones' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN req_condiciones TINYINT(1) DEFAULT 0 AFTER req_cronograma",
    'doc_constancia_inscripcion' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN doc_constancia_inscripcion VARCHAR(255) NULL AFTER doc_propuesta",
    'consultas_observaciones' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN consultas_observaciones TEXT NULL AFTER consolidacion_informe",
    'seguimiento_legal' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN seguimiento_legal TEXT NULL AFTER revision_economica",
    'doc_apelacion' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN doc_apelacion VARCHAR(255) NULL AFTER doc_subsanacion",
    'apelacion_sustento' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN apelacion_sustento TEXT NULL AFTER seguimiento_legal",
    'perfeccionamiento_retencion' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN perfeccionamiento_retencion VARCHAR(100) NULL",
    'num_cta_cte' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN num_cta_cte VARCHAR(100) NULL",
    'dni_representante_ruc' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN dni_representante_ruc VARCHAR(100) NULL",
    'doc_desagregados' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN doc_desagregados VARCHAR(255) NULL",
    'doc_equipamiento' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN doc_equipamiento VARCHAR(255) NULL",
    'doc_carta_fianza' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN doc_carta_fianza VARCHAR(255) NULL",
    'proyecto_id' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN proyecto_id INT NULL",
    'doc_req_legales' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN doc_req_legales VARCHAR(255) NULL",
    'doc_req_cronograma' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN doc_req_cronograma VARCHAR(255) NULL",
    'doc_req_condiciones' => "ALTER TABLE oportunidad_evaluaciones ADD COLUMN doc_req_condiciones VARCHAR(255) NULL"
];

foreach ($colsToAdd as $col => $sql) {
    if (!in_array($col, $existingCols)) {
        dbExecute($sql);
        echo "Agregada columna $col\n";
    } else {
        echo "Columna $col ya existe\n";
    }
}

echo "Migración BPMN completada con éxito.\n";
