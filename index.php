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

// Alias directo para registro
if ($page === 'registro') {
    $page   = 'auth';
    $action = 'registro';
}

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
        case 'registro':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->registroPost();
            } else {
                $auth->registro();
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
        case 'notificacionesColaborador':
            $auth->notificacionesColaborador();
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

// ─── Sincronizar estado en caliente del usuario y verificar Drive ─────────────
$currentUser = $_SESSION['user'] ?? null;
if ($currentUser) {
    $uId = (int)($currentUser['id'] ?? 0);
    $userDb = dbQueryOne("SELECT u.id, u.activo, u.rol, u.acceso_aprobado, u.tipo_usuario_id, u.area_id,
                                 u.cargo_id, u.org_area_id,
                                 u.drive_vinculado, u.drive_folder_id, u.email, u.nombre, u.empresa_id,
                                 e.drive_vinculado as emp_drive_vinculado, e.drive_folder_id as emp_drive_folder_id
                          FROM usuarios u
                          LEFT JOIN empresas e ON u.empresa_id = e.id
                          WHERE u.id = ? LIMIT 1", [$uId]);

    if (!$userDb || empty($userDb['activo'])) {
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    // Actualizar sesión en caliente con datos reales de la BD
    $_SESSION['user']['rol']             = $userDb['rol'];
    $_SESSION['user']['acceso_aprobado'] = (int)$userDb['acceso_aprobado'];
    $_SESSION['user']['tipo_usuario_id'] = $userDb['tipo_usuario_id'];
    $_SESSION['user']['area_id']         = $userDb['area_id'];
    $_SESSION['user']['cargo_id']        = $userDb['cargo_id'] ?? null;
    $_SESSION['user']['org_area_id']     = $userDb['org_area_id'] ?? null;

    $userRol        = $userDb['rol'] ?? 'usuario';
    $accesoAprobado = (int)$userDb['acceso_aprobado'];

    $isLinked = false;
    if (!empty($userDb['drive_vinculado']) && !empty($userDb['drive_folder_id'])) {
        $isLinked = true;
    } elseif (!empty($userDb['emp_drive_folder_id']) && !empty($userDb['emp_drive_vinculado'])) {
        $isLinked = true;
        dbExecute("UPDATE usuarios SET drive_folder_id = ?, drive_vinculado = 1 WHERE id = ?", [
            $userDb['emp_drive_folder_id'], $uId
        ]);
        $_SESSION['user']['drive_folder_id'] = $userDb['emp_drive_folder_id'];
        $_SESSION['user']['drive_vinculado'] = 1;
    } else {
        // Asignar Drive global de la empresa de forma transparente
        $swDrive = dbQueryOne("SELECT drive_folder_id, drive_email, drive_vinculado FROM empresa_software WHERE drive_vinculado = 1 LIMIT 1");
        $globalFolder = $swDrive['drive_folder_id'] ?? '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr';
        $globalEmail  = $swDrive['drive_email'] ?? 'devops.maxisoftnet@gmail.com';
        dbExecute("UPDATE usuarios SET drive_folder_id = ?, drive_email = ?, drive_vinculado = 1 WHERE id = ?", [
            $globalFolder, $globalEmail, $uId
        ]);
        $_SESSION['user']['drive_folder_id'] = $globalFolder;
        $_SESSION['user']['drive_vinculado'] = 1;
        $isLinked = true;
    }

    // ─── Control de Acceso Estricto para Colaboradores No Aprobados ─────────
    if ($userRol === 'usuario' && !$accesoAprobado) {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || !empty($_GET['ajax']) 
               || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        if ($isAjax || $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acceso denegado: Tu cuenta de colaborador está en espera de aprobación por el administrador.'
            ]);
            exit;
        }

        if ($page !== 'dashboard') {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    // ─── Control de Acceso a Módulos Exclusivos de Administradores ───────────
    if ($userRol === 'usuario' && in_array($page, ['usuarios', 'configuracion', 'empresas', 'organizacion'])) {
        require_once __DIR__ . '/helpers/PermisosHelper.php';
        if (!PermisosHelper::puede('ver', $page)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }
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
    'profesionales' => 'ProfesionalController',
    'usuarios'      => 'UsuarioController',
    'configuracion' => 'ConfiguracionController',
    'organizacion'  => 'OrganizacionController',
    'tareas'        => 'TareaController',
    'asistencias'   => 'AsistenciaController',
    'compras'       => 'CompraController',
    'catalogos'     => 'CatalogoController',
    'almacen'       => 'AlmacenController',
    'presupuesto'   => 'ControlPresupuestalController',
    'subcontratos'  => 'SubcontratoController',
];

if (!isset($controllers[$page])) {
    $page = 'dashboard';
}

$controllerName = $controllers[$page];
$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $controller = new $controllerName();
    if (substr($action, 0, 1) !== '_' && is_callable([$controller, $action])) {
        $controller->$action($id);
    } else {
        $controller->index();
    }
} else {
    require_once __DIR__ . '/controllers/DashboardController.php';
    $controller = new DashboardController();
    $controller->index();
}
