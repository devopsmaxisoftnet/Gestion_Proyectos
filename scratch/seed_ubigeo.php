<?php
// Script to seed Ubigeo Peru (Departamentos, Provincias, Distritos)
try {
    $conn = new PDO('mysql:host=127.0.0.1;dbname=gestion_comercial', 'root', '123456');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Recreate tables with relations
    $conn->exec("DROP TABLE IF EXISTS cat_distritos");
    $conn->exec("DROP TABLE IF EXISTS cat_provincias");
    $conn->exec("DROP TABLE IF EXISTS cat_departamentos");

    $conn->exec("CREATE TABLE cat_departamentos (id VARCHAR(2) PRIMARY KEY, nombre VARCHAR(100))");
    $conn->exec("CREATE TABLE cat_provincias (id VARCHAR(4) PRIMARY KEY, nombre VARCHAR(100), departamento_id VARCHAR(2))");
    $conn->exec("CREATE TABLE cat_distritos (id VARCHAR(6) PRIMARY KEY, nombre VARCHAR(100), provincia_id VARCHAR(4), departamento_id VARCHAR(2))");

    // Alter table clientes to match VARCHAR lengths
    $conn->exec("ALTER TABLE clientes MODIFY departamento_id VARCHAR(2) DEFAULT NULL");
    $conn->exec("ALTER TABLE clientes MODIFY provincia_id VARCHAR(4) DEFAULT NULL");
    $conn->exec("ALTER TABLE clientes MODIFY distrito_id VARCHAR(6) DEFAULT NULL");

    // We can fetch from a known Github repo for Peru Ubigeo
    // Repo: https://raw.githubusercontent.com/ernestorivero/ubigeo-peru/master/json/ubigeo_peru_2016_departamentos.json
    $deps_json = file_get_contents('https://raw.githubusercontent.com/ernestorivero/ubigeo-peru/master/json/ubigeo_peru_2016_departamentos.json');
    $provs_json = file_get_contents('https://raw.githubusercontent.com/ernestorivero/ubigeo-peru/master/json/ubigeo_peru_2016_provincias.json');
    $dists_json = file_get_contents('https://raw.githubusercontent.com/ernestorivero/ubigeo-peru/master/json/ubigeo_peru_2016_distritos.json');
    if ($deps_json && $provs_json && $dists_json) {
        $deps = json_parse_or_array($deps_json);
        $provs = json_parse_or_array($provs_json);
        $dists = json_parse_or_array($dists_json);

        $stmtD = $conn->prepare("INSERT INTO cat_departamentos (id, nombre) VALUES (?, ?)");
        foreach($deps as $d) { $stmtD->execute([$d['id'], $d['name']]); }

        $stmtP = $conn->prepare("INSERT INTO cat_provincias (id, nombre, departamento_id) VALUES (?, ?, ?)");
        foreach($provs as $p) { $stmtP->execute([$p['id'], $p['name'], $p['department_id']]); }

        $stmtDi = $conn->prepare("INSERT INTO cat_distritos (id, nombre, provincia_id, departamento_id) VALUES (?, ?, ?, ?)");
        foreach($dists as $di) { $stmtDi->execute([$di['id'], $di['name'], $di['province_id'], $di['department_id']]); }
        
        echo "Ubigeo seed completado!\n";
    } else {
        echo "No se pudo descargar el JSON.\n";
    }

} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}

function json_parse_or_array($json) {
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}
