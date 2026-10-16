<?php
require_once __DIR__ . '/../models/PropuestaModel.php';
require_once __DIR__ . '/../models/LicitacionModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class PropuestaController {
    private $model;
    
    public function __construct() {
        PermisosHelper::verificarAcceso('propuestas');
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

            $uploadedTec = null;
            $uploadedEco = null;

            require_once __DIR__ . '/../helpers/FileSecurityHelper.php';

            if (!empty($_FILES['archivo_tecnica']['name']) && $_FILES['archivo_tecnica']['error'] === UPLOAD_ERR_OK) {
                $upTec = FileSecurityHelper::validateAndUpload($_FILES['archivo_tecnica'], $upload_dir, ['prefix' => 'tec']);
                if ($upTec['success']) {
                    $data['archivo_tecnica'] = $upTec['path'];
                    $uploadedTec = ['path' => $upTec['path'], 'name' => $upTec['orig_name']];
                } else {
                    error_log("Upload técnica rechazado: " . $upTec['error']);
                }
            }
            if (!empty($_FILES['archivo_economica']['name']) && $_FILES['archivo_economica']['error'] === UPLOAD_ERR_OK) {
                $upEco = FileSecurityHelper::validateAndUpload($_FILES['archivo_economica'], $upload_dir, ['prefix' => 'eco']);
                if ($upEco['success']) {
                    $data['archivo_economica'] = $upEco['path'];
                    $uploadedEco = ['path' => $upEco['path'], 'name' => $upEco['orig_name']];
                } else {
                    error_log("Upload económica rechazado: " . $upEco['error']);
                }
            }

            $nuevoId = $this->model->create($data);

            if ($nuevoId && ($uploadedTec || $uploadedEco)) {
                $createdProp = $this->model->getById($nuevoId);
                $propCode = !empty($createdProp['codigo']) ? $createdProp['codigo'] : ('PROP-' . $nuevoId);
                try {
                    require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';
                    if ($uploadedTec) {
                        GoogleDriveHelper::uploadSystemFile($uploadedTec['path'], $uploadedTec['name'], 'Propuestas', $propCode);
                    }
                    if ($uploadedEco) {
                        GoogleDriveHelper::uploadSystemFile($uploadedEco['path'], $uploadedEco['name'], 'Propuestas', $propCode);
                    }
                } catch (\Exception $e) {}
            }

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

            $prop = $this->model->getById($id);
            $propCode = !empty($prop['codigo']) ? $prop['codigo'] : ('PROP-' . $id);

            require_once __DIR__ . '/../helpers/FileSecurityHelper.php';
            require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';

            if (!empty($_FILES['archivo_tecnica']['name']) && $_FILES['archivo_tecnica']['error'] === UPLOAD_ERR_OK) {
                $upTec = FileSecurityHelper::validateAndUpload($_FILES['archivo_tecnica'], $upload_dir, ['prefix' => 'tec']);
                if ($upTec['success']) {
                    $data['archivo_tecnica'] = $upTec['path'];
                    try {
                        GoogleDriveHelper::uploadSystemFile($upTec['path'], $upTec['orig_name'], 'Propuestas', $propCode);
                    } catch (\Exception $e) {}
                } else {
                    error_log("Upload técnica rechazado en editar: " . $upTec['error']);
                }
            }
            if (!empty($_FILES['archivo_economica']['name']) && $_FILES['archivo_economica']['error'] === UPLOAD_ERR_OK) {
                $upEco = FileSecurityHelper::validateAndUpload($_FILES['archivo_economica'], $upload_dir, ['prefix' => 'eco']);
                if ($upEco['success']) {
                    $data['archivo_economica'] = $upEco['path'];
                    try {
                        GoogleDriveHelper::uploadSystemFile($upEco['path'], $upEco['orig_name'], 'Propuestas', $propCode);
                    } catch (\Exception $e) {}
                } else {
                    error_log("Upload económica rechazado en editar: " . $upEco['error']);
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
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRol = $_SESSION['user']['rol'] ?? 'usuario';
        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $_SESSION['flash_error'] = 'Acceso denegado: Se requieren permisos de Administrador.';
            header('Location: ' . BASE_URL . '/propuestas');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/propuestas');
            exit;
        }

        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/propuestas');
        exit;
    }
}
