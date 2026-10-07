<!-- LICITACIONES -->
<div class="page-header">
    <div>
        <h2>Licitaciones</h2>
        <p>Gestión de ofertas y seguimiento de resultados.</p>
    </div>
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar licitación..." id="search-licitaciones"
               value="<?= htmlspecialchars($data['filtros']['busqueda']) ?>"
               onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=licitaciones&busqueda='+this.value">
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-licitaciones">
        <thead>
            <tr>
                <th>Código</th>
                <th>Oportunidad</th>
                <th>Entidad</th>
                <th>Monto ofertado</th>
                <th>Fecha presentación</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['licitaciones'])): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-gavel"></i><h3>No hay licitaciones</h3></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['licitaciones'] as $lic): ?>
            <tr>
                <td><span class="cell-main"><?= $lic['codigo'] ?></span></td>
                <td>
                    <span class="cell-main"><?= htmlspecialchars(mb_substr($lic['oportunidad_nombre'], 0, 40)) ?></span>
                    <span class="cell-sub"><?= $lic['oportunidad_codigo'] ?></span>
                </td>
                <td><?= htmlspecialchars($lic['entidad'] ?? '-') ?></td>
                <td class="cell-money"><?= formatMoney($lic['monto_ofertado']) ?></td>
                <td><?= formatDate($lic['fecha_presentacion']) ?></td>
                <td><span class="badge <?= getEstadoClass($lic['estado']) ?>"><?= getEstadoLabel($lic['estado']) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/licitaciones/editar/<?= $lic['id'] ?>" data-side-modal title="Editar Licitación" class="btn-icon"><i class="fas fa-pen"></i></a>
                        <a href="<?= BASE_URL ?>/licitaciones/eliminar/<?= $lic['id'] ?>" class="btn-icon" title="Eliminar" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>



