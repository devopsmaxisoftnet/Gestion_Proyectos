<!-- =====================================================
     PROPUESTAS
     ===================================================== -->

<div class="page-header">
    <div>
        <h2>Propuestas</h2>
        <p>Control de versiones técnicas y económicas.</p>
    </div>
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar propuesta..." id="search-propuestas"
               value="<?= htmlspecialchars($data['filtros']['busqueda']) ?>"
               onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=propuestas&busqueda='+this.value">
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-propuestas">
        <thead>
            <tr>
                <th>Código</th>
                <th>Objeto</th>
                <th>Versión</th>
                <th>Monto</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['propuestas'])): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-paper-plane"></i><h3>No hay propuestas</h3><p>Crea una propuesta vinculada a una licitación.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['propuestas'] as $i => $prop): ?>
            <tr>
                <td><span class="cell-main"><?= $prop['codigo'] ?></span></td>
                <td>
                    <span class="cell-main"><?= htmlspecialchars(mb_substr($prop['oportunidad_nombre'] ?? '-', 0, 50)) ?></span>
                </td>
                <td>v<?= $i + 1 ?></td>
                <td class="cell-money"><?= formatMoney($prop['monto_propuesta']) ?></td>
                <td><span class="badge <?= getEstadoClass($prop['estado']) ?>"><?= getEstadoLabel($prop['estado']) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/propuestas/editar/<?= $prop['id'] ?>" data-side-modal title="Ver Propuesta" class="section-action" style="font-size:12px">
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



