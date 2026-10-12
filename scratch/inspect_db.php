<?php
require_once __DIR__ . '/../config/database.php';

echo "=== COLUMNAS EN PROYECTOS ===\n";
foreach(dbQuery("DESCRIBE proyectos") as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ")\n";
}
