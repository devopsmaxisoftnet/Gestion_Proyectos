<?php
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class UsuarioController {
    private $model;
    
    public function __construct() {
        PermisosHelper::verificarAcceso('usuarios');
        $this->model = new UsuarioModel();
    }
    
    public function index() {

        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'rol_id' => $_GET['rol_id'] ?? '',
        ];
        
        $data = [
            'usuarios' => $this->model->getAll($filtros),
            'roles' => $this->model->getRoles(),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Usuarios';
        $currentPage = 'usuarios';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/usuarios/lista_usuarios.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }
        
        $data = [
            'roles' => $this->model->getRoles(),
            'areas' => $this->model->getOrgAreas(),
            'cargos' => $this->model->getOrgCargos(),
            'tipos' => $this->model->getTiposUsuario()
        ];
        
        $pageTitle = 'Nuevo usuario';
        $currentPage = 'usuarios';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/usuarios/crear_usuario.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }
        
        $data = [
            'usuario' => $this->model->getById($id),
            'roles' => $this->model->getRoles(),
            'areas' => $this->model->getOrgAreas(),
            'cargos' => $this->model->getOrgCargos(),
            'tipos' => $this->model->getTiposUsuario()
        ];
        
        $pageTitle = 'Editar usuario';
        $currentPage = 'usuarios';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/usuarios/crear_usuario.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }

        // Proteger contra auto-eliminación
        if ((int)$id === (int)($_SESSION['user']['id'] ?? 0)) {
            $_SESSION['flash_error'] = 'No puedes eliminar tu propia cuenta de usuario en sesión.';
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }

        // Proteger contra eliminación del último Superadministrador
        $targetUser = $this->model->getById($id);
        if ($targetUser && ($targetUser['rol'] === 'superadmin' || ($targetUser['rol_id'] ?? 0) == 1)) {
            $totalSuper = (int)(dbQueryOne("SELECT COUNT(*) as t FROM usuarios WHERE (rol = 'superadmin' OR rol_id = 1) AND activo = 1")['t'] ?? 0);
            if ($totalSuper <= 1) {
                $_SESSION['flash_error'] = 'No es posible eliminar al único Superadministrador del sistema.';
                header('Location: ' . BASE_URL . '/usuarios');
                exit;
            }
        }

        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/usuarios');
        exit;
    }
    
    public function alternar_estado($id) {
        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }

        // Proteger contra auto-suspensión
        if ((int)$id === (int)($_SESSION['user']['id'] ?? 0)) {
            $_SESSION['flash_error'] = 'No puedes suspender tu propia cuenta de usuario.';
            header('Location: ' . BASE_URL . '/usuarios');
            exit;
        }

        $this->model->toggleStatus($id);
        header('Location: ' . BASE_URL . '/usuarios');
        exit;
    }

    /**
     * Vista de Gestión y Aprobación de Solicitudes de Acceso (Módulo de Usuarios)
     */
    public function solicitudes() {
        PermisosHelper::verificarAcceso('usuarios');
        require_once __DIR__ . '/../models/OrganizacionModel.php';
        $orgModel = new OrganizacionModel();

        // Sincronizar automáticamente cualquier colaborador pendiente sin solicitud
        dbExecute("
            INSERT INTO solicitudes_acceso (usuario_id, mensaje, estado, created_at)
            SELECT u.id, 'Solicitud de activación y asignación de puesto corporativo.', 'pendiente', NOW()
            FROM usuarios u
            LEFT JOIN solicitudes_acceso sa ON u.id = sa.usuario_id AND sa.estado = 'pendiente'
            WHERE u.rol = 'usuario' AND (u.acceso_aprobado = 0 OR u.acceso_aprobado IS NULL) AND sa.id IS NULL
        ");

        // Consultar todas las solicitudes con PDO preparado
        $solicitudes = dbQueryAll("
            SELECT sa.*, 
                   u.nombre as usuario_nombre, u.email as usuario_email, u.avatar as usuario_avatar,
                   oa.nombre as area_nombre, oa.codigo as area_codigo, oa.color as area_color,
                   oc.nombre as cargo_nombre, oc.codigo as cargo_codigo, oc.nivel_jerarquico
            FROM solicitudes_acceso sa
            JOIN usuarios u ON sa.usuario_id = u.id
            LEFT JOIN org_areas oa ON sa.org_area_id = oa.id
            LEFT JOIN org_cargos oc ON sa.cargo_id = oc.id
            ORDER BY (sa.estado = 'pendiente') DESC, sa.created_at DESC
        ");

        $areas = $orgModel->getAllAreas();
        $cargos = $orgModel->getAllCargos();

        $pendientesCount = 0;
        $aprobadasCount = 0;
        $rechazadasCount = 0;
        foreach ($solicitudes as $s) {
            if ($s['estado'] === 'pendiente') $pendientesCount++;
            elseif ($s['estado'] === 'aprobada') $aprobadasCount++;
            elseif ($s['estado'] === 'rechazada') $rechazadasCount++;
        }

        $pageTitle = 'Solicitudes de Acceso';
        $currentPage = 'usuarios';

        $data = [
            'solicitudes' => $solicitudes,
            'areas' => $areas,
            'cargos' => $cargos,
            'totalSolicitudes' => count($solicitudes),
            'pendientesCount' => $pendientesCount,
            'aprobadasCount' => $aprobadasCount,
            'rechazadasCount' => $rechazadasCount
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/usuarios/solicitudes.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    public function solicitudes_acceso() {
        $this->solicitudes();
    }

    /**
     * Endpoint AJAX/POST: Aprobar solicitud y asignar puesto desde Usuarios
     */
    public function aprobarSolicitud() {
        require_once __DIR__ . '/OrganizacionController.php';
        $orgCtrl = new OrganizacionController();
        $orgCtrl->aprobarSolicitud();
    }

    /**
     * Endpoint AJAX/POST: Rechazar solicitud desde Usuarios
     */
    public function rechazarSolicitud() {
        require_once __DIR__ . '/OrganizacionController.php';
        $orgCtrl = new OrganizacionController();
        $orgCtrl->rechazarSolicitud();
    }
}





