<?php
$conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
foreach($conn->query('DESCRIBE profesionales')->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo $r['Field'].' | '.$r['Type']."\n";
}
