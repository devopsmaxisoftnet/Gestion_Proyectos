<?php
/**
 * Controlador de Estructura Organizacional y Permisos Granulares
 * Gestión de Proyectos - ERP Core de Seguridad
 */
require_once __DIR__ . '/../models/OrganizacionModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class OrganizacionController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Bloqueo de acceso: Solo usuarios autenticados con permiso
        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        PermisosHelper::verificarAcceso('organizacion');
        $this->model = new OrganizacionModel();
    }

    /**
     * Vista principal: Organigrama Corporativo interactivo y lista jerárquica de Áreas
     */
    public function index() {
        $areasTree = $this->model->getAreasTree();
        $areasFlat = $this->model->getAllAreas();
        $cargos = $this->model->getAllCargos();
        $usuarios = $this->model->getUsuariosConEstructura();

        $pageTitle = 'Estructura Organizacional y Organigrama';
        $currentPage = 'organizacion';

        $data = [
            'areasTree' => $areasTree,
            'areasFlat' => $areasFlat,
            'totalAreas' => count($areasFlat),
            'totalCargos' => count($cargos),
            'totalUsuarios' => count($usuarios)
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/organizacion/index.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Vista de Lista y Catálogo Detallado de Áreas
     */
    public function areas() {
        PermisosHelper::requirePermiso('ver', 'organizacion');

        $areasFlat = $this->model->getAllAreas();
        $cargos = $this->model->getAllCargos();
        $usuarios = $this->model->getUsuariosConEstructura();

        $pageTitle = 'Catálogo de Áreas Organizacionales';
        $currentPage = 'organizacion';

        $data = [
            'areasFlat' => $areasFlat,
            'totalAreas' => count($areasFlat),
            'totalCargos' => count($cargos),
            'totalUsuarios' => count($usuarios)
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/organizacion/lista_areas.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Redirección: La asignación de roles y cargos se gestiona de forma centralizada en el módulo de Usuarios
     */
    public function asignarRoles() {
        header('Location: ' . BASE_URL . '/usuarios');
        exit;
    }

    public function asignar_roles() {
        $this->asignarRoles();
    }

    /**
     * Crear nueva área organizacional
     */
    public function crearArea() {
        PermisosHelper::requirePermiso('crear', 'organizacion');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/organizacion');
                exit;
            }

            try {
                $this->model->createArea($_POST);
                $_SESSION['flash_success'] = 'Área organizacional creada con éxito.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error al crear área: ' . $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/organizacion');
            exit;
        }

        $areas = $this->model->getAllAreas();
        $pageTitle = 'Nueva Área Organizacional';
        $currentPage = 'organizacion';

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/organizacion/modal_area.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Editar área organizacional
     */
    public function editarArea($id) {
        PermisosHelper::requirePermiso('editar', 'organizacion');
        $id = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/organizacion');
                exit;
            }

            try {
                $this->model->updateArea($id, $_POST);
                $_SESSION['flash_success'] = 'Área actualizada con éxito.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error al actualizar área: ' . $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/organizacion');
            exit;
        }

        $area = $this->model->getAreaById($id);
        if (!$area) {
            $_SESSION['flash_error'] = 'Área no encontrada.';
            header('Location: ' . BASE_URL . '/organizacion');
            exit;
        }

        $areas = $this->model->getAllAreas();
        $pageTitle = 'Editar Área Organizacional';
        $currentPage = 'organizacion';

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/organizacion/modal_area.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Eliminar área organizacional
     */
    public function eliminarArea($id) {
        PermisosHelper::requirePermiso('eliminar', 'organizacion');
        $id = (int)$id;

        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/organizacion');
            exit;
        }

        try {
            $this->model->deleteArea($id);
            $_SESSION['flash_success'] = 'Área eliminada correctamente.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/organizacion');
        exit;
    }

    /**
     * Vista de Cargos y Puestos
     */
    public function cargos() {
        $areaId = !empty($_GET['area_id']) ? (int)$_GET['area_id'] : null;
        $nivelFilter = !empty($_GET['nivel']) ? (int)$_GET['nivel'] : null;
        
        $cargos = $this->model->getAllCargos();

        $areas = $this->model->getAllAreas();
        $usuarios = $this->model->getUsuariosConEstructura();

        $pageTitle = 'Cargos y Puestos Corporativos';
        $currentPage = 'organizacion';

        $data = [
            'cargos' => $cargos,
            'areas' => $areas,
            'usuarios' => $usuarios,
            'selectedAreaId' => $areaId,
            'selectedNivel' => $nivelFilter
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/organizacion/cargos.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Crear cargo
     */
    public function crearCargo() {
        PermisosHelper::requirePermiso('crear', 'organizacion');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/organizacion/cargos');
                exit;
            }

            try {
                $cargoId = $this->model->createCargo($_POST);
                $_SESSION['flash_success'] = 'Cargo registrado correctamente. Ahora puedes asignarle permisos.';
                header('Location: ' . BASE_URL . '/organizacion/permisos/' . $cargoId);
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error al registrar cargo: ' . $e->getMessage();
                header('Location: ' . BASE_URL . '/organizacion/cargos');
                exit;
            }
        }
    }

    /**
     * Editar cargo
     */
    public function editarCargo($id) {
        PermisosHelper::requirePermiso('editar', 'organizacion');
        $id = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/organizacion/cargos');
                exit;
            }

            try {
                $this->model->updateCargo($id, $_POST);
                $_SESSION['flash_success'] = 'Cargo actualizado correctamente.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error al actualizar cargo: ' . $e->getMessage();
            }
            header('Location: ' . BASE_URL . '/organizacion/cargos');
            exit;
        }
    }

    /**
     * Eliminar cargo
     */
    public function eliminarCargo($id) {
        PermisosHelper::requirePermiso('eliminar', 'organizacion');
        $id = (int)$id;

        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/organizacion/cargos');
            exit;
        }

        try {
            $this->model->deleteCargo($id);
            $_SESSION['flash_success'] = 'Cargo eliminado correctamente.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/organizacion/cargos');
        exit;
    }

    /**
     * API JSON: Obtener datos de cargo, catálogo completo y permisos asignados
     */
    public function apiPermisosCargo($cargoId) {
        PermisosHelper::requirePermiso('editar', 'organizacion');
        header('Content-Type: application/json; charset=utf-8');

        $cargoId = (int)$cargoId;
        $cargo = $this->model->getCargoById($cargoId);
        if (!$cargo) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cargo no encontrado']);
            exit;
        }

        $catalogoPermisos = $this->model->getPermisosCatalogo();
        $permisosAsignados = $this->model->getPermisosIdsByCargo($cargoId);
        $cargos = $this->model->getAllCargos();

        echo json_encode([
            'success' => true,
            'cargo' => $cargo,
            'catalogo' => $catalogoPermisos,
            'asignados' => $permisosAsignados,
            'cargos' => array_map(function($c) {
                return [
                    'id' => (int)$c['id'],
                    'nombre' => $c['nombre'],
                    'codigo' => $c['codigo'],
                    'area_nombre' => $c['area_nombre'],
                    'nivel_jerarquico' => (int)$c['nivel_jerarquico']
                ];
            }, $cargos),
            'csrf_token' => CsrfHelper::getToken()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Matriz interactiva de Permisos para un cargo específico
     * Redirige a la vista de cargos abriendo automáticamente el modal expansivo
     */
    public function permisos($cargoId) {
        PermisosHelper::requirePermiso('editar', 'organizacion');
        $cargoId = (int)$cargoId;

        header('Location: ' . BASE_URL . '/organizacion/cargos?open_permisos=' . $cargoId);
        exit;
    }

    /**
     * Guardar matriz de permisos de un cargo (soporta AJAX o POST tradicional)
     */
    public function guardarPermisos() {
        PermisosHelper::requirePermiso('editar', 'organizacion');

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || !empty($_GET['ajax'])
               || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        if (!CsrfHelper::validateToken()) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
                exit;
            }
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/organizacion/cargos');
            exit;
        }

        $cargoId = (int)($_POST['cargo_id'] ?? 0);
        $permisoIds = isset($_POST['permisos']) && is_array($_POST['permisos']) ? array_map('intval', $_POST['permisos']) : [];

        if (!$cargoId) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de cargo requerido']);
                exit;
            }
            $_SESSION['flash_error'] = 'Cargo no válido.';
            header('Location: ' . BASE_URL . '/organizacion/cargos');
            exit;
        }

        try {
            $this->model->syncCargoPermisos($cargoId, $permisoIds);
            PermisosHelper::clearCache();

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => 'Permisos actualizados exitosamente.',
                    'totalAsignados' => count($permisoIds)
                ]);
                exit;
            }

            $_SESSION['flash_success'] = 'Permisos del cargo actualizados exitosamente (' . count($permisoIds) . ' permisos).';
            header('Location: ' . BASE_URL . '/organizacion/permisos/' . $cargoId);
            exit;
        } catch (Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                exit;
            }
            $_SESSION['flash_error'] = 'Error al guardar permisos: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/organizacion/permisos/' . $cargoId);
            exit;
        }
    }

    /**
     * Gestión de Delegaciones Temporales de Permisos
     */
    public function delegaciones() {
        PermisosHelper::requirePermiso('ver', 'organizacion');

        $delegaciones = $this->model->getDelegaciones();
        $usuarios = $this->model->getUsuariosConEstructura();
        $areas = $this->model->getAllAreas();
        $catalogoPermisos = $this->model->getPermisosCatalogo();

        $pageTitle = 'Delegaciones Temporales de Permisos';
        $currentPage = 'organizacion';

        $data = [
            'delegaciones' => $delegaciones,
            'usuarios' => $usuarios,
            'areas' => $areas,
            'catalogoPermisos' => $catalogoPermisos
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/organizacion/delegaciones.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Crear delegación temporal
     */
    public function crearDelegacion() {
        PermisosHelper::requirePermiso('editar', 'organizacion');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/organizacion/delegaciones');
                exit;
            }

            try {
                $otorgadoPor = (int)($_SESSION['user']['id'] ?? 0);
                $this->model->createDelegacion($_POST, $otorgadoPor);
                PermisosHelper::clearCache();
                $_SESSION['flash_success'] = 'Permiso temporal delegado correctamente.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
            }

            header('Location: ' . BASE_URL . '/organizacion/delegaciones');
            exit;
        }
    }

    /**
     * Revocar delegación temporal
     */
    public function revocarDelegacion($id) {
        PermisosHelper::requirePermiso('editar', 'organizacion');
        $id = (int)$id;

        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido.';
            header('Location: ' . BASE_URL . '/organizacion/delegaciones');
            exit;
        }

        try {
            $this->model->revocarDelegacion($id);
            PermisosHelper::clearCache();
            $_SESSION['flash_success'] = 'Delegación revocada correctamente.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/organizacion/delegaciones');
        exit;
    }

    /**
     * Asignar usuario a un Cargo y Área
     */
    public function asignarUsuario() {
        PermisosHelper::requirePermiso('editar', 'organizacion');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $redirect = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : (BASE_URL . '/organizacion');

            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido.';
                header('Location: ' . $redirect);
                exit;
            }

            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $cargoId = !empty($_POST['cargo_id']) ? (int)$_POST['cargo_id'] : null;
            $areaId = !empty($_POST['area_id']) ? (int)$_POST['area_id'] : null;

            try {
                $this->model->asignarCargoUsuario($usuarioId, $cargoId, $areaId);
                PermisosHelper::clearCache();
                $_SESSION['flash_success'] = 'Cargo y área asignados al usuario exitosamente.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
            }

            header('Location: ' . $redirect);
            exit;
        }
    }

    /**
     * Vista de Gestión y Aprobación de Solicitudes de Acceso (Redirigida a Usuarios)
     */
    public function solicitudes() {
        header('Location: ' . BASE_URL . '/usuarios/solicitudes');
        exit;
    }

    /**
     * Vista de Gestión y Aprobación de Solicitudes de Acceso (Módulo Organización)
     */
    public function solicitudes_acceso() {
        PermisosHelper::verificarAcceso('organizacion');

        $solicitudes = dbQueryAll("
            SELECT sa.*, 
                   u.nombre as usuario_nombre, 
                   u.email as usuario_email, 
                   u.avatar as usuario_avatar, 
                   u.created_at as usuario_registro,
                   oa.nombre as area_nombre, 
                   oa.codigo as area_codigo, 
                   oa.color as area_color,
                   oc.nombre as cargo_nombre, 
                   oc.nivel_jerarquico, 
                   oc.codigo as cargo_codigo
            FROM solicitudes_acceso sa
            JOIN usuarios u ON sa.usuario_id = u.id
            LEFT JOIN org_areas oa ON sa.org_area_id = oa.id
            LEFT JOIN org_cargos oc ON sa.cargo_id = oc.id
            ORDER BY (sa.estado = 'pendiente') DESC, sa.created_at DESC
        ");

        $areas = $this->model->getAllAreas();
        $cargos = $this->model->getAllCargos();

        $pendientesCount = 0;
        $aprobadasCount = 0;
        $rechazadasCount = 0;
        foreach ($solicitudes as $s) {
            if ($s['estado'] === 'pendiente') $pendientesCount++;
            elseif ($s['estado'] === 'aprobada') $aprobadasCount++;
            elseif ($s['estado'] === 'rechazada') $rechazadasCount++;
        }

        $pageTitle = 'Solicitudes de Acceso';
        $currentPage = 'organizacion';
        $activeTab = 'solicitudes';

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
        include __DIR__ . '/../views/organizacion/solicitudes_acceso.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Endpoint AJAX/POST: Aprobar solicitud y asignar Área, Cargo y Rol (PDO Preparado + CSRF)
     */
    public function aprobarSolicitud() {
        PermisosHelper::verificarAcceso('organizacion');
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        if (!CsrfHelper::validateToken()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o sesión expirada']);
            exit;
        }

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        $areaId = (int)($_POST['area_id'] ?? 0);
        $cargoId = (int)($_POST['cargo_id'] ?? 0);
        $rol = trim($_POST['rol'] ?? 'usuario');
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (!$solicitudId || !$areaId || !$cargoId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Debes seleccionar el Área y el Cargo oficial para habilitar al usuario.']);
            exit;
        }

        // Validar rol permitido
        if (!in_array($rol, ['admin', 'usuario'])) {
            $rol = 'usuario';
        }

        // Obtener la solicitud
        $solicitud = dbQueryOne("SELECT * FROM solicitudes_acceso WHERE id = ? LIMIT 1", [$solicitudId]);
        if (!$solicitud) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Solicitud de acceso no encontrada.']);
            exit;
        }

        $usuarioId = (int)$solicitud['usuario_id'];
        $adminId = (int)($_SESSION['user']['id'] ?? 0);

        try {
            // 1. Actualizar solicitud de acceso con sentencia preparada PDO
            dbExecute("
                UPDATE solicitudes_acceso 
                SET estado = 'aprobada', 
                    area_id = ?, 
                    org_area_id = ?,
                    cargo_id = ?, 
                    revisado_por = ?, 
                    fecha_revision = NOW(), 
                    observaciones = ? 
                WHERE id = ?
            ", [$areaId, $areaId, $cargoId, $adminId, $observaciones, $solicitudId]);

            // 2. Activar usuario y vincular a su puesto organizacional oficial
            $rolId = ($rol === 'admin') ? 1 : 2;
            dbExecute("
                UPDATE usuarios 
                SET acceso_aprobado = 1, 
                    activo = 1,
                    area_id = ?,
                    org_area_id = ?, 
                    cargo_id = ?, 
                    rol = ?,
                    rol_id = ?
                WHERE id = ?
            ", [$areaId, $areaId, $cargoId, $rol, $rolId, $usuarioId]);

            PermisosHelper::clearCache();

            // Obtener datos enriquecidos para confirmación visual modal y notificaciones
            $areaData = dbQueryOne("SELECT nombre, codigo FROM org_areas WHERE id = ?", [$areaId]);
            $cargoData = dbQueryOne("SELECT nombre, codigo, nivel_jerarquico FROM org_cargos WHERE id = ?", [$cargoId]);
            $userData = dbQueryOne("SELECT nombre, email FROM usuarios WHERE id = ?", [$usuarioId]);
            $permisosCount = dbQueryOne("SELECT COUNT(*) as total FROM cargo_permisos WHERE cargo_id = ?", [$cargoId]);

            echo json_encode([
                'success' => true,
                'message' => 'Usuario aprobado exitosamente. Puesto, área y permisos asignados.',
                'colaborador' => $userData['nombre'] ?? 'Colaborador',
                'colaborador_email' => $userData['email'] ?? '',
                'area_nombre' => $areaData['nombre'] ?? 'Área Asignada',
                'area_codigo' => $areaData['codigo'] ?? '',
                'cargo_nombre' => $cargoData['nombre'] ?? 'Cargo Oficial',
                'cargo_codigo' => $cargoData['codigo'] ?? '',
                'nivel_jerarquico' => (int)($cargoData['nivel_jerarquico'] ?? 3),
                'permisos_count' => (int)($permisosCount['total'] ?? 0)
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al procesar aprobación: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Endpoint AJAX/POST: Rechazar solicitud de acceso (PDO Preparado + CSRF)
     */
    public function rechazarSolicitud() {
        PermisosHelper::verificarAcceso('organizacion');
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        if (!CsrfHelper::validateToken()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            exit;
        }

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (!$solicitudId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de solicitud requerido.']);
            exit;
        }

        $adminId = (int)($_SESSION['user']['id'] ?? 0);

        try {
            dbExecute("
                UPDATE solicitudes_acceso 
                SET estado = 'rechazada', 
                    revisado_por = ?, 
                    fecha_revision = NOW(), 
                    observaciones = ? 
                WHERE id = ?
            ", [$adminId, $observaciones, $solicitudId]);

            echo json_encode([
                'success' => true,
                'message' => 'Solicitud rechazada correctamente.'
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al rechazar solicitud: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}
