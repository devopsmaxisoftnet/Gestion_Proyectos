<!-- Detalle Oportunidad -->
<?php 
$item = $data['oportunidad']; 
$eval = $data['evaluacion'] ?? [];
$estadosFlujo = [
    'identificada' => 'Identificación',
    'evaluacion_requisitos' => 'Licitaciones (Requisitos)',
    'viabilidad_preliminar' => 'Proyectos (Viabilidad)',
    'evaluacion_especializada' => 'Especializada (Téc/Econ)',
    'aprobacion_gerencia' => 'Aprobación Gerencia',
    'preparacion_propuesta' => 'Propuesta',
    'presentada' => 'Presentada',
    'adjudicada' => 'Adjudicada'
];

$currentIndex = array_search($item['estado'], array_keys($estadosFlujo));
if ($currentIndex === false) $currentIndex = -1;
if (in_array($item['estado'], ['descartada', 'no_adjudicada'])) {
    $isClosed = true;
} else {
    $isClosed = false;
}
?>

<style>
.bpmn-stepper {
    display: flex;
    justify-content: space-between;
    margin: 30px 0;
    position: relative;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
}
.bpmn-stepper::before {
    content: '';
    position: absolute;
    top: 45px;
    left: 40px;
    right: 40px;
    height: 4px;
    background: #e2e8f0;
    z-index: 1;
}
.bpmn-step {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    width: 12%;
    opacity: 0.5;
    transition: all 0.3s ease;
}
.bpmn-step.active, .bpmn-step.completed {
    opacity: 1;
}
.bpmn-step-icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 10px;
    border: 4px solid white;
    box-shadow: 0 0 0 1px #e2e8f0;
    transition: all 0.3s;
}
.bpmn-step.completed .bpmn-step-icon {
    background: #10b981;
    color: white;
    box-shadow: 0 0 0 1px #10b981;
}
.bpmn-step.active .bpmn-step-icon {
    background: #6366f1;
    color: white;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
    transform: scale(1.1);
}
.bpmn-step-label {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
}
.workflow-form {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-top: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    border: 1px solid #e2e8f0;
}
.workflow-form h3 {
    margin-top: 0;
    color: #1e293b;
    font-size: 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.checklist {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.check-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.check-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
}
.action-buttons {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}
</style>

<div class="page-header">
    <div>
        <h2>Seguimiento BPMN de Oportunidad</h2>
        <p><?= $item['codigo'] ?> - <?= htmlspecialchars($item['nombre']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>/oportunidades" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<!-- Stepper BPMN -->
<div class="bpmn-stepper">
    <?php 
    $i = 0;
    foreach ($estadosFlujo as $key => $label): 
        $class = '';
        if ($i < $currentIndex) $class = 'completed';
        else if ($i === $currentIndex) $class = 'active';
    ?>
    <div class="bpmn-step <?= $class ?>">
        <div class="bpmn-step-icon">
            <?php if ($i < $currentIndex): ?>
                <i class="fas fa-check"></i>
            <?php else: ?>
                <?= $i + 1 ?>
            <?php endif; ?>
        </div>
        <div class="bpmn-step-label"><?= $label ?></div>
    </div>
    <?php $i++; endforeach; ?>
</div>

<div class="detail-card mb-3">
    <div class="detail-header">
        <h2>Detalles Generales</h2>
        <span class="badge <?= getEstadoClass($item['estado']) ?>"><?= getEstadoLabel($item['estado']) ?></span>
    </div>
    <div class="detail-body">
        <div class="detail-grid">
            <div class="detail-field"><label>Entidad / Cliente</label><span><?= htmlspecialchars($item['entidad'] ?? '-') ?></span></div>
            <div class="detail-field"><label>Monto estimado</label><span><?= formatMoney($item['monto_estimado']) ?></span></div>
            <div class="detail-field"><label>Fecha límite</label><span><?= formatDate($item['fecha_limite']) ?></span></div>
            <div class="detail-field"><label>Origen</label><span><?= ucfirst(str_replace('_', ' ', $item['origen'] ?? 'No definido')) ?></span></div>
        </div>
    </div>
</div>

<!-- Workflow Forms -->
<?php if (!$isClosed): ?>
    <?php if ($item['estado'] === 'identificada'): ?>
    <div class="workflow-form">
        <h3><i class="fas fa-search"></i> 1. Identificación y Clasificación (Licitaciones)</h3>
        <p class="text-sm mb-3">La oportunidad ha sido detectada. Para avanzar, asegúrese de clasificarla y enviarla a revisión de requisitos.</p>
        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
            <div class="action-buttons">
                <button type="submit" name="siguiente_estado" value="evaluacion_requisitos" class="btn btn-primary">Proceder a Evaluación de Requisitos</button>
                <button type="submit" name="siguiente_estado" value="descartada" class="btn btn-danger">Descartar</button>
            </div>
        </form>
    </div>

    <?php elseif ($item['estado'] === 'evaluacion_requisitos'): ?>
    <div class="workflow-form">
        <h3><i class="fas fa-list-check"></i> 2. Revisión de Requisitos Mínimos (Licitaciones)</h3>
        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
            <div class="checklist">
                <label class="check-item">
                    <input type="checkbox" name="req_bases_tdr" <?= !empty($eval['req_bases_tdr']) ? 'checked' : '' ?>> 
                    Revisión de Bases / TDR
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_legales" <?= !empty($eval['req_legales']) ? 'checked' : '' ?>> 
                    Requisitos Legales
                </label>
                <label class="check-item">
                    <input type="checkbox" name="req_cronograma" <?= !empty($eval['req_cronograma']) ? 'checked' : '' ?>> 
                    Cronograma Viable
                </label>
            </div>
            <div class="action-buttons">
                <button type="submit" name="siguiente_estado" value="viabilidad_preliminar" class="btn btn-primary">Aprobar y pasar a Viabilidad Preliminar</button>
                <button type="submit" name="siguiente_estado" value="descartada" class="btn btn-danger">No participar</button>
                <button type="submit" class="btn btn-secondary">Guardar sin avanzar</button>
            </div>
        </form>
    </div>

    <?php elseif ($item['estado'] === 'viabilidad_preliminar'): ?>
    <div class="workflow-form">
        <h3><i class="fas fa-project-diagram"></i> 3. Viabilidad Preliminar (Gestión de Proyectos)</h3>
        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Evaluación de Alcance</label>
                    <textarea name="viabilidad_alcance" class="form-control" rows="2"><?= htmlspecialchars($eval['viabilidad_alcance'] ?? '') ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Evaluación de Plazos y Riesgos</label>
                    <textarea name="viabilidad_riesgos" class="form-control" rows="2"><?= htmlspecialchars($eval['viabilidad_riesgos'] ?? '') ?></textarea>
                </div>
                <div class="form-group full-width" style="padding: 15px; background: #fffbeb; border-radius: 8px; border: 1px solid #fde68a;">
                    <label class="check-item" style="border:none; background:transparent; padding:0; margin:0; font-weight:600; color:#d97706;">
                        <input type="checkbox" name="requiere_eval_especializada" value="1" <?= !empty($item['requiere_evaluacion_especializada']) ? 'checked' : '' ?>> 
                        ¿Requiere evaluación especializada (Consultoría / Finanzas)?
                    </label>
                </div>
            </div>
            <div class="action-buttons">
                <!-- Se usa JS para cambiar el valor según el checkbox -->
                <button type="submit" name="siguiente_estado" value="evaluacion_especializada" id="btnAvanzarViabilidad" class="btn btn-primary">Aprobar Viabilidad</button>
                <button type="submit" name="siguiente_estado" value="descartada" class="btn btn-danger">No viable</button>
            </div>
        </form>
    </div>
    <script>
        const chkEval = document.querySelector('input[name="requiere_eval_especializada"]');
        const btnAvanzar = document.getElementById('btnAvanzarViabilidad');
        chkEval.addEventListener('change', (e) => {
            btnAvanzar.value = e.target.checked ? 'evaluacion_especializada' : 'aprobacion_gerencia';
        });
        // Run once on load
        btnAvanzar.value = chkEval.checked ? 'evaluacion_especializada' : 'aprobacion_gerencia';
    </script>

    <?php elseif ($item['estado'] === 'evaluacion_especializada'): ?>
    <div class="workflow-form">
        <h3><i class="fas fa-microscope"></i> 4. Evaluación Especializada (Consultoría y Finanzas)</h3>
        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Informe Técnico (Alcance, metodología, personal)</label>
                    <textarea name="informe_tecnico" class="form-control" rows="3"><?= htmlspecialchars($eval['informe_tecnico'] ?? '') ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Informe Económico (Costos, rentabilidad, flujo de caja)</label>
                    <textarea name="informe_economico" class="form-control" rows="3"><?= htmlspecialchars($eval['informe_economico'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="action-buttons">
                <button type="submit" name="siguiente_estado" value="aprobacion_gerencia" class="btn btn-primary">Consolidar y Enviar a Gerencia</button>
                <button type="submit" name="siguiente_estado" value="descartada" class="btn btn-danger">No viable</button>
            </div>
        </form>
    </div>

    <?php elseif ($item['estado'] === 'aprobacion_gerencia'): ?>
    <div class="workflow-form">
        <h3><i class="fas fa-user-tie"></i> 5. Aprobación de Gerencia General / CEO</h3>
        <div style="background: #f8fafc; padding:15px; border-radius:8px; margin-bottom:15px; font-size:13px;">
            <p><strong>Revisión Técnica:</strong> <?= htmlspecialchars($eval['informe_tecnico'] ?? 'No requerida o no ingresada') ?></p>
            <p><strong>Revisión Económica:</strong> <?= htmlspecialchars($eval['informe_economico'] ?? 'No requerida o no ingresada') ?></p>
        </div>
        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
            <div class="action-buttons">
                <button type="submit" name="siguiente_estado" value="preparacion_propuesta" class="btn btn-success"><i class="fas fa-check"></i> Aprobar Participación / Propuesta</button>
                <button type="submit" name="siguiente_estado" value="descartada" class="btn btn-danger">Rechazar</button>
            </div>
        </form>
    </div>

    <?php elseif ($item['estado'] === 'preparacion_propuesta'): ?>
    <div class="workflow-form">
        <h3><i class="fas fa-file-contract"></i> 6. Preparación de Propuesta</h3>
        <p class="text-sm mb-3">La Gerencia ha aprobado participar. Procede a elaborar la propuesta técnica y económica oficial.</p>
        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
            <div class="action-buttons">
                <button type="submit" name="siguiente_estado" value="presentada" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Marcar como Presentada</button>
            </div>
        </form>
    </div>

    <?php elseif ($item['estado'] === 'presentada'): ?>
    <div class="workflow-form">
        <h3><i class="fas fa-gavel"></i> 7. Seguimiento y Resultados</h3>
        <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=procesar_etapa&id=<?= $item['id'] ?>">
            <input type="hidden" name="estado_actual" value="<?= $item['estado'] ?>">
            <div class="action-buttons">
                <button type="submit" name="siguiente_estado" value="adjudicada" class="btn btn-success"><i class="fas fa-trophy"></i> Adjudicada (Buena Pro)</button>
                <button type="submit" name="siguiente_estado" value="no_adjudicada" class="btn btn-danger">No Adjudicada</button>
            </div>
        </form>
    </div>

    <?php endif; ?>
<?php else: ?>
    <div class="workflow-form" style="text-align:center; padding: 40px 20px;">
        <h2 style="color: <?= $item['estado'] === 'adjudicada' ? '#10b981' : '#ef4444' ?>;">
            <?= $item['estado'] === 'adjudicada' ? '<i class="fas fa-trophy"></i> Oportunidad Adjudicada' : '<i class="fas fa-times-circle"></i> Oportunidad Cerrada / Descartada' ?>
        </h2>
        <p class="text-muted mt-2">Esta oportunidad ha finalizado su ciclo BPMN.</p>
    </div>
<?php endif; ?>
