<!-- Detalle Oportunidad - Flujo BPMN -->
<?php 
$item = $data['oportunidad']; 
$eval = $data['evaluacion'] ?? [];
$isModal = isset($_GET['modal_iframe']) && $_GET['modal_iframe'] == '1';

$estadosFlujo = [
    'identificada' => [
        'num' => 1,
        'label' => 'Identificación',
        'title' => 'Identificación y Clasificación',
        'icon' => 'fas fa-search',
        'area' => 'Licitaciones'
    ],
    'evaluacion_requisitos' => [
        'num' => 2,
        'label' => 'Requisitos',
        'title' => 'Revisión de Requisitos Mínimos',
        'icon' => 'fas fa-tasks',
        'area' => 'Licitaciones'
    ],
    'viabilidad_preliminar' => [
        'num' => 3,
        'label' => 'Viabilidad',
        'title' => 'Viabilidad Preliminar',
        'icon' => 'fas fa-chart-pie',
        'area' => 'Gestión de Proyectos'
    ],
    'evaluacion_especializada' => [
        'num' => 4,
        'label' => 'Especializada',
        'title' => 'Evaluación Especializada',
        'icon' => 'fas fa-microscope',
        'area' => 'Consultoría y Licitaciones'
    ],
    'consolidacion_viable' => [
        'num' => 5,
        'label' => 'Consolidación',
        'title' => 'Consolidar Evaluación',
        'icon' => 'fas fa-compress-arrows-alt',
        'area' => 'Licitaciones'
    ],
    'elaboracion_propuesta' => [
        'num' => 6,
        'label' => 'Elaborar Prop.',
        'title' => 'Elaborar Propuesta Técnica y Económica',
        'icon' => 'fas fa-file-contract',
        'area' => 'Licitaciones'
    ],
    'preparacion_propuesta' => [
        'num' => 6,
        'label' => 'Elaborar Prop.',
        'title' => 'Elaborar Propuesta Técnica y Económica',
        'icon' => 'fas fa-file-contract',
        'area' => 'Licitaciones'
    ],
    'revision_propuesta' => [
        'num' => 7,
        'label' => 'Revisión Prop.',
        'title' => 'Revisión y Validación de Propuesta',
        'icon' => 'fas fa-check-double',
        'area' => 'Consultoría y Proyectos'
    ],
    'aprobacion_gerencia' => [
        'num' => 8,
        'label' => 'Gerencia',
        'title' => 'Aprobación de Gerencia General / CEO',
        'icon' => 'fas fa-user-tie',
        'area' => 'Dirección General'
    ],
    'presentada' => [
        'num' => 9,
        'label' => 'Seguimiento',
        'title' => 'Seguimiento y Resultados',
        'icon' => 'fas fa-paper-plane',
        'area' => 'Licitaciones'
    ],
    'adjudicada' => [
        'num' => 10,
        'label' => 'Adjudicación',
        'title' => 'Adjudicada (Buena Pro)',
        'icon' => 'fas fa-trophy',
        'area' => 'Contratación'
    ]
];

// Pasos únicos para el stepper visual según modelo BPMN Bizagi
$stepperPasos = [
    1 => ['key' => 'identificada', 'label' => 'Identificación', 'title' => '1. Identificación y Registro', 'fase' => '1. Identificación y registro', 'area' => 'Licitaciones', 'num' => 1],
    2 => ['key' => 'evaluacion_requisitos', 'label' => 'Requisitos', 'title' => '2. Revisión de Requisitos Mínimos', 'fase' => '2. Evaluación y decisión de participación', 'area' => 'Licitaciones', 'num' => 2],
    3 => ['key' => 'viabilidad_preliminar', 'label' => 'Viabilidad', 'title' => '3. Viabilidad Preliminar', 'fase' => '2. Evaluación y decisión de participación', 'area' => 'Gestión de Proyectos', 'num' => 3],
    4 => ['key' => 'evaluacion_especializada', 'label' => 'Especializada', 'title' => '4. Evaluación Especializada', 'fase' => '2. Evaluación y decisión de participación', 'area' => 'Consultorías y Licitaciones', 'num' => 4],
    5 => ['key' => 'consolidacion_viable', 'label' => 'Consolidación', 'title' => '5. Consolidar Evaluación', 'fase' => '2. Evaluación y decisión de participación', 'area' => 'Licitaciones', 'num' => 5],
    6 => ['key' => 'elaboracion_propuesta', 'label' => 'Elaborar Prop.', 'title' => '6. Elaborar Propuesta Técnica y Económica', 'fase' => '3. Preparación y aprobación de propuesta', 'area' => 'Licitaciones', 'num' => 6],
    7 => ['key' => 'revision_propuesta', 'label' => 'Revisión Prop.', 'title' => '7. Revisión y Validación de Propuesta', 'fase' => '3. Preparación y aprobación de propuesta', 'area' => 'Consultorías y Gestión de Proyectos', 'num' => 7],
    8 => ['key' => 'aprobacion_gerencia', 'label' => 'Gerencia', 'title' => '8. Aprobación de Gerencia General / CEO', 'fase' => '3. Preparación y aprobación de propuesta', 'area' => 'Gerencia General / CEO', 'num' => 8],
    9 => ['key' => 'presentada', 'label' => 'Seguimiento', 'title' => '9. Presentación y Seguimiento', 'fase' => '4. Presentación y seguimiento', 'area' => 'Licitaciones y Administración', 'num' => 9],
    10 => ['key' => 'adjudicada', 'label' => 'Resultados', 'title' => '10. Resultados, Contrato y Transición', 'fase' => '5. Resultados y transición', 'area' => 'Licitaciones y Gestión de Proyectos', 'num' => 10],
];

// Mapeo de estado actual a número de paso 1..10
$estadoToStep = [
    'identificada' => 1,
    'evaluacion_requisitos' => 2,
    'viabilidad_preliminar' => 3,
    'evaluacion_especializada' => 4,
    'consolidacion_viable' => 5,
    'elaboracion_propuesta' => 6,
    'preparacion_propuesta' => 6,
    'revision_propuesta' => 7,
    'aprobacion_gerencia' => 8,
    'presentada' => 9,
    'adjudicada' => 10,
    'no_adjudicada' => 10,
    'descartada' => 10,
];

$currentStepNum = $estadoToStep[$item['estado']] ?? 1;
$isClosed = in_array($item['estado'], ['descartada', 'no_adjudicada', 'adjudicada']);

if ($item['estado'] === 'adjudicada') {
    $progressPct = 100;
} elseif ($isClosed) {
    $progressPct = round(($currentStepNum / 10) * 100);
} else {
    $progressPct = round(($currentStepNum / 10) * 100);
}

// Función auxiliar para renderizar el uploader de documentos BPMN
function renderDocUpload($name, $label, $currentPath = null, $desc = 'Adjunte documento en PDF, Word, Excel o Imagen') {
    ?>
    <div class="op-doc-field">
        <label class="op-doc-label"><i class="fas fa-file-invoice text-primary"></i> <?= $label ?></label>
        
        <?php if (!empty($currentPath)): ?>
            <div class="op-current-doc">
                <a href="<?= BASE_URL ?>/<?= htmlspecialchars($currentPath) ?>" target="_blank" class="op-doc-link" title="Abrir archivo adjunto">
                    <i class="fas fa-file-pdf"></i>
                    <span class="op-doc-filename"><?= htmlspecialchars(basename($currentPath)) ?></span>
                    <span class="op-doc-tag"><i class="fas fa-eye"></i> Ver documento</span>
                </a>
            </div>
        <?php endif; ?>

        <div class="op-upload-dropzone <?= !empty($currentPath) ? 'is-replace' : '' ?>">
            <input type="file" name="<?= $name ?>" id="input_<?= $name ?>" class="op-file-native" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" onchange="actualizarNombreDoc(this)">
            <div class="op-dropzone-content">
                <div class="op-dropzone-icon">
                    <i class="fas <?= !empty($currentPath) ? 'fa-arrows-rotate' : 'fa-cloud-arrow-up' ?>"></i>
                </div>
                <div class="op-dropzone-text">
                    <strong class="op-dropzone-title"><?= !empty($currentPath) ? 'Reemplazar archivo adjunto' : 'Seleccionar o arrastrar documento / informe' ?></strong>
                    <span class="op-dropzone-sub"><?= $desc ?> (Máx. 25MB)</span>
                </div>
            </div>
            <div class="op-file-name-display" id="display_<?= $name ?>"></div>
        </div>
    </div>
    <?php
}
?>

<style>
/* Reset & Base dentro del modal o vista */
.op-modal-wrapper {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    padding: <?= $isModal ? '14px 18px 30px' : '20px 24px 40px' ?>;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1e293b;
    line-height: 1.5;
}

/* 1. Header Hero Card */
.op-hero-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    padding: 16px 18px;
    margin-bottom: 14px;
    position: relative;
    overflow: hidden;
}
.op-hero-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6, #3b82f6);
}

.op-hero-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.op-tags-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.op-badge-code {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    color: #4f46e5;
    background: #eef2ff;
    padding: 3px 8px;
    border-radius: 6px;
    letter-spacing: 0.3px;
}
.op-badge-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 20px;
}
.op-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    display: inline-block;
}
.op-step-counter {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    background: #f1f5f9;
    padding: 3px 10px;
    border-radius: 20px;
}

.op-hero-title {
    font-size: 16.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 14px 0;
    line-height: 1.35;
    word-break: break-word;
}

/* Metadata Grid 2x2 */
.op-meta-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px 14px;
    background: #f8fafc;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #f1f5f9;
}
.op-meta-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.op-meta-label {
    font-size: 10.5px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.op-meta-label i {
    font-size: 11px;
    color: #8b5cf6;
}
.op-meta-value {
    font-size: 12.5px;
    font-weight: 600;
    color: #1e293b;
    word-break: break-word;
}
.op-money-val {
    color: #059669 !important;
    font-weight: 700 !important;
    font-size: 13.5px !important;
}

/* 2. BPMN Stepper Card */
.op-stepper-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    padding: 14px 16px;
    margin-bottom: 14px;
}

