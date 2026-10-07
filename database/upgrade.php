<?php
/**
 * Script para actualizar la base de datos de Producción
 * Ejecuta los archivos SQL pendientes.
 */
require __DIR__ . '/../config/database.php';

try {
    $conn = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $archivos_sql = [
        __DIR__ . '/update_roles_areas.sql',
        __DIR__ . '/update_convocatorias_cronograma_perfiles.sql',
        __DIR__ . '/cleanup_duplicate_clientes.sql',
        __DIR__ . '/clean_duplicate_lineas_and_roles.sql'
    ];

    echo "<h1>Actualización de Base de Datos</h1>";
    echo "<ul>";

    foreach ($archivos_sql as $file) {
        if (file_exists($file)) {
            $sql = file_get_contents($file);
            // Ejecutar el script SQL completo
            // PDO exec puede ejecutar múltiples sentencias si el servidor lo permite
            try {
                $conn->exec($sql);
                echo "<li><span style='color:green'>Éxito</span>: " . basename($file) . " aplicado correctamente.</li>";
            } catch (Exception $e) {
                echo "<li><span style='color:orange'>Aviso/Error</span>: " . basename($file) . " - " . $e->getMessage() . "</li>";
            }
        } else {
            echo "<li><span style='color:red'>No encontrado</span>: " . basename($file) . "</li>";
        }
    }

    echo "</ul>";
    echo "<p><strong>¡Actualización terminada!</strong> Ya puedes regresar al sistema.</p>";

} catch (Exception $e) {
    echo "<h2>Error de conexión:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
