<?php
require_once __DIR__ . '/../models/ContratoModel.php';
require_once __DIR__ . '/../models/OportunidadModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class ContratoController {
    private $model;
    
    public function __construct() {
        $this->model = new ContratoModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estado' => $_GET['estado'] ?? '',
        ];
        
        $data = [
            'contratos' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Contratos';
        $currentPage = 'contratos';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/contratos/lista_contratos.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/contratos');
            exit;
        }
        
        $data = [
            'oportunidades' => (new OportunidadModel())->getAll(),
            'clientes' => (new ClienteModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Nuevo contrato';
        $currentPage = 'contratos';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/contratos/crear_contrato.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/contratos');
            exit;
        }
        
        $data = [
            'contrato' => $this->model->getById($id),
            'oportunidades' => (new OportunidadModel())->getAll(),
            'clientes' => (new ClienteModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Editar contrato';
        $currentPage = 'contratos';
        
        $isAjax = !empty($_GET['ajax']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/contratos/crear_contrato.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/contratos');
        exit;
    }
}





