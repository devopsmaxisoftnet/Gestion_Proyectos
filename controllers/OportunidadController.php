<?php
require_once __DIR__ . '/../models/OportunidadModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';

class OportunidadController {
    private $model;
    
    public function __construct() {
        $this->model = new OportunidadModel();
    }
    
    public function index() {
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'linea_negocio_id' => $_GET['linea_negocio_id'] ?? '',
        ];
        
        $data = [
            'oportunidades' => $this->model->getAll($filtros),
            'filtros' => $filtros,
        ];
        
        $pageTitle = 'Bandeja de oportunidades';
        $currentPage = 'oportunidades';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/oportunidades/lista_oportunidades.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->create($_POST);
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        
        $data = [
            'lineas_negocio' => (new UsuarioModel())->getLineasNegocio(),
            'clientes' => (new ClienteModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        $pageTitle = 'Nueva oportunidad';
        $currentPage = 'oportunidades';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/oportunidades/crear_oportunidad.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function editar($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->update($id, $_POST);
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        
        $data = [
            'oportunidad' => $this->model->getById($id),
            'lineas_negocio' => (new UsuarioModel())->getLineasNegocio(),
            'clientes' => (new ClienteModel())->getAll(),
            'usuarios' => (new UsuarioModel())->getAll(),
        ];
        
        if (!$data['oportunidad']) {
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        
        $pageTitle = 'Editar oportunidad';
        $currentPage = 'oportunidades';
        
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/oportunidades/crear_oportunidad.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
    
    public function eliminar($id) {
        $this->model->delete($id);
        header('Location: ' . BASE_URL . '/oportunidades');
        exit;
    }
    
    public function ver($id) {
        $oportunidad = $this->model->getById($id);
        if (!$oportunidad) {
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        
        $evaluacion = $this->model->getEvaluacion($id) ?: [];
        
        $data = [
            'oportunidad' => $oportunidad,
            'evaluacion' => $evaluacion
        ];
        
        $pageTitle = 'Detalle de oportunidad';
        $currentPage = 'oportunidades';
        
        $isModal = isset($_GET['modal_iframe']) && $_GET['modal_iframe'] == '1';
        
        if (!$isModal) {
            include __DIR__ . '/../views/layout/header.php';
        } else {
            // Include a minimal header for modal to have styles
            echo '<!DOCTYPE html><html><head><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"><link href="' . BASE_URL . '/assets/css/styles.css" rel="stylesheet"><style>body{background:#fff;padding:20px;margin:0;}</style></head><body class="is-modal-view">';
        }
        
        include __DIR__ . '/../views/oportunidades/ver_oportunidad.php';
        
        if (!$isModal) {
            include __DIR__ . '/../views/layout/footer.php';
        } else {
            echo '</body></html>';
        }
    }

    public function procesar_etapa($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $estado_actual = $_POST['estado_actual'] ?? '';
            $siguiente_estado = $_POST['siguiente_estado'] ?? '';
            
            // Si hay datos de evaluación, los actualizamos
            $eval_data = [];
            if (isset($_POST['req_bases_tdr'])) $eval_data['req_bases_tdr'] = 1;
            if (isset($_POST['req_legales'])) $eval_data['req_legales'] = 1;
            if (isset($_POST['req_cronograma'])) $eval_data['req_cronograma'] = 1;
            if (isset($_POST['viabilidad_alcance'])) $eval_data['viabilidad_alcance'] = $_POST['viabilidad_alcance'];
            if (isset($_POST['viabilidad_plazo'])) $eval_data['viabilidad_plazo'] = $_POST['viabilidad_plazo'];
            if (isset($_POST['viabilidad_riesgos'])) $eval_data['viabilidad_riesgos'] = $_POST['viabilidad_riesgos'];
            if (isset($_POST['informe_tecnico'])) $eval_data['informe_tecnico'] = $_POST['informe_tecnico'];
            if (isset($_POST['informe_economico'])) $eval_data['informe_economico'] = $_POST['informe_economico'];
            if (isset($_POST['consolidacion_informe'])) $eval_data['consolidacion_informe'] = $_POST['consolidacion_informe'];
            if (isset($_POST['revision_tecnica'])) $eval_data['revision_tecnica'] = $_POST['revision_tecnica'];
            if (isset($_POST['revision_economica'])) $eval_data['revision_economica'] = $_POST['revision_economica'];
            
            if (!empty($eval_data)) {
                $this->model->updateEvaluacion($id, $eval_data);
            }
            
            // Actualizar si requiere eval especializada
            if (isset($_POST['requiere_eval_especializada'])) {
                dbExecute("UPDATE oportunidades SET requiere_evaluacion_especializada = ? WHERE id = ?", [intval($_POST['requiere_eval_especializada']), $id]);
            }
            if (isset($_POST['observaciones'])) {
                dbExecute("UPDATE oportunidades SET observaciones = ? WHERE id = ?", [trim($_POST['observaciones']), $id]);
            }
            
            // Cambiar el estado principal si se especificó
            if ($siguiente_estado) {
                $this->model->cambiarEstado($id, $siguiente_estado);
                
                if (in_array($siguiente_estado, ['descartada', 'no_adjudicada']) && !empty($_POST['motivo_descarte'])) {
                    dbExecute("UPDATE oportunidades SET motivo_descarte = ? WHERE id = ?", [trim($_POST['motivo_descarte']), $id]);
                }
            }
            
            $redirectUrl = BASE_URL . '/oportunidades/ver/' . $id;
            if (isset($_POST['modal_iframe']) && $_POST['modal_iframe'] == '1') {
                $redirectUrl .= '?modal_iframe=1';
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}





