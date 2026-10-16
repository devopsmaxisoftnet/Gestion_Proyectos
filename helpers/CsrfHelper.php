<?php
/**
 * Helper de Protección contra Ataques CSRF (Cross-Site Request Forgery)
 * Gestión de Proyectos - Oportunidades y Contratos
 */
class CsrfHelper {
    
    /**
     * Obtiene el token CSRF actual de la sesión o genera uno nuevo criptográficamente seguro
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Alias de getToken()
     */
    public static function generarToken() {
        return self::getToken();
    }

    /**
     * Genera un campo HTML oculto con el token CSRF para incrustar en formularios
     */
    public static function getField() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Valida el token CSRF recibido contra el token de la sesión activa
     */
    public static function validateToken($token = null) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken)) {
            return false;
        }

        if ($token === null) {
            $token = $_POST['csrf_token']
                ?? $_GET['csrf_token']
                ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }

        if (empty($token) || !is_string($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
