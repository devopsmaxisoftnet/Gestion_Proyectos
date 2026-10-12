<?php
require_once __DIR__ . '/../config/database.php';

echo "=== TABLES ===\n";
$tables = dbQuery("SHOW TABLES");
foreach ($tables as $t) {
    echo current($t) . "\n";
}

echo "\n=== PROFESIONALES ===\n";
$cols = dbQuery("DESCRIBE profesionales");
foreach ($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";

echo "\n=== CONTRATOS ===\n";
$cols = dbQuery("DESCRIBE contratos");
foreach ($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";

echo "\n=== PROPUESTAS ===\n";
$cols = dbQuery("DESCRIBE propuestas");
foreach ($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ")\n";
