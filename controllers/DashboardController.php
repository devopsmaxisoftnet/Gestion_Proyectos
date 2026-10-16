<?php
require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class DashboardController {
    private $model;
    
    public function __construct() {
        $this->model = new DashboardModel();
    }
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $uIdSesion = (int)($_SESSION['user']['id'] ?? 0);
        $userRol = $_SESSION['user']['rol'] ?? 'usuario';

        // Sincronizar en caliente el estado del colaborador desde la base de datos
        if ($userRol === 'usuario' && $uIdSesion > 0) {
            $uDbCheck = dbQueryOne("
                SELECT u.acceso_aprobado, u.rol, u.rol_id, u.tipo_usuario_id, u.area_id, u.cargo_id, u.org_area_id,
                       COALESCE(oa.nombre, a.nombre) as area_nombre,
                       oc.nombre as cargo_nombre
                FROM usuarios u
                LEFT JOIN org_areas oa ON COALESCE(u.org_area_id, u.area_id) = oa.id
                LEFT JOIN areas a ON u.area_id = a.id
                LEFT JOIN org_cargos oc ON u.cargo_id = oc.id
                WHERE u.id = ? LIMIT 1
            ", [$uIdSesion]);
            if ($uDbCheck) {
                $_SESSION['user']['acceso_aprobado'] = (int)($uDbCheck['acceso_aprobado'] ?? 0);
                if (!empty($uDbCheck['org_area_id']) || !empty($uDbCheck['area_id'])) {
                    $_SESSION['user']['area_id'] = $uDbCheck['org_area_id'] ?? $uDbCheck['area_id'];
                }
                if (!empty($uDbCheck['cargo_id'])) {
                    $_SESSION['user']['cargo_id'] = $uDbCheck['cargo_id'];
                    $_SESSION['user']['cargo_nombre'] = $uDbCheck['cargo_nombre'];
                }
            }
        }

        $accesoAprobado = $_SESSION['user']['acceso_aprobado'] ?? 0;

        // Si es colaborador pendiente de aprobación, renderizar el portal de espera personalizado
        if ($userRol === 'usuario' && !$accesoAprobado) {
            if (isset($_GET['fase']) && (int)$_GET['fase'] !== 1) {
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }

            $pageTitle = 'Portal Colaborador - En Espera de Asignación';
            $currentPage = 'dashboard';

            include __DIR__ . '/../views/layout/header.php';
            include __DIR__ . '/../views/dashboard/colaborador_pendiente.php';
            include __DIR__ . '/../views/layout/footer.php';
            return;
        }

        // Fases 2 a 5: cada una tiene su propio dashboard
        $fase = isset($_GET['fase']) ? (int)$_GET['fase'] : 1;
        if ($fase >= 2 && $fase <= 5) {
            $this->indexFase($fase);
            return;
        }

        $data = [
            'oportunidades_activas' => $this->model->getOportunidadesActivas(),
            'licitaciones' => $this->model->getLicitacionesCount(),
            'propuestas' => $this->model->getPropuestasCount(),
            'contratos_vigentes' => $this->model->getContratosVigentes(),
            'valor_portafolio' => $this->model->getValorPortafolio(),
            'cartera_lineas' => $this->model->getCarteraPorLinea(),
            'oportunidades_prioritarias' => $this->model->getOportunidadesPrioritarias(),
            'flujo_comercial' => $this->model->getFlujoComercial(),
            'proximos_vencimientos' => $this->model->getProximosVencimientos(),
            'licitaciones_preparacion' => $this->model->getLicitacionesEnPreparacion(),
            'evaluacion_portafolio' => $this->model->getEvaluacionPortafolio(),
            'desglose_oportunidades' => $this->model->getDesgloseCompletoOportunidades(),
        ];
        
        // Obtener oportunidades por línea
        foreach ($data['cartera_lineas'] as $key => $linea) {
            $data['cartera_lineas'][$key]['oportunidades'] = $this->model->getOportunidadesPorLinea($linea['id']);
        }
        
        $pageTitle = 'Gestión de oportunidades';
        $currentPage = 'dashboard';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/dashboard/resumen_dashboard.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Dashboard de las fases 2 a 5. Privado: el router solo expone métodos públicos.
     * El acceso se valida con el mismo módulo que abre la fase en el menú lateral.
     */
    private function indexFase(int $fase): void {
        $modulo = [2 => 'proyectos', 3 => 'compras', 4 => 'tesoreria', 5 => 'contabilidad'][$fase];
        if (!PermisosHelper::tieneAcceso($modulo)) {
            $_SESSION['flash_error'] = 'No tienes acceso al panel de esta fase según tu puesto en el organigrama.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        require_once __DIR__ . '/../models/DashboardFasesModel.php';
        $fasesModel = new DashboardFasesModel();
        $data = $fasesModel->{'fase' . $fase}();

        $titulos = [
            2 => 'Planificación y proyectos',
            3 => 'Ejecución y operación',
            4 => 'Control económico y financiero',
            5 => 'Análisis y rentabilidad',
        ];
        $pageTitle = $titulos[$fase];
        $currentPage = 'dashboard';
        $faseActiva = $fase;

        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/dashboard/fase' . $fase . '.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    /**
     * Endpoint AJAX para obtener el desglose detallado de oportunidades y precios
     */
    public function desgloseOportunidades() {
        header('Content-Type: application/json; charset=utf-8');
        $lineaId = $_GET['linea_id'] ?? null;
        $items = $this->model->getDesgloseCompletoOportunidades();
        
        if ($lineaId !== null && $lineaId !== '' && $lineaId !== 'todas') {
            $items = array_values(array_filter($items, function($item) use ($lineaId) {
                return (string)$item['linea_negocio_id'] === (string)$lineaId;
            }));
        }
        
        $totalSuma = 0;
        foreach ($items as $it) {
            $totalSuma += (float)($it['monto_estimado'] ?? 0);
        }
        
        echo json_encode([
            'success' => true,
            'total_suma' => $totalSuma,
            'total_suma_formateado' => formatMoney($totalSuma),
            'count' => count($items),
            'data' => $items
        ]);
        exit;
    }
}





