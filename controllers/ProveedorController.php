<?php
require_once __DIR__ . '/../models/ProveedorModel.php';

class ProveedorController {
    private $model;
    
    public function __construct() {
        $this->model = new ProveedorModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
        ];
        
        $data = [
            'proveedores' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Proveedores';
        $currentPage = 'proveedores';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/proveedores/lista_proveedores.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/proveedores');
            exit;
        }
        
        $pageTitle = 'Nuevo proveedor';
        $currentPage = 'proveedores';
        $data = [];
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/proveedores/crear_proveedor.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/proveedores');
            exit;
        }
        
        $data = [
            'proveedor' => $this->model->getById($id),
        ];
        
        $pageTitle = 'Editar proveedor';
        $currentPage = 'proveedores';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/proveedores/crear_proveedor.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/proveedores');
        exit;
    }
}





