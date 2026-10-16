<?php
require_once __DIR__ . '/../models/ProyectoModel.php';
require_once __DIR__ . '/../models/ContratoModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class ProyectoController {
    private $model;
    
    public function __construct() {
        PermisosHelper::verificarAcceso('proyectos');
        $this->model = new ProyectoModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estado' => $_GET['estado'] ?? '',
        ];
        
        $data = [
            'proyectos' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Proyectos';
        $currentPage = 'proyectos';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/proyectos/lista_proyectos.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/proyectos');
            exit;
        }
        
        $data = [
            'contratos' => (new ContratoModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Nuevo proyecto';
        $currentPage = 'proyectos';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/proyectos/crear_proyecto.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/proyectos');
            exit;
        }
        
        $data = [
            'proyecto' => $this->model->getById($id),
            'contratos' => (new ContratoModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Editar proyecto';
        $currentPage = 'proyectos';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/proyectos/crear_proyecto.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRol = $_SESSION['user']['rol'] ?? 'usuario';
        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $_SESSION['flash_error'] = 'Acceso denegado: Se requieren permisos de Administrador.';
            header('Location: ' . BASE_URL . '/proyectos');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/proyectos');
            exit;
        }

        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/proyectos');
        exit;
    }
}





