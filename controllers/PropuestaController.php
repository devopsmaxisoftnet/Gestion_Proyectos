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
            $data = $_POST;
            $upload_dir = 'uploads/propuestas/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (!empty($_FILES['archivo_tecnica']['name']) && $_FILES['archivo_tecnica']['error'] === UPLOAD_ERR_OK) {
                $file = time() . '_tec_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['archivo_tecnica']['name']));
                if (move_uploaded_file($_FILES['archivo_tecnica']['tmp_name'], $upload_dir . $file)) {
                    $data['archivo_tecnica'] = $upload_dir . $file;
                }
            }
            if (!empty($_FILES['archivo_economica']['name']) && $_FILES['archivo_economica']['error'] === UPLOAD_ERR_OK) {
                $file = time() . '_eco_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['archivo_economica']['name']));
                if (move_uploaded_file($_FILES['archivo_economica']['tmp_name'], $upload_dir . $file)) {
                    $data['archivo_economica'] = $upload_dir . $file;
                }
            }

            $this->model->create($data);
            header('Location: ' . BASE_URL . '/propuestas');
            exit;
        }
        
        $data = [
            'licitaciones' => (new LicitacionModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Nueva propuesta';
        $currentPage = 'propuestas';
        
        $isAjax = !empty($_GET['ajax']) || !empty($_GET['modal_iframe']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/propuestas/crear_propuesta.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $upload_dir = 'uploads/propuestas/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (!empty($_FILES['archivo_tecnica']['name']) && $_FILES['archivo_tecnica']['error'] === UPLOAD_ERR_OK) {
                $file = time() . '_tec_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['archivo_tecnica']['name']));
                if (move_uploaded_file($_FILES['archivo_tecnica']['tmp_name'], $upload_dir . $file)) {
                    $data['archivo_tecnica'] = $upload_dir . $file;
                }
            }
            if (!empty($_FILES['archivo_economica']['name']) && $_FILES['archivo_economica']['error'] === UPLOAD_ERR_OK) {
                $file = time() . '_eco_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['archivo_economica']['name']));
                if (move_uploaded_file($_FILES['archivo_economica']['tmp_name'], $upload_dir . $file)) {
                    $data['archivo_economica'] = $upload_dir . $file;
                }
            }

            $this->model->update($id, $data);
            if (!empty($_POST['modal_iframe'])) {
                echo "<script>if(window.parent&&window.parent.cerrarGlobalModal){window.parent.location.reload();}else{window.location.href='" . BASE_URL . "/propuestas';}</script>";
                exit;
            }
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
        
        $isAjax = !empty($_GET['ajax']) || !empty($_GET['modal_iframe']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/propuestas/crear_propuesta.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }

    public function ficha($id) {
        $propuesta = $this->model->getById($id);
        if (!$propuesta) {
            header('Location: ' . BASE_URL . '/propuestas');
            exit;
        }

        $empresa = dbQueryOne("SELECT * FROM empresas LIMIT 1");
        $data = [
            'propuesta' => $propuesta,
            'empresa' => $empresa
        ];

        $pageTitle = 'Ficha de Propuesta ' . $propuesta['codigo'];
        $currentPage = 'propuestas';

        $isAjax = !empty($_GET['ajax']) || !empty($_GET['modal_iframe']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/propuestas/ficha_propuesta.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/propuestas');
        exit;
    }
}
