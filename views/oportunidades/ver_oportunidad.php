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

$keysFlujo = array_keys($estadosFlujo);
$currentIndex = array_search($item['estado'], $keysFlujo);
if ($currentIndex === false) $currentIndex = -1;

$isClosed = in_array($item['estado'], ['descartada', 'no_adjudicada', 'adjudicada']);

if ($item['estado'] === 'adjudicada') {
    $progressPct = 100;
} elseif ($currentIndex >= 0) {
    $progressPct = round((($currentIndex + 1) / 10) * 100);
} else {
    $progressPct = 0;
}
?>

<style>
/* Reset & Base dentro del modal o vista */
.op-modal-wrapper {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    padding: <?= $isModal ? '14px 16px 28px' : '20px 24px 40px' ?>;
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
    margin-bottom: 12px;
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
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 5px;
    scrollbar-width: thin;
    scrollbar-color: #e2e8f0 transparent;
}
.op-step-pills-wrap::-webkit-scrollbar {
    height: 3px;
}
.op-step-pills-wrap::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
}
.op-step-pills-wrap::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

.op-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 9px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.op-pill.completed {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
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

/* Checklist Cards (Paso 2) */
.op-checklist {
    display: flex;
    flex-direction: column;
    gap: 9px;
    margin-bottom: 16px;
}
.op-check-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.op-check-card:hover {
    border-color: #cbd5e1;
    background: #ffffff;
}
.op-check-card input[type="checkbox"] {
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
    font-size: 12.5px;
    font-weight: 600;
    color: #1e293b;
}
.op-check-desc {
    font-size: 11px;
    color: #64748b;
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
                    Etapa <strong><?= ($currentIndex + 1) ?></strong> de 10
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
                Etapa actual: <strong><?= isset($estadosFlujo[$item['estado']]) ? $estadosFlujo[$item['estado']]['title'] : 'Cerrada' ?></strong>
            </span>
            <span class="op-stepper-pct"><?= $progressPct ?>%</span>
        </div>

        <div class="op-progress-bar">
            <div class="op-progress-fill" style="width: <?= $progressPct ?>%;"></div>
        </div>

        <div class="op-step-pills-wrap" id="stepPillsTrack">
            <?php 
            $i = 0;
            foreach ($estadosFlujo as $key => $st): 
                $isPassed = ($i < $currentIndex);
                $isCurrent = ($i === $currentIndex);
                $pillClass = $isPassed ? 'completed' : ($isCurrent ? 'active' : 'pending');
            ?>
            <div class="op-pill <?= $pillClass ?>" <?= $isCurrent ? 'id="activeStepPill"' : '' ?> title="<?= $st['title'] ?>">
                <span class="op-pill-num">
                    <?php if ($isPassed): ?>
                        <i class="fas fa-check"></i>
                    <?php else: ?>
                        <?= $st['num'] ?>
                    <?php endif; ?>
                </span>
                <?php if ($isCurrent): ?><span class="op-pulse"></span><?php endif; ?>
                <span><?= $st['label'] ?></span>
            </div>
            <?php $i++; endforeach; ?>
        </div>
    </div>

    <!-- 3. ACTION / STAGE WORKFLOW CARD -->
    <?php if (!$isClosed): ?>
        
        <?php if ($item['estado'] === 'identificada'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-search"></i></div>
                <div class="op-stage-info">
                    <h4>1. Identificación y Clasificación</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                </div>
            </div>

            <div class="op-guide-banner">
                <i class="fas fa-info-circle"></i>
                <span>La oportunidad ha sido detectada y registrada en el sistema. Para continuar el flujo BPMN, clasifíquela y proceda a la revisión de requisitos mínimos.</span>
            </div>

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
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
        </div>

        <?php elseif ($item['estado'] === 'evaluacion_requisitos'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-tasks"></i></div>
                <div class="op-stage-info">
                    <h4>2. Revisión de Requisitos Mínimos</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                </div>
            </div>

            <div class="op-guide-banner">
                <i class="fas fa-info-circle"></i>
                <span>Verifique el cumplimiento de bases, aspectos legales y cronograma antes de comprometer recursos en la etapa de viabilidad.</span>
            </div>

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                <div class="op-checklist">
                    <label class="op-check-card">
                        <input type="checkbox" name="req_bases_tdr" <?= !empty($eval['req_bases_tdr']) ? 'checked' : '' ?>>
                        <div class="op-check-content">
                            <span class="op-check-title">Revisión de Bases / TDR</span>
                            <span class="op-check-desc">Términos de referencia y especificaciones técnicas claras y alcanzables</span>
                        </div>
                    </label>

                    <label class="op-check-card">
                        <input type="checkbox" name="req_legales" <?= !empty($eval['req_legales']) ? 'checked' : '' ?>>
                        <div class="op-check-content">
                            <span class="op-check-title">Requisitos Legales</span>
                            <span class="op-check-desc">RNP vigente, capacidad legal y ausencia de impedimentos para contratar</span>
                        </div>
                    </label>

                    <label class="op-check-card">
                        <input type="checkbox" name="req_cronograma" <?= !empty($eval['req_cronograma']) ? 'checked' : '' ?>>
                        <div class="op-check-content">
                            <span class="op-check-title">Cronograma Viable</span>
                            <span class="op-check-desc">Plazos suficientes para consultas, observaciones y formulación de ofertas</span>
                        </div>
                    </label>
                </div>

                <div class="op-actions-bar">
                    <button type="submit" name="siguiente_estado" value="viabilidad_preliminar" class="btn-op btn-op-primary">
                        <i class="fas fa-arrow-right"></i> Aprobar y pasar a Viabilidad Preliminar
                    </button>
                    <button type="submit" class="btn-op btn-op-secondary">
                        <i class="fas fa-save"></i> Guardar sin avanzar
                    </button>
                    <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                        <i class="fas fa-ban"></i> No participar
                    </button>
                </div>
            </form>
        </div>

        <?php elseif ($item['estado'] === 'viabilidad_preliminar'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-chart-pie"></i></div>
                <div class="op-stage-info">
                    <h4>3. Viabilidad Preliminar</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Gestión de Proyectos</span>
                </div>
            </div>

            <div class="op-guide-banner">
                <i class="fas fa-info-circle"></i>
                <span>Evalúe el alcance, los plazos y los riesgos del proyecto para determinar si requiere un análisis especializado o pasa a consolidación.</span>
            </div>

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
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
                    <button type="submit" name="siguiente_estado" value="evaluacion_especializada" id="btnAvanzarViabilidad" class="btn-op btn-op-primary">
                        <i class="fas fa-arrow-right"></i> Aprobar Viabilidad
                    </button>
                    <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                        <i class="fas fa-ban"></i> No viable
                    </button>
                </div>
            </form>
        </div>
        <script>
            (function() {
                const chk = document.querySelector('input[name="requiere_eval_especializada"]');
                const btn = document.getElementById('btnAvanzarViabilidad');
                if (chk && btn) {
                    const updateTarget = () => {
                        btn.value = chk.checked ? 'evaluacion_especializada' : 'consolidacion_viable';
                    };
                    chk.addEventListener('change', updateTarget);
                    updateTarget();
                }
            })();
        </script>

        <?php elseif ($item['estado'] === 'evaluacion_especializada'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-microscope"></i></div>
                <div class="op-stage-info">
                    <h4>4. Evaluación Especializada</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Consultoría y Licitaciones</span>
                </div>
            </div>

            <div class="op-guide-banner">
                <i class="fas fa-info-circle"></i>
                <span>Ingrese los informes técnico y económico detallados por parte de los especialistas asignados.</span>
            </div>

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                <div class="op-form-group">
                    <label><i class="fas fa-cogs text-primary"></i> Análisis Técnico (Consultoría: Alcance, metodología, personal)</label>
                    <textarea name="informe_tecnico" class="op-textarea" rows="3" placeholder="Registre la metodología técnica propuesta..."><?= htmlspecialchars($eval['informe_tecnico'] ?? '') ?></textarea>
                </div>

                <div class="op-form-group">
                    <label><i class="fas fa-chart-line text-primary"></i> Evaluación Económica (Costos, margen y flujo de caja)</label>
                    <textarea name="informe_economico" class="op-textarea" rows="3" placeholder="Registre los costos estimados, margen esperado..."><?= htmlspecialchars($eval['informe_economico'] ?? '') ?></textarea>
                </div>

                <div class="op-actions-bar">
                    <button type="submit" name="siguiente_estado" value="consolidacion_viable" class="btn-op btn-op-primary">
                        <i class="fas fa-arrow-right"></i> Enviar a Consolidación
                    </button>
                </div>
            </form>
        </div>

        <?php elseif ($item['estado'] === 'consolidacion_viable'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-compress-arrows-alt"></i></div>
                <div class="op-stage-info">
                    <h4>5. Consolidar Evaluación</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                </div>
            </div>

            <div class="op-guide-banner">
                <i class="fas fa-info-circle"></i>
                <span>Consolide los informes técnico, económico y legal para confirmar la viabilidad final y autorizar la elaboración de la oferta.</span>
            </div>

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                <div class="op-form-group">
                    <label><i class="fas fa-file-alt text-primary"></i> Informe de Evaluación Consolidada Técnica, Económica y Legal</label>
                    <textarea name="consolidacion_informe" class="op-textarea" rows="3" placeholder="Resumen del dictamen consolidado para pasar a elaboración de propuesta..."><?= htmlspecialchars($eval['consolidacion_informe'] ?? '') ?></textarea>
                </div>

                <div class="op-actions-bar">
                    <button type="submit" name="siguiente_estado" value="elaboracion_propuesta" class="btn-op btn-op-primary">
                        <i class="fas fa-arrow-right"></i> Confirmar Viabilidad y Elaborar Propuesta
                    </button>
                    <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                        <i class="fas fa-ban"></i> No viable
                    </button>
                </div>
            </form>
        </div>

        <?php elseif ($item['estado'] === 'elaboracion_propuesta'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-file-contract"></i></div>
                <div class="op-stage-info">
                    <h4>6. Elaborar Propuesta Técnica y Económica</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                </div>
            </div>

            <div class="op-guide-banner">
                <i class="fas fa-info-circle"></i>
                <span>La oportunidad ha sido confirmada como viable. Proceda con la redacción y armado de la propuesta oficial para enviarla a revisión interna.</span>
            </div>

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                <div class="op-actions-bar">
                    <button type="submit" name="siguiente_estado" value="revision_propuesta" class="btn-op btn-op-primary">
                        <i class="fas fa-paper-plane"></i> Enviar a Revisión de Propuesta
                    </button>
                </div>
            </form>
        </div>

        <?php elseif ($item['estado'] === 'revision_propuesta'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-check-double"></i></div>
                <div class="op-stage-info">
                    <h4>7. Revisión y Validación de Propuesta</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Consultoría y Gestión de Proyectos</span>
                </div>
            </div>

            <div class="op-guide-banner">
                <i class="fas fa-info-circle"></i>
                <span>Valide que la oferta cumpla con los estándares técnicos y la rentabilidad esperada antes de remitirla a Gerencia General.</span>
            </div>

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                <div class="op-form-group">
                    <label><i class="fas fa-check-circle text-primary"></i> Revisión y Validación Técnica (Consultorías)</label>
                    <textarea name="revision_tecnica" class="op-textarea" rows="2" placeholder="Observaciones o visto bueno técnico..."><?= htmlspecialchars($eval['revision_tecnica'] ?? '') ?></textarea>
                </div>

                <div class="op-form-group">
                    <label><i class="fas fa-dollar-sign text-primary"></i> Revisión y Validación Económica (Gestión de Proyectos)</label>
                    <textarea name="revision_economica" class="op-textarea" rows="2" placeholder="Validación de precios unitarios y rentabilidad final..."><?= htmlspecialchars($eval['revision_economica'] ?? '') ?></textarea>
                </div>

                <div class="op-actions-bar">
                    <button type="submit" name="siguiente_estado" value="aprobacion_gerencia" class="btn-op btn-op-primary">
                        <i class="fas fa-share"></i> Validar y Enviar a Gerencia General
                    </button>
                </div>
            </form>
        </div>

        <?php elseif ($item['estado'] === 'aprobacion_gerencia'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-user-tie"></i></div>
                <div class="op-stage-info">
                    <h4>8. Aprobación de Gerencia General / CEO</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Dirección General</span>
                </div>
            </div>

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

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                <div class="op-actions-bar">
                    <button type="submit" name="siguiente_estado" value="presentada" class="btn-op btn-op-success">
                        <i class="fas fa-check-double"></i> Aprobar Participación y Presentación
                    </button>
                    <button type="submit" name="siguiente_estado" value="descartada" class="btn-op btn-op-danger btn-discard">
                        <i class="fas fa-times-circle"></i> Rechazar Oferta
                    </button>
                </div>
            </form>
        </div>

        <?php elseif ($item['estado'] === 'presentada'): ?>
        <div class="op-stage-card">
            <div class="op-stage-header">
                <div class="op-stage-icon-box"><i class="fas fa-paper-plane"></i></div>
                <div class="op-stage-info">
                    <h4>9. Seguimiento y Resultados</h4>
                    <span class="op-stage-subtitle"><i class="fas fa-sitemap"></i> Área: Licitaciones</span>
                </div>
            </div>

            <div class="op-guide-banner">
                <i class="fas fa-info-circle"></i>
                <span>La propuesta ha sido presentada formalmente. Registre observaciones, consultas, aclaraciones o subsanaciones mientras se espera la publicación de la Buena Pro.</span>
            </div>

            <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>
                <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">

                <div class="op-form-group">
                    <label><i class="fas fa-clipboard-list text-primary"></i> Bitácora de Seguimiento (Subsanaciones / Observaciones)</label>
                    <textarea name="observaciones" class="op-textarea" rows="3" placeholder="Registre incidencias, subsanaciones presentadas o estado de apertura de sobres..."><?= htmlspecialchars($item['observaciones'] ?? '') ?></textarea>
                </div>

                <div class="op-actions-bar">
                    <button type="submit" class="btn-op btn-op-secondary">
                        <i class="fas fa-save"></i> Guardar Bitácora
                    </button>
                    <button type="submit" name="siguiente_estado" value="adjudicada" class="btn-op btn-op-success">
                        <i class="fas fa-trophy"></i> Adjudicada (Buena Pro Ganada)
                    </button>
                    <button type="submit" name="siguiente_estado" value="no_adjudicada" class="btn-op btn-op-danger btn-discard">
                        <i class="fas fa-times-circle"></i> No Adjudicada
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- ESTADO CERRADO (ADJUDICADA O DESCARTADA) -->
        <div class="op-closed-banner">
            <div class="op-closed-icon <?= $item['estado'] === 'adjudicada' ? 'won' : 'lost' ?>">
                <i class="<?= $item['estado'] === 'adjudicada' ? 'fas fa-trophy' : 'fas fa-times-circle' ?>"></i>
            </div>
            
            <h3 class="op-closed-title" style="color: <?= $item['estado'] === 'adjudicada' ? '#059669' : '#dc2626' ?>;">
                <?= $item['estado'] === 'adjudicada' ? '¡Oportunidad Adjudicada (Buena Pro)!' : 'Oportunidad Cerrada / Descartada' ?>
            </h3>
            
            <p class="op-closed-desc">
                <?= $item['estado'] === 'adjudicada' ? 'El proceso concluyó con éxito y la propuesta fue ganadora. Se procede a la fase de suscripción de contrato.' : 'Esta oportunidad fue cerrada y no continuará en el flujo comercial activo.' ?>
            </p>

            <?php if (!empty($item['motivo_descarte'])): ?>
                <div class="op-motivo-box">
                    <strong><i class="fas fa-exclamation-circle"></i> Motivo de cierre registrado:</strong><br>
                    <?= nl2br(htmlspecialchars($item['motivo_descarte'])) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Auto-scroll al paso activo en el stepper horizontal
    const activePill = document.getElementById('activeStepPill');
    const track = document.getElementById('stepPillsTrack');
    if (activePill && track) {
        setTimeout(function() {
            activePill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }, 150);
    }

    // 2. Manejador de confirmación para descarte con motivo obligatorio
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

    // 3. Notificar redimensión al modal padre si está en un iframe
    if (window.parent && window.parent !== window && typeof window.parent.redimensionarModalIframe === 'function') {
        window.parent.redimensionarModalIframe();
        setTimeout(window.parent.redimensionarModalIframe, 80);
        setTimeout(window.parent.redimensionarModalIframe, 250);
    }
});
</script>
