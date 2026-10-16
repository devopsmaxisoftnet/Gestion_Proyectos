<?php
require_once __DIR__ . '/../models/LicitacionModel.php';
require_once __DIR__ . '/../models/OportunidadModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class LicitacionController {
    private $model;
    
    public function __construct() {
        PermisosHelper::verificarAcceso('licitaciones');
        $this->model = new LicitacionModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estado' => $_GET['estado'] ?? '',
        ];
        
        $data = [
            'licitaciones' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Licitaciones';
        $currentPage = 'licitaciones';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/licitaciones/lista_licitaciones.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/licitaciones');
            exit;
        }
        
        $data = [
            'oportunidades' => (new OportunidadModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Nueva licitación';
        $currentPage = 'licitaciones';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/licitaciones/crear_licitacion.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/licitaciones');
            exit;
        }
        
        $data = [
            'licitacion' => $this->model->getById($id),
            'oportunidades' => (new OportunidadModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Editar licitación';
        $currentPage = 'licitaciones';
        
        $isAjax = !empty($_GET['ajax']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/licitaciones/crear_licitacion.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRol = $_SESSION['user']['rol'] ?? 'usuario';
        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $_SESSION['flash_error'] = 'Acceso denegado: Se requieren permisos de Administrador.';
            header('Location: ' . BASE_URL . '/licitaciones');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/licitaciones');
            exit;
        }

        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/licitaciones');
        exit;
    }
}





