<?php
require_once __DIR__ . '/../models/EmpresaModel.php';

class EmpresaController {
    private $model;
    
    public function __construct() {
        $this->model = new EmpresaModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
        ];
        
        $data = [
            'empresas' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Empresas';
        $currentPage = 'empresas';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/empresas/lista_empresas.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/?page=empresas');
            exit;
        }
        
        $pageTitle = 'Nueva empresa';
        $currentPage = 'empresas';
        $data = [
            'cat_especialidades' => $this->model->getCatEspecialidades()
        ];
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/empresas/crear_empresa.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/empresas');
            exit;
        }
        
        $data = [
            'empresa' => $this->model->getById($id),
            'profesionales' => $this->model->getProfesionales($id),
            'all_profesionales' => (new \ProfesionalModel())->getAll(),
            'documentos' => $this->model->getDocumentos($id),
            'cat_especialidades' => $this->model->getCatEspecialidades(),
            'cat_tipos_profesional' => $this->model->getCatTiposProfesional(),
            'cat_tipos_documento' => $this->model->getCatTiposDocumento()
        ];
        
        $pageTitle = 'Editar empresa';
        $currentPage = 'empresas';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/empresas/crear_empresa.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/empresas');
        exit;
    }
    
    public function linkProfesional($empresa_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['profesional_id'])) {
            $this->model->linkProfesional($empresa_id, $_POST['profesional_id']);
        }
        header('Location: ' . BASE_URL . '/empresas/editar/' . $empresa_id);
        exit;
    }

    public function unlinkProfesional($prof_id) {
        if (isset($_GET['empresa_id'])) {
            $emp_id = $_GET['empresa_id'];
            $this->model->unlinkProfesional($prof_id);
            header('Location: ' . BASE_URL . '/empresas/editar/' . $emp_id);
        } else {
            header('Location: ' . BASE_URL . '/empresas');
        }
        exit;
    }

    public function addDocumento($empresa_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->createDocumento($empresa_id, $_POST);
        }
        header('Location: ' . BASE_URL . '/empresas/editar/' . $empresa_id);
        exit;
    }

    public function eliminarDocumento($doc_id) {
        if (isset($_GET['empresa_id'])) {
            $emp_id = $_GET['empresa_id'];
            $this->model->deleteDocumento($doc_id);
            header('Location: ' . BASE_URL . '/empresas/editar/' . $emp_id);
        } else {
            header('Location: ' . BASE_URL . '/empresas');
        }
        exit;
    }
    public function addCatalogItem() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipo = $_POST['tipo'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            $table = '';
            
            if ($tipo === 'especialidad') $table = 'cat_especialidades';
            elseif ($tipo === 'tipo_profesional') $table = 'cat_tipos_profesional';
            elseif ($tipo === 'tipo_documento') $table = 'cat_tipos_documento';
            
            if ($table && $nombre) {
                try {
                    dbExecute("INSERT INTO $table (nombre) VALUES (?)", [$nombre]);
                    $id = dbLastId();
                    echo json_encode(['success' => true, 'id' => $id, 'nombre' => $nombre]);
                } catch(Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
            }
        }
        exit;
    }
}
