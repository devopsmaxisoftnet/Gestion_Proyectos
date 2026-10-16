<?php
require_once __DIR__ . '/../models/OportunidadModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';
require_once __DIR__ . '/../helpers/PermisosHelper.php';

class OportunidadController {
    private $model;
    
    public function __construct() {
        PermisosHelper::verificarAcceso('oportunidades');
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
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRol = $_SESSION['user']['rol'] ?? 'usuario';
        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $_SESSION['flash_error'] = 'Acceso denegado: Se requieren permisos de Administrador.';
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        if (!CsrfHelper::validateToken()) {
            $_SESSION['flash_error'] = 'Token de seguridad inválido o expirado.';
            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }

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
            // Include a modern minimal header for modal view with UTF-8 charset
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Seguimiento de Oportunidad</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"><link href="' . BASE_URL . '/assets/css/styles.css" rel="stylesheet"><style>body{background:#f8fafc;padding:0;margin:0;font-family:\'Inter\',sans-serif;color:#1e293b;overflow-x:hidden;}::-webkit-scrollbar{width:6px;height:6px;}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px;}</style></head><body class="is-modal-view">';
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
            if (isset($_POST['has_req_checks'])) {
                $eval_data['req_bases_tdr'] = isset($_POST['req_bases_tdr']) ? 1 : 0;
                $eval_data['req_legales'] = isset($_POST['req_legales']) ? 1 : 0;
                $eval_data['req_cronograma'] = isset($_POST['req_cronograma']) ? 1 : 0;
                $eval_data['req_condiciones'] = isset($_POST['req_condiciones']) ? 1 : 0;
            } else {
                if (isset($_POST['req_bases_tdr'])) $eval_data['req_bases_tdr'] = 1;
                if (isset($_POST['req_legales'])) $eval_data['req_legales'] = 1;
                if (isset($_POST['req_cronograma'])) $eval_data['req_cronograma'] = 1;
                if (isset($_POST['req_condiciones'])) $eval_data['req_condiciones'] = 1;
            }
            if (isset($_POST['viabilidad_alcance'])) $eval_data['viabilidad_alcance'] = $_POST['viabilidad_alcance'];
            if (isset($_POST['viabilidad_plazo'])) $eval_data['viabilidad_plazo'] = $_POST['viabilidad_plazo'];
            if (isset($_POST['viabilidad_riesgos'])) $eval_data['viabilidad_riesgos'] = $_POST['viabilidad_riesgos'];
            if (isset($_POST['informe_tecnico'])) $eval_data['informe_tecnico'] = $_POST['informe_tecnico'];
            if (isset($_POST['informe_economico'])) $eval_data['informe_economico'] = $_POST['informe_economico'];
            if (isset($_POST['consolidacion_informe'])) $eval_data['consolidacion_informe'] = $_POST['consolidacion_informe'];
            if (isset($_POST['consultas_observaciones'])) $eval_data['consultas_observaciones'] = $_POST['consultas_observaciones'];
            if (isset($_POST['revision_tecnica'])) $eval_data['revision_tecnica'] = $_POST['revision_tecnica'];
            if (isset($_POST['revision_economica'])) $eval_data['revision_economica'] = $_POST['revision_economica'];
            if (isset($_POST['seguimiento_legal'])) $eval_data['seguimiento_legal'] = $_POST['seguimiento_legal'];
            if (isset($_POST['apelacion_sustento'])) $eval_data['apelacion_sustento'] = $_POST['apelacion_sustento'];
            if (isset($_POST['perfeccionamiento_retencion'])) $eval_data['perfeccionamiento_retencion'] = $_POST['perfeccionamiento_retencion'];
            if (isset($_POST['num_cta_cte'])) $eval_data['num_cta_cte'] = $_POST['num_cta_cte'];
            if (isset($_POST['dni_representante_ruc'])) $eval_data['dni_representante_ruc'] = $_POST['dni_representante_ruc'];
            
            // Procesamiento de documentos e informes adjuntos (PDF, imagen, etc.) según flujo BPMN
            $doc_fields = [
                'doc_bases_tdr',
                'doc_req_legales',
                'doc_req_cronograma',
                'doc_req_condiciones',
                'doc_informe_tecnico',
                'doc_evaluacion_economica',
                'doc_informe_evaluacion',
                'doc_propuesta',
                'doc_constancia_inscripcion',
                'doc_constancia_presentacion',
                'doc_subsanacion',
                'doc_apelacion',
                'doc_acta_adjudicacion',
                'doc_contrato_garantias',
                'doc_desagregados',
                'doc_equipamiento',
                'doc_carta_fianza',
                'doc_cierre'
            ];

            $upload_dir = 'uploads/oportunidades/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $currentOp = $this->model->getById($id);
            $opCodigo = !empty($currentOp['codigo']) ? $currentOp['codigo'] : ('OP-' . $id);

            require_once __DIR__ . '/../helpers/FileSecurityHelper.php';
            require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';

            foreach ($doc_fields as $field) {
                if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = FileSecurityHelper::validateAndUpload($_FILES[$field], $upload_dir, [
                        'prefix' => $id . '_' . $field
                    ]);

                    if ($uploadResult['success']) {
                        $target_file = $uploadResult['path'];
                        $eval_data[$field] = $target_file;
                        // Sincronizar automáticamente con Google Drive en su subcarpeta correspondiente
                        try {
                            GoogleDriveHelper::uploadSystemFile($target_file, $uploadResult['orig_name'], 'Oportunidades', $opCodigo);
                        } catch (\Exception $e) {
                            // Silencioso para no interrumpir el flujo local
                        }
                    } else {
                        error_log("Upload rechazado en OportunidadController [{$field}]: " . $uploadResult['error']);
                    }
                }
            }
            
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

