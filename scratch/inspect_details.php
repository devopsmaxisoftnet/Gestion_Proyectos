<?php
require_once __DIR__ . '/../config/database.php';

echo "=== cat_especialidades ===\n";
$cat = dbQuery("SELECT * FROM cat_especialidades");
print_r($cat);

echo "\n=== licitaciones ===\n";
$cols = dbQuery("DESCRIBE licitaciones");
foreach ($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";

echo "\n=== ContratoModel & PropuestaModel inspect ===\n";
$contratos = dbQuery("SELECT * FROM contratos LIMIT 3");
print_r($contratos);

$propuestas = dbQuery("SELECT * FROM propuestas LIMIT 3");
print_r($propuestas);