.op-stepper-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 12px;
}
.op-stepper-title {
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 6px;
}
.op-stepper-title i {
    color: #6366f1;
}
.op-stepper-pct {
    font-weight: 700;
    color: #6366f1;
    background: #eef2ff;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
}

.op-progress-bar {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 16px;
}
.op-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
    border-radius: 10px;
    transition: width 0.4s ease;
}

/* Horizontal Scrollable Step Pills */
.op-step-pills-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
    overflow-x: auto;
    padding: 8px 4px 12px 4px;
    margin-top: 4px;
    margin-bottom: 2px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.op-step-pills-wrap::-webkit-scrollbar {
    height: 5px;
}
.op-step-pills-wrap::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.op-step-pills-wrap::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.op-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    border-radius: 10px;
    font-size: 11.5px;
    font-weight: 600;
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer !important;
    border: 1px solid #e2e8f0;
    outline: none;
    font-family: inherit;
    background: #f8fafc;
    color: #475569;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.op-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.op-pill.completed {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.op-pill.completed:hover {
    background: #d1fae5;
    color: #047857;
}
.op-pill.active {
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.35);
    border: 1px solid transparent;
}
.op-pill.pending {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.op-pill.pending:hover {
    background: #f1f5f9;
    color: #334155;
}
.op-pill.selected-tab {
    outline: 2.5px solid #6366f1 !important;
    outline-offset: 1px !important;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35) !important;
}
.op-pill-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    font-size: 10px;
    font-weight: 700;
}
.op-pill.completed .op-pill-num {
    background: #10b981;
    color: #fff;
}
.op-pill.active .op-pill-num {
    background: #ffffff;
    color: #6366f1;
}
.op-pill.pending .op-pill-num {
    background: #e2e8f0;
    color: #64748b;
}

/* 3. Stage Action Card */
.op-stage-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #6366f1;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    padding: 18px 20px;
    margin-bottom: 14px;
}

.op-stage-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}
.op-stage-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.25);
}
.op-stage-info {
    flex: 1;
}
.op-stage-info h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}
.op-stage-subtitle {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    color: #64748b;
    font-weight: 500;
    margin-top: 3px;
}
.op-stage-subtitle i {
    color: #8b5cf6;
}

/* Guide banner */
.op-guide-banner {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 10px 12px;
    margin-bottom: 16px;
    font-size: 12px;
    color: #475569;
    display: flex;
    align-items: flex-start;
    gap: 9px;
    line-height: 1.45;
}
.op-guide-banner i {
    color: #6366f1;
    font-size: 14px;
    margin-top: 2px;
    flex-shrink: 0;
}

/* Checklist & Requirement Cards (Paso 2: 4 Requisitos con sus adjuntos) */
.op-checklist {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}
.op-req-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 11px;
    padding: 12px 14px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.op-req-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 3px 10px rgba(0,0,0,0.04);
}
.op-req-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}
.op-req-check-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    flex: 1;
}
.op-req-check-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #6366f1;
    cursor: pointer;
    margin: 0;
    flex-shrink: 0;
}
.op-check-content {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.op-check-title {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
}
.op-check-desc {
    font-size: 11px;
    color: #64748b;
}
.op-req-doc-box {
    padding-left: 28px;
}

/* Inputs & Textareas */
.op-form-group {
    margin-bottom: 14px;
}
.op-form-group label {
    display: block;
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 5px;
}
.op-textarea {
    width: 100%;
    box-sizing: border-box;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 9px 12px;
    font-family: inherit;
    font-size: 12.5px;
    color: #0f172a;
    transition: all 0.2s;
    outline: none;
    resize: vertical;
}
.op-textarea:focus {
    background: #ffffff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

/* Executive Inputs & Selects for BPMN Forms */
.op-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.op-input-icon {
    position: absolute;
    left: 13px;
    color: #6366f1;
    font-size: 13px;
    pointer-events: none;
    z-index: 2;
    transition: color 0.2s ease;
}
.op-input {
    width: 100%;
    box-sizing: border-box;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    border-radius: 9px;
    padding: 9px 13px 9px 38px;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 500;
    color: #0f172a;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
    height: 40px;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);
}
.op-input:focus {
    background: #ffffff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}
.op-input:focus + .op-input-icon,
.op-input-wrap:focus-within .op-input-icon {
    color: #4f46e5;
}
.op-input::placeholder {
    color: #94a3b8;
    font-weight: 400;
}
.op-select {
    width: 100%;
    box-sizing: border-box;
    background-color: #f8fafc;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236366f1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 13px;
    -webkit-appearance: none;
    appearance: none;
    border: 1.5px solid #cbd5e1;
    border-radius: 9px;
    padding: 9px 38px 9px 38px;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    color: #0f172a;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
    height: 40px;
    cursor: pointer;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);
}
.op-select:focus {
    background-color: #ffffff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

/* Custom Modern File Upload Dropzone */
.op-doc-field {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 14px;
}
.op-doc-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 8px;
    letter-spacing: 0.2px;
}
.op-doc-label i {
    color: #6366f1;
    font-size: 13px;
}
.op-current-doc {
    margin-bottom: 8px;
}
.op-doc-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f5f3ff;
    border: 1px solid #ddd6fe;
    border-radius: 8px;
    padding: 6px 12px;
    color: #5b21b6;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
    max-width: 100%;
}
.op-doc-link:hover {
    background: #ede9fe;
    color: #4c1d95;
    transform: translateY(-1px);
}
.op-doc-link i.fa-file-pdf {
    color: #ef4444;
}
.op-doc-filename {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 320px;
}
.op-doc-tag {
    font-size: 10px;
    background: #7c3aed;
    color: #fff;
    padding: 2px 7px;
    border-radius: 12px;
    margin-left: 6px;
}

.op-upload-dropzone {
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 9px;
    padding: 10px 14px;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.op-upload-dropzone:hover {
    border-color: #6366f1;
    background: #fbf9ff;
}
.op-upload-dropzone.has-file {
    border-color: #10b981;
    border-style: solid;
    background: #f0fdf4;
}
.op-file-native {
    position: absolute;
    inset: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    z-index: 5;
}
.op-dropzone-content {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    pointer-events: none;
}
.op-dropzone-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #ede9fe;
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    transition: transform 0.2s;
}
.op-upload-dropzone:hover .op-dropzone-icon {
    transform: scale(1.08);
}
.op-dropzone-text {
    text-align: left;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.op-dropzone-title {
    font-size: 12px;
    font-weight: 600;
    color: #1e293b;
}
.op-dropzone-sub {
    font-size: 10.5px;
    color: #64748b;
}
.op-file-name-display {
    display: none;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    font-size: 11.5px;
    font-weight: 700;
    color: #059669;
    background: #dcfce7;
    padding: 3px 10px;
    border-radius: 6px;
    border: 1px solid #86efac;
    width: calc(100% - 20px);
    box-sizing: border-box;
    justify-content: center;
}

/* Document Preview Box in Review stages */
.op-doc-preview-box {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.op-doc-preview-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Alert Toggle Card (Paso 3) */
.op-alert-card {
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    border-radius: 10px;
    padding: 11px 14px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 11px;
    cursor: pointer;
}
.op-alert-card input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #d97706;
    margin: 0;
    cursor: pointer;
    flex-shrink: 0;
}
.op-alert-card-text {
    font-size: 12px;
    font-weight: 600;
    color: #b45309;
}

/* Review Recap Box (Paso 8) */
.op-recap-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 16px;
}
.op-recap-item {
    margin-bottom: 8px;
    font-size: 12px;
}
.op-recap-item:last-child {
    margin-bottom: 0;
}
.op-recap-label {
    font-weight: 700;
    color: #475569;
    display: block;
    margin-bottom: 2px;
}
.op-recap-text {
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #edf2f7;
    border-radius: 6px;
    padding: 6px 10px;
}

/* Action Buttons */
.op-actions-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 18px;
    flex-wrap: wrap;
}
.btn-op {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 16px;
    border-radius: 9px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
}
.btn-op:hover {
    transform: translateY(-1px);
}
.btn-op:active {
    transform: translateY(0);
}

.btn-op-primary {
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.btn-op-primary:hover {
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
}

.btn-op-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.btn-op-success:hover {
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.btn-op-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.btn-op-secondary:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-op-danger {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.btn-op-danger:hover {
    background: #fecaca;
    color: #b91c1c;
}

/* Closed state banner */
.op-closed-banner {
    text-align: center;
    padding: 30px 20px;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
}
.op-closed-icon {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 12px;
}
.op-closed-icon.won {
    background: #d1fae5;
    color: #059669;
}
.op-closed-icon.lost {
    background: #fee2e2;
    color: #dc2626;
}
.op-closed-title {
    font-size: 17px;
    font-weight: 700;
    margin: 0 0 6px 0;
    color: #0f172a;
}
.op-closed-desc {
    font-size: 12.5px;
    color: #64748b;
    margin: 0 0 16px 0;
}
.op-motivo-box {
    text-align: left;
    background: #fee2e2;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 12px;
    color: #991b1b;
    display: inline-block;
    max-width: 100%;
    margin-top: 6px;
}

/* Pulsing dot for active pill */
.op-pulse {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #ffffff;
    display: inline-block;
    animation: opPulseAnim 1.4s infinite;
}
@keyframes opPulseAnim {
    0% { transform: scale(0.9); opacity: 0.7; }
    50% { transform: scale(1.3); opacity: 1; }
    100% { transform: scale(0.9); opacity: 0.7; }
}

/* Multi-Stage BPMN Views & History Navigation */
.op-stage-view {
    animation: opFadeIn 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes opFadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.stage-completed-badge-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
    border: 1.5px solid #a7f3d0;
    border-radius: 11px;
    padding: 10px 14px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.06);
}
.badge-stage-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #065f46;
}
.stage-history-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.btn-return-current {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #ffffff;
    font-size: 11.5px;
    font-weight: 700;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.25);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-return-current:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(99, 102, 241, 0.35);
}
.btn-return-current:active {
    transform: translateY(0);
}

.op-stage-pending {
    opacity: 0.9;
    border: 1.5px dashed #cbd5e1;
    background: #f8fafc;
}

