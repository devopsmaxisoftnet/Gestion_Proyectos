<?php
/**
 * Controlador de Catálogos Maestros (materiales, equipos, proveedores).
 * Se opera desde las pestañas del módulo de Compras; aquí solo viven los endpoints JSON.
 * Seguridad: mismos permisos del módulo 'compras' + token CSRF en cada escritura.
 */
require_once __DIR__ . '/../models/CatalogoModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class CatalogoController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        PermisosHelper::verificarAcceso('compras');
        $this->model = new CatalogoModel();
    }

    public function index() {
        header('Location: ' . BASE_URL . '/compras#materiales');
        exit;
    }

    /**
     * Crea o actualiza un registro (POST: tipo, id, + campos del formulario).
     */
    public function guardar($id = 0) {
        $tipo = (string)($_POST['tipo'] ?? '');
        $registroId = (int)($_POST['id'] ?? 0);
        $this->exigirEscritura($registroId > 0 ? 'editar' : 'crear');

        try {
            $item = $this->model->guardar($tipo, $registroId, $_POST);
            $this->json(['success' => true, 'message' => $registroId > 0 ? 'Registro actualizado.' : 'Registro creado.', 'item' => $item]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Activa / desactiva un registro (POST: tipo, id, activo).
     */
    public function estado($id = 0) {
        $this->exigirEscritura('editar');

        try {
            $this->model->cambiarEstado((string)($_POST['tipo'] ?? ''), (int)($_POST['id'] ?? 0), !empty($_POST['activo']));
            $this->json(['success' => true, 'message' => 'Estado actualizado.']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Solo POST + permiso + CSRF; responde JSON y corta si algo falla. */
    private function exigirEscritura(string $accion): void {
        PermisosHelper::requirePermiso($accion, 'compras');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido.'], 405);
        }
        if (!CsrfHelper::validateToken()) {
            $this->json(['success' => false, 'message' => 'Token de seguridad inválido o expirado. Recarga la página.'], 403);
        }
    }

    private function json(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }
}
