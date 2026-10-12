<?php
require_once __DIR__ . '/../models/ProfesionalModel.php';
require_once __DIR__ . '/../models/EmpresaModel.php';

class ProfesionalController {
    private $model;
    
    public function __construct() {
        $this->model = new ProfesionalModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'especialidad_id' => $_GET['especialidad_id'] ?? '',
            'empresa_id' => $_GET['empresa_id'] ?? '',
        ];
        
        $data = [
            'profesionales' => $this->model->getAll($filtros),
            'especialidades' => $this->model->getEspecialidades(),
            'empresas' => (new EmpresaModel())->getAll(),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Profesionales';
        $currentPage = 'profesionales';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/profesionales/lista_profesionales.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/profesionales');
            exit;
        }
        
        $data = [
            'empresas' => (new EmpresaModel())->getAll(),
            'especialidades' => $this->model->getEspecialidades(),
            'tipos' => $this->model->getTiposProfesional(),
        ];
        
        $pageTitle = 'Nuevo Profesional';
        $currentPage = 'profesionales';
        
        $isAjax = !empty($_GET['ajax']) || !empty($_GET['modal_iframe']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/profesionales/crear_profesional.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            if (!empty($_POST['from_empresa'])) {
                header('Location: ' . BASE_URL . '/empresas/ver/' . $_POST['from_empresa']);
            } else {
                header('Location: ' . BASE_URL . '/profesionales');
            }
            exit;
        }
        
        $data = [
            'profesional' => $this->model->getById($id),
            'empresas' => (new EmpresaModel())->getAll(),
            'especialidades' => $this->model->getEspecialidades(),
            'tipos' => $this->model->getTiposProfesional(),
        ];
        
        $pageTitle = 'Editar Profesional';
        $currentPage = 'profesionales';
        
        $isAjax = !empty($_GET['ajax']) || !empty($_GET['modal_iframe']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/profesionales/crear_profesional.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/profesionales');
        exit;
    }
}
