<!-- Crear / Editar Propuesta - Diseño Ejecutivo de Alta Fidelidad -->
<?php 
$item = $data['propuesta'] ?? null; 
$isModal = !empty($_GET['modal_iframe']);
?>

<style>
.prop-form-wrapper {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    padding: <?= $isModal ? '12px 18px 28px' : '20px 24px 40px' ?>;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1e293b;
}

/* Header de la ficha si no está en iframe modal */
.prop-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}
.prop-page-title h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
}
.prop-page-title p {
    margin: 3px 0 0;
    font-size: 12.5px;
    color: #64748b;
}

/* Banner de Resumen / Estado */
.prop-hero-status-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.prop-code-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 800;
    color: #3b82f6;
    letter-spacing: 0.3px;
}
.btn-prop-sheet {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 14px;
    background: #ffffff;
    color: #4338ca;
    border: 1.5px solid #c7d2fe;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(67, 56, 202, 0.06);
}
.btn-prop-sheet:hover {
    background: #4338ca;
    color: #ffffff;
    border-color: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);
}

/* Secciones organizadas en tarjetas ejecutivas */
.prop-section-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    padding: 18px 20px;
    margin-bottom: 18px;
    box-shadow: 0 3px 12px rgba(15, 23, 42, 0.03);
    transition: box-shadow 0.2s;
}
.prop-section-card:hover {
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}
.prop-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.prop-sec-icon-box {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.icon-comercial { background: #eff6ff; color: #2563eb; }
.icon-tecnica { background: #f5f3ff; color: #7c3aed; }
.icon-economica { background: #ecfdf5; color: #059669; }
.icon-notas { background: #fffbeb; color: #d97706; }

.prop-sec-title-group h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}
.prop-sec-title-group span {
    font-size: 11.5px;
    color: #64748b;
}

/* Grid de Formulario */
.prop-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 18px;
}
.prop-grid-full {
    grid-column: 1 / -1;
}

.prop-form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.prop-label {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.prop-label i {
    margin-right: 5px;
}
.prop-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.prop-input-icon {
    position: absolute;
    left: 12px;
    font-size: 13px;
    color: #94a3b8;
    pointer-events: none;
}
.prop-control {
    width: 100%;
    box-sizing: border-box;
    padding: 9px 12px 9px 36px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 12.5px;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.2s;
    outline: none;
}
.prop-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    background: #ffffff;
}
.prop-control-no-icon {
    padding-left: 12px;
}
.prop-textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 12.5px;
    font-family: inherit;
    color: #0f172a;
    background: #ffffff;
    transition: all 0.2s;
    outline: none;
    resize: vertical;
    line-height: 1.45;
}
.prop-textarea:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

/* Custom Dropzones para archivos adjuntos */
.prop-doc-current {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 8px;
}
.prop-doc-current-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 11.5px;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
}
.prop-doc-current-link:hover {
    text-decoration: underline;
}

.prop-dropzone {
    position: relative;
    border: 1.5px dashed #cbd5e1;
    border-radius: 9px;
    padding: 12px 14px;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
}
.prop-dropzone:hover {
    border-color: #6366f1;
    background: #eef2ff;
}
.prop-dropzone-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #6366f1;
    flex-shrink: 0;
}
.prop-dropzone-text {
    flex: 1;
}
.prop-dropzone-title {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
}
.prop-dropzone-desc {
    display: block;
    font-size: 10.5px;
    color: #64748b;
}
.prop-native-file {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    opacity: 0;
    cursor: pointer;
}
.prop-file-selected-name {
    margin-top: 6px;
    font-size: 11px;
    color: #059669;
    font-weight: 600;
    display: none;
    align-items: center;
    gap: 5px;
}

