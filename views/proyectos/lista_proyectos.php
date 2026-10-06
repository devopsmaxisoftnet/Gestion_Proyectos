<!-- =====================================================
     PROYECTOS
     ===================================================== -->

<div class="page-header">
    <div>
        <h2>Proyectos</h2>
        <p>Supervisión, administración y ejecución de contratos adjudicados.</p>
    </div>
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar proyecto..." id="search-proyectos"
               value="<?= htmlspecialchars($data['filtros']['busqueda']) ?>"
               onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=proyectos&busqueda='+this.value">
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-proyectos">
        <thead>
            <tr>
                <th>Proyecto</th>
                <th>Contrato</th>
                <th>Presupuesto</th>
                <th>Avance</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['proyectos'])): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-project-diagram"></i><h3>No hay proyectos</h3><p>Los proyectos se crean a partir de contratos firmados.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['proyectos'] as $proy): ?>
            <tr>
                <td>
                    <span class="cell-main"><?= htmlspecialchars(mb_substr($proy['nombre'], 0, 45)) ?></span>
                    <span class="cell-sub"><?= $proy['codigo'] ?></span>
                </td>
                <td><span class="cell-main"><?= $proy['contrato_codigo'] ?? '-' ?></span></td>
                <td class="cell-money"><?= formatMoney($proy['monto_presupuesto']) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="progress-bar" style="width:80px">
                            <div class="progress-bar-fill" style="width:<?= $proy['avance_porcentaje'] ?>%"></div>
                        </div>
                        <span style="font-size:12px;font-weight:600;color:var(--gray-600)"><?= $proy['avance_porcentaje'] ?>%</span>
                    </div>
                </td>
                <td><span class="badge <?= getEstadoClass($proy['estado']) ?>"><?= getEstadoLabel($proy['estado']) ?></span></td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/proyectos/editar/<?= $proy['id'] ?>" class="btn-icon" title="Editar"><i class="fas fa-pen"></i></a>
                        <a href="<?= BASE_URL ?>/proyectos/eliminar/<?= $proy['id'] ?>" class="btn-icon" title="Eliminar" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div style="padding: 20px 24px; border-top: 1px solid var(--gray-100);">
        <div style="display:flex;align-items:center;gap:10px;padding:16px 20px;background:var(--gray-50);border-radius:var(--radius-sm);border:1px solid var(--gray-100);">
            <i class="fas fa-info-circle" style="color:var(--gray-400)"></i>
            <span style="font-size:13px;color:var(--gray-500)">Seguimiento con Gantt y preparación manual de valorizaciones se incorporará en la siguiente iteración.</span>
        </div>
    </div>
</div>



