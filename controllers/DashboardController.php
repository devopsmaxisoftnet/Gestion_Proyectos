<?php
require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController {
    private $model;
    
    public function __construct() {
        $this->model = new DashboardModel();
    }
    
    public function index() {
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
        ];
        
        // Obtener oportunidades por línea
        foreach ($data['cartera_lineas'] as &$linea) {
            $linea['oportunidades'] = $this->model->getOportunidadesPorLinea($linea['id']);
        }
        
        $pageTitle = 'Gestión de oportunidades';
        $currentPage = 'dashboard';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/dashboard/resumen_dashboard.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}





