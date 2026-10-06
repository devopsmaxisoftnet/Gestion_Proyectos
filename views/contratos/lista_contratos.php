<!-- =====================================================
     CONTRATOS
     ===================================================== -->

<div class="page-header">
    <div>
        <h2>Contratos</h2>
        <p>Obligaciones, garantías y transferencia al proyecto.</p>
    </div>
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar contrato..." id="search-contratos"
               value="<?= htmlspecialchars($data['filtros']['busqueda']) ?>"
               onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=contratos&busqueda='+this.value">
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-contratos">
        <thead>
            <tr>
                <th>Contrato</th>
                <th>Cliente</th>
                <th>Monto</th>
                <th>Vencimiento</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['contratos'])): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-file-contract"></i><h3>No hay contratos</h3><p>Los contratos se generan a partir de oportunidades adjudicadas.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['contratos'] as $ct): ?>
            <tr>
                <td><span class="cell-main"><?= $ct['codigo'] ?></span></td>
                <td><?= htmlspecialchars($ct['cliente_nombre'] ?? '-') ?></td>
                <td class="cell-money"><?= formatMoney($ct['monto_contrato']) ?></td>
                <td><?= formatDate($ct['fecha_fin']) ?></td>
                <td>
                    <?php
                    $ctEstado = $ct['estado'];
                    $ctLabel = getEstadoLabel($ctEstado);
                    // Map to display labels matching the screenshot
                    if ($ctEstado === 'en_ejecucion' || $ctEstado === 'firmado') $ctLabel = 'Vigente';
                    if ($ctEstado === 'en_elaboracion') $ctLabel = 'En formalización';
                    ?>
                    <span class="badge <?= getEstadoClass($ct['estado']) ?>"><?= $ctLabel ?></span>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/contratos/editar/<?= $ct['id'] ?>" class="section-action" style="font-size:12px">
                            Ver <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Info notice -->
    <div style="padding: 20px 24px; border-top: 1px solid var(--gray-100);">
        <div style="display:flex;align-items:center;gap:10px;padding:16px 20px;background:var(--gray-50);border-radius:var(--radius-sm);border:1px solid var(--gray-100);">
            <i class="fas fa-info-circle" style="color:var(--gray-400)"></i>
            <span style="font-size:13px;color:var(--gray-500)">La edición completa de este módulo se incorporará en la siguiente iteración.</span>
        </div>
    </div>
</div>



