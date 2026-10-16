<?php
/**
 * Controlador de Tareo / RRHH por actividad (Centro de Costo = proyecto, imputación = actividad del WBS)
 * ERP Gestión de Proyectos
 */
require_once __DIR__ . '/../models/AsistenciaModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class AsistenciaController {
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        PermisosHelper::verificarAcceso('asistencias');
        $this->model = new AsistenciaModel();
    }

    /**
     * Hoja de tareo del día + productividad del proyecto
     */
    public function index() {
        PermisosHelper::requirePermiso('ver', 'asistencias');

        $proyectos = $this->model->getProyectos();
        $proyectoId = !empty($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : (!empty($proyectos[0]['id']) ? (int)$proyectos[0]['id'] : 0);
        $fecha = $this->fechaValida($_GET['fecha'] ?? '');

        $this->mostrar($proyectos, $proyectoId, $fecha, null);
    }

    /**
     * Guardar la hoja de tareo del día (reemplaza los registros del proyecto en esa fecha)
     */
    public function guardar() {
        PermisosHelper::requirePermiso('crear', 'asistencias');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/asistencias');
            exit;
        }

        $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
        $fecha = $this->fechaValida($_POST['fecha'] ?? '');
        $volver = BASE_URL . '/asistencias?proyecto_id=' . $proyectoId . '&fecha=' . $fecha;

        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o sesión expirada.';
            header('Location: ' . $volver);
            exit;
        }

        $items = is_array($_POST['tareo'] ?? null) ? array_values($_POST['tareo']) : [];

        try {
            $r = $this->model->guardarDia($proyectoId, $fecha, $items, (int)($_SESSION['user']['id'] ?? 0));
            $_SESSION['flash_success'] = $r['registros'] > 0
                ? 'Tareo guardado: ' . $r['registros'] . ' registro(s), ' . number_format($r['horas'], 1) . ' h, mano de obra S/ ' . number_format($r['costo'], 2) . ' imputada a las actividades.'
                : 'Se limpió el tareo del día.';
            header('Location: ' . $volver);
            exit;
        } catch (Exception $e) {
            // Se vuelve a mostrar la hoja con lo digitado para no perder el trabajo
            $_SESSION['flash_error'] = 'No se pudo guardar el tareo: ' . $e->getMessage();
            $this->mostrar($this->model->getProyectos(), $proyectoId, $fecha, $items);
        }
    }

    /**
     * Eliminar un registro de tareo (recalcula el costo de su actividad)
     */
    public function eliminar($id) {
        PermisosHelper::requirePermiso('eliminar', 'asistencias');
        $id = (int)$id;
        $proyectoId = !empty($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : 0;
        $fecha = $this->fechaValida($_GET['fecha'] ?? '');

        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido.';
        } else {
            try {
                $this->model->delete($id);
                $_SESSION['flash_success'] = 'Registro de tareo eliminado y costo de la actividad actualizado.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error al eliminar: ' . $e->getMessage();
            }
        }

        header('Location: ' . BASE_URL . '/asistencias?proyecto_id=' . $proyectoId . '&fecha=' . $fecha);
        exit;
    }

    // ── Privados (no enrutables) ─────────────────────────────────────────

    private function fechaValida(string $f): string {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : date('Y-m-d');
    }

    /**
     * Renderiza la hoja. $itemsPost != null reconstruye las filas con lo que el usuario había digitado.
     */
    private function mostrar(array $proyectos, int $proyectoId, string $fecha, ?array $itemsPost): void {
        $proyectoActual = null;
        $registros = []; $tareas = []; $resumen = []; $analitica = []; $otrasHoras = [];

        if ($proyectoId > 0) {
            foreach ($proyectos as $p) {
                if ((int)$p['id'] === $proyectoId) { $proyectoActual = $p; break; }
            }
            $tareas = $this->model->getTareasProyecto($proyectoId);
            $resumen = $this->model->getResumen($proyectoId, $fecha);
            $analitica = $this->model->getAnalitica($proyectoId);
            $otrasHoras = $this->model->getHorasOtrosProyectos($proyectoId, $fecha);

            $registros = $itemsPost ?? array_map(fn($r) => [
                'profesional_id' => (int)$r['profesional_id'],
                'tarea_id' => $r['tarea_id'] ? (int)$r['tarea_id'] : null,
                'horas_normales' => (float)$r['horas_normales'],
                'horas_extras' => (float)$r['horas_extras'],
                'tipo_labor' => $r['tipo_labor'],
                'observaciones' => $r['observaciones'],
                'guardado' => true,
            ], $this->model->getRegistrosDia($proyectoId, $fecha));
        }

        $pageTitle = 'Tareo / RRHH por Actividad';
        $currentPage = 'asistencias';
        $faseActiva = 2;

        $data = [
            'proyectos' => $proyectos,
            'proyectoId' => $proyectoId,
            'proyectoActual' => $proyectoActual,
            'fecha' => $fecha,
            'registros' => $registros,
            'reintento' => $itemsPost !== null,
            'profesionales' => $this->model->getProfesionales(),
            'tareas' => $tareas,
            'resumen' => $resumen,
            'analitica' => $analitica,
            'otrasHoras' => $otrasHoras,
            'puedeGuardar' => PermisosHelper::puede('crear', 'asistencias'),
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/asistencias/index.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}
