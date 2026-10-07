<?php require __DIR__ . '/../config/database.php'; foreach(dbQuery('SELECT id, nombre, estado FROM oportunidades') as $r) echo $r['id'] . ' - ' . $r['nombre'] . ' - ' . $r['estado'] . "\n";
