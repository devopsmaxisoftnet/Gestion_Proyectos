<?php
/**
 * Controlador del Control Presupuestal por actividad (Fase 2: Planificación y proyectos)
 * Disponibilidad presupuestal, compromisos (OC) y bitácora de excesos autorizados.
 * ERP Gestión de Proyectos
 */
require_once __DIR__ . '/../models/ControlPresupuestalModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class ControlPresupuestalController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Comparte el módulo de permisos del WBS: quien gestiona actividades controla su presupuesto
        PermisosHelper::verificarAcceso('tareas');
        $this->model = new ControlPresupuestalModel();
    }

    /**
     * Tablero de control presupuestal de un proyecto
     */
    public function index() {
        PermisosHelper::requirePermiso('ver', 'tareas');

        $proyectos = (new TareaModel())->getProyectos();
        $proyectoId = !empty($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : (!empty($proyectos[0]['id']) ? (int)$proyectos[0]['id'] : 0);

        $proyectoActual = null;
        foreach ($proyectos as $p) {
            if ((int)$p['id'] === $proyectoId) { $proyectoActual = $p; break; }
        }

        $estado = null; $excesos = []; $resumenExcesos = ['n' => 0, 'total' => 0.0];
        if ($proyectoActual) {
            $estado = $this->model->getEstadoProyecto($proyectoId);
            $excesos = $this->model->getExcesos($proyectoId, 12);
            $resumenExcesos = $this->model->getResumenExcesos($proyectoId);
        }

        $pageTitle = 'Control Presupuestal';
        $currentPage = 'presupuesto';
        $faseActiva = 2;

        $data = [
            'migrado' => $this->model->disponible(),
            'proyectos' => $proyectos,
            'proyectoId' => $proyectoId,
            'proyectoActual' => $proyectoActual,
            'estado' => $estado,
            'excesos' => $excesos,
            'resumenExcesos' => $resumenExcesos,
            'puedeConfigurar' => PermisosHelper::puede('editar', 'tareas'),
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/presupuesto/index.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Guarda el modo de control, la tolerancia y el umbral de alerta del proyecto ($id = proyecto_id)
     */
    public function configurar($id) {
        PermisosHelper::requirePermiso('editar', 'tareas');
        $proyectoId = (int)$id;
        $volver = BASE_URL . '/presupuesto?proyecto_id=' . $proyectoId;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o método no permitido.';
            header('Location: ' . $volver);
            exit;
        }

        try {
            $this->model->guardarConfig(
                $proyectoId,
                (string)($_POST['modo'] ?? ''),
                (float)str_replace(',', '.', (string)($_POST['tolerancia'] ?? '0')),
                (float)str_replace(',', '.', (string)($_POST['umbral'] ?? '80')),
                (int)($_SESSION['user']['id'] ?? 0)
            );
            $_SESSION['flash_success'] = 'Control presupuestal actualizado para el proyecto.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'No se pudo guardar: ' . $e->getMessage();
        }

        header('Location: ' . $volver);
        exit;
    }
}
