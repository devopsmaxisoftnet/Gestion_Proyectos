<?php
/**
 * Configuración de Base de Datos
 * Gestión Comercial - Oportunidades y Contratos
 */

$_isLocal = (php_sapi_name() === 'cli' || (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)));

if ($_isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '123456');
    define('DB_NAME', 'gestion_comercial');
    define('BASE_URL', '/PROYECTO');
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'mnxsrokm_user_GestComercial');
    define('DB_PASS', '}(ep9=-.R9KKkj__');
    define('DB_NAME', 'mnxsrokm_comercial');
    define('BASE_URL', '');
}

define('DB_CHARSET', 'utf8mb4');

// ── Google OAuth ────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     '963879865677-9oirqdfcrng44os4nghjq9pjd12i2r4v.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'TU_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  $_isLocal
    ? 'http://localhost/PROYECTO/auth/google/callback'
    : 'https://monitoreo.maxisoftnet.com/auth/google/callback'
);

// Conexión PDO
function getConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    return $pdo;
}

/**
 * Helper: Ejecutar query SELECT
 */
function dbQuery($sql, $params = []) {
    $stmt = getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Helper: Ejecutar query SELECT uno
 */
function dbQueryOne($sql, $params = []) {
    $stmt = getConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Helper: Ejecutar INSERT/UPDATE/DELETE
 */
function dbExecute($sql, $params = []) {
    $stmt = getConnection()->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Helper: Obtener último ID insertado
 */
function dbLastId() {
    return getConnection()->lastInsertId();
}

/**
 * Helper: Formatear moneda
 */
function formatMoney($amount, $currency = 'S/') {
    return $currency . ' ' . number_format($amount, 0, '.', ',');
}

/**
 * Helper: Formatear fecha
 */
function formatDate($date) {
    if (!$date) return '-';
    $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];
    $d = new DateTime($date);
    return $d->format('d') . ' ' . $months[(int)$d->format('m') - 1] . '. ' . $d->format('Y');
}

/**
 * Helper: Obtener clase CSS de estado
 */
function getEstadoClass($estado) {
    $classes = [
        'detectada' => 'badge-detected',
        'en_revision' => 'badge-review',
        'convertida' => 'badge-converted',
        'descartada' => 'badge-discarded',
        'en_evaluacion' => 'badge-evaluation',
        'participar' => 'badge-participate',
        'no_participar' => 'badge-nopart',
        'en_propuesta' => 'badge-proposal',
        'adjudicada' => 'badge-awarded',
        'no_adjudicada' => 'badge-notawarded',
        'cerrada' => 'badge-closed',
        'en_preparacion' => 'badge-preparation',
        'revisada' => 'badge-reviewed',
        'presentada' => 'badge-submitted',
        'ganada' => 'badge-won',
        'perdida' => 'badge-lost',
        'borrador' => 'badge-draft',
        'enviada' => 'badge-sent',
        'aceptada' => 'badge-accepted',
        'rechazada' => 'badge-rejected',
        'en_elaboracion' => 'badge-preparation',
        'firmado' => 'badge-signed',
        'en_ejecucion' => 'badge-execution',
        'finalizado' => 'badge-finished',
        'resuelto' => 'badge-resolved',
        'planificacion' => 'badge-planning',
        'suspendido' => 'badge-suspended',
        'liquidado' => 'badge-settled',
        'aprobada' => 'badge-approved',
        'pagada' => 'badge-paid',
    ];
    return $classes[$estado] ?? 'badge-default';
}

function getEstadoLabel($estado) {
    $labels = [
        'detectada' => 'Detectada',
        'en_revision' => 'En revisión',
        'convertida' => 'Convertida',
        'descartada' => 'Descartada',
        'en_evaluacion' => 'En evaluación',
        'participar' => 'Participar',
        'no_participar' => 'No participar',
        'en_propuesta' => 'En propuesta',
        'adjudicada' => 'Adjudicada',
        'no_adjudicada' => 'No adjudicada',
        'cerrada' => 'Cerrada',
        'en_preparacion' => 'En preparación',
        'revisada' => 'Revisada',
        'presentada' => 'Presentada',
        'ganada' => 'Ganada',
        'perdida' => 'Perdida',
        'borrador' => 'Borrador',
        'en_revision' => 'En revisión',
        'enviada' => 'Enviada',
        'aceptada' => 'Aceptada',
        'rechazada' => 'Rechazada',
        'en_elaboracion' => 'En elaboración',
        'firmado' => 'Firmado',
        'en_ejecucion' => 'En ejecución',
        'finalizado' => 'Finalizado',
        'resuelto' => 'Resuelto',
        'planificacion' => 'Planificación',
        'suspendido' => 'Suspendido',
        'liquidado' => 'Liquidado',
        'aprobada' => 'Aprobada',
        'pagada' => 'Pagada',
    ];
    return $labels[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}
