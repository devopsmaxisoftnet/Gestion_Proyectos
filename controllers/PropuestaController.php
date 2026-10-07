<?php
require_once __DIR__ . '/../models/PropuestaModel.php';
require_once __DIR__ . '/../models/LicitacionModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class PropuestaController {
    private $model;
    
    public function __construct() {
        $this->model = new PropuestaModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estado' => $_GET['estado'] ?? '',
        ];
        
        $data = [
            'propuestas' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Propuestas';
        $currentPage = 'propuestas';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/propuestas/lista_propuestas.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/propuestas');
            exit;
        }
        
        $data = [
            'licitaciones' => (new LicitacionModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Nueva propuesta';
        $currentPage = 'propuestas';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/propuestas/crear_propuesta.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/propuestas');
            exit;
        }
        
        $data = [
            'propuesta' => $this->model->getById($id),
            'licitaciones' => (new LicitacionModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Editar propuesta';
        $currentPage = 'propuestas';
        
        $isAjax = !empty($_GET['ajax']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/propuestas/crear_propuesta.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/propuestas');
        exit;
    }
}





