<?php
require_once __DIR__ . '/../models/OportunidadModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';

class OportunidadController {
    private $model;
    
    public function __construct() {
        $this->model = new OportunidadModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'linea_negocio_id' => $_GET['linea_negocio_id'] ?? '',
        ];
        
        $data = [
            'oportunidades' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Bandeja de oportunidades';
        $currentPage = 'oportunidades';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/oportunidades/lista_oportunidades.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        
        $data = [
            'lineas_negocio' => (new UsuarioModel())->getLineasNegocio(),
            'clientes' => (new ClienteModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Nueva oportunidad';
        $currentPage = 'oportunidades';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/oportunidades/crear_oportunidad.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        
        $data = [
            'oportunidad' => $this->model->getById($id),
            'lineas_negocio' => (new UsuarioModel())->getLineasNegocio(),
            'clientes' => (new ClienteModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        if (!$data['oportunidad']) {
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        
        $pageTitle = 'Editar oportunidad';
        $currentPage = 'oportunidades';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/oportunidades/crear_oportunidad.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/oportunidades');
        exit;
    }
    
    public function ver($id) {
        $data = [
            'oportunidad' => $this->model->getById($id),
        ];
        
        if (!$data['oportunidad']) {
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        
        $pageTitle = 'Detalle de oportunidad';
        $currentPage = 'oportunidades';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/oportunidades/ver_oportunidad.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}





