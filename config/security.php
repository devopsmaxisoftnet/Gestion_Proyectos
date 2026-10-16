<?php
/**
 * Configuración de Seguridad del Sistema
 * Gestión de Proyectos - Oportunidades y Contratos
 */

// ── 1. Restricción de Dominio Corporativo para Administradores ──
// Si se define un dominio (ej. 'maxisoftnet.com'), solo correos con ese dominio
// o que estén en la lista blanca podrán acceder al panel administrativo vía Google OAuth.
// Deje vacío '' si no desea forzar un único dominio empresarial.
define('ALLOWED_ADMIN_DOMAIN', getenv('ALLOWED_ADMIN_DOMAIN') ?: '');

// ── 2. Lista Blanca de Correos de Administradores Autorizados ──
// Únicamente estas dos cuentas tienen autorización para ingresar como Administrador al ERP.
define('ALLOWED_ADMIN_EMAILS', [
    'devops.maxisoftnet@gmail.com',
    'asconsultti@gmail.com',
]);

// ── 3. Parámetros de Seguridad para Subida de Archivos ──
define('MAX_UPLOAD_FILE_SIZE', 25 * 1024 * 1024); // 25 MB en bytes

define('ALLOWED_FILE_EXTENSIONS', [
    // Documentos de oficina
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
    // Imágenes
    'jpg', 'jpeg', 'png', 'webp',
    // Archivos comprimidos
    'zip', 'rar'
]);

// ── 4. Cabeceras y Sesiones Seguras ──
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        @ini_set('session.cookie_secure', '1');
    }
}
