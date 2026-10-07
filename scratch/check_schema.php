<?php
require __DIR__ . '/../config/database.php';
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
echo "=== EMPRESAS ===\n";
foreach($pdo->query('DESCRIBE empresas') as $r) echo $r['Field'].' | '.$r['Type']."\n";
echo "\n=== CONVOCATORIAS ===\n";
foreach($pdo->query('DESCRIBE convocatorias') as $r) echo $r['Field'].' | '.$r['Type']."\n";
echo "\n=== LICITACIONES ===\n";
foreach($pdo->query('DESCRIBE licitaciones') as $r) echo $r['Field'].' | '.$r['Type']."\n";
