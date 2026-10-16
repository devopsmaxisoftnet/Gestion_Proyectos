<?php
/**
 * Controlador de Subcontratos y Valorizaciones del subcontratista (Fase 3: Logística)
 * ERP Gestión de Proyectos
 */
require_once __DIR__ . '/../models/SubcontratoModel.php';
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/ControlPresupuestalModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class SubcontratoController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        PermisosHelper::verificarAcceso('subcontratos');
        $this->model = new SubcontratoModel();
    }

    // ═════════════════════════════════════════════════════════════════════
    //  LISTADO
    // ═════════════════════════════════════════════════════════════════════

    public function index() {
        PermisosHelper::requirePermiso('ver', 'subcontratos');

        $filtros = [
            'proyecto_id' => !empty($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : null,
            'estado' => $_GET['estado'] ?? '',
            'q' => trim((string)($_GET['q'] ?? '')),
        ];
        $proyectoId = $filtros['proyecto_id'];

        $this->render('index', [
            'migrado' => $this->model->disponible(),
            'filtros' => $filtros,
            'subcontratos' => $this->model->disponible() ? $this->model->getAll($filtros) : [],
            'resumen' => $this->model->disponible() ? $this->model->getResumen($proyectoId) : null,
            'proyectos' => $this->model->getProyectos(),
            'permisos' => $this->permisos(),
        ], 'Subcontratos');
    }

    // ═════════════════════════════════════════════════════════════════════
    //  CONTRATO: ALTA Y EDICIÓN
    // ═════════════════════════════════════════════════════════════════════

    public function crear() {
        PermisosHelper::requirePermiso('crear', 'subcontratos');
        $this->formulario(null);
    }

    public function editar($id) {
        PermisosHelper::requirePermiso('editar', 'subcontratos');
        $sub = $this->model->getById((int)$id);
        if (!$sub) { $this->volver('El subcontrato no existe.', 'error', '/subcontratos'); }
        $this->formulario($sub);
    }

    private function formulario(?array $sub): void {
        $post = []; $subId = $sub ? (int)$sub['id'] : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $this->volver('Token de seguridad inválido o sesión expirada.', 'error', $subId ? '/subcontratos/editar/' . $subId : '/subcontratos/crear');
            }
            $post = $_POST;
            try {
                $userId = (int)($_SESSION['user']['id'] ?? 0);
                $pid = $subId ? (int)$sub['proyecto_id'] : (int)($post['proyecto_id'] ?? 0);
                $monto = (float)str_replace(',', '', (string)($post['monto'] ?? 0));
                $control = $this->aplicarControl($pid, (int)($post['tarea_id'] ?? 0), $monto, $subId, $post);

                if ($subId) {
                    $this->model->update($subId, $post);
                    $id = $subId; $msg = 'Subcontrato actualizado.';
                } else {
                    $id = $this->model->create($post);
                    $msg = 'Subcontrato registrado. Su monto ya se refleja como compromiso en el control presupuestal.';
                }
                if ($control['conExceso']) {
                    $codigo = (string)($this->model->getById($id)['codigo'] ?? ('#' . $id));
                    (new ControlPresupuestalModel())->registrarExcesos($pid, $control['eval'], 'subcontrato', $id, $codigo, $control['motivo'], $userId);
                    $msg .= ' El exceso de presupuesto quedó registrado en la bitácora.';
                }
                $this->volver($msg, 'success', '/subcontratos/ver/' . $id);
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'No se pudo guardar el subcontrato: ' . $e->getMessage();
            }
        } elseif (!empty($_GET['proyecto_id']) && !$sub) {
            $post['proyecto_id'] = (int)$_GET['proyecto_id'];
        }

        $this->render('form', [
            'sub' => $sub,
            'post' => $post,
            'proyectos' => $this->model->getProyectos(),
            'proveedores' => $this->model->getProveedores(),
            'puedeAutorizar' => ControlPresupuestalModel::puedeAutorizar('subcontratos'),
        ], $sub ? 'Editar Subcontrato ' . $sub['codigo'] : 'Nuevo Subcontrato');
    }

    /**
     * Control de disponibilidad presupuestal del compromiso que genera un contrato (alta, edición o reactivación).
     * @return array{eval:array, conExceso:bool, motivo:?string}
     * @throws Exception si el modo bloquear exige una autorización que no está dada
     */
    private function aplicarControl(int $proyectoId, int $tareaId, float $monto, ?int $subId, array $post): array {
        $cp = new ControlPresupuestalModel();
        $eval = $cp->evaluarSubcontrato($proyectoId, $tareaId, $monto, $subId);
        $conExceso = $eval['hay_exceso'] && $eval['modo'] !== 'informar';
        $motivo = null;

        if ($conExceso) {
            $motivoPost = trim((string)($post['motivo_exceso'] ?? ''));
            if ($eval['modo'] === 'bloquear') {
                $detalle = ControlPresupuestalModel::resumenExcesos($eval);
                if (!ControlPresupuestalModel::puedeAutorizar('subcontratos')) {
                    throw new Exception("el contrato excede el presupuesto de: $detalle. El proyecto está en modo bloquear y tu usuario no puede autorizar excesos.");
                }
                if (empty($post['autorizar_exceso'])) {
                    throw new Exception("el contrato excede el presupuesto de: $detalle. Marca la casilla de autorización e indica el motivo.");
                }
                $motivo = ControlPresupuestalModel::validarMotivo($motivoPost);
            } else {
                $motivo = $motivoPost !== '' ? mb_substr(preg_replace('/\s+/', ' ', $motivoPost), 0, 500) : 'Registrado con advertencia de presupuesto';
            }
        }
        return ['eval' => $eval, 'conExceso' => $conExceso, 'motivo' => $motivo];
    }

    // ═════════════════════════════════════════════════════════════════════
    //  FICHA DEL SUBCONTRATO
    // ═════════════════════════════════════════════════════════════════════

    public function ver($id) {
        PermisosHelper::requirePermiso('ver', 'subcontratos');
        $sub = $this->model->getById((int)$id);
        if (!$sub) { $this->volver('El subcontrato no existe.', 'error', '/subcontratos'); }

        // Reabrir o reactivar vuelve a comprometer el saldo: se muestra su impacto presupuestal
        $reactivar = null;
        if (in_array($sub['estado'], ['suspendido', 'finalizado'], true)) {
            $reactivar = (new ControlPresupuestalModel())->evaluarSubcontrato((int)$sub['proyecto_id'], (int)$sub['tarea_id'], (float)$sub['monto'], (int)$sub['id']);
        }

        $this->render('ver', [
            'sub' => $sub,
            'valorizaciones' => $this->model->getValorizaciones((int)$sub['id']),
            'reactivar' => $reactivar,
            'puedeAutorizar' => ControlPresupuestalModel::puedeAutorizar('subcontratos'),
            'permisos' => $this->permisos(),
        ], 'Subcontrato ' . $sub['codigo']);
    }

    // ═════════════════════════════════════════════════════════════════════
    //  ACCIONES (POST + CSRF)
    // ═════════════════════════════════════════════════════════════════════

    /** Cambia el estado del contrato. $id = subcontrato_id; POST: nuevo_estado (+ autorización si reactiva con exceso). */
    public function estado($id) {
        PermisosHelper::requirePermiso('editar', 'subcontratos');
        $id = (int)$id;
        $this->exigirPost('/subcontratos/ver/' . $id);

        $nuevo = (string)($_POST['nuevo_estado'] ?? '');
        if ($nuevo === 'anulado') { PermisosHelper::requirePermiso('eliminar', 'subcontratos'); }

        try {
            $sub = $this->model->getById($id);
            if (!$sub) { throw new Exception('El subcontrato no existe.'); }
            $control = ['conExceso' => false];
            if ($nuevo === 'activo') {
                $control = $this->aplicarControl((int)$sub['proyecto_id'], (int)$sub['tarea_id'], (float)$sub['monto'], $id, $_POST);
            }
            $this->model->cambiarEstado($id, $nuevo);
            $msg = ['activo' => 'Subcontrato activo.', 'finalizado' => 'Subcontrato finalizado: lo no valorizado dejó de comprometer presupuesto.',
                    'suspendido' => 'Subcontrato suspendido: su saldo ya no compromete presupuesto.', 'anulado' => 'Subcontrato anulado.'][$nuevo] ?? 'Estado actualizado.';
            if ($control['conExceso']) {
                (new ControlPresupuestalModel())->registrarExcesos((int)$sub['proyecto_id'], $control['eval'], 'subcontrato', $id, (string)$sub['codigo'], $control['motivo'], (int)($_SESSION['user']['id'] ?? 0));
                $msg .= ' El exceso quedó registrado en la bitácora.';
            }
            $this->volver($msg, 'success', '/subcontratos/ver/' . $id);
        } catch (Exception $e) {
            $this->volver('No se pudo cambiar el estado: ' . $e->getMessage(), 'error', '/subcontratos/ver/' . $id);
        }
    }

    /** Crea o edita (borrador) una valorización. $id = subcontrato_id; POST: valorizacion_id?, avance_acumulado_pct, periodo_desde, periodo_hasta, observaciones */
    public function valorizar($id) {
        PermisosHelper::requirePermiso('crear', 'subcontratos');
        $id = (int)$id;
        $this->exigirPost('/subcontratos/ver/' . $id);

        try {
            $valId = !empty($_POST['valorizacion_id']) ? (int)$_POST['valorizacion_id'] : null;
            $this->model->guardarValorizacion($id, $valId, $_POST, (int)($_SESSION['user']['id'] ?? 0));
            $this->volver($valId ? 'Valorización actualizada.' : 'Valorización registrada en borrador. Al aprobarla se suma al costo real de la actividad.', 'success', '/subcontratos/ver/' . $id);
        } catch (Exception $e) {
            $this->volver('No se pudo guardar la valorización: ' . $e->getMessage(), 'error', '/subcontratos/ver/' . $id);
        }
    }

    public function aprobarVal($id) { $this->accionValorizacion((int)$id, 'aprobar', 'aprobar', 'Valorización aprobada: ya forma parte del costo real de la actividad.'); }
    public function anularVal($id)  { $this->accionValorizacion((int)$id, 'eliminar', 'anular', 'Valorización anulada.'); }
    public function pagarVal($id)   { $this->accionValorizacion((int)$id, 'editar', 'pagar', 'Pago registrado.'); }

    private function accionValorizacion(int $valId, string $permiso, string $accion, string $okMsg): void {
        PermisosHelper::requirePermiso($permiso, 'subcontratos');
        $v = dbQueryOne("SELECT subcontrato_id FROM subcontrato_valorizaciones WHERE id = ?", [$valId]);
        $subId = (int)($v['subcontrato_id'] ?? 0);
        $volver = $subId ? '/subcontratos/ver/' . $subId : '/subcontratos';
        $this->exigirPost($volver);

        try {
            if (!$v) { throw new Exception('La valorización no existe.'); }
            if ($accion === 'aprobar') { $this->model->aprobarValorizacion($valId, (int)($_SESSION['user']['id'] ?? 0)); }
            elseif ($accion === 'anular') { $this->model->anularValorizacion($valId); }
            else { $this->model->pagarValorizacion($valId, (string)($_POST['fecha_pago'] ?? ''), (string)($_POST['nro_factura'] ?? '')); }
            $this->volver($okMsg, 'success', $volver);
        } catch (Exception $e) {
            $this->volver('No se pudo completar la acción: ' . $e->getMessage(), 'error', $volver);
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    //  JSON PARA EL FORMULARIO
    // ═════════════════════════════════════════════════════════════════════

    /** Actividades del proyecto con disponibilidad (verificación en vivo). $id = proyecto_id; GET excluir_sub = contrato en edición */
    public function tareasProyecto($id) {
        PermisosHelper::requirePermiso('ver', 'subcontratos');
        $excluir = !empty($_GET['excluir_sub']) ? (int)$_GET['excluir_sub'] : null;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'tareas' => (new CompraModel())->getTareasProyecto((int)$id, null, $excluir),
            'control' => (new ControlPresupuestalModel())->getConfig((int)$id),
            'puede_autorizar' => ControlPresupuestalModel::puedeAutorizar('subcontratos'),
        ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    // ═════════════════════════════════════════════════════════════════════
    //  PRIVADOS (no enrutables)
    // ═════════════════════════════════════════════════════════════════════

    private function permisos(): array {
        return [
            'crear' => PermisosHelper::puede('crear', 'subcontratos'),
            'editar' => PermisosHelper::puede('editar', 'subcontratos'),
            'aprobar' => PermisosHelper::puede('aprobar', 'subcontratos'),
            'eliminar' => PermisosHelper::puede('eliminar', 'subcontratos'),
        ];
    }

    private function exigirPost(string $volver): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateToken()) {
            $this->volver('Token de seguridad inválido o método no permitido.', 'error', $volver);
        }
    }

    private function volver(string $mensaje, string $tipo, string $ruta): void {
        $_SESSION[$tipo === 'success' ? 'flash_success' : 'flash_error'] = $mensaje;
        header('Location: ' . BASE_URL . $ruta);
        exit;
    }

    private function render(string $vista, array $data, string $titulo): void {
        $pageTitle = $titulo;
        $currentPage = 'subcontratos';
        $faseActiva = 3;
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/subcontratos/' . $vista . '.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}
