<?php
$conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql1 = "ALTER TABLE oportunidades MODIFY COLUMN estado ENUM('detectada','en_evaluacion','participar','no_participar','en_propuesta','adjudicada','no_adjudicada','cerrada', 'identificada','evaluacion_requisitos','viabilidad_preliminar','evaluacion_especializada','aprobacion_gerencia','preparacion_propuesta','presentada','descartada');";
$conn->exec($sql1);

$sql2 = "UPDATE oportunidades SET estado = 'identificada' WHERE estado = 'detectada';
UPDATE oportunidades SET estado = 'evaluacion_especializada' WHERE estado = 'en_evaluacion';
UPDATE oportunidades SET estado = 'preparacion_propuesta' WHERE estado = 'participar' OR estado = 'en_propuesta';
UPDATE oportunidades SET estado = 'descartada' WHERE estado = 'no_participar' OR estado = 'cerrada';";
$conn->exec($sql2);

$sql3 = "ALTER TABLE oportunidades MODIFY COLUMN estado ENUM(
        'identificada',
        'evaluacion_requisitos',
        'viabilidad_preliminar',
        'evaluacion_especializada',
        'aprobacion_gerencia',
        'preparacion_propuesta',
        'presentada',
        'adjudicada',
        'no_adjudicada',
        'descartada'
    ) DEFAULT 'identificada';";
$conn->exec($sql3);

echo "Estados corregidos\n";
