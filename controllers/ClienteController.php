<?php
require_once __DIR__ . '/../models/ClienteModel.php';

class ClienteController {
    private $model;
    
    public function __construct() {
        $this->model = new ClienteModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
        ];
        
        $data = [
            'clientes' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Clientes';
        $currentPage = 'clientes';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/clientes/lista_clientes.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }
        
        $pageTitle = 'Nuevo cliente';
        $currentPage = 'clientes';
        $data = [];
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/clientes/crear_cliente.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/clientes');
            exit;
        }
        
        $data = [
            'cliente' => $this->model->getById($id),
        ];
        
        $pageTitle = 'Editar cliente';
        $currentPage = 'clientes';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/clientes/crear_cliente.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/clientes');
        exit;
    }
}





