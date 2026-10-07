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
            $perfiles = [];
            if (!empty($_POST['perfil_nombre']) && is_array($_POST['perfil_nombre'])) {
                foreach ($_POST['perfil_nombre'] as $idx => $pNombre) {
                    if (!empty(trim($pNombre))) {
                        $perfiles[] = [
                            'nombre' => trim($pNombre),
                            'tipo' => $_POST['perfil_tipo'][$idx] ?? 'Profesional clave',
                            'cantidad' => max(1, (int)($_POST['perfil_cantidad'][$idx] ?? 1))
                        ];
                    }
                }
            }
            $_POST['perfiles'] = $perfiles;
            
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/convocatorias');
            exit;
        }
        
        $usuarioModel = new UsuarioModel();
        $data = [
            'lineas_negocio' => $usuarioModel->getLineasNegocio(),
            'clientes' => (new \ClienteModel())->getAll(),
            'catalogo_perfiles' => $this->model->getCatalogoPerfiles(),
        ];
        
        $pageTitle = 'Nueva convocatoria';
        $currentPage = 'convocatorias';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/convocatorias/crear_convocatoria.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $perfiles = [];
            if (!empty($_POST['perfil_nombre']) && is_array($_POST['perfil_nombre'])) {
                foreach ($_POST['perfil_nombre'] as $idx => $pNombre) {
                    if (!empty(trim($pNombre))) {
                        $perfiles[] = [
                            'nombre' => trim($pNombre),
                            'tipo' => $_POST['perfil_tipo'][$idx] ?? 'Profesional clave',
                            'cantidad' => max(1, (int)($_POST['perfil_cantidad'][$idx] ?? 1))
                        ];
                    }
                }
            }
            $_POST['perfiles'] = $perfiles;
            
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/convocatorias');
            exit;
        }
        
        $data = [
            'convocatoria' => $this->model->getById($id),
            'lineas_negocio' => (new UsuarioModel())->getLineasNegocio(),
            'clientes' => (new \ClienteModel())->getAll(),
            'catalogo_perfiles' => $this->model->getCatalogoPerfiles(),
            'perfiles' => $this->model->getPerfilesByConvocatoria($id),
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

    public function agregar_perfil_catalogo() {
        header('Content-Type: application/json');
        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'Nombre requerido']);
            exit;
        }
        $res = $this->model->addPerfilCatalogo($nombre);
        echo json_encode(['success' => $res, 'nombre' => $nombre]);
        exit;
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