@media (max-width: 520px) {
    .op-meta-grid {
        grid-template-columns: 1fr;
    }
    .btn-op {
        width: 100%;
    }
}
</style>

<div class="op-modal-wrapper" id="opModalWrapper">

    <?php if (!$isModal): ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <div>
            <h2 style="margin:0; font-size:20px; font-weight:700; color:#0f172a;">Seguimiento BPMN de Oportunidad</h2>
            <span style="font-size:12px; color:#64748b;"><?= htmlspecialchars($item['codigo']) ?> • Flujo integral de licitaciones</span>
        </div>
        <a href="<?= BASE_URL ?>/oportunidades" class="btn btn-secondary" style="font-size:12px; padding:6px 12px;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
    <?php endif; ?>

    <!-- 1. HERO CARD: OPORTUNIDAD -->
    <div class="op-hero-card">
        <div class="op-hero-top">
            <div class="op-tags-wrap">
                <span class="op-badge-code">
                    <i class="fas fa-fingerprint"></i> <?= htmlspecialchars($item['codigo'] ?: 'OPP-' . $item['id']) ?>
                </span>
                <span class="op-badge-status badge <?= getEstadoClass($item['estado']) ?>">
                    <span class="op-status-dot"></span>
                    <?= getEstadoLabel($item['estado']) ?>
                </span>
            </div>
            <div class="op-step-counter">
                <?php if (!$isClosed): ?>
                    Etapa <strong><?= $currentStepNum ?></strong> de 10
                <?php else: ?>
                    <?= $item['estado'] === 'adjudicada' ? '🏆 Adjudicada' : 'Cerrada' ?>
                <?php endif; ?>
            </div>
        </div>

        <h3 class="op-hero-title"><?= htmlspecialchars($item['nombre']) ?></h3>

        <div class="op-meta-grid">
            <div class="op-meta-item">
                <span class="op-meta-label"><i class="fas fa-building"></i> Entidad / Cliente</span>
                <span class="op-meta-value"><?= htmlspecialchars($item['entidad'] ?: '-') ?></span>
            </div>
            <div class="op-meta-item">
                <span class="op-meta-label"><i class="fas fa-coins"></i> Monto Estimado</span>
                <span class="op-meta-value op-money-val"><?= formatMoney($item['monto_estimado']) ?></span>
            </div>
            <div class="op-meta-item">
                <span class="op-meta-label"><i class="fas fa-calendar-alt"></i> Fecha Límite</span>
                <span class="op-meta-value"><?= formatDate($item['fecha_limite']) ?: 'No especificada' ?></span>
            </div>
            <div class="op-meta-item">
                <span class="op-meta-label"><i class="fas fa-layer-group"></i> Origen / Proceso</span>
                <span class="op-meta-value"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $item['origen'] ?? 'Licitación pública'))) ?></span>
            </div>
        </div>
    </div>

    <!-- 2. STEPPER BPMN (10 PASOS) -->
    <div class="op-stepper-card">
        <div class="op-stepper-header">
            <span class="op-stepper-title">
                <i class="fas fa-project-diagram"></i> 
                Etapa actual: <strong><?= isset($stepperPasos[$currentStepNum]) ? $stepperPasos[$currentStepNum]['title'] : 'Cerrada' ?></strong>
            </span>
            <span class="op-stepper-pct"><?= $progressPct ?>%</span>
        </div>

        <div class="op-progress-bar">
            <div class="op-progress-fill" style="width: <?= $progressPct ?>%;"></div>
        </div>

        <div class="op-step-pills-wrap" id="stepPillsTrack" role="tablist">
            <?php 
            foreach ($stepperPasos as $stepIndex => $st): 
                $isPassed = ($stepIndex < $currentStepNum);
                $isCurrent = ($stepIndex === $currentStepNum);
                $pillClass = $isPassed ? 'completed' : ($isCurrent ? 'active' : 'pending');
            ?>
            <button type="button" 
                    role="tab"
                    aria-selected="<?= $isCurrent ? 'true' : 'false' ?>"
                    class="op-pill <?= $pillClass ?> <?= $isCurrent ? 'selected-tab' : '' ?>" 
                    id="stepPill_<?= $stepIndex ?>" 
                    onclick="cambiarPasoVisual(<?= $stepIndex ?>)" 
                    title="Ver etapa: <?= htmlspecialchars($st['title']) ?> (Haz clic para ver)">
                <span class="op-pill-num">
                    <?php if ($isPassed): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <?= $st['num'] ?>
                    <?php endif; ?>
                </span>
                <?php if ($isCurrent): ?><span class="op-pulse"></span><?php endif; ?>
                <span><?= htmlspecialchars($st['label']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 3. CONTENEDOR MULTI-ETAPAS BPMN (ACCESO INTERACTIVO A CADA PASO) -->
    <div id="opStagesContainer">

        <!-- ==========================================
             ETAPA 1: IDENTIFICACIÓN Y CLASIFICACIÓN
             ========================================== -->
        <div class="op-stage-view" id="stage_view_1" style="display: <?= ($currentStepNum === 1) ? 'block' : 'none' ?>;">
            <div class="op-stage-card">
                <div class="op-stage-header">
                    <div class="op-stage-icon-box"><i class="fas fa-search"></i></div>
                    <div class="op-stage-info">
                        <h4>1. Identificación y Clasificación</h4>
                        <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                    </div>
                </div>

                <?php if ($currentStepNum > 1): ?>
                    <div class="stage-completed-badge-bar">
                        <div class="stage-history-notice">
                            <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 1 Completada</span>
                            <span style="font-size:11.5px; color:#475569;">Oportunidad detectada y registrada en CRM</span>
                        </div>
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                <?php else: ?>
                    <div class="op-guide-banner">
                        <i class="fas fa-info-circle"></i>
                        <span>La oportunidad ha sido detectada y registrada en el sistema CRM. Para continuar el flujo BPMN, verifique los datos y proceda a la revisión de requisitos mínimos.</span>
                    </div>
                <?php endif; ?>

                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:16px;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; font-size:12px;">
                        <div>
                            <span style="color:#64748b; font-weight:600; display:block;">CÓDIGO DE OPORTUNIDAD:</span>
                            <strong style="color:#1e293b;"><?= htmlspecialchars($item['codigo'] ?: 'OPP-'.$item['id']) ?></strong>
                        </div>
                        <div>
                            <span style="color:#64748b; font-weight:600; display:block;">ENTIDAD / CLIENTE:</span>
                            <strong style="color:#1e293b;"><?= htmlspecialchars($item['entidad'] ?: '-') ?></strong>
                        </div>
                        <div>
                            <span style="color:#64748b; font-weight:600; display:block;">MONTO ESTIMADO:</span>
                            <strong style="color:#059669; font-size:13px;"><?= formatMoney($item['monto_estimado']) ?></strong>
                        </div>
                        <div>
                            <span style="color:#64748b; font-weight:600; display:block;">ORIGEN / PROCESO:</span>
                            <strong style="color:#1e293b;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $item['origen'] ?? 'Licitación pública'))) ?></strong>
                        </div>
                        <div>
                            <span style="color:#64748b; font-weight:600; display:block;">FECHA LÍMITE:</span>
                            <strong style="color:#1e293b;"><?= formatDate($item['fecha_limite']) ?: 'No especificada' ?></strong>
                        </div>
                        <div>
                            <span style="color:#64748b; font-weight:600; display:block;">FECHA DE REGISTRO:</span>
                            <strong style="color:#1e293b;"><?= formatDate($item['created_at']) ?: '-' ?></strong>
                        </div>
                    </div>
                    <?php if (!empty($item['observaciones'])): ?>
                        <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #cbd5e1; font-size:12px;">
                            <span style="color:#64748b; font-weight:600; display:block;">DESCRIPCIÓN / NOTAS:</span>
                            <span style="color:#334155;"><?= nl2br(htmlspecialchars($item['observaciones'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($currentStepNum === 1 && !$isClosed): ?>
                    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                        <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
                        
                        <div class="op-actions-bar">
                            <button type="submit" name="siguiente_estado" value="evaluacion_requisitos" class="btn-op btn-op-primary">
                                <i class="fas fa-arrow-right"></i> Proceder a Evaluación de Requisitos
                            </button>
                            <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                                <i class="fas fa-ban"></i> Descartar
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==========================================
             ETAPA 2: REVISIÓN DE REQUISITOS MÍNIMOS
             ========================================== -->
        <div class="op-stage-view" id="stage_view_2" style="display: <?= ($currentStepNum === 2) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 2): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4 style="color:#64748b;">2. Revisión de Requisitos Mínimos</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa se habilitará una vez que se complete la etapa 1 de identificación.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="op-stage-card">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box"><i class="fas fa-tasks"></i></div>
                        <div class="op-stage-info">
                            <h4>2. Revisión de Requisitos Mínimos</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                        </div>
                    </div>

                    <?php if ($currentStepNum > 2): ?>
                        <div class="stage-completed-badge-bar">
                            <div class="stage-history-notice">
                                <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 2 Completada y Validada</span>
                                <span style="font-size:11.5px; color:#475569;">Bases, requisitos legales, cronograma y condiciones revisados y adjuntados</span>
                            </div>
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="op-guide-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>Verifique el cumplimiento de los 4 requisitos mínimos y adjunte el documento sustentatorio correspondiente a cada uno (Bases/TDR, Requisitos Legales, Cronograma y Condiciones).</span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                        <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
                        <input type="hidden" name="has_req_checks" value="1">

                        <?php
                        $requisitosItems = [
                            [
                                'check_name' => 'req_bases_tdr',
                                'checked' => !empty($eval['req_bases_tdr']),
                                'title' => 'Revisión de Bases / TDR',
                                'desc' => 'Términos de referencia y especificaciones técnicas claras y alcanzables',
                                'icon' => 'fas fa-file-alt',
                                'doc_name' => 'doc_bases_tdr',
                                'current_doc' => $eval['doc_bases_tdr'] ?? null,
                                'doc_label' => 'Bases Administrativas / TDR (PDF o documento)',
                                'doc_hint' => 'Adjunte bases integradas, especificaciones técnicas o TDR'
                            ],
                            [
                                'check_name' => 'req_legales',
                                'checked' => !empty($eval['req_legales']),
                                'title' => 'Requisitos Legales',
                                'desc' => 'RNP vigente, capacidad legal y ausencia de impedimentos para contratar',
                                'icon' => 'fas fa-balance-scale',
                                'doc_name' => 'doc_req_legales',
                                'current_doc' => $eval['doc_req_legales'] ?? null,
                                'doc_label' => 'Sustento Legal / RNP / Vigencia de Poder (PDF o documento)',
                                'doc_hint' => 'Adjunte constancia RNP, vigencia de poder o DJ de no impedimento'
                            ],
                            [
                                'check_name' => 'req_cronograma',
                                'checked' => !empty($eval['req_cronograma']),
                                'title' => 'Cronograma Viable',
                                'desc' => 'Plazos suficientes para consultas, observaciones y formulación de ofertas',
                                'icon' => 'fas fa-calendar-check',
                                'doc_name' => 'doc_req_cronograma',
                                'current_doc' => $eval['doc_req_cronograma'] ?? null,
                                'doc_label' => 'Cronograma Oficial del Proceso (PDF o imagen)',
                                'doc_hint' => 'Adjunte cronograma de etapas, calendario de SEACE o plazos'
                            ],
                            [
                                'check_name' => 'req_condiciones',
                                'checked' => !empty($eval['req_condiciones']),
                                'title' => 'Condiciones de Contratación',
                                'desc' => 'Garantías exigidas, forma de pago y condiciones contractuales favorables',
                                'icon' => 'fas fa-handshake',
                                'doc_name' => 'doc_req_condiciones',
                                'current_doc' => $eval['doc_req_condiciones'] ?? null,
                                'doc_label' => 'Condiciones Contractuales / Pliego (PDF o documento)',
                                'doc_hint' => 'Adjunte condiciones de pago, penalidades, garantías o proforma'
                            ]
                        ];
                        ?>

                        <div class="op-checklist">
                            <?php foreach ($requisitosItems as $req): ?>
                                <div class="op-req-card">
                                    <div class="op-req-card-header">
                                        <label class="op-req-check-label">
                                            <input type="checkbox" name="<?= $req['check_name'] ?>" <?= $req['checked'] ? 'checked' : '' ?>>
                                            <div class="op-check-content">
                                                <span class="op-check-title"><i class="<?= $req['icon'] ?> text-primary" style="margin-right:5px;"></i> <?= $req['title'] ?></span>
                                                <span class="op-check-desc"><?= $req['desc'] ?></span>
                                            </div>
                                        </label>
                                        <?php if (!empty($req['current_doc'])): ?>
                                            <span style="font-size: 11px; padding: 3px 9px; border-radius: 12px; font-weight: 700; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-check-circle"></i> Adjunto cargado
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Cargador específico del requisito -->
                                    <div class="op-req-doc-box">
                                        <?php if (!empty($req['current_doc'])): ?>
                                            <div class="op-current-doc" style="margin-bottom:6px;">
                                                <a href="<?= BASE_URL ?>/<?= htmlspecialchars($req['current_doc']) ?>" target="_blank" class="op-doc-link" title="Abrir archivo adjunto" style="font-size:11.5px; padding:4px 10px;">
                                                    <i class="fas fa-file-pdf"></i>
                                                    <span class="op-doc-filename"><?= htmlspecialchars(basename($req['current_doc'])) ?></span>
                                                    <span class="op-doc-tag"><i class="fas fa-eye"></i> Ver archivo</span>
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <div class="op-upload-dropzone <?= !empty($req['current_doc']) ? 'is-replace' : '' ?>" style="padding: 7px 12px; min-height: 44px;">
                                            <input type="file" name="<?= $req['doc_name'] ?>" id="input_<?= $req['doc_name'] ?>" class="op-file-native" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" onchange="actualizarNombreDoc(this)">
                                            <div class="op-dropzone-content">
                                                <div class="op-dropzone-icon" style="width:28px; height:28px; font-size:12px;">
                                                    <i class="fas <?= !empty($req['current_doc']) ? 'fa-arrows-rotate' : 'fa-cloud-arrow-up' ?>"></i>
                                                </div>
                                                <div class="op-dropzone-text">
                                                    <strong class="op-dropzone-title" style="font-size:11.5px;">
                                                        <?= !empty($req['current_doc']) ? 'Reemplazar ' . $req['doc_label'] : 'Adjuntar ' . $req['doc_label'] ?>
                                                    </strong>
                                                    <span class="op-dropzone-sub" style="font-size:10.5px;"><?= $req['doc_hint'] ?> (Máx. 25MB)</span>
                                                </div>
                                            </div>
                                            <div class="op-file-name-display" id="display_<?= $req['doc_name'] ?>" style="font-size:10.5px;"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="op-actions-bar">
                            <?php if ($currentStepNum === 2 && !$isClosed): ?>
                                <button type="submit" name="siguiente_estado" value="viabilidad_preliminar" class="btn-op btn-op-primary">
                                    <i class="fas fa-arrow-right"></i> Aprobar y pasar a Viabilidad Preliminar
                                </button>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar sin avanzar
                                </button>
                                <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                                    <i class="fas fa-ban"></i> No participar
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar cambios en este paso
                                </button>
                                <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                    <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             ETAPA 3: VIABILIDAD PRELIMINAR
             ========================================== -->
        <div class="op-stage-view" id="stage_view_3" style="display: <?= ($currentStepNum === 3) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 3): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4 style="color:#64748b;">3. Viabilidad Preliminar</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa se habilitará al completar la revisión de requisitos mínimos.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="op-stage-card">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box"><i class="fas fa-chart-pie"></i></div>
                        <div class="op-stage-info">
                            <h4>3. Viabilidad Preliminar</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Gestión de Proyectos</span>
                        </div>
                    </div>

                    <?php if ($currentStepNum > 3): ?>
                        <div class="stage-completed-badge-bar">
                            <div class="stage-history-notice">
                                <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 3 Viabilidad Evaluada</span>
                                <span style="font-size:11.5px; color:#475569;">Alcance, plazos y riesgos analizados</span>
                            </div>
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="op-guide-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>Evalúe el alcance, los plazos y los riesgos del proyecto para determinar si requiere un análisis especializado o pasa directamente a consolidación.</span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                        <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                        <div class="op-form-group">
                            <label><i class="fas fa-bullseye text-primary"></i> Evaluación de Alcance</label>
                            <textarea name="viabilidad_alcance" class="op-textarea" rows="2" placeholder="Detalle la capacidad operativa y alcance técnico requerido..."><?= htmlspecialchars($eval['viabilidad_alcance'] ?? '') ?></textarea>
                        </div>

                        <div class="op-form-group">
                            <label><i class="fas fa-clock text-primary"></i> Evaluación de Plazos y Riesgos</label>
                            <textarea name="viabilidad_riesgos" class="op-textarea" rows="2" placeholder="Análisis de riesgos contractuales, plazos y contingencias..."><?= htmlspecialchars($eval['viabilidad_riesgos'] ?? '') ?></textarea>
                        </div>

                        <label class="op-alert-card">
                            <input type="checkbox" name="requiere_eval_especializada" value="1" <?= !empty($item['requiere_evaluacion_especializada']) ? 'checked' : '' ?>>
                            <span class="op-alert-card-text">
                                <i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i> ¿Requiere evaluación especializada (Consultoría / Finanzas)?
                            </span>
                        </label>

                        <div class="op-actions-bar">
                            <?php if ($currentStepNum === 3 && !$isClosed): ?>
                                <button type="submit" name="siguiente_estado" value="evaluacion_especializada" id="btnAvanzarViabilidad" class="btn-op btn-op-primary">
                                    <i class="fas fa-arrow-right"></i> Aprobar Viabilidad
                                </button>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar Avance
                                </button>
                                <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                                    <i class="fas fa-ban"></i> No viable
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar cambios en este paso
                                </button>
                                <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                    <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             ETAPA 4: EVALUACIÓN ESPECIALIZADA
             ========================================== -->
        <div class="op-stage-view" id="stage_view_4" style="display: <?= ($currentStepNum === 4) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 4): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4 style="color:#64748b;">4. Evaluación Especializada</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa técnica se activa si la oportunidad requiere dictamen de Consultoría y Finanzas.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="op-stage-card">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box"><i class="fas fa-microscope"></i></div>
                        <div class="op-stage-info">
                            <h4>4. Evaluación Especializada</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Consultoría y Licitaciones</span>
                        </div>
                    </div>

                    <?php if ($currentStepNum > 4): ?>
                        <div class="stage-completed-badge-bar">
                            <div class="stage-history-notice">
                                <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 4 Evaluada y Registrada</span>
                                <span style="font-size:11.5px; color:#475569;">Informes técnico y económico emitidos por especialistas</span>
                            </div>
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="op-guide-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>Ingrese los análisis técnico y económico, y adjunte los informes sustentatorios correspondientes emitidos por los especialistas.</span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                        <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                        <div class="op-form-group">
                            <label><i class="fas fa-cogs text-primary"></i> Análisis Técnico (Consultoría: Metodología, alcance, personal)</label>
                            <textarea name="informe_tecnico" class="op-textarea" rows="2" placeholder="Resumen de la metodología técnica propuesta..."><?= htmlspecialchars($eval['informe_tecnico'] ?? '') ?></textarea>
                        </div>
                        <!-- DOCUMENTO: INFORME TÉCNICO -->
                        <?php renderDocUpload('doc_informe_tecnico', 'Informe Técnico de Ingeniería / Consultoría (PDF / Imagen)', $eval['doc_informe_tecnico'] ?? null, 'Adjunte el informe técnico detallado emitido por Consultoría'); ?>

                        <div class="op-form-group" style="margin-top:14px;">
                            <label><i class="fas fa-chart-line text-primary"></i> Evaluación Económica (Licitaciones: Costos, margen, flujo de caja)</label>
                            <textarea name="informe_economico" class="op-textarea" rows="2" placeholder="Resumen de costos estimados y rentabilidad..."><?= htmlspecialchars($eval['informe_economico'] ?? '') ?></textarea>
                        </div>
                        <!-- DOCUMENTO: EVALUACIÓN ECONÓMICA -->
                        <?php renderDocUpload('doc_evaluacion_economica', 'Evaluación Económica / Estructura de Costos (PDF / Excel / Imagen)', $eval['doc_evaluacion_economica'] ?? null, 'Adjunte la plantilla o informe económico'); ?>

                        <div class="op-actions-bar">
                            <?php if ($currentStepNum === 4 && !$isClosed): ?>
                                <button type="submit" name="siguiente_estado" value="consolidacion_viable" class="btn-op btn-op-primary">
                                    <i class="fas fa-arrow-right"></i> Enviar a Consolidación
                                </button>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar Avance
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar cambios en este paso
                                </button>
                                <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                    <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             ETAPA 5: CONSOLIDAR EVALUACIÓN
             ========================================== -->
        <div class="op-stage-view" id="stage_view_5" style="display: <?= ($currentStepNum === 5) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 5): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4 style="color:#64748b;">5. Consolidar Evaluación</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa se activará cuando concluya la evaluación de viabilidad preliminar o especializada.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="op-stage-card">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box"><i class="fas fa-compress-arrows-alt"></i></div>
                        <div class="op-stage-info">
                            <h4>5. Consolidar Evaluación</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                        </div>
                    </div>

                    <?php if ($currentStepNum > 5): ?>
                        <div class="stage-completed-badge-bar">
                            <div class="stage-history-notice">
                                <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 5 Dictamen Consolidado Aprobado</span>
                                <span style="font-size:11.5px; color:#475569;">Informes técnico, económico y legal unificados</span>
                            </div>
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="op-guide-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>Consolide los informes técnico, económico y legal. Adjunte el Informe de Evaluación Consolidada para autorizar la preparación de la propuesta.</span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                        <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                        <div class="op-form-group">
                            <label><i class="fas fa-file-alt text-primary"></i> Dictamen Consolidado Técnico, Económico y Legal</label>
                            <textarea name="consolidacion_informe" class="op-textarea" rows="3" placeholder="Resumen del dictamen consolidado para pasar a elaboración de propuesta..."><?= htmlspecialchars($eval['consolidacion_informe'] ?? '') ?></textarea>
                        </div>

                        <!-- CONSULTAS Y OBSERVACIONES A LAS BASES (BPMN Fase 2) -->
                        <div class="op-form-group">
                            <label><i class="fas fa-question-circle text-primary"></i> Consultas y Observaciones a las Bases (Pliego ante la Entidad o Subsanaciones)</label>
                            <textarea name="consultas_observaciones" class="op-textarea" rows="2" placeholder="Plantee consultas u observaciones técnicas, legales o económicas a las bases si se requieren aclaraciones antes de continuar..."><?= htmlspecialchars($eval['consultas_observaciones'] ?? '') ?></textarea>
                        </div>

                        <!-- DOCUMENTO: INFORME DE EVALUACIÓN CONSOLIDADA -->
                        <?php renderDocUpload('doc_informe_evaluacion', 'Informe de Evaluación Consolidada (PDF / Documento)', $eval['doc_informe_evaluacion'] ?? null, 'Adjunte el informe o dictamen consolidado final'); ?>

                        <div class="op-actions-bar">
                            <?php if ($currentStepNum === 5 && !$isClosed): ?>
                                <button type="submit" name="siguiente_estado" value="elaboracion_propuesta" class="btn-op btn-op-primary">
                                    <i class="fas fa-arrow-right"></i> Confirmar Viabilidad y Elaborar Propuesta
                                </button>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar Informe
                                </button>
                                <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                                    <i class="fas fa-ban"></i> No viable
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar cambios en este paso
                                </button>
                                <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                    <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             ETAPA 6: ELABORAR PROPUESTA TÉCNICA Y ECONÓMICA
             ========================================== -->
        <div class="op-stage-view" id="stage_view_6" style="display: <?= ($currentStepNum === 6) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 6): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4 style="color:#64748b;">6. Elaborar Propuesta Técnica y Económica</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa se habilitará una vez que se declare la viabilidad de la oportunidad.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="op-stage-card">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box"><i class="fas fa-file-contract"></i></div>
                        <div class="op-stage-info">
                            <h4>6. Elaborar Propuesta Técnica y Económica</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                        </div>
                    </div>

                    <?php if ($currentStepNum > 6): ?>
                        <div class="stage-completed-badge-bar">
                            <div class="stage-history-notice">
                                <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 6 Propuesta Formulada</span>
                                <span style="font-size:11.5px; color:#475569;">Expediente técnico y económico cargado</span>
                            </div>
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="op-guide-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>La oportunidad es viable. Formule la propuesta oficial y <strong>adjunte el expediente de la propuesta (PDF o documento)</strong> para que las áreas técnicas y de proyectos la revisen.</span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                        <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                        <div class="op-form-group">
                            <label><i class="fas fa-align-left text-primary"></i> Resumen / Observaciones de la Oferta Formulada</label>
                            <textarea name="observaciones" class="op-textarea" rows="2" placeholder="Resumen de la propuesta técnica, montos ofertados, componentes clave..."><?= htmlspecialchars($item['observaciones'] ?? '') ?></textarea>
                        </div>

                        <!-- DOCUMENTO CLAVE: EXPEDIENTE DE LA PROPUESTA (PDF / DOCUMENTO) -->
                        <?php renderDocUpload('doc_propuesta', '📄 Documento de la Propuesta Oficial (Técnica y Económica)', $eval['doc_propuesta'] ?? null, 'Adjunte la propuesta final en PDF, Word o comprimido'); ?>

                        <!-- DOCUMENTO BPMN: CONSTANCIA DE INSCRIPCIÓN -->
                        <?php renderDocUpload('doc_constancia_inscripcion', '📄 Constancia de Inscripción (RNP / Registro de Proveedor)', $eval['doc_constancia_inscripcion'] ?? null, 'Adjunte constancia de inscripción o acreditación vigente'); ?>

                        <div class="op-actions-bar">
                            <?php if ($currentStepNum === 6 && !$isClosed): ?>
                                <button type="submit" name="siguiente_estado" value="revision_propuesta" class="btn-op btn-op-primary">
                                    <i class="fas fa-paper-plane"></i> Enviar a Revisión de Propuesta
                                </button>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar Propuesta
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar cambios en este paso
                                </button>
                                <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                    <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             ETAPA 7: REVISIÓN Y VALIDACIÓN DE PROPUESTA
             ========================================== -->
        <div class="op-stage-view" id="stage_view_7" style="display: <?= ($currentStepNum === 7) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 7): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4 style="color:#64748b;">7. Revisión y Validación de Propuesta</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa se activará cuando se cargue el expediente de la propuesta en la Etapa 6.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="op-stage-card">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box"><i class="fas fa-check-double"></i></div>
                        <div class="op-stage-info">
                            <h4>7. Revisión y Validación de Propuesta</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Consultoría y Gestión de Proyectos</span>
                        </div>
                    </div>

                    <?php if ($currentStepNum > 7): ?>
                        <div class="stage-completed-badge-bar">
                            <div class="stage-history-notice">
                                <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 7 Validaciones Completadas</span>
                                <span style="font-size:11.5px; color:#475569;">Vistos buenos técnico y económico emitidos</span>
                            </div>
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="op-guide-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>Descargue y revise la propuesta formulada en la etapa anterior. Ingrese los vistos buenos técnico y económico para enviar a Gerencia.</span>
                        </div>
                    <?php endif; ?>

                    <!-- Previsualización / enlace a la propuesta cargada -->
                    <?php if (!empty($eval['doc_propuesta'])): ?>
                    <div class="op-doc-preview-box">
                        <div class="op-doc-preview-info">
                            <i class="fas fa-file-contract" style="font-size:22px; color:#6366f1;"></i>
                            <div>
                                <strong style="font-size:12.5px; color:#1e293b;">Propuesta formulada en Etapa 6</strong><br>
                                <span style="font-size:11px; color:#64748b;"><?= htmlspecialchars(basename($eval['doc_propuesta'])) ?></span>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($eval['doc_propuesta']) ?>" target="_blank" class="op-doc-link">
                            <i class="fas fa-download"></i> Descargar / Revisar Propuesta
                        </a>
                    </div>
                    <?php endif; ?>

                    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                        <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                        <div class="op-form-group">
                            <label><i class="fas fa-check-circle text-primary"></i> Revisión y Validación Técnica (Consultorías)</label>
                            <textarea name="revision_tecnica" class="op-textarea" rows="2" placeholder="Observaciones o conformidad técnica sobre la propuesta..."><?= htmlspecialchars($eval['revision_tecnica'] ?? '') ?></textarea>
                        </div>

                        <div class="op-form-group">
                            <label><i class="fas fa-dollar-sign text-primary"></i> Revisión y Validación Económica (Gestión de Proyectos)</label>
                            <textarea name="revision_economica" class="op-textarea" rows="2" placeholder="Validación de precios unitarios, margen y rentabilidad..."><?= htmlspecialchars($eval['revision_economica'] ?? '') ?></textarea>
                        </div>

                        <div class="op-actions-bar">
                            <?php if ($currentStepNum === 7 && !$isClosed): ?>
                                <button type="submit" name="siguiente_estado" value="aprobacion_gerencia" class="btn-op btn-op-primary">
                                    <i class="fas fa-share"></i> Validar y Enviar a Gerencia General
                                </button>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar Validaciones
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar cambios en este paso
                                </button>
                                <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                    <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             ETAPA 8: APROBACIÓN DE GERENCIA GENERAL / CEO
             ========================================== -->
        <div class="op-stage-view" id="stage_view_8" style="display: <?= ($currentStepNum === 8) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 8): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4 style="color:#64748b;">8. Aprobación de Gerencia General / CEO</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa se activará cuando la propuesta cuente con las validaciones de las áreas técnicas.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="op-stage-card">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box"><i class="fas fa-user-tie"></i></div>
                        <div class="op-stage-info">
                            <h4>8. Aprobación de Gerencia General / CEO</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Dirección General</span>
                        </div>
                    </div>

                    <?php if ($currentStepNum > 8): ?>
                        <div class="stage-completed-badge-bar">
                            <div class="stage-history-notice">
                                <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 8 Aprobación Otorgada</span>
                                <span style="font-size:11.5px; color:#475569;">Participación autorizada por Dirección General</span>
                            </div>
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="op-guide-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>Gerencia General revisa los dictámenes y la propuesta final para autorizar la participación y presentación de la oferta.</span>
                        </div>
                    <?php endif; ?>

                    <!-- Previsualización / enlace a la propuesta cargada -->
                    <?php if (!empty($eval['doc_propuesta'])): ?>
                    <div class="op-doc-preview-box">
                        <div class="op-doc-preview-info">
                            <i class="fas fa-file-contract" style="font-size:22px; color:#6366f1;"></i>
                            <div>
                                <strong style="font-size:12.5px; color:#1e293b;">Propuesta lista para presentar</strong><br>
                                <span style="font-size:11px; color:#64748b;"><?= htmlspecialchars(basename($eval['doc_propuesta'])) ?></span>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($eval['doc_propuesta']) ?>" target="_blank" class="op-doc-link">
                            <i class="fas fa-eye"></i> Ver Expediente
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="op-recap-box">
                        <div class="op-recap-item">
                            <span class="op-recap-label"><i class="fas fa-cogs text-primary"></i> Revisión Técnica Registrada:</span>
                            <div class="op-recap-text"><?= htmlspecialchars($eval['revision_tecnica'] ?: 'Conforme y validado por Consultoría') ?></div>
                        </div>
                        <div class="op-recap-item">
                            <span class="op-recap-label"><i class="fas fa-dollar-sign text-primary"></i> Revisión Económica Registrada:</span>
                            <div class="op-recap-text"><?= htmlspecialchars($eval['revision_economica'] ?: 'Costos y margen validados por Gestión de Proyectos') ?></div>
                        </div>
                    </div>

                    <?php if ($currentStepNum === 8 && !$isClosed): ?>
                        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                            <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                            <div class="op-actions-bar">
                                <button type="submit" name="siguiente_estado" value="presentada" class="btn-op btn-op-success">
                                    <i class="fas fa-check-double"></i> Aprobar Participación y Presentar Propuesta
                                </button>
                                <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                                    <i class="fas fa-times-circle"></i> Rechazar Oferta
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="op-actions-bar" style="justify-content:flex-end;">
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             ETAPA 9: SEGUIMIENTO Y RESULTADOS
             ========================================== -->
        <div class="op-stage-view" id="stage_view_9" style="display: <?= ($currentStepNum === 9) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 9): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4>9. Seguimiento y Resultados</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa se activará cuando la propuesta sea formalmente presentada al cliente o entidad.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="op-stage-card">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box"><i class="fas fa-paper-plane"></i></div>
                        <div class="op-stage-info">
                            <h4>9. Seguimiento y Resultados</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                        </div>
                    </div>

                    <?php if ($currentStepNum > 9): ?>
                        <div class="stage-completed-badge-bar">
                            <div class="stage-history-notice">
                                <span class="badge-stage-status"><i class="fas fa-check-circle"></i> Etapa 9 Presentación y Seguimiento Completados</span>
                                <span style="font-size:11.5px; color:#475569;">Cargos y subsanaciones registrados</span>
                            </div>
                            <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="op-guide-banner">
                            <i class="fas fa-info-circle"></i>
                            <span>La propuesta ha sido presentada formalmente. Adjunte la constancia de presentación o pliego de subsanaciones mientras se espera la publicación de la Buena Pro.</span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data">
                        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                        <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                        <!-- DOCUMENTO: CONSTANCIA DE PRESENTACIÓN -->
                        <?php renderDocUpload('doc_constancia_presentacion', '📄 Constancia / Cargo de Presentación (SEACE o Mesa de Partes)', $eval['doc_constancia_presentacion'] ?? null, 'Adjunte el cargo oficial generado'); ?>

                        <div class="op-form-group" style="margin-top:14px;">
                            <label><i class="fas fa-clipboard-list text-primary"></i> Bitácora de Seguimiento (Subsanaciones / Consultas)</label>
                            <textarea name="observaciones" class="op-textarea" rows="2" placeholder="Registre incidencias, fechas de apertura de sobres o requerimientos de subsanación..."><?= htmlspecialchars($item['observaciones'] ?? '') ?></textarea>
                        </div>

                        <!-- SEGUIMIENTO LEGAL (ADMINISTRACIÓN Y FINANZAS) -->
                        <div class="op-form-group" style="margin-top:14px;">
                            <label><i class="fas fa-balance-scale text-primary"></i> Seguimiento Legal (Área: Administración y Finanzas)</label>
                            <textarea name="seguimiento_legal" class="op-textarea" rows="2" placeholder="Registro de acciones legales, cumplimiento de plazos o consultas jurídicas..."><?= htmlspecialchars($eval['seguimiento_legal'] ?? '') ?></textarea>
                        </div>

                        <!-- DOCUMENTO: SUBSANACIONES (SI HUBIERON) -->
                        <?php renderDocUpload('doc_subsanacion', '📄 Documento de Subsanaciones / Aclaraciones (Si aplica)', $eval['doc_subsanacion'] ?? null, 'Adjunte el pliego de subsanación presentado si hubieron observaciones'); ?>

                        <!-- APELACIÓN ANTE EL TRIBUNAL (SI APLICA SEGÚN DIAGRAMA BPMN) -->
                        <div style="margin-top:14px; padding:12px; background:#fff7ed; border:1px solid #fed7aa; border-radius:10px;">
                            <span style="font-size:12px; font-weight:700; color:#c2410c; display:block; margin-bottom:6px;">
                                <i class="fas fa-gavel"></i> Presentación de Apelación (En caso de observaciones al resultado)
                            </span>
                            <textarea name="apelacion_sustento" class="op-textarea" rows="2" placeholder="Detalle el sustento del recurso de apelación presentado..."><?= htmlspecialchars($eval['apelacion_sustento'] ?? '') ?></textarea>
                            <div style="margin-top:8px;">
                                <?php renderDocUpload('doc_apelacion', '📄 Recurso de Apelación Presentado (Si aplica)', $eval['doc_apelacion'] ?? null, 'Adjunte el escrito formal de apelación y cargo'); ?>
                            </div>
                        </div>

                        <div class="op-actions-bar">
                            <?php if ($currentStepNum === 9 && !$isClosed): ?>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar Seguimiento
                                </button>
                                <button type="submit" name="siguiente_estado" value="adjudicada" class="btn-op btn-op-success">
                                    <i class="fas fa-trophy"></i> Adjudicada (Buena Pro Ganada)
                                </button>
                                <button type="submit" name="siguiente_estado" value="no_adjudicada" class="btn-op btn-op-danger btn-discard">
                                    <i class="fas fa-times-circle"></i> No Adjudicada
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar cambios en este paso
                                </button>
                                <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                                    <i class="fas fa-arrow-right"></i> Ir a Etapa Actual (Paso <?= $currentStepNum ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- ==========================================
             ETAPA 10: ADJUDICACIÓN / CIERRE CONTRACTUAL
             ========================================== -->
        <div class="op-stage-view" id="stage_view_10" style="display: <?= ($currentStepNum === 10) ? 'block' : 'none' ?>;">
            <?php if ($currentStepNum < 10): ?>
                <div class="op-stage-card op-stage-pending">
                    <div class="op-stage-header">
                        <div class="op-stage-icon-box" style="background:#f1f5f9; color:#94a3b8;"><i class="fas fa-lock"></i></div>
                        <div class="op-stage-info">
                            <h4 style="color:#64748b;">10. Adjudicación y Cierre Contractual</h4>
                            <span class="op-stage-subtitle"><i class="fas fa-clock"></i> Etapa Pendiente</span>
                        </div>
                    </div>
                    <div class="op-guide-banner" style="background:#f8fafc; border-color:#e2e8f0; color:#64748b;">
                        <i class="fas fa-info-circle"></i>
                        <span>Esta etapa culminante se habilitará al definir el resultado de la Buena Pro o conclusión del concurso.</span>
                    </div>
                    <div class="op-actions-bar" style="justify-content:flex-end;">
                        <button type="button" onclick="cambiarPasoVisual(<?= $currentStepNum ?>)" class="btn-return-current">
                            <i class="fas fa-arrow-left"></i> Volver a Etapa Actual (Paso <?= $currentStepNum ?>)
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- ESTADO CERRADO (ADJUDICADA O DESCARTADA) -->
                <div class="op-closed-banner">
                    <div class="op-closed-icon <?= $item['estado'] === 'adjudicada' ? 'won' : 'lost' ?>">
                        <i class="<?= $item['estado'] === 'adjudicada' ? 'fas fa-trophy' : 'fas fa-times-circle' ?>"></i>
                    </div>
                    
                    <h3 class="op-closed-title" style="color: <?= $item['estado'] === 'adjudicada' ? '#059669' : '#dc2626' ?>;">
                        <?= $item['estado'] === 'adjudicada' ? '¡Oportunidad Adjudicada (Buena Pro Ganada)!' : 'Oportunidad Cerrada / Descartada' ?>
                    </h3>
                    
                    <p class="op-closed-desc">
                        <?= $item['estado'] === 'adjudicada' ? 'El proceso concluyó con éxito y la propuesta fue ganadora. Se generó y vinculó el registro formal en el módulo de Contratos para la suscripción y ejecución de proyecto.' : 'Esta oportunidad fue cerrada y no continuará en el flujo comercial activo.' ?>
                    </p>

                    <?php if ($item['estado'] === 'adjudicada'): ?>
                        <?php
                        // 1. Obtener o auto-vincular el contrato generado de esta oportunidad
                        $contratoGenerado = dbQueryOne("SELECT id, codigo, objeto, monto_contrato, estado, fecha_firma, fecha_inicio, fecha_fin, plazo_dias FROM contratos WHERE oportunidad_id = ? ORDER BY id DESC LIMIT 1", [$item['id']]);
                        if (!$contratoGenerado) {
                            $totalContratos = dbQueryOne("SELECT COUNT(*) as t FROM contratos")['t'] ?? 0;
                            $numContrato = str_pad($totalContratos + 1, 3, '0', STR_PAD_LEFT);
                            $nuevoCodigo = 'CONT-' . date('Y') . '-' . $numContrato;
                            $cliId = !empty($item['cliente_id']) ? intval($item['cliente_id']) : null;
                            if (!$cliId) {
                                $cliDefault = dbQueryOne("SELECT id FROM clientes LIMIT 1");
                                $cliId = $cliDefault ? $cliDefault['id'] : null;
                            }
                            dbExecute("
                                INSERT INTO contratos (codigo, oportunidad_id, cliente_id, objeto, monto_contrato, moneda, fecha_firma, fecha_inicio, fecha_fin, plazo_dias, estado, tipo_contrato)
                                VALUES (?, ?, ?, ?, ?, 'PEN', CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 180, 'en_elaboracion', ?)
                            ", [$nuevoCodigo, $item['id'], $cliId, $item['nombre'], $item['monto_estimado'], $item['origen'] ?? 'Licitación Pública']);
                            $contratoGenerado = dbQueryOne("SELECT id, codigo, objeto, monto_contrato, estado, fecha_firma, fecha_inicio, fecha_fin, plazo_dias FROM contratos WHERE oportunidad_id = ? ORDER BY id DESC LIMIT 1", [$item['id']]);
                        }

                        // 2. Obtener proyecto si ya fue transferido a planificación
                        $proyectoGenerado = null;
                        if ($contratoGenerado) {
                            $proyectoGenerado = dbQueryOne("SELECT id, codigo, nombre, estado, monto_presupuesto, fecha_inicio, fecha_fin_planificada FROM proyectos WHERE contrato_id = ? ORDER BY id DESC LIMIT 1", [$contratoGenerado['id']]);
                        }
                        if (!$proyectoGenerado && !empty($eval['proyecto_id'])) {
                            $proyectoGenerado = dbQueryOne("SELECT id, codigo, nombre, estado, monto_presupuesto, fecha_inicio, fecha_fin_planificada FROM proyectos WHERE id = ?", [$eval['proyecto_id']]);
                        }
                        ?>

                        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data" style="text-align:left; max-width:680px; margin:0 auto;">
                            <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                            <!-- HITO 1: REGISTRAR ADJUDICACIÓN EN CRM -->
                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;">
                                    <span style="width:28px; height:28px; border-radius:8px; background:#e0e7ff; color:#4f46e5; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:700;">1</span>
                                    <div>
                                        <h5 style="margin:0; font-size:13.5px; font-weight:700; color:#1e293b;"><i class="fas fa-trophy text-warning"></i> Registrar Adjudicación en el CRM</h5>
                                        <span style="font-size:11px; color:#64748b;">Área: Licitaciones • Acreditar acta oficial de Buena Pro</span>
                                    </div>
                                </div>
                                <?php renderDocUpload('doc_acta_adjudicacion', '📄 Acta de Buena Pro / Resolución de Adjudicación', $eval['doc_acta_adjudicacion'] ?? null, 'Acta oficial emitida por el Comité de Selección o SEACE'); ?>
                            </div>

                            <!-- HITO 2: PERFECCIONAR CONTRATO (REQUISITOS, DATOS Y GARANTÍAS) -->
                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;">
                                    <span style="width:28px; height:28px; border-radius:8px; background:#e0e7ff; color:#4f46e5; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:700;">2</span>
                                    <div>
                                        <h5 style="margin:0; font-size:13.5px; font-weight:700; color:#1e293b;"><i class="fas fa-file-signature text-primary"></i> Perfeccionar Contrato</h5>
                                        <span style="font-size:11px; color:#64748b;">Requisitos de perfeccionamiento según Ley de Contrataciones del Estado</span>
                                    </div>
                                </div>

                                <div class="op-form-group" style="margin-bottom:14px;">
                                    <label style="font-size:12px; font-weight:700; color:#1e293b; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                                        <i class="fas fa-shield-alt text-primary"></i> Modalidad de Garantía / Retención de 10% (BPMN)
                                    </label>
                                    <div class="op-input-wrap">
                                        <i class="fas fa-shield-halved op-input-icon"></i>
                                        <select name="perfeccionamiento_retencion" class="op-select">
                                            <option value="">-- Seleccionar Modalidad de Garantía --</option>
                                            <option value="retencion_10" <?= ($eval['perfeccionamiento_retencion'] ?? '') === 'retencion_10' ? 'selected' : '' ?>>Garantía o retención del 10% del contrato (Art. 149 RLCE)</option>
                                            <option value="carta_fianza" <?= ($eval['perfeccionamiento_retencion'] ?? '') === 'carta_fianza' ? 'selected' : '' ?>>Carta Fianza de Fiel Cumplimiento (10%)</option>
                                            <option value="poliza_caucion" <?= ($eval['perfeccionamiento_retencion'] ?? '') === 'poliza_caucion' ? 'selected' : '' ?>>Póliza de Caución (10%)</option>
                                        </select>
                                    </div>
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                                    <div class="op-form-group" style="margin:0;">
                                        <label style="font-size:12px; font-weight:700; color:#1e293b; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                                            <i class="fas fa-university text-primary"></i> # CTA CTE Bancaria
                                        </label>
                                        <div class="op-input-wrap">
                                            <i class="fas fa-credit-card op-input-icon"></i>
                                            <input type="text" name="num_cta_cte" class="op-input" value="<?= htmlspecialchars($eval['num_cta_cte'] ?? '') ?>" placeholder="Ej: BCP 191-XXXXXXXX-0-XX CCI...">
                                        </div>
                                    </div>
                                    <div class="op-form-group" style="margin:0;">
                                        <label style="font-size:12px; font-weight:700; color:#1e293b; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                                            <i class="fas fa-id-card text-primary"></i> DNI Rep. Legal / RUC
                                        </label>
                                        <div class="op-input-wrap">
                                            <i class="fas fa-user-check op-input-icon"></i>
                                            <input type="text" name="dni_representante_ruc" class="op-input" value="<?= htmlspecialchars($eval['dni_representante_ruc'] ?? '') ?>" placeholder="Ej: RUC 20601234567 / DNI 45892312">
                                        </div>
                                    </div>
                                </div>

                                <?php renderDocUpload('doc_desagregados', '📄 Desagregados de Precios Unitarios y Gastos Generales', $eval['doc_desagregados'] ?? null, 'Estructura analítica de costos y oferta económica desagregada'); ?>
                                
                                <div style="margin-top:10px;">
                                    <?php renderDocUpload('doc_equipamiento', '📄 Constancia de Equipamiento Estratégico', $eval['doc_equipamiento'] ?? null, 'Acreditación de maquinaria y equipos requeridos en bases'); ?>
                                </div>

                                <div style="margin-top:10px;">
                                    <?php renderDocUpload('doc_carta_fianza', '📄 Carta Fianza / Póliza de Fiel Cumplimiento (Si aplica)', $eval['doc_carta_fianza'] ?? null, 'Garantía financiera original emitida a favor de la Entidad'); ?>
                                </div>
                            </div>

                            <!-- HITO 3: FORMALIZAR CONTRATO -->
                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:16px; box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;">
                                    <span style="width:28px; height:28px; border-radius:8px; background:#e0e7ff; color:#4f46e5; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:700;">3</span>
                                    <div>
                                        <h5 style="margin:0; font-size:13.5px; font-weight:700; color:#1e293b;"><i class="fas fa-file-contract text-success"></i> Formalizar Contrato</h5>
                                        <span style="font-size:11px; color:#64748b;">Contrato firmado y garantías formalizadas</span>
                                    </div>
                                </div>

                                <?php renderDocUpload('doc_contrato_garantias', '📄 Contrato Firmado y Garantías Formalizadas', $eval['doc_contrato_garantias'] ?? null, 'Expediente del contrato suscrito por ambas partes'); ?>

                                <?php if ($contratoGenerado): ?>
                                    <div class="contract-linked-card" style="margin-top:12px; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1.5px solid #a7f3d0; border-radius: 10px; padding: 14px 16px;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-check-circle" style="color:#059669; font-size:16px;"></i>
                                                <strong style="font-size:13.5px; color:#064e3b;"><?= htmlspecialchars($contratoGenerado['codigo']) ?></strong>
                                            </div>
                                            <span style="font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 700; background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;">
                                                <?= getEstadoLabel($contratoGenerado['estado'] ?? 'en_elaboracion') ?>
                                            </span>
                                        </div>
                                        <div style="font-size:12px; color:#065f46; margin-bottom:10px;">
                                            <span>Monto adjudicado: <strong><?= formatMoney($contratoGenerado['monto_contrato'] ?? $item['monto_estimado']) ?></strong></span>
                                        </div>
                                        <div style="text-align:right;">
                                            <button type="button" onclick="if(window.parent){window.parent.location.href='<?= BASE_URL ?>/contratos';}else{window.location.href='<?= BASE_URL ?>/contratos';}" class="btn-op btn-op-success" style="padding: 5px 14px; font-size: 11.5px; cursor: pointer;">
                                                <i class="fas fa-external-link-alt"></i> Ver en Contratos
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- HITO 4: TRANSFERIR A PLANIFICACIÓN DE PROYECTOS (EXPEDIENTE COMPLETO) -->
                            <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1.5px solid #cbd5e1; border-radius:12px; padding:16px; margin-bottom:16px;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
                                    <span style="width:28px; height:28px; border-radius:8px; background:#dbeafe; color:#1d4ed8; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:700;">4</span>
                                    <div>
                                        <h5 style="margin:0; font-size:13.5px; font-weight:800; color:#0f172a;"><i class="fas fa-sitemap text-indigo"></i> Transferir a Planificación de Proyectos (Crear Proyecto)</h5>
                                        <span style="font-size:11px; color:#475569;">Área: Gestión de Proyectos • Fin de Gestión de Oportunidades</span>
                                    </div>
                                </div>

                                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:14px;">
                                    <span style="font-size:11px; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:0.5px; display:block; margin-bottom:8px;">
                                        <i class="fas fa-clipboard-check"></i> Expediente del Proyecto (Requisitos BPMN)
                                    </span>
                                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap:8px; font-size:11.5px;">
                                        <div style="padding:6px 8px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                                            <strong>1. TDR / Bases:</strong><br>
                                            <span style="color:<?= !empty($eval['doc_bases_tdr']) ? '#059669' : '#d97706' ?>; font-weight:600;">
                                                <i class="fas <?= !empty($eval['doc_bases_tdr']) ? 'fa-check-circle' : 'fa-clock' ?>"></i> <?= !empty($eval['doc_bases_tdr']) ? 'Adjunto' : 'Pendiente' ?>
                                            </span>
                                        </div>
                                        <div style="padding:6px 8px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                                            <strong>2. Personal Clave:</strong><br>
                                            <span style="color:<?= !empty($eval['doc_propuesta']) ? '#059669' : '#d97706' ?>; font-weight:600;">
                                                <i class="fas <?= !empty($eval['doc_propuesta']) ? 'fa-check-circle' : 'fa-clock' ?>"></i> <?= !empty($eval['doc_propuesta']) ? 'En Propuesta' : 'Pendiente' ?>
                                            </span>
                                        </div>
                                        <div style="padding:6px 8px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                                            <strong>3. Ppto Ofertado:</strong><br>
                                            <span style="color:<?= (!empty($eval['doc_desagregados']) || !empty($eval['doc_evaluacion_economica'])) ? '#059669' : '#d97706' ?>; font-weight:600;">
                                                <i class="fas <?= (!empty($eval['doc_desagregados']) || !empty($eval['doc_evaluacion_economica'])) ? 'fa-check-circle' : 'fa-clock' ?>"></i> <?= (!empty($eval['doc_desagregados']) || !empty($eval['doc_evaluacion_economica'])) ? 'Conforme' : 'Pendiente' ?>
                                            </span>
                                        </div>
                                        <div style="padding:6px 8px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                                            <strong>4. Contrato:</strong><br>
                                            <span style="color:<?= !empty($contratoGenerado) ? '#059669' : '#d97706' ?>; font-weight:600;">
                                                <i class="fas <?= !empty($contratoGenerado) ? 'fa-check-circle' : 'fa-clock' ?>"></i> <?= !empty($contratoGenerado) ? 'Vinculado' : 'Pendiente' ?>
                                            </span>
                                        </div>
                                        <div style="padding:6px 8px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                                            <strong>5. Carta Fianza:</strong><br>
                                            <span style="color:<?= (!empty($eval['doc_carta_fianza']) || !empty($eval['perfeccionamiento_retencion'])) ? '#059669' : '#d97706' ?>; font-weight:600;">
                                                <i class="fas <?= (!empty($eval['doc_carta_fianza']) || !empty($eval['perfeccionamiento_retencion'])) ? 'fa-check-circle' : 'fa-clock' ?>"></i> <?= (!empty($eval['doc_carta_fianza']) || !empty($eval['perfeccionamiento_retencion'])) ? 'Acreditada' : 'Pendiente' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($proyectoGenerado): ?>
                                    <div style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); border: 1.5px solid #6ee7b7; border-radius: 10px; padding: 14px 16px; margin-bottom: 10px;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 6px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-flag-checkered" style="color: #059669; font-size: 18px;"></i>
                                                <div>
                                                    <strong style="color: #065f46; font-size: 13.5px;"><?= htmlspecialchars($proyectoGenerado['codigo']) ?> — <?= htmlspecialchars($proyectoGenerado['nombre']) ?></strong>
                                                    <span style="display:block; font-size: 11px; color: #047857;">Transferencia a Planificación Culminada con Éxito</span>
                                                </div>
                                            </div>
                                            <span style="font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 700; background: #059669; color: #ffffff;">
                                                <?= ucfirst($proyectoGenerado['estado'] ?? 'planificacion') ?>
                                            </span>
                                        </div>
                                        <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                                            <button type="button" onclick="if(window.parent){window.parent.location.href='<?= BASE_URL ?>/proyectos';}else{window.location.href='<?= BASE_URL ?>/proyectos';}" class="btn-op btn-op-success" style="padding: 6px 16px; font-size: 12px; cursor: pointer;">
                                                <i class="fas fa-folder-open"></i> Ver en Gestión de Proyectos
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; margin-top: 12px;">
                                        <button type="submit" name="crear_proyecto" value="1" class="btn-op btn-op-success" style="padding: 10px 22px; font-size: 13px; font-weight: 800; background: linear-gradient(135deg, #059669, #0d9488); box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
                                            <i class="fas fa-rocket"></i> Transferir a Planificación de Proyectos (Crear Proyecto)
                                        </button>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 6px;">
                                            Hito final BPMN: Transfiere el expediente hacia la etapa operativa en Proyectos.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar Cambios de Perfeccionamiento
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <?php if (!empty($item['motivo_descarte'])): ?>
                            <div class="op-motivo-box" style="margin-bottom:14px; width:100%; box-sizing:border-box;">
                                <strong><i class="fas fa-exclamation-circle"></i> Motivo de cierre registrado:</strong><br>
                                <?= nl2br(htmlspecialchars($item['motivo_descarte'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>" enctype="multipart/form-data" style="text-align:left; max-width:620px; margin:0 auto;">
                            <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
                            <?php renderDocUpload('doc_cierre', '📄 Sustento Documental de Cierre / Descarte', $eval['doc_cierre'] ?? null, 'Notificación o informe de no adjudicación'); ?>
                            
                            <div style="text-align:center; margin-top:14px;">
                                <button type="submit" class="btn-op btn-op-secondary">
                                    <i class="fas fa-save"></i> Guardar Sustento de Cierre
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /opStagesContainer -->

</div><!-- /op-modal-wrapper -->

<script>
// Cambiar dinámicamente de paso BPMN sin recargar la página
function cambiarPasoVisual(stepNum) {
    // 1. Ocultar todas las etapas
    const allStages = document.querySelectorAll('.op-stage-view');
    allStages.forEach(function(el) {
        el.style.display = 'none';
    });

    // 2. Mostrar la etapa solicitada
    const targetStage = document.getElementById('stage_view_' + stepNum);
    if (targetStage) {
        targetStage.style.display = 'block';
    }

    // 3. Actualizar selección visual en los botones del stepper
    const allPills = document.querySelectorAll('.op-pill');
    allPills.forEach(function(p) {
        p.classList.remove('selected-tab');
        p.setAttribute('aria-selected', 'false');
    });

    const targetPill = document.getElementById('stepPill_' + stepNum);
    if (targetPill) {
        targetPill.classList.add('selected-tab');
        targetPill.setAttribute('aria-selected', 'true');
        targetPill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }

    // 4. Si está en un iframe modal, ajustar dimensiones automáticamente
    if (window.parent && typeof window.parent.redimensionarModalIframe === 'function') {
        window.parent.redimensionarModalIframe();
    }
}

// Manejador visual interactivo al seleccionar un archivo
function actualizarNombreDoc(input) {
    if (!input || !input.files || input.files.length === 0) return;
    const file = input.files[0];
    const display = document.getElementById('display_' + input.name);
    const dropzone = input.closest('.op-upload-dropzone');
    if (display) {
        display.innerHTML = '<i class="fas fa-check-circle"></i> Archivo seleccionado: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        display.style.display = 'inline-flex';
    }
    if (dropzone) {
        dropzone.classList.add('has-file');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Auto-scroll al paso activo en el stepper horizontal
    const activePill = document.getElementById('stepPill_<?= $currentStepNum ?>');
    const track = document.getElementById('stepPillsTrack');
    if (activePill && track) {
        setTimeout(function() {
            activePill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }, 150);
    }

    // 2. Manejador condicional en etapa 3 para evaluación especializada
    const chkEsp = document.querySelector('input[name="requiere_eval_especializada"]');
    const btnViab = document.getElementById('btnAvanzarViabilidad');
    if (chkEsp && btnViab) {
        const updateViabTarget = () => {
            btnViab.value = chkEsp.checked ? 'evaluacion_especializada' : 'consolidacion_viable';
        };
        chkEsp.addEventListener('change', updateViabTarget);
        updateViabTarget();
    }

    // 3. Manejador de confirmación para descarte con motivo obligatorio
    const discardBtns = document.querySelectorAll('.btn-discard');
    discardBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const motivo = prompt("Por favor, ingrese el motivo del descarte/cierre de esta oportunidad (obligatorio):");
            if (motivo !== null) {
                if (motivo.trim() === '') {
                    alert("Debe ingresar un motivo para poder descartar la oportunidad.");
                    return;
                }
                
                let inputMotivo = form.querySelector('input[name="motivo_descarte"]');
                if (!inputMotivo) {
                    inputMotivo = document.createElement('input');
                    inputMotivo.type = 'hidden';
                    inputMotivo.name = 'motivo_descarte';
                    form.appendChild(inputMotivo);
                }
                inputMotivo.value = motivo.trim();
                
                let inputBtn = form.querySelector('input[name="' + this.name + '"]');
                if (!inputBtn) {
                    inputBtn = document.createElement('input');
                    inputBtn.type = 'hidden';
                    inputBtn.name = this.name;
                    form.appendChild(inputBtn);
                }
                inputBtn.value = this.value;
                
                form.submit();
            }
        });
    });

    // 4. Notificar redimensión y asegurar ancho óptimo al modal padre si está en un iframe
    if (window.parent && window.parent !== window) {
        try {
            const parentModal = window.parent.document.querySelector('.global-modal');
            if (parentModal) parentModal.classList.add('modal-wide');
        } catch(e) {}
        if (typeof window.parent.redimensionarModalIframe === 'function') {
            window.parent.redimensionarModalIframe();
            setTimeout(window.parent.redimensionarModalIframe, 80);
            setTimeout(window.parent.redimensionarModalIframe, 250);
        }
    }
});
</script>
