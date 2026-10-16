<?php
/**
 * Controlador de Almacén (Fase 3): ingresos desde OC, vales de salida a obra imputados al WBS,
 * inventario valorizado y alertas de stock crítico.
 * ERP Gestión de Proyectos
 */
require_once __DIR__ . '/../models/AlmacenModel.php';
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/ControlPresupuestalModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class AlmacenController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        PermisosHelper::verificarAcceso('almacenes');
        $this->model = new AlmacenModel();
    }

    /**
     * Panel principal: KPIs, gráficos, movimientos, inventario y OC por recepcionar
     */
    public function index() {
        PermisosHelper::requirePermiso('ver', 'almacenes');

        $filtros = [
            'tipo' => $_GET['tipo'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'proyecto_id' => !empty($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : null,
            'busqueda' => $_GET['busqueda'] ?? '',
        ];

        $data = [
            'kpis' => $this->model->getKpis(),
            'graficos' => [
                'meses' => $this->model->getMovimientosPorMes(6),
                'critico' => $this->model->getStockCritico(10),
            ],
            'movimientos' => $this->model->getMovimientos($filtros),
            'inventario' => $this->model->getInventario(),
            'porRecepcionar' => $this->model->getOrdenesRecepcionables(),
            'proyectos' => $this->model->getProyectos(),
            'filtros' => $filtros,
            'permisos' => $this->permisos(),
        ];

        $this->render('index', $data, 'Almacén · Inventario y Movimientos');
    }

    /**
     * Ingreso a almacén: recepción de una OC aprobada
     */
    public function ingreso() {
        PermisosHelper::requirePermiso('crear', 'almacenes');

        $ocId = (int)($_POST['orden_compra_id'] ?? $_GET['oc_id'] ?? 0);
        $viejo = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/almacen/ingreso' . ($ocId ? '?oc_id=' . $ocId : ''));
                exit;
            }

            $items = is_array($_POST['items'] ?? null) ? array_values($_POST['items']) : [];
            try {
                $movId = $this->model->registrarIngreso($ocId, $_POST, $items, (int)($_SESSION['user']['id'] ?? 0));
                $_SESSION['flash_success'] = 'Ingreso registrado: el stock se incrementó y el WBS se actualizó.';
                header('Location: ' . BASE_URL . '/almacen/ver/' . $movId);
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'No se pudo registrar el ingreso: ' . $e->getMessage();
                foreach ($items as $it) { $viejo[(int)($it['material_id'] ?? 0)] = $it['cantidad'] ?? ''; }
            }
        }

        $this->render('ingreso', [
            'ordenes' => $this->model->getOrdenesRecepcionables(),
            'ocId' => $ocId,
            'viejo' => $viejo,
            'post' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [],
        ], 'Ingreso a Almacén');
    }

    /**
     * Vale de salida a obra: cada línea se imputa a una actividad del WBS
     */
    public function salida() {
        PermisosHelper::requirePermiso('crear', 'almacenes');

        $post = [];
        $items = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                header('Location: ' . BASE_URL . '/almacen/salida');
                exit;
            }

            $post = $_POST;
            $items = is_array($_POST['items'] ?? null) ? array_values($_POST['items']) : [];
            try {
                $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
                $userId = (int)($_SESSION['user']['id'] ?? 0);

                // Control de disponibilidad presupuestal: avisa, registra o exige autorización según el modo del proyecto
                $cp = new ControlPresupuestalModel();
                $eval = $cp->evaluarSalida($proyectoId, $items);
                $conExceso = $eval['hay_exceso'] && $eval['modo'] !== 'informar';
                $motivoExceso = null;
                if ($conExceso) {
                    $motivoPost = trim((string)($_POST['motivo_exceso'] ?? ''));
                    if ($eval['modo'] === 'bloquear') {
                        $detalle = ControlPresupuestalModel::resumenExcesos($eval);
                        if (!ControlPresupuestalModel::puedeAutorizar('almacenes')) {
                            throw new Exception("el vale excede el presupuesto de: $detalle. El proyecto está en modo bloquear y tu usuario no puede autorizar excesos.");
                        }
                        if (empty($_POST['autorizar_exceso'])) {
                            throw new Exception("el vale excede el presupuesto de: $detalle. Marca la casilla de autorización e indica el motivo.");
                        }
                        $motivoExceso = ControlPresupuestalModel::validarMotivo($motivoPost);
                    } else {
                        $motivoExceso = $motivoPost !== '' ? mb_substr(preg_replace('/\s+/', ' ', $motivoPost), 0, 500) : 'Registrado con advertencia de presupuesto';
                    }
                }

                $movId = $this->model->registrarSalida($proyectoId, $_POST, $items, $userId);
                $msg = 'Vale de salida registrado: stock descontado y costo real de las actividades actualizado.';
                if ($conExceso) {
                    $mov = $this->model->getMovimiento($movId);
                    $cp->registrarExcesos($proyectoId, $eval, 'salida', $movId, (string)($mov['guia_remision'] ?? ('#' . $movId)), $motivoExceso, $userId);
                    $msg .= ' El exceso de presupuesto quedó registrado en la bitácora de control presupuestal.';
                }
                $_SESSION['flash_success'] = $msg;
                header('Location: ' . BASE_URL . '/almacen/ver/' . $movId);
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'No se pudo registrar el vale: ' . $e->getMessage();
            }
        } elseif (!empty($_GET['proyecto_id'])) {
            $post['proyecto_id'] = (int)$_GET['proyecto_id'];
        }

        $this->render('salida', [
            'proyectos' => $this->model->getProyectos(),
            'catalogo' => $this->model->getCatalogoSalida(),
            'post' => $post,
            'items' => array_map(fn($i) => [
                'tarea_id' => !empty($i['tarea_id']) ? (int)$i['tarea_id'] : null,
                'material_id' => !empty($i['material_id']) ? (int)$i['material_id'] : null,
                'equipo_id' => !empty($i['equipo_id']) ? (int)$i['equipo_id'] : null,
                'cantidad' => (float)($i['cantidad'] ?? 1),
            ], $items),
        ], 'Vale de Salida a Obra');
    }

    /**
     * Detalle de un movimiento
     */
    public function ver($id) {
        PermisosHelper::requirePermiso('ver', 'almacenes');
        $id = (int)$id;

        $mov = $this->model->getMovimiento($id);
        if (!$mov) {
            $_SESSION['flash_error'] = 'El movimiento no existe.';
            header('Location: ' . BASE_URL . '/almacen');
            exit;
        }

        $this->render('ver', [
            'mov' => $mov,
            'detalles' => $this->model->getDetallesMovimiento($id),
            'permisos' => $this->permisos(),
        ], 'Movimiento de Almacén #' . $id);
    }

    /**
     * Anula un movimiento (POST + CSRF + motivo)
     */
    public function anular($id) {
        PermisosHelper::requirePermiso('editar', 'almacenes');
        $id = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o método no permitido.';
            header('Location: ' . BASE_URL . '/almacen/ver/' . $id);
            exit;
        }

        try {
            $this->model->anular($id, (string)($_POST['motivo'] ?? ''));
            $_SESSION['flash_success'] = 'Movimiento anulado: stock, costo promedio y costos del WBS revertidos.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'No se pudo anular: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/almacen/ver/' . $id);
        exit;
    }

    /** JSON: actividades de un proyecto (para el vale de salida). $id = proyecto_id */
    public function tareasProyecto($id) {
        PermisosHelper::requirePermiso('ver', 'almacenes');
        $this->json([
            'success' => true,
            'tareas' => (new CompraModel())->getTareasProyecto((int)$id),
            'control' => (new ControlPresupuestalModel())->getConfig((int)$id),
            'puede_autorizar' => ControlPresupuestalModel::puedeAutorizar('almacenes'),
        ]);
    }

    /** JSON: materiales pendientes de recepción de una OC. $id = orden_compra_id */
    public function pendientesOC($id) {
        PermisosHelper::requirePermiso('ver', 'almacenes');
        $oc = $this->model->getOrdenResumen((int)$id);
        if (!$oc) { $this->json(['success' => false, 'message' => 'Orden no encontrada'], 404); }
        $this->json(['success' => true, 'orden' => $oc, 'items' => $this->model->getPendientesOC((int)$id)]);
    }

    /** JSON: historial de movimientos de un material. $id = material_id */
    public function kardex($id) {
        PermisosHelper::requirePermiso('ver', 'almacenes');
        $this->json(['success' => true, 'movimientos' => $this->model->getKardex((int)$id)]);
    }

    // ── Privados (no enrutables) ─────────────────────────────────────────

    private function permisos(): array {
        return [
            'crear' => PermisosHelper::puede('crear', 'almacenes'),
            'editar' => PermisosHelper::puede('editar', 'almacenes'),
        ];
    }

    private function render(string $vista, array $data, string $titulo): void {
        $pageTitle = $titulo;
        $currentPage = 'almacenes';
        $faseActiva = 3;

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/almacen/' . $vista . '.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    private function json(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }
}