/* Botonera de acciones inferior */
.prop-actions-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}
.btn-prop {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 20px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-prop-primary {
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.btn-prop-primary:hover {
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    transform: translateY(-1px);
}
.btn-prop-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.btn-prop-secondary:hover {
    background: #e2e8f0;
    color: #1e293b;
}

@media (max-width: 650px) {
    .prop-grid {
        grid-template-columns: 1fr;
    }
    .btn-prop {
        width: 100%;
    }
}
</style>

<div class="prop-form-wrapper">

    <?php if (!$isModal): ?>
    <div class="prop-page-header">
        <div class="prop-page-title">
            <h2><?= $item ? 'Editar Propuesta ' . htmlspecialchars($item['codigo'] ?? '') : 'Nueva Propuesta Comercial y Técnica' ?></h2>
            <p><?= $item ? 'Modifica los datos técnicos, económicos y sustentos de la propuesta' : 'Registra una nueva propuesta comercial y técnica' ?></p>
        </div>
        <div style="display:flex; gap:10px;">
            <?php if ($item): ?>
                <a href="<?= BASE_URL ?>/propuestas/ficha/<?= $item['id'] ?>" class="btn-prop-sheet">
                    <i class="fas fa-file-pdf"></i> Ver Ficha Oficial
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/propuestas" class="btn-prop btn-prop-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($item): ?>
    <!-- HERO STATUS CARD -->
    <div class="prop-hero-status-card">
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span class="prop-code-badge">
                <i class="fas fa-file-contract"></i> <?= htmlspecialchars($item['codigo']) ?>
            </span>
            <span class="badge <?= getEstadoClass($item['estado'] ?? 'borrador') ?>">
                <?= getEstadoLabel($item['estado'] ?? 'borrador') ?>
            </span>
            <?php if (!empty($item['monto_propuesta'])): ?>
                <span style="font-size:12px; font-weight:700; color:#059669;">
                    <i class="fas fa-coins"></i> <?= formatMoney($item['monto_propuesta']) ?>
                </span>
            <?php endif; ?>
        </div>
        <div>
            <button type="button" onclick="if(window.parent&&window.parent.abrirGlobalModal){window.parent.abrirGlobalModal('Documento Oficial de Propuesta <?= $item['codigo'] ?>', '<?= BASE_URL ?>/propuestas/ficha/<?= $item['id'] ?>?modal_iframe=1', 'fas fa-file-invoice-dollar', 'Documento formal con membrete y firmas', 'wide');}else{window.location.href='<?= BASE_URL ?>/propuestas/ficha/<?= $item['id'] ?>';}" class="btn-prop-sheet">
                <i class="fas fa-print"></i> Generar / Imprimir Ficha Oficial
            </button>
        </div>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/?page=propuestas&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>" enctype="multipart/form-data">
        <?php if ($isModal): ?><input type="hidden" name="modal_iframe" value="1"><?php endif; ?>

        <!-- SECCIÓN 1: VINCULACIÓN COMERCIAL Y RESPONSABLE -->
        <div class="prop-section-card">
            <div class="prop-section-header">
                <div class="prop-sec-icon-box icon-comercial">
                    <i class="fas fa-gavel"></i>
                </div>
                <div class="prop-sec-title-group">
                    <h4>1. Vinculación Comercial y Licitación</h4>
                    <span>Expediente de licitación origen, responsable asignado y montos</span>
                </div>
            </div>

            <div class="prop-grid">
                <div class="prop-form-group prop-grid-full">
                    <label class="prop-label">
                        <span><i class="fas fa-sitemap text-primary"></i> Licitación vinculada <span style="color:#ef4444;">*</span></span>
                    </label>
                    <div class="prop-input-wrap">
                        <i class="fas fa-search prop-input-icon"></i>
                        <select name="licitacion_id" class="prop-control" required>
                            <option value="">-- Seleccionar licitación del sistema --</option>
                            <?php foreach ($data['licitaciones'] ?? [] as $lic): ?>
                            <option value="<?= $lic['id'] ?>" <?= (($item['licitacion_id'] ?? '') == $lic['id']) ? 'selected' : '' ?>>
                                <?= $lic['codigo'] ?> - <?= htmlspecialchars(mb_substr($lic['oportunidad_nombre'] ?? $lic['objeto'] ?? '', 0, 70)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="prop-form-group">
                    <label class="prop-label">
                        <span><i class="fas fa-user-tie text-primary"></i> Responsable de elaboración</span>
                    </label>
                    <div class="prop-input-wrap">
                        <i class="fas fa-user-check prop-input-icon"></i>
                        <select name="responsable_id" class="prop-control">
                            <option value="">-- Seleccionar especialista --</option>
                            <?php foreach ($data['usuarios'] ?? [] as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (($item['responsable_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="prop-form-group">
                    <label class="prop-label">
                        <span><i class="fas fa-coins text-success"></i> Monto de la Oferta (S/ inc. IGV) <span style="color:#ef4444;">*</span></span>
                    </label>
                    <div class="prop-input-wrap">
                        <i class="fas fa-dollar-sign prop-input-icon"></i>
                        <input type="number" step="0.01" name="monto_propuesta" class="prop-control" required value="<?= $item['monto_propuesta'] ?? '' ?>" placeholder="Ej: 1850000.00">
                    </div>
                </div>

                <div class="prop-form-group">
                    <label class="prop-label">
                        <span><i class="fas fa-calendar-alt text-primary"></i> Fecha de envío / presentación</span>
                    </label>
                    <div class="prop-input-wrap">
                        <i class="fas fa-calendar prop-input-icon"></i>
                        <input type="date" name="fecha_envio" class="prop-control" value="<?= $item['fecha_envio'] ?? date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="prop-form-group">
                    <label class="prop-label">
                        <span><i class="fas fa-flag text-primary"></i> Estado de la propuesta</span>
                    </label>
                    <div class="prop-input-wrap">
                        <i class="fas fa-check-circle prop-input-icon"></i>
                        <select name="estado" class="prop-control">
                            <?php foreach (['borrador','en_revision','enviada','aceptada','rechazada'] as $est): ?>
                            <option value="<?= $est ?>" <?= (($item['estado'] ?? 'borrador') === $est) ? 'selected' : '' ?>>
                                <?= getEstadoLabel($est) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: SUSTENTO TÉCNICO Y METODOLÓGICO -->
        <div class="prop-section-card">
            <div class="prop-section-header">
                <div class="prop-sec-icon-box icon-tecnica">
                    <i class="fas fa-drafting-compass"></i>
                </div>
                <div class="prop-sec-title-group">
                    <h4>2. Sustento y Memoria Técnica (Consultoría / Proyectos)</h4>
                    <span>Metodología de trabajo, alcance ofertado y especificaciones técnicas</span>
                </div>
            </div>

            <div class="prop-form-group" style="margin-bottom:14px;">
                <label class="prop-label">
                    <span><i class="fas fa-align-left text-primary"></i> Alcance y Memoria Técnica Ofertada</span>
                </label>
                <textarea name="propuesta_tecnica" class="prop-textarea" rows="3" placeholder="Detalle la metodología técnica, cronograma de trabajo, personal asignado y especificaciones..."><?= htmlspecialchars($item['propuesta_tecnica'] ?? '') ?></textarea>
            </div>

            <div class="prop-form-group">
                <label class="prop-label">
                    <span><i class="fas fa-file-invoice text-primary"></i> Expediente Técnico Adjunto</span>
                </label>

                <?php if (!empty($item['archivo_tecnica'])): ?>
                    <div class="prop-doc-current">
                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($item['archivo_tecnica']) ?>" target="_blank" class="prop-doc-current-link">
                            <i class="fas fa-file-pdf" style="color:#ef4444; font-size:15px;"></i>
                            <span><?= htmlspecialchars(basename($item['archivo_tecnica'])) ?></span>
                        </a>
                        <span style="font-size:11px; color:#64748b;"><i class="fas fa-check-circle text-success"></i> Documento cargado</span>
                    </div>
                <?php endif; ?>

                <div class="prop-dropzone">
                    <div class="prop-dropzone-icon">
                        <i class="fas <?= !empty($item['archivo_tecnica']) ? 'fa-sync-alt' : 'fa-cloud-upload-alt' ?>"></i>
                    </div>
                    <div class="prop-dropzone-text">
                        <span class="prop-dropzone-title"><?= !empty($item['archivo_tecnica']) ? 'Reemplazar expediente técnico adjunto' : 'Cargar expediente técnico (PDF, Word, Zip)' ?></span>
                        <span class="prop-dropzone-desc">Documento formal de la memoria técnica y anexos (Máx. 25MB)</span>
                    </div>
                    <input type="file" name="archivo_tecnica" class="prop-native-file" accept=".pdf,.doc,.docx,.zip" onchange="mostrarArchivoProp(this, 'disp_tec')">
                </div>
                <div class="prop-file-selected-name" id="disp_tec"></div>
            </div>
        </div>

        <!-- SECCIÓN 3: ESTRUCTURA ECONÓMICA Y PRESUPUESTO -->
        <div class="prop-section-card">
            <div class="prop-section-header">
                <div class="prop-sec-icon-box icon-economica">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="prop-sec-title-group">
                    <h4>3. Estructura Económica y Presupuesto Detallado (Licitaciones)</h4>
                    <span>Análisis de precios unitarios (APU), costos directos, margen y utilidades</span>
                </div>
            </div>

            <div class="prop-form-group" style="margin-bottom:14px;">
                <label class="prop-label">
                    <span><i class="fas fa-calculator text-success"></i> Detalle del Presupuesto y Desagregado Económico</span>
                </label>
                <textarea name="propuesta_economica" class="prop-textarea" rows="3" placeholder="Desglose de partidas, costos directos, gastos generales, utilidad y estructura tributaria..."><?= htmlspecialchars($item['propuesta_economica'] ?? '') ?></textarea>
            </div>

            <div class="prop-form-group">
                <label class="prop-label">
                    <span><i class="fas fa-file-excel text-success"></i> Plantilla Presupuestal / APU Adjunta</span>
                </label>

                <?php if (!empty($item['archivo_economica'])): ?>
                    <div class="prop-doc-current">
                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($item['archivo_economica']) ?>" target="_blank" class="prop-doc-current-link">
                            <i class="fas fa-file-excel" style="color:#059669; font-size:15px;"></i>
                            <span><?= htmlspecialchars(basename($item['archivo_economica'])) ?></span>
                        </a>
                        <span style="font-size:11px; color:#64748b;"><i class="fas fa-check-circle text-success"></i> Presupuesto cargado</span>
                    </div>
                <?php endif; ?>

                <div class="prop-dropzone">
                    <div class="prop-dropzone-icon" style="color:#059669;">
                        <i class="fas <?= !empty($item['archivo_economica']) ? 'fa-sync-alt' : 'fa-file-excel' ?>"></i>
                    </div>
                    <div class="prop-dropzone-text">
                        <span class="prop-dropzone-title"><?= !empty($item['archivo_economica']) ? 'Reemplazar presupuesto detallado (Excel / PDF)' : 'Cargar presupuesto detallado o APU (Excel, PDF)' ?></span>
                        <span class="prop-dropzone-desc">Hoja de cálculo con precios unitarios y desglose (Máx. 25MB)</span>
                    </div>
                    <input type="file" name="archivo_economica" class="prop-native-file" accept=".xls,.xlsx,.pdf,.zip" onchange="mostrarArchivoProp(this, 'disp_eco')">
                </div>
                <div class="prop-file-selected-name" id="disp_eco"></div>
            </div>
        </div>

        <!-- SECCIÓN 4: OBSERVACIONES Y NOTAS INTERNAS -->
        <div class="prop-section-card">
            <div class="prop-section-header">
                <div class="prop-sec-icon-box icon-notas">
                    <i class="fas fa-comment-alt"></i>
                </div>
                <div class="prop-sec-title-group">
                    <h4>4. Observaciones y Notas Internas</h4>
                    <span>Anotaciones comerciales, condiciones de entrega o consideraciones clave</span>
                </div>
            </div>

            <div class="prop-form-group">
                <textarea name="observaciones" class="prop-textarea" rows="2" placeholder="Notas internas para el equipo de licitaciones y proyectos..."><?= htmlspecialchars($item['observaciones'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- ACCIONES -->
        <div class="prop-actions-bar">
            <?php if ($isModal): ?>
                <button type="button" onclick="if(window.parent&&window.parent.cerrarGlobalModal){window.parent.cerrarGlobalModal();}else{window.history.back();}" class="btn-prop btn-prop-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/propuestas" class="btn-prop btn-prop-secondary">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
            <?php endif; ?>

            <button type="submit" class="btn-prop btn-prop-primary">
                <i class="fas fa-save"></i> <?= $item ? 'Guardar Cambios' : 'Registrar Propuesta' ?>
            </button>
        </div>
    </form>
</div>

<script>
function mostrarArchivoProp(input, dispId) {
    if (!input || !input.files || input.files.length === 0) return;
    const file = input.files[0];
    const disp = document.getElementById(dispId);
    if (disp) {
        disp.innerHTML = '<i class="fas fa-check-circle"></i> Archivo seleccionado: <strong>' + file.name + '</strong> (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        disp.style.display = 'inline-flex';
    }
}

document.addEventListener('DOMContentLoaded', function() {
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
