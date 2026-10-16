<?php
/**
 * Controlador de Logística y Órdenes de Compra (Fase 3: Ejecución de Proyectos)
 * ERP Gestión de Proyectos
 */
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/ControlPresupuestalModel.php';
require_once __DIR__ . '/../models/CatalogoModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class CompraController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        PermisosHelper::verificarAcceso('compras');
        $this->model = new CompraModel();
    }

    /**
     * Listado Principal: KPIs, gráficos, órdenes y pestañas de catálogos
     */
    public function index() {
        PermisosHelper::requirePermiso('ver', 'compras');

        $filtros = [
            'proyecto_id' => !empty($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : null,
            'estado' => $_GET['estado'] ?? '',
            'proveedor_id' => !empty($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : null,
            'busqueda' => $_GET['busqueda'] ?? ''
        ];

        $catalogoModel = new CatalogoModel();

        $data = [
            'ordenes' => $this->model->getAll($filtros),
            'resumen' => $this->model->getResumen($filtros['proyecto_id']),
            'proyectos' => $this->model->getProyectos(),
            'proveedores' => $this->model->getProveedores(),
            'filtros' => $filtros,
            'graficos' => [
                'estados' => $this->model->getComprasPorEstado($filtros['proyecto_id']),
                'top' => $this->model->getTopActividades($filtros['proyecto_id'], 8),
            ],
            'catalogos' => [
                'material' => $catalogoModel->getMateriales(),
                'equipo' => $catalogoModel->getEquipos(),
                'proveedor' => $catalogoModel->getProveedores(),
            ],
            'permisos' => [
                'crear' => PermisosHelper::puede('crear', 'compras'),
                'editar' => PermisosHelper::puede('editar', 'compras'),
                'eliminar' => PermisosHelper::puede('eliminar', 'compras'),
            ],
        ];

        $pageTitle = 'Logística · Órdenes de Compra';
        $currentPage = 'compras';
        $faseActiva = 3;

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/compras/index.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Formulario y procesamiento para crear una nueva Orden de Compra
     */
    public function crear() {
        PermisosHelper::requirePermiso('crear', 'compras');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/compras/crear');
                exit;
            }

            $detalles = $_POST['items'] ?? [];
            if (!is_array($detalles) || empty($detalles)) {
                $_SESSION['flash_error'] = 'Debes agregar al menos un ítem o insumo a la orden.';
                header('Location: ' . BASE_URL . '/compras/crear');
                exit;
            }

            try {
                $solicitadoPor = (int)($_SESSION['user']['id'] ?? 0);
                $ordenId = $this->model->create($_POST, $detalles, $solicitadoPor);
                $_SESSION['flash_success'] = 'Orden de Compra registrada exitosamente. Queda pendiente de aprobación por Logística o Gerencia.';
                header('Location: ' . BASE_URL . '/compras/ver/' . $ordenId);
                exit;
            } catch (Exception $e) {
                // Se vuelve a mostrar el formulario con lo digitado (no se pierde el trabajo del usuario)
                $_SESSION['flash_error'] = 'No se pudo registrar la orden: ' . $e->getMessage();
                $this->mostrarFormulario('crear', $_POST, array_values($detalles), 0);
                return;
            }
        }

        $previo = [];
        if (!empty($_GET['proyecto_id'])) { $previo['proyecto_id'] = (int)$_GET['proyecto_id']; }
        $this->mostrarFormulario('crear', $previo, [], 0);
    }

    /**
     * Edición de una Orden de Compra (borrador, pendiente o rechazada)
     */
    public function editar($id) {
        PermisosHelper::requirePermiso('editar', 'compras');
        $id = (int)$id;

        $orden = $this->model->getById($id);
        if (!$orden) {
            $_SESSION['flash_error'] = 'La Orden de Compra no existe.';
            header('Location: ' . BASE_URL . '/compras');
            exit;
        }
        if (!in_array($orden['estado'], CompraModel::ESTADOS_EDITABLES, true)) {
            $_SESSION['flash_error'] = 'Solo se pueden editar órdenes en borrador, pendientes de aprobación o rechazadas.';
            header('Location: ' . BASE_URL . '/compras/ver/' . $id);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/compras/editar/' . $id);
                exit;
            }

            $detalles = $_POST['items'] ?? [];
            try {
                if (!is_array($detalles) || empty($detalles)) {
                    throw new Exception('Debes mantener al menos un ítem en la orden.');
                }
                $this->model->update($id, $_POST, $detalles);
                $_SESSION['flash_success'] = 'Orden de Compra actualizada. Los costos del WBS fueron recalculados.';
                header('Location: ' . BASE_URL . '/compras/ver/' . $id);
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'No se pudo actualizar la orden: ' . $e->getMessage();
                $this->mostrarFormulario('editar', $_POST, is_array($detalles) ? array_values($detalles) : [], $id, $orden['codigo']);
                return;
            }
        }

        $this->mostrarFormulario('editar', $orden, $this->model->getDetalles($id), $id, $orden['codigo']);
    }

    /**
     * Vista de Detalle y Comprobante de la Orden de Compra
     */
    public function ver($id) {
        PermisosHelper::requirePermiso('ver', 'compras');
        $id = (int)$id;

        $orden = $this->model->getById($id);
        if (!$orden) {
            $_SESSION['flash_error'] = 'La Orden de Compra no existe.';
            header('Location: ' . BASE_URL . '/compras');
            exit;
        }

        $detalles = $this->model->getDetalles($id);
        $puedeAprobar = $this->validarPermisoAprobacion();

        // Impacto presupuestal de aprobar la orden (solo mientras está pendiente de aprobación)
        $control = $orden['estado'] === 'pendiente_aprobacion' ? (new ControlPresupuestalModel())->evaluarOC($id) : null;

        $pageTitle = 'Detalle de Orden de Compra: ' . $orden['codigo'];
        $currentPage = 'compras';
        $faseActiva = 3;

        $data = [
            'orden' => $orden,
            'detalles' => $detalles,
            'control' => $control,
            'puedeAprobar' => $puedeAprobar,
            'puedeEditar' => PermisosHelper::puede('editar', 'compras') && in_array($orden['estado'], CompraModel::ESTADOS_EDITABLES, true),
            'puedeAnular' => ($puedeAprobar || PermisosHelper::puede('eliminar', 'compras'))
                && !in_array($orden['estado'], ['anulada', 'recibida'], true) && !(int)$orden['tiene_ingreso_almacen'],
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/compras/ver.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Actividades (WBS) de un proyecto, en JSON, para el selector de cada línea de la OC.
     * $id = proyecto_id
     */
    public function tareasProyecto($id) {
        PermisosHelper::requirePermiso('ver', 'compras');

        $proyectoId = (int)$id;
        $excluir = !empty($_GET['excluir_oc']) ? (int)$_GET['excluir_oc'] : null;   // OC en edición: no se cuenta dos veces

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'tareas' => $this->model->getTareasProyecto($proyectoId, $excluir),
            'control' => (new ControlPresupuestalModel())->getConfig($proyectoId),
        ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    /**
     * Aprobación oficial de la Orden de Compra
     * REGLA DE NEGOCIO: Solo usuarios con rol/cargo de "Logística" o "Gerencia" pueden aprobar.
     */
    public function aprobar($id) {
        $id = (int)$id;

        if (!$this->validarPermisoAprobacion()) {
            $_SESSION['flash_error'] = 'Acceso denegado: Solo los usuarios autorizados de Logística o Gerencia tienen potestad para aprobar Órdenes de Compra.';
            header('Location: ' . BASE_URL . '/compras/ver/' . $id);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/compras/ver/' . $id);
            exit;
        }

        try {
            $aprobadorId = (int)($_SESSION['user']['id'] ?? 0);

            // Control de disponibilidad presupuestal: avisa, registra o exige autorización según el modo del proyecto
            $cp = new ControlPresupuestalModel();
            $eval = $cp->evaluarOC($id);
            $motivoExceso = null;
            $conExceso = $eval && $eval['hay_exceso'] && $eval['modo'] !== 'informar';
            if ($conExceso) {
                $motivoPost = trim((string)($_POST['motivo_exceso'] ?? ''));
                if ($eval['modo'] === 'bloquear') {
                    try {
                        $motivoExceso = ControlPresupuestalModel::validarMotivo($motivoPost);
                    } catch (Exception $e) {
                        $_SESSION['flash_error'] = 'La orden excede el presupuesto de: ' . ControlPresupuestalModel::resumenExcesos($eval)
                            . '. ' . $e->getMessage();
                        header('Location: ' . BASE_URL . '/compras/ver/' . $id);
                        exit;
                    }
                } else {
                    $motivoExceso = $motivoPost !== '' ? mb_substr(preg_replace('/\s+/', ' ', $motivoPost), 0, 500) : 'Aprobada con advertencia de presupuesto';
                }
            }

            if ($this->model->aprobar($id, $aprobadorId)) {
                $msg = 'Orden de Compra aprobada. El gasto ya se refleja en el costo real de las actividades imputadas.';
                if ($conExceso) {
                    $oc = $this->model->getById($id);
                    $cp->registrarExcesos((int)$oc['proyecto_id'], $eval, 'oc', $id, (string)$oc['codigo'], $motivoExceso, $aprobadorId);
                    $msg .= ' El exceso de presupuesto quedó registrado en la bitácora de control presupuestal.';
                }
                $_SESSION['flash_success'] = $msg;
            } else {
                $_SESSION['flash_error'] = 'La orden no está pendiente de aprobación (puede haber sido procesada por otro usuario).';
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error al aprobar orden: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/compras/ver/' . $id);
        exit;
    }

    /**
     * Rechazar Orden de Compra
     */
    public function rechazar($id) {
        $id = (int)$id;

        if (!$this->validarPermisoAprobacion()) {
            $_SESSION['flash_error'] = 'Acceso denegado: No cuentas con permisos de aprobación para rechazar esta orden.';
            header('Location: ' . BASE_URL . '/compras/ver/' . $id);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido.';
            header('Location: ' . BASE_URL . '/compras/ver/' . $id);
            exit;
        }

        $motivo = trim($_POST['motivo_rechazo'] ?? '');
        if ($motivo === '') { $motivo = 'Observada por el área autorizante.'; }

        try {
            $rechazadorId = (int)($_SESSION['user']['id'] ?? 0);
            if ($this->model->rechazar($id, $motivo, $rechazadorId)) {
                $_SESSION['flash_success'] = 'La Orden de Compra ha sido rechazada y devuelta con observaciones.';
            } else {
                $_SESSION['flash_error'] = 'La orden no está pendiente de aprobación.';
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error al rechazar: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/compras/ver/' . $id);
        exit;
    }

    /**
     * Anular una Orden de Compra (conserva el registro, deja de computar costo)
     */
    public function anular($id) {
        $id = (int)$id;

        if (!$this->validarPermisoAprobacion() && !PermisosHelper::puede('eliminar', 'compras')) {
            $_SESSION['flash_error'] = 'Acceso denegado: no tienes permisos para anular órdenes de compra.';
            header('Location: ' . BASE_URL . '/compras/ver/' . $id);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/compras/ver/' . $id);
            exit;
        }

        try {
            if ($this->model->anular($id)) {
                $_SESSION['flash_success'] = 'Orden de Compra anulada. Sus líneas ya no computan en el costo de las actividades.';
            } else {
                $_SESSION['flash_error'] = 'La orden no puede anularse en su estado actual.';
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error al anular: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/compras/ver/' . $id);
        exit;
    }

    /**
     * Eliminar Orden de Compra
     */
    public function eliminar($id) {
        PermisosHelper::requirePermiso('eliminar', 'compras');
        $id = (int)$id;

        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido.';
            header('Location: ' . BASE_URL . '/compras');
            exit;
        }

        try {
            $this->model->delete($id);
            $_SESSION['flash_success'] = 'Orden de compra eliminada del sistema.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error al eliminar: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/compras');
        exit;
    }

    /**
     * Renderiza el formulario de OC (crear / editar) reutilizando la misma vista.
     * Privado: el router solo expone métodos públicos.
     */
    private function mostrarFormulario(string $modo, array $orden, array $items, int $ordenId, string $codigo = ''): void {
        $pageTitle = $modo === 'editar' ? 'Editar Orden de Compra ' . $codigo : 'Generar Orden de Compra';
        $currentPage = 'compras';
        $faseActiva = 3;

        $data = [
            'modo' => $modo,
            'ordenId' => $ordenId,
            'codigo' => $codigo,
            'orden' => $orden,
            'items' => $items,
            'proyectos' => $this->model->getProyectos(),
            'proveedores' => $this->model->getProveedores(),
            'catalogoItems' => $this->model->getCatalogoItems(),
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/compras/crear.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Valida si el usuario en sesión cumple la condición estricta de pertenecer a Logística o Gerencia
     */
    private function validarPermisoAprobacion(): bool {
        $user = $_SESSION['user'] ?? [];
        $rol = $user['rol'] ?? 'usuario';
        $userId = (int)($user['id'] ?? 0);

        if ($userId <= 0) return false;

        // Administradores y Superadmins tienen permiso total
        if (in_array($rol, ['superadmin', 'admin'])) {
            return true;
        }

        // 1. Validar si tiene el permiso base en el catálogo
        if (!PermisosHelper::puede('aprobar', 'compras')) {
            return false;
        }

        // 2. Comprobar estructura corporativa real del usuario (Área y Cargo)
        $userOrg = dbQueryOne("SELECT u.org_area_id, u.cargo_id,
                                      c.codigo as cargo_codigo, c.nombre as cargo_nombre,
                                      c.nivel_jerarquico, c.puede_aprobar,
                                      a.codigo as area_codigo, a.nombre as area_nombre
                               FROM usuarios u
                               LEFT JOIN org_cargos c ON u.cargo_id = c.id
                               LEFT JOIN org_areas a ON (u.org_area_id = a.id OR c.area_id = a.id)
                               WHERE u.id = ? LIMIT 1", [$userId]);

        if (!$userOrg) return false;

        $nivel = (int)($userOrg['nivel_jerarquico'] ?? 99);
        $cargoCodigo = strtoupper($userOrg['cargo_codigo'] ?? '');
        $areaCodigo = strtoupper($userOrg['area_codigo'] ?? '');
        $puedeAprobarCargo = (int)($userOrg['puede_aprobar'] ?? 0);

        // Pertenece a Gerencia (Nivel jerárquico 0, 1 o 2, o área directiva)
        $esGerencia = ($nivel <= 2) || in_array($areaCodigo, ['GG', 'GAF', 'GP']) || in_array($cargoCodigo, ['CEO', 'GG', 'GAF', 'GP']);

        // Pertenece a Logística (Área LOG o código de cargo JEFE-LOG / ASIST-LOG)
        $esLogistica = (strpos($cargoCodigo, 'LOG') !== false) || (strpos($areaCodigo, 'LOG') !== false) || ((int)($userOrg['org_area_id'] ?? 0) === 6);

        return ($esGerencia || $esLogistica || $puedeAprobarCargo === 1);
    }
}
