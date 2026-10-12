<!-- Ficha y Documento Oficial de Propuesta Técnica & Económica -->
<?php
$p = $data['propuesta'] ?? [];
$empresa = $data['empresa'] ?? [];
$isModal = !empty($_GET['modal_iframe']);
?>

<!-- Biblioteca para descarga directa de PDF de alta calidad -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
@media print {
    .ficha-top-bar, .app-layout, .sidebar, .top-header { display: none !important; }
    body, html { background: #ffffff !important; margin: 0 !important; padding: 0 !important; }
    .ficha-propuesta-wrapper { padding: 0 !important; max-width: 100% !important; margin: 0 !important; }
    .documento-propuesta-sheet { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
.btn-pdf-download:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35) !important;
}
</style>

<div class="ficha-propuesta-wrapper" style="padding: 20px; background: #ffffff; max-width: 840px; margin: 0 auto; font-family: 'Inter', -apple-system, sans-serif; color: #1e293b;">

    <!-- Barra de acciones y control con botón PDF mejorado y descarga directa -->
    <div class="ficha-top-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom: 22px; padding: 12px 18px; background: #f8fafc; border-radius: 12px; border: 1.5px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
        <div style="display:flex; align-items:center; gap: 10px;">
            <span class="badge <?= getEstadoClass($p['estado'] ?? 'borrador') ?>" style="font-size: 12px; padding: 6px 14px; font-weight:700; border-radius:20px;">
                <?= getEstadoLabel($p['estado'] ?? 'borrador') ?>
            </span>
            <span style="font-size: 14.5px; font-weight: 800; color: #0f172a; letter-spacing: -0.2px;"><?= htmlspecialchars($p['codigo'] ?? '') ?></span>
            <span style="font-size: 11.5px; color: #64748b; font-weight: 500;">&bull; Expediente Oficial</span>
        </div>
        
        <div style="display:flex; gap: 10px; align-items: center; flex-wrap:wrap;">
            <!-- BOTÓN DESTACADO PARA DESCARGAR PDF DIRECTO -->
            <button type="button" id="btnDescargarPdf" onclick="descargarFichaPDF()" class="btn-pdf-download" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color:#ffffff; font-weight:700; font-size:12.5px; padding: 8px 18px; border-radius: 9px; border: none; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.28); display:inline-flex; align-items:center; gap: 8px; cursor: pointer; transition: all 0.2s;">
                <i class="fas fa-file-pdf" style="font-size: 15px;"></i> Descargar PDF Oficial
            </button>

            <!-- Botón Imprimir -->
            <button type="button" onclick="window.print()" class="btn btn-secondary" style="font-size: 12px; padding: 8px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color:#334155; font-weight: 600; display:inline-flex; align-items:center; gap: 6px; cursor: pointer;">
                <i class="fas fa-print"></i> Imprimir
            </button>

            <?php if (!$isModal): ?>
                <a href="<?= BASE_URL ?>/propuestas" class="btn btn-secondary" style="font-size: 12px; padding: 8px 14px; border-radius: 8px; text-decoration: none; border: 1px solid #cbd5e1;">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- DOCUMENTO OFICIAL A EXPORTAR A PDF -->
    <div id="documentoPropuestaSheet" class="documento-propuesta-sheet" style="background:#ffffff; padding: 24px 28px; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);">

        <!-- Membrete Ejecutivo / Encabezado de la Empresa -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 24px; padding: 18px 22px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
            <div>
                <h3 style="margin: 0 0 5px 0; font-size: 18px; font-weight: 800; color: #0f172a; letter-spacing: -0.3px;">
                    <?= htmlspecialchars($empresa['razon_social'] ?? 'CONSORCIO & INGENIERÍA CONSTRUCTORA') ?>
                </h3>
                <div style="font-size: 11.5px; color: #64748b; line-height: 1.5;">
                    <strong>RUC:</strong> <?= htmlspecialchars($empresa['ruc'] ?? '20601234567') ?> &bull; 
                    <strong>Dirección:</strong> <?= htmlspecialchars($empresa['direccion'] ?? 'Av. Canaval y Moreyra 480, San Isidro') ?><br>
                    <strong>Teléfono:</strong> <?= htmlspecialchars($empresa['telefono'] ?? '(01) 421-9988') ?> &bull; 
                    <strong>Email:</strong> <?= htmlspecialchars($empresa['email'] ?? 'licitaciones@consorcio.pe') ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 10.5px; text-transform: uppercase; font-weight: 700; color: #6366f1; letter-spacing: 0.5px;">Expediente de Licitación</div>
                <div style="font-size: 16px; font-weight: 800; color: #1e1b4b;"><?= htmlspecialchars($p['licitacion_codigo'] ?? 'LIC-2026') ?></div>
                <div style="font-size: 11.5px; color: #64748b;">Fecha: <?= formatDate($p['fecha_envio'] ?? date('Y-m-d')) ?></div>
            </div>
        </div>

        <!-- Título Principal del Documento -->
        <div style="text-align: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 17px; font-weight: 800; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;">
                PROPUESTA TÉCNICA Y ECONÓMICA OFICIAL
            </h2>
            <div style="font-size: 13px; color: #475569; margin-top: 5px;">
                Objeto: <strong><?= htmlspecialchars($p['oportunidad_nombre'] ?? $p['licitacion_objeto'] ?? '-') ?></strong>
            </div>
        </div>

        <!-- Metadatos de la Entidad y Licitación -->
        <div style="margin-bottom: 22px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
            <div style="background: #f1f5f9; padding: 10px 16px; font-size: 11.5px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.3px;">
                <i class="fas fa-landmark" style="margin-right: 6px; color:#6366f1;"></i> 1. Información del Convocante y Proceso
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; padding: 14px 18px; font-size: 12.5px;">
                <div>
                    <span style="color: #64748b; font-size: 11px; font-weight: 600; display:block;">ENTIDAD CONTRATANTE / CLIENTE:</span>
                    <strong style="color: #0f172a;"><?= htmlspecialchars($p['entidad'] ?? 'Entidad Pública / Privada') ?></strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; font-weight: 600; display:block;">CÓDIGO DE PROCESO / LICITACIÓN:</span>
                    <strong style="color: #0f172a;"><?= htmlspecialchars($p['licitacion_codigo'] ?? '-') ?></strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; font-weight: 600; display:block;">ESPECIALISTA RESPONSABLE:</span>
                    <strong style="color: #0f172a;"><?= htmlspecialchars(trim(($p['resp_nombre'] ?? '') . ' ' . ($p['resp_apellido'] ?? '')) ?: 'Equipo de Licitaciones') ?></strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; font-weight: 600; display:block;">ESTADO DEL EXPEDIENTE:</span>
                    <strong style="color: #0f172a;"><?= getEstadoLabel($p['estado'] ?? 'borrador') ?></strong>
                </div>
            </div>
        </div>

        <!-- Sección 2: Propuesta Técnica -->
        <div style="margin-bottom: 22px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
            <div style="background: #f1f5f9; padding: 10px 16px; font-size: 11.5px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.3px; display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fas fa-drafting-compass" style="margin-right: 6px; color:#2563eb;"></i> 2. Sustento y Alcance de la Propuesta Técnica</span>
                <?php if (!empty($p['archivo_tecnica'])): ?>
                    <a href="<?= BASE_URL ?>/<?= htmlspecialchars($p['archivo_tecnica']) ?>" target="_blank" style="font-size: 11px; font-weight: 600; color: #2563eb; text-decoration: none;">
                        <i class="fas fa-file-pdf" style="color:#ef4444;"></i> Descargar Anexo Técnico
                    </a>
                <?php endif; ?>
            </div>
            <div style="padding: 16px 18px; font-size: 12.5px; line-height: 1.6; color: #334155;">
                <?php if (!empty($p['propuesta_tecnica'])): ?>
                    <?= nl2br(htmlspecialchars($p['propuesta_tecnica'])) ?>
                <?php else: ?>
                    <p style="color: #94a3b8; font-style: italic; margin: 0;">Memoria técnica descriptiva, metodología de ejecución, plan de trabajo y matriz de cumplimiento de especificaciones técnicas aprobada por la gerencia de ingeniería.</p>
                <?php endif; ?>

                <?php if (!empty($p['archivo_tecnica'])): ?>
                    <div style="margin-top: 12px; padding: 10px 14px; background: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe; display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size: 12px; color: #1e40af; font-weight: 600;">
                            <i class="fas fa-paperclip"></i> Archivo Técnico Adjunto: <?= basename($p['archivo_tecnica']) ?>
                        </span>
                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($p['archivo_tecnica']) ?>" target="_blank" class="btn btn-primary" style="font-size: 11.5px; padding: 4px 10px;">
                            Ver Documento
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sección 3: Propuesta Económica y Oferta -->
        <div style="margin-bottom: 22px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
            <div style="background: #f1f5f9; padding: 10px 16px; font-size: 11.5px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.3px; display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fas fa-file-invoice-dollar" style="margin-right: 6px; color:#059669;"></i> 3. Propuesta Económica y Presupuesto Ofertado</span>
                <?php if (!empty($p['archivo_economica'])): ?>
                    <a href="<?= BASE_URL ?>/<?= htmlspecialchars($p['archivo_economica']) ?>" target="_blank" style="font-size: 11px; font-weight: 600; color: #059669; text-decoration: none;">
                        <i class="fas fa-file-excel" style="color:#10b981;"></i> Descargar Anexo Económico
                    </a>
                <?php endif; ?>
            </div>
            <div style="padding: 16px 18px;">
                <div style="display:flex; justify-content:space-between; align-items:center; background: #f0fdf4; border: 1.5px solid #a7f3d0; border-radius: 10px; padding: 16px 20px; margin-bottom: 14px;">
                    <div>
                        <span style="font-size: 11.5px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.4px;">Monto Total de la Propuesta (Inc. IGV)</span>
                        <div style="font-size: 11px; color: #065f46;">Moneda: Soles (PEN) - A todo costo según bases de contratación</div>
                    </div>
                    <div style="font-size: 26px; font-weight: 900; color: #059669; letter-spacing: -0.5px;">
                        <?= formatMoney($p['monto_propuesta']) ?>
                    </div>
                </div>

                <?php if (!empty($p['propuesta_economica'])): ?>
                    <div style="font-size: 12.5px; line-height: 1.6; color: #334155;">
                        <?= nl2br(htmlspecialchars($p['propuesta_economica'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($p['archivo_economica'])): ?>
                    <div style="margin-top: 10px; padding: 10px 14px; background: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0; display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size: 12px; color: #166534; font-weight: 600;">
                            <i class="fas fa-paperclip"></i> Hoja de Presupuesto / APU Adjunta: <?= basename($p['archivo_economica']) ?>
                        </span>
                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($p['archivo_economica']) ?>" target="_blank" class="btn btn-primary" style="font-size: 11.5px; padding: 4px 10px; background:#059669; border-color:#059669;">
                            Ver Documento
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Firmas Oficiales de Representación -->
        <div style="margin-top: 36px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; padding-top: 18px; border-top: 1px dashed #cbd5e1;">
            <div style="text-align: center;">
                <div style="height: 45px;"></div>
                <div style="border-top: 1px solid #475569; width: 80%; margin: 0 auto; padding-top: 6px;">
                    <strong style="display:block; font-size: 12px; color: #0f172a;"><?= htmlspecialchars(trim(($p['resp_nombre'] ?? '') . ' ' . ($p['resp_apellido'] ?? '')) ?: 'Ing. Responsable de Propuesta') ?></strong>
                    <span style="font-size: 11px; color: #64748b;">Responsable Técnico / Comercial</span>
                </div>
            </div>
            <div style="text-align: center;">
                <div style="height: 45px;"></div>
                <div style="border-top: 1px solid #475569; width: 80%; margin: 0 auto; padding-top: 6px;">
                    <strong style="display:block; font-size: 12px; color: #0f172a;">Gerencia General / Representante Legal</strong>
                    <span style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($empresa['razon_social'] ?? 'Consorcio Constructor') ?></span>
                </div>
            </div>
        </div>

    </div><!-- /.documento-propuesta-sheet -->

</div>

<script>
function descargarFichaPDF() {
    const btn = document.getElementById('btnDescargarPdf');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
    btn.disabled = true;

    const element = document.getElementById('documentoPropuestaSheet');
    const opt = {
        margin:       [10, 10, 10, 10],
        filename:     'Propuesta_<?= htmlspecialchars($p['codigo'] ?? 'PROP-2026') ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    if (typeof html2pdf !== 'undefined') {
        html2pdf().set(opt).from(element).save().then(function() {
            btn.innerHTML = '<i class="fas fa-check-circle"></i> ¡PDF Descargado!';
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2500);
        }).catch(function(err) {
            console.error('Error html2pdf:', err);
            window.print();
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    } else {
        window.print();
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>
