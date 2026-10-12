<!-- =====================================================
     CONTRATOS
     ===================================================== -->
<?php
$totalContratos = count($data['contratos'] ?? []);
$vigentes = 0;
$enFormalizacion = 0;
$montoTotalContratado = 0;

foreach ($data['contratos'] ?? [] as $cItem) {
    $montoTotalContratado += floatval($cItem['monto_contrato'] ?? 0);
    $st = strtolower($cItem['estado'] ?? '');
    if (in_array($st, ['vigente', 'firmado', 'en_ejecucion'])) {
        $vigentes++;
    } elseif ($st === 'en_elaboracion') {
        $enFormalizacion++;
    }
}
?>

<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h2>Contratos</h2>
        <p>Obligaciones contractuales, garantías, plazos y transferencia al proyecto.</p>
    </div>
    <div style="display:flex; align-items:center; gap: 12px;">
        <div class="search-bar" style="margin:0;">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar contrato, cliente u objeto..." id="search-contratos"
                   value="<?= htmlspecialchars($data['filtros']['busqueda'] ?? '') ?>"
                   onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=contratos&busqueda='+encodeURIComponent(this.value)">
        </div>
        <button type="button" class="btn btn-primary" onclick="abrirGlobalModal('Nuevo Contrato', '<?= BASE_URL ?>/contratos/crear?modal_iframe=1', 'fas fa-file-contract', 'Registro y formalización contractual')">
            <i class="fas fa-plus"></i> Nuevo contrato
        </button>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="dashboard-kpis" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 22px;">
    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; color: #2563eb; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-file-signature"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Contratos</span>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;"><?= $totalContratos ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #ecfdf5; color: #059669; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-check-double"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Vigentes / Firmados</span>
            <div style="font-size: 22px; font-weight: 800; color: #059669; line-height: 1.2;"><?= $vigentes ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #d97706; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">En Formalización</span>
            <div style="font-size: 22px; font-weight: 800; color: #d97706; line-height: 1.2;"><?= $enFormalizacion ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #f5f3ff; color: #7c3aed; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-coins"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Monto Total Contratado</span>
            <div style="font-size: 18px; font-weight: 800; color: #7c3aed; line-height: 1.2;"><?= formatMoney($montoTotalContratado) ?></div>
        </div>
    </div>
</div>

<div class="data-card data-section full-width" style="border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0;">
    <table class="data-table" id="tabla-contratos">
        <thead>
            <tr>
                <th>Contrato</th>
                <th>Cliente / Entidad</th>
                <th>Objeto</th>
                <th>Monto</th>
                <th>Vencimiento</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['contratos'])): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-contract"></i><h3>No hay contratos</h3><p>Los contratos se generan automáticamente a partir de oportunidades adjudicadas o registrando uno nuevo.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['contratos'] as $ct): ?>
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap: 8px;">
                        <span class="cell-main" style="font-weight: 700; color: #1e293b;"><?= $ct['codigo'] ?></span>
                        <?php if (!empty($ct['oportunidad_id'])): ?>
                            <span title="Vinculado a oportunidad" style="background:#e0e7ff; color:#4338ca; font-size:10px; font-weight:700; padding:2px 6px; border-radius:6px;">OPP-<?= $ct['oportunidad_id'] ?></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <span style="font-weight: 600; color: #334155;"><?= htmlspecialchars($ct['cliente_nombre'] ?? '-') ?></span>
                </td>
                <td>
                    <span style="color: #475569; font-size: 12.5px;"><?= htmlspecialchars(mb_substr($ct['objeto'] ?? '-', 0, 48)) ?><?= mb_strlen($ct['objeto'] ?? '') > 48 ? '...' : '' ?></span>
                </td>
                <td class="cell-money" style="font-weight: 700; color: #059669;"><?= formatMoney($ct['monto_contrato']) ?></td>
                <td>
                    <span style="font-size: 12.5px; color: #64748b;">
                        <i class="far fa-calendar-alt" style="margin-right: 4px; color: #94a3b8;"></i><?= formatDate($ct['fecha_fin']) ?>
                    </span>
                </td>
                <td>
                    <?php
                    $ctEstado = trim($ct['estado'] ?? '');
                    if (empty($ctEstado) || $ctEstado === 'en_elaboracion') {
                        $badgeClass = 'badge-preparation';
                        $ctLabel = 'En formalización';
                    } elseif (in_array($ctEstado, ['firmado', 'vigente', 'en_ejecucion'])) {
                        $badgeClass = 'badge-awarded';
                        $ctLabel = 'Vigente';
                    } elseif ($ctEstado === 'finalizado') {
                        $badgeClass = 'badge-closed';
                        $ctLabel = 'Finalizado';
                    } elseif ($ctEstado === 'resuelto') {
                        $badgeClass = 'badge-discarded';
                        $ctLabel = 'Resuelto';
                    } else {
                        $badgeClass = 'badge-review';
                        $ctLabel = ucfirst($ctEstado);
                    }
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= $ctLabel ?></span>
                </td>
                <td style="text-align: right;">
                    <div class="table-actions" style="justify-content: flex-end;">
                        <button type="button" onclick="abrirGlobalModal('Detalle del Contrato <?= $ct['codigo'] ?>', '<?= BASE_URL ?>/contratos/editar/<?= $ct['id'] ?>?modal_iframe=1', 'fas fa-file-contract', 'Edición y seguimiento de contrato')" class="section-action" style="font-size:12px; background:none; border:none; cursor:pointer;">
                            Ver <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
