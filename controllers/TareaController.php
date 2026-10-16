<?php
/**
 * Controlador de Tareas y EDT / WBS (Fase 2: Planificación de Proyectos)
 * ERP Gestión de Proyectos
 */
require_once __DIR__ . '/../models/TareaModel.php';
require_once __DIR__ . '/../models/PresupuestoModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class TareaController {
    private $model;
    private $presupuesto;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        PermisosHelper::verificarAcceso('tareas');
        $this->model = new TareaModel();
        $this->presupuesto = new PresupuestoModel();
    }

    /**
     * Vista Principal del WBS / Tareas por Proyecto
     */
    public function index() {
        PermisosHelper::requirePermiso('ver', 'tareas');

        $proyectos = $this->model->getProyectos();
        $proyectoId = !empty($_GET['proyecto_id']) ? (int)$_GET['proyecto_id'] : (!empty($proyectos[0]['id']) ? (int)$proyectos[0]['id'] : 0);

        $filtros = [
            'estado' => $_GET['estado'] ?? '',
            'prioridad' => $_GET['prioridad'] ?? '',
            'busqueda' => $_GET['busqueda'] ?? ''
        ];

        $proyectoActual = null;
        $tareas = [];
        $arbol = [];
        $resumen = [];
        $costos = ['totales' => [], 'por_tarea' => []];
        $graficos = [];
        $insumosPorTarea = [];
        $presupuestoInsumos = null;
        $catalogosInsumos = null;

        if ($proyectoId > 0) {
            foreach ($proyectos as $p) {
                if ((int)$p['id'] === $proyectoId) {
                    $proyectoActual = $p;
                    break;
                }
            }
            $tareas = $this->model->getAllByProyecto($proyectoId, $filtros);
            $arbol = $this->model->construirArbol($tareas);
            $resumen = $this->model->getResumenProyecto($proyectoId);
            $costos = $this->model->getCostosDirectosProyecto($proyectoId);
            $graficos = $this->armarDatosGraficos($arbol, $costos['totales']);
            if ($this->presupuesto->disponible()) {
                $insumosPorTarea = $this->presupuesto->getResumenPorTarea($proyectoId);
                $presupuestoInsumos = $this->presupuesto->getResumenProyecto($proyectoId);
                $catalogosInsumos = $this->presupuesto->getCatalogos();
            }
        }

        $usuarios = $this->model->getUsuarios();
        $esquemaFaltante = $this->model->getEsquemaFaltante();
        $pageTitle = 'Planificación WBS / EDT';
        $currentPage = 'tareas';
        $faseActiva = 2;

        $data = [
            'proyectos' => $proyectos,
            'proyectoId' => $proyectoId,
            'proyectoActual' => $proyectoActual,
            'tareas' => $tareas,
            'arbol' => $arbol,
            'resumen' => $resumen,
            'costos' => $costos,
            'graficos' => $graficos,
            'usuarios' => $usuarios,
            'filtros' => $filtros,
            'esquemaFaltante' => $esquemaFaltante,
            'insumosPorTarea' => $insumosPorTarea,
            'presupuestoInsumos' => $presupuestoInsumos,
            'catalogosInsumos' => $catalogosInsumos
        ];

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/tareas/index.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Ver WBS de un proyecto específico
     */
    public function proyecto($proyectoId) {
        $_GET['proyecto_id'] = (int)$proyectoId;
        $this->index();
    }

    /**
     * Crear nueva tarea EDT en el proyecto
     */
    public function crear() {
        PermisosHelper::requirePermiso('crear', 'tareas');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o sesión expirada.';
                $proyId = (int)($_POST['proyecto_id'] ?? 0);
                header('Location: ' . BASE_URL . '/tareas?proyecto_id=' . $proyId);
                exit;
            }

            try {
                $proyectoId = (int)($_POST['proyecto_id'] ?? 0);
                $this->model->create($_POST);
                $_SESSION['flash_success'] = 'Tarea agregada al WBS exitosamente.';
                header('Location: ' . BASE_URL . '/tareas?proyecto_id=' . $proyectoId);
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error al registrar tarea: ' . $e->getMessage();
                $proyId = (int)($_POST['proyecto_id'] ?? 0);
                header('Location: ' . BASE_URL . '/tareas?proyecto_id=' . $proyId);
                exit;
            }
        }
    }

    /**
     * Editar tarea existente
     */
    public function editar($id) {
        PermisosHelper::requirePermiso('editar', 'tareas');
        $id = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validateToken()) {
                $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
                $proyId = (int)($_POST['proyecto_id'] ?? 0);
                header('Location: ' . BASE_URL . '/tareas?proyecto_id=' . $proyId);
                exit;
            }

            try {
                $this->model->update($id, $_POST);
                $_SESSION['flash_success'] = 'Tarea actualizada correctamente.';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'Error al actualizar: ' . $e->getMessage();
            }

            $proyId = (int)($_POST['proyecto_id'] ?? 0);
            header('Location: ' . BASE_URL . '/tareas?proyecto_id=' . $proyId);
            exit;
        }

        // Si es AJAX para obtener datos de la tarea
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || !empty($_GET['ajax']);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            $tarea = $this->model->getById($id);
            if ($tarea) {
                echo json_encode(['success' => true, 'data' => $tarea]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Tarea no encontrada']);
            }
            exit;
        }
    }

    /**
     * Actualizar rápidamente el porcentaje de avance y estado (AJAX)
     */
    public function actualizarProgreso($id) {
        PermisosHelper::requirePermiso('editar', 'tareas');
        $id = (int)$id;

        header('Content-Type: application/json; charset=utf-8');

        if (!CsrfHelper::validateToken()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            exit;
        }

        $progreso = isset($_POST['progreso']) ? (float)$_POST['progreso'] : 0.00;
        $estado = !empty($_POST['estado']) ? trim($_POST['estado']) : null;

        try {
            $this->model->updateProgreso($id, $progreso, $estado);
            $tarea = $this->model->getById($id);
            $resumen = $tarea ? $this->model->getResumenProyecto((int)$tarea['proyecto_id']) : null;

            echo json_encode([
                'success' => true,
                'message' => 'Avance actualizado.',
                'tarea' => $tarea,
                'resumen' => $resumen
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Eliminar tarea WBS
     */
    public function eliminar($id) {
        PermisosHelper::requirePermiso('eliminar', 'tareas');
        $id = (int)$id;

        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido.';
            header('Location: ' . BASE_URL . '/tareas');
            exit;
        }

        $tarea = $this->model->getById($id);
        $proyectoId = $tarea['proyecto_id'] ?? 0;

        try {
            $this->model->delete($id);
            $_SESSION['flash_success'] = 'Tarea eliminada del WBS correctamente.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error al eliminar: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/tareas?proyecto_id=' . $proyectoId);
        exit;
    }

    /**
     * Desglose de gastos de una actividad (AJAX/JSON): compras, subcontratos, almacén y tareo.
     */
    public function desglose($id) {
        PermisosHelper::requirePermiso('ver', 'tareas');
        $id = (int)$id;

        header('Content-Type: application/json; charset=utf-8');
        $tarea = $this->model->getById($id);
        if (!$tarea) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
            exit;
        }

        $desglose = $this->model->getDesgloseTarea($id);
        $salud = TareaModel::calcularSalud((float)$tarea['costo_real'], (float)$tarea['costo_estimado']);

        echo json_encode([
            'success' => true,
            'tarea' => [
                'id' => (int)$tarea['id'],
                'codigo' => $tarea['codigo'],
                'nombre' => $tarea['nombre'],
                'costo_estimado' => (float)$tarea['costo_estimado'],
                'costo_real' => (float)$tarea['costo_real']
            ],
            'salud' => $salud,
            'directos' => $desglose['directos'],
            'detalle' => $desglose['detalle']
        ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    /**
     * Presupuesto por insumos de una actividad + comparación contra el consumo real (JSON).
     */
    public function insumos($id) {
        PermisosHelper::requirePermiso('ver', 'tareas');
        $id = (int)$id;

        header('Content-Type: application/json; charset=utf-8');
        $tarea = $this->model->getById($id);
        if (!$tarea) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'tarea' => [
                'id' => (int)$tarea['id'], 'codigo' => $tarea['codigo'], 'nombre' => $tarea['nombre'],
                'costo_estimado' => (float)$tarea['costo_estimado'], 'costo_real' => (float)$tarea['costo_real'],
                'es_hoja' => $this->model->contarDescendientes($id) === 0,
            ],
            'insumos' => $this->presupuesto->getInsumos($id),
            'comparativo' => $this->presupuesto->getComparativo($id),
        ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    /**
     * Guarda el presupuesto por insumos de una actividad hoja (POST + CSRF, campo `lineas` en JSON).
     */
    public function guardarInsumos($id) {
        PermisosHelper::requirePermiso('editar', 'tareas');
        $id = (int)$id;

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateToken()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido o sesión expirada.']);
            exit;
        }

        $lineas = json_decode((string)($_POST['lineas'] ?? '[]'), true);
        if (!is_array($lineas)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Formato de insumos inválido.']);
            exit;
        }

        try {
            $r = $this->presupuesto->guardarInsumos($id, array_values($lineas));
            $msg = $r['lineas'] > 0
                ? 'Presupuesto guardado: ' . $r['lineas'] . ' insumo(s), S/ ' . number_format($r['total'], 2) . '.'
                : 'Se quitó el detalle de insumos: la actividad vuelve a presupuesto manual.';
            $_SESSION['flash_success'] = $msg;
            echo json_encode(['success' => true, 'message' => $msg, 'total' => $r['total']]);
        } catch (Exception $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    /**
     * Fuerza el recálculo de costos de todo el proyecto (POST + CSRF). $id = proyecto_id.
     */
    public function recalcular($id) {
        PermisosHelper::requirePermiso('editar', 'tareas');
        $proyectoId = (int)$id;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o método no permitido.';
            header('Location: ' . BASE_URL . '/tareas?proyecto_id=' . $proyectoId);
            exit;
        }

        try {
            $r = $this->model->recalcularCostoProyecto($proyectoId);
            $_SESSION['flash_success'] = 'Costos recalculados para ' . $r['tareas'] . ' actividades. Costo real del proyecto: S/ ' . number_format($r['total_real'], 2);
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error al recalcular costos: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/tareas?proyecto_id=' . $proyectoId);
        exit;
    }

    /**
     * Prepara los datasets de Chart.js (barras Presupuesto vs Real y dona de distribución).
     * Es privado a propósito: el router solo expone métodos públicos.
     */
    private function armarDatosGraficos(array $arbol, array $totales): array {
        $armar = function (array $filas): array {
            $o = ['labels' => [], 'nombres' => [], 'estimado' => [], 'real' => [], 'colores' => []];
            foreach ($filas as $t) {
                $salud = TareaModel::calcularSalud((float)$t['costo_real'], (float)$t['costo_estimado']);
                $o['labels'][] = $t['codigo'];
                $o['nombres'][] = $t['nombre'];
                $o['estimado'][] = round((float)$t['costo_estimado'], 2);
                $o['real'][] = round((float)$t['costo_real'], 2);
                $o['colores'][] = $salud['color'];
            }
            return $o;
        };

        $principales = array_values(array_filter($arbol, fn($t) => $t['_nivel'] === 0));
        $hojas = array_values(array_filter($arbol, fn($t) => !$t['_tiene_hijos']));

        return [
            'principales' => $armar($principales),
            'detalle' => $armar($hojas),
            'dona' => [
                'labels' => ['Materiales', 'Equipos', 'Mano de Obra', 'Subcontratos'],
                'valores' => [
                    round($totales['materiales'] ?? 0, 2),
                    round($totales['equipos'] ?? 0, 2),
                    round($totales['mano_obra'] ?? 0, 2),
                    round($totales['subcontratos'] ?? 0, 2)
                ]
            ]
        ];
    }
}
