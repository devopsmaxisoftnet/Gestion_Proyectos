<?php
require_once __DIR__ . '/../models/ContratoModel.php';
require_once __DIR__ . '/../models/OportunidadModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class ContratoController {
    private $model;
    
    public function __construct() {
        PermisosHelper::verificarAcceso('contratos');
        $this->model = new ContratoModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'tipo_gestion' => $_GET['tipo_gestion'] ?? '',
            'anio' => $_GET['anio'] ?? '',
        ];
        
        $data = [
            'contratos' => $this->model->getAll($filtros),
            'totales' => $this->model->getTotalesGenerales($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Contratos y Valorizaciones';
        $currentPage = 'contratos';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/contratos/lista_contratos.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    public function ver($id) {
        $contrato = $this->model->getById($id);
        if (!$contrato) {
            header('Location: ' . BASE_URL . '/contratos');
            exit;
        }

        $data = [
            'contrato' => $contrato,
        ];

        $pageTitle = 'Detalle de Contrato - ' . ($contrato['nombre_corto'] ?: $contrato['codigo']);
        $currentPage = 'contratos';
        
        $isAjax = !empty($_GET['modal_iframe']) || !empty($_GET['ajax']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/contratos/ver_contrato.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nuevoId = $this->model->create($_POST);
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
        
        $isAjax = !empty($_GET['modal_iframe']) || !empty($_GET['ajax']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/contratos/crear_contrato.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
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
        
        $isAjax = !empty($_GET['modal_iframe']) || !empty($_GET['ajax']);
        if (!$isAjax) include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/contratos/crear_contrato.php';
        if (!$isAjax) include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRol = $_SESSION['user']['rol'] ?? 'usuario';
        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $_SESSION['flash_error'] = 'Acceso denegado: Se requieren permisos de Administrador.';
            header('Location: ' . BASE_URL . '/contratos');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/contratos');
            exit;
        }

        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/contratos');
        exit;
    }

    public function agregar_pago($contratoId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->agregarPago($contratoId, $_POST);
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $redirect = (!empty($referer) && (strpos($referer, '/contratos') !== false || strpos($referer, 'localhost') !== false))
                ? $referer
                : (BASE_URL . '/contratos/ver/' . (int)$contratoId);
            header('Location: ' . $redirect);
            exit;
        }
    }

    public function eliminar_pago($pagoId) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRol = $_SESSION['user']['rol'] ?? 'usuario';
        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $_SESSION['flash_error'] = 'Acceso denegado: Se requieren permisos de Administrador.';
            header('Location: ' . BASE_URL . '/contratos');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/contratos');
            exit;
        }

        $this->model->eliminarPago($pagoId);
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $redirect = (!empty($referer) && (strpos($referer, '/contratos') !== false || strpos($referer, 'localhost') !== false))
            ? $referer
            : (BASE_URL . '/contratos');
        header('Location: ' . $redirect);
        exit;
    }
}
