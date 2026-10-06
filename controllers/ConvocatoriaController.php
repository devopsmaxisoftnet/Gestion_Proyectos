<?php
require_once __DIR__ . '/../models/ConvocatoriaModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';

class ConvocatoriaController {
    private $model;
    
    public function __construct() {
        $this->model = new ConvocatoriaModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'linea_negocio_id' => $_GET['linea_negocio_id'] ?? '',
            'departamento' => $_GET['departamento'] ?? '',
        ];
        
        $data = [
            'convocatorias' => $this->model->getAll($filtros),
            'stats' => $this->model->getStats(),
            'filtros' => $filtros,
        ];
        
        $usuarioModel = new UsuarioModel();
        $data['lineas_negocio'] = $usuarioModel->getLineasNegocio();
        
        $pageTitle = 'Búsqueda de licitaciones';
        $currentPage = 'convocatorias';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/convocatorias/lista_convocatorias.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/convocatorias');
            exit;
        }
        
        $usuarioModel = new UsuarioModel();
        $data = [
            'lineas_negocio' => $usuarioModel->getLineasNegocio(),
            'clientes' => (new \ClienteModel())->getAll(),
        ];
        
        $pageTitle = 'Nueva convocatoria';
        $currentPage = 'convocatorias';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/convocatorias/crear_convocatoria.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/convocatorias');
            exit;
        }
        
        $data = [
            'convocatoria' => $this->model->getById($id),
            'lineas_negocio' => (new UsuarioModel())->getLineasNegocio(),
            'clientes' => (new \ClienteModel())->getAll(),
        ];
        
        if (!$data['convocatoria']) {
            header('Location: ' . BASE_URL . '/convocatorias');
            exit;
        }
        
        $pageTitle = 'Editar convocatoria';
        $currentPage = 'convocatorias';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/convocatorias/crear_convocatoria.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/convocatorias');
        exit;
    }
    
    public function evaluar($id) {
        $convId = $id;
        $this->model->convertirAOportunidad($convId);
        header('Location: ' . BASE_URL . '/oportunidades');
        exit;
    }
    
    public function ver($id) {
        $data = [
            'convocatoria' => $this->model->getById($id),
        ];
        
        if (!$data['convocatoria']) {
            header('Location: ' . BASE_URL . '/convocatorias');
            exit;
        }
        
        $pageTitle = 'Detalle de convocatoria';
        $currentPage = 'convocatorias';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/convocatorias/ver_convocatoria.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}





