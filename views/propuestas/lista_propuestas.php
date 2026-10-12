<!-- =====================================================
     PROPUESTAS TÉCNICAS Y ECONÓMICAS
     ===================================================== -->
<?php
$totalPropuestas = count($data['propuestas'] ?? []);
$aceptadas = 0;
$enRevision = 0;
$montoTotal = 0;

foreach ($data['propuestas'] ?? [] as $pr) {
    $montoTotal += floatval($pr['monto_propuesta'] ?? 0);
    $st = strtolower($pr['estado'] ?? '');
    if ($st === 'aceptada') {
        $aceptadas++;
    } elseif (in_array($st, ['en_revision', 'enviada', 'borrador'])) {
        $enRevision++;
    }
}
?>

<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h2>Propuestas Técnicas y Económicas</h2>
        <p>Control de versiones, ofertas económicas, expedientes técnicos y generación de propuestas formales.</p>
    </div>
    <div style="display:flex; align-items:center; gap: 12px;">
        <div class="search-bar" style="margin:0;">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar propuesta, código o proceso..." id="search-propuestas"
                   value="<?= htmlspecialchars($data['filtros']['busqueda'] ?? '') ?>"
                   onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=propuestas&busqueda='+encodeURIComponent(this.value)">
        </div>
        <button type="button" class="btn btn-primary" onclick="abrirGlobalModal('Nueva Propuesta', '<?= BASE_URL ?>/propuestas/crear?modal_iframe=1', 'fas fa-paper-plane', 'Elaboración de oferta técnica y económica')">
            <i class="fas fa-plus"></i> Nueva propuesta
        </button>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="dashboard-kpis" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 22px;">
    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; color: #2563eb; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-layer-group"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Propuestas</span>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;"><?= $totalPropuestas ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #ecfdf5; color: #059669; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-trophy"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Aceptadas / Ganadoras</span>
            <div style="font-size: 22px; font-weight: 800; color: #059669; line-height: 1.2;"><?= $aceptadas ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #d97706; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-edit"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">En Elaboración / Revisión</span>
            <div style="font-size: 22px; font-weight: 800; color: #d97706; line-height: 1.2;"><?= $enRevision ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #f5f3ff; color: #7c3aed; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-coins"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Monto Total Ofertado</span>
            <div style="font-size: 18px; font-weight: 800; color: #7c3aed; line-height: 1.2;"><?= formatMoney($montoTotal) ?></div>
        </div>
    </div>
</div>

<div class="data-card data-section full-width" style="border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0;">
    <table class="data-table" id="tabla-propuestas">
        <thead>
            <tr>
                <th>Código</th>
                <th>Objeto / Proceso Convocado</th>
                <th>Versión</th>
                <th>Monto Ofertado</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['propuestas'])): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-paper-plane"></i><h3>No hay propuestas</h3><p>Crea una propuesta técnica y económica vinculada a una licitación.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['propuestas'] as $i => $prop): ?>
            <tr>
                <td>
                    <div style="display:flex; flex-direction:column;">
                        <span class="cell-main" style="font-weight:700; color:#1e293b;"><?= $prop['codigo'] ?></span>
                        <span style="font-size:11px; color:#6366f1; font-weight:600;"><?= htmlspecialchars($prop['licitacion_codigo'] ?? '') ?></span>
                    </div>
                </td>
                <td>
                    <span class="cell-main" style="font-weight:600; color:#334155;"><?= htmlspecialchars(mb_substr($prop['oportunidad_nombre'] ?? '-', 0, 50)) ?></span>
                </td>
                <td>
                    <span style="background: #f1f5f9; color: #475569; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">v<?= $i + 1 ?></span>
                </td>
                <td class="cell-money" style="font-weight:700; color:#059669;"><?= formatMoney($prop['monto_propuesta']) ?></td>
                <td><span class="badge <?= getEstadoClass($prop['estado']) ?>"><?= getEstadoLabel($prop['estado']) ?></span></td>
                <td style="text-align: right;">
                    <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                        <!-- Ver Documento / Ficha Oficial -->
                        <button type="button" onclick="abrirGlobalModal('Documento Oficial de Propuesta <?= $prop['codigo'] ?>', '<?= BASE_URL ?>/propuestas/ficha/<?= $prop['id'] ?>?modal_iframe=1', 'fas fa-file-invoice-dollar', 'Documento formal con membrete y firmas', 'wide')" class="section-action" style="font-size:11.5px; background:#eff6ff; color:#1d4ed8; padding:5px 10px; border-radius:6px; border:1px solid #bfdbfe; cursor:pointer;" title="Ver Documento Oficial de Propuesta">
                            <i class="fas fa-file-alt"></i> Ficha Oficial
                        </button>

                        <!-- Editar / Configurar -->
                        <button type="button" onclick="abrirGlobalModal('Editar Propuesta <?= $prop['codigo'] ?>', '<?= BASE_URL ?>/propuestas/editar/<?= $prop['id'] ?>?modal_iframe=1', 'fas fa-edit', 'Modificación de propuesta técnica y económica')" class="section-action" style="font-size:11.5px; background:none; border:none; cursor:pointer;" title="Editar propuesta">
                            Editar <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
