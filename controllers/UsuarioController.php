<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

class UsuarioController {
    private $model;
    
    public function __construct() {
        $this->model = new UsuarioModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'rol_id' => $_GET['rol_id'] ?? '',
        ];
        
        $data = [
            'usuarios' => $this->model->getAll($filtros),
            'roles' => $this->model->getRoles(),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Usuarios';
        $currentPage = 'usuarios';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/usuarios/lista_usuarios.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }
        
        $data = [
            'roles' => $this->model->getRoles(),
        ];
        
        $pageTitle = 'Nuevo usuario';
        $currentPage = 'usuarios';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/usuarios/crear_usuario.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }
        
        $data = [
            'usuario' => $this->model->getById($id),
            'roles' => $this->model->getRoles(),
        ];
        
        $pageTitle = 'Editar usuario';
        $currentPage = 'usuarios';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/usuarios/crear_usuario.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/usuarios');
        exit;
    }
}