                // Si fue adjudicada (Buena Pro Ganada), generar o vincular automáticamente el contrato
                if ($siguiente_estado === 'adjudicada') {
                    $contratoExistente = dbQueryOne("SELECT id, codigo FROM contratos WHERE oportunidad_id = ?", [$id]);
                    if (!$contratoExistente) {
                        $opRow = $this->model->getById($id);
                        $totalContratos = dbQueryOne("SELECT COUNT(*) as t FROM contratos")['t'] ?? 0;
                        $numContrato = str_pad($totalContratos + 1, 3, '0', STR_PAD_LEFT);
                        $nuevoCodigo = 'CONT-' . date('Y') . '-' . $numContrato;
                        
                        while (dbQueryOne("SELECT id FROM contratos WHERE codigo = ?", [$nuevoCodigo])) {
                            $totalContratos++;
                            $nuevoCodigo = 'CONT-' . date('Y') . '-' . str_pad($totalContratos + 1, 3, '0', STR_PAD_LEFT);
                        }
                        
                        $monto = !empty($opRow['monto_estimado']) ? floatval($opRow['monto_estimado']) : 0.00;
                        $objeto = !empty($opRow['nombre']) ? $opRow['nombre'] : 'Contrato derivado de Adjudicación';
                        $cliId = !empty($opRow['cliente_id']) ? intval($opRow['cliente_id']) : null;
                        if (!$cliId) {
                            $cliDefault = dbQueryOne("SELECT id FROM clientes LIMIT 1");
                            $cliId = $cliDefault ? $cliDefault['id'] : null;
                        }
                        
                        dbExecute("
                            INSERT INTO contratos (codigo, oportunidad_id, cliente_id, objeto, monto_contrato, moneda, fecha_firma, fecha_inicio, fecha_fin, plazo_dias, estado, tipo_contrato)
                            VALUES (?, ?, ?, ?, ?, 'PEN', CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 180, 'en_elaboracion', ?)
                        ", [$nuevoCodigo, $id, $cliId, $objeto, $monto, $opRow['origen'] ?? 'Licitación Pública']);
                    }
                }
            }

            // BPMN Fase 5: Transferir a planificación de proyectos (Crear proyecto con expediente)
            if (isset($_POST['crear_proyecto']) || $siguiente_estado === 'transferir_proyecto') {
                $contratoRow = dbQueryOne("SELECT id, codigo, objeto, monto_contrato FROM contratos WHERE oportunidad_id = ? ORDER BY id DESC LIMIT 1", [$id]);
                $proyExistente = $contratoRow ? dbQueryOne("SELECT id FROM proyectos WHERE contrato_id = ?", [$contratoRow['id']]) : null;
                if (!$proyExistente && $contratoRow) {
                    $opRow = $this->model->getById($id);
                    $totalProy = dbQueryOne("SELECT COUNT(*) as t FROM proyectos")['t'] ?? 0;
                    $codProy = 'PROY-' . date('Y') . '-' . str_pad($totalProy + 1, 3, '0', STR_PAD_LEFT);
                    while (dbQueryOne("SELECT id FROM proyectos WHERE codigo = ?", [$codProy])) {
                        $totalProy++;
                        $codProy = 'PROY-' . date('Y') . '-' . str_pad($totalProy + 1, 3, '0', STR_PAD_LEFT);
                    }
                    dbExecute("
                        INSERT INTO proyectos (codigo, contrato_id, nombre, fecha_inicio, fecha_fin_planificada, monto_presupuesto, estado, observaciones)
                        VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), ?, 'planificacion', 'Transferido desde Oportunidad BPMN (Expediente completo de licitación y contrato)')
                    ", [$codProy, $contratoRow['id'], $opRow['nombre'], $contratoRow['monto_contrato']]);
                    
                    $newProy = dbQueryOne("SELECT id FROM proyectos WHERE codigo = ?", [$codProy]);
                    if ($newProy) {
                        $this->model->updateEvaluacion($id, ['proyecto_id' => $newProy['id']]);
                    }
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





