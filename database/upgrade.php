<?php
/**
 * Script para actualizar la base de datos de Producción
 * Ejecuta los archivos SQL pendientes.
 */
require __DIR__ . '/../config/database.php';

// Bloqueo de seguridad: Solo permitir ejecución desde CLI o por un Superadministrador autenticado
if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (empty($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'superadmin') {
        http_response_code(403);
        die('Acceso denegado: Este script de actualización solo puede ejecutarse desde la consola de comandos (CLI) o por un Superadministrador autenticado.');
    }
}

try {
    $conn = getConnection();

    $archivos_sql = [
        __DIR__ . '/update_roles_areas.sql',
        __DIR__ . '/update_convocatorias_cronograma_perfiles.sql',
        __DIR__ . '/cleanup_duplicate_clientes.sql',
        __DIR__ . '/clean_duplicate_lineas_and_roles.sql',
        __DIR__ . '/migration_org_security.sql',
        __DIR__ . '/migration_fase2_fase3.sql',
        __DIR__ . '/migration_fase3_fase4_catalogos.sql',
        __DIR__ . '/migration_costeo_actividades.sql'
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
