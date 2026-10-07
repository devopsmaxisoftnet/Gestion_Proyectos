<?php require __DIR__ . '/../config/database.php'; foreach(dbQuery('DESCRIBE oportunidad_evaluaciones') as $r) echo $r['Field']." | ".$r['Type']."\n";
