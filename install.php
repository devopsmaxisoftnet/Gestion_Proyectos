<?php
/**
 * Script de instalación - Ejecutar una vez para crear la BD y tablas
 */

// Primero crear la base de datos
try {
    $pdo = new PDO("mysql:host=localhost", "root", "123456", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gestion_comercial CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos 'gestion_comercial' creada correctamente.<br>";
} catch (PDOException $e) {
    die("❌ Error creando la base de datos: " . $e->getMessage());
}

// Conectar a la BD
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_comercial;charset=utf8mb4", "root", "123456", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Conectado a 'gestion_comercial'.<br>";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// Leer el schema
$schemaFile = __DIR__ . '/database/schema.sql';
if (!file_exists($schemaFile)) {
    die("❌ No se encontró el archivo schema.sql");
}

$sql = file_get_contents($schemaFile);

// Eliminar líneas de CREATE DATABASE y USE
$sql = preg_replace('/^CREATE DATABASE.*$/mi', '', $sql);
$sql = preg_replace('/^USE\s+\w+\s*;/mi', '', $sql);

// Eliminar comentarios de línea
$sql = preg_replace('/^--.*$/m', '', $sql);

// Separar por punto y coma
$statements = explode(';', $sql);

$errors = [];
$success = 0;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    
    try {
        $pdo->exec($stmt);
        $success++;
    } catch (PDOException $e) {
        $code = $e->getCode();
        // Ignorar "tabla ya existe" (1050) y "entrada duplicada" (1062)
        if ($code == '42S01' || $code == '23000' || strpos($e->getMessage(), '1062') !== false || strpos($e->getMessage(), '1050') !== false) {
            $success++;
            continue;
        }
        $errors[] = $e->getMessage();
    }
}

echo "✅ Se ejecutaron <strong>$success</strong> sentencias SQL correctamente.<br>";

if (!empty($errors)) {
    echo "<br>⚠️ Errores encontrados (" . count($errors) . "):<br>";
    foreach ($errors as $err) {
        echo "<div style='padding:8px;margin:4px 0;background:#fee2e2;border-radius:6px;font-size:12px'>" . htmlspecialchars($err) . "</div>";
    }
} else {
    echo "✅ ¡Todas las tablas y datos de demostración fueron creados sin errores!<br>";
}

echo "<br><br>";
echo "<a href='/PROYECTO/' style='padding:12px 24px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:white;border-radius:10px;text-decoration:none;font-weight:600;font-family:Inter,sans-serif;display:inline-block'>🚀 Ir al sistema</a>";
echo "<br><br><small style='color:#888'>Puedes eliminar este archivo (install.php) después de la instalación.</small>";
