<?php
/**
 * Router Principal - Gestión Comercial
 */
require_once __DIR__ . '/config/database.php';

// Obtener la página solicitada (Soporte para Clean URLs)
$request_uri = $_SERVER['REQUEST_URI'];
// Quitar la parte del basePath según ambiente (case-insensitive para Windows/XAMPP)
$_strippedUri = preg_replace('#^/Gestion_Proyectos(/|$)#i', '/', $request_uri);
$request_uri  = explode('?', $_strippedUri)[0];
$parts        = explode('/', trim($request_uri, '/'));

$page   = !empty($parts[0]) ? $parts[0]   : (isset($_GET['page'])   ? $_GET['page']   : 'dashboard');
$action = !empty($parts[1]) ? $parts[1]   : (isset($_GET['action']) ? $_GET['action'] : 'index');
$id     = !empty($parts[2]) ? (int)$parts[2] : (isset($_GET['id'])  ? (int)$_GET['id'] : 0);

// ─── Rutas de Autenticación (sin sesión requerida) ─────────────────────────
if ($page === 'auth') {
    require_once __DIR__ . '/controllers/AuthController.php';
    $auth = new AuthController();
    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->loginPost();
            } else {
                $auth->login();
            }
            break;
        case 'loginColaborador':
            $auth->loginColaborador();
            break;
        case 'devLogin':
            $auth->devLogin();
            break;
        case 'google':
            // /auth/google          → iniciar flujo OAuth
            // /auth/google/callback → callback de Google
            if (!empty($parts[2]) && $parts[2] === 'callback') {
                $auth->googleCallback();
            } else {
                $auth->google();
            }
            break;
        case 'solicitarAcceso':
            $auth->solicitarAcceso();
            break;
        case 'solicitudes':
            $auth->solicitudes();
            break;
        case 'gestionarSolicitud':
            $auth->gestionarSolicitud();
            break;
        case 'logout':
            $auth->logout();
            break;
        default:
            $auth->login();
    }
    exit;
}

// ─── Verificar sesión activa para el resto de páginas ──────────────────────
session_start();
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/auth/login');
    exit;
}

// ─── Mapeo de páginas a controladores ──────────────────────────────────────
$controllers = [
    'dashboard'     => 'DashboardController',
    'convocatorias' => 'ConvocatoriaController',
    'oportunidades' => 'OportunidadController',
    'licitaciones'  => 'LicitacionController',
    'propuestas'    => 'PropuestaController',
    'contratos'     => 'ContratoController',
    'proyectos'     => 'ProyectoController',
    'clientes'      => 'ClienteController',
    'empresas'      => 'EmpresaController',
    'usuarios'      => 'UsuarioController',
];

if (!isset($controllers[$page])) {
    $page = 'dashboard';
}

$controllerName = $controllers[$page];
$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controller = new $controllerName();
    if (method_exists($controller, $action)) {
        $controller->$action($id);
    } else {
        $controller->index();
    }
} else {
    require_once __DIR__ . '/controllers/DashboardController.php';
    $controller = new DashboardController();
    $controller->index();
}
