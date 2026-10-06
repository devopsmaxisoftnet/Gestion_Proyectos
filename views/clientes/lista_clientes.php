<!-- =====================================================
     CLIENTES
     ===================================================== -->

<div class="page-header">
    <div>
        <h2>Clientes</h2>
        <p>Entidades y empresas relacionadas con oportunidades y contratos.</p>
    </div>
    <div class="d-flex gap-2 items-center">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar cliente..." id="search-clientes"
                   value="<?= htmlspecialchars($data['filtros']['busqueda']) ?>"
                   onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=clientes&busqueda='+this.value">
        </div>
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-clientes">
        <thead>
            <tr>
                <th>RUC</th>
                <th>Razón social</th>
                <th>Clasificación</th>
                <th>Departamento</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['clientes'])): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-building"></i><h3>No hay clientes</h3><p>Registra un nuevo cliente o entidad.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['clientes'] as $cl): ?>
            <tr>
                <td><span class="cell-main"><?= htmlspecialchars($cl['ruc'] ?? '-') ?></span></td>
                <td><span class="cell-main"><?= htmlspecialchars($cl['razon_social']) ?></span></td>
                <td>
                    <?php if ($cl['tipo'] === 'publico'): ?>
                        <span>Entidad pública</span>
                    <?php else: ?>
                        <span>Empresa privada</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($cl['departamento'] ?? '-') ?></td>
                <td>
                    <span class="badge <?= $cl['activo'] ? 'badge-participate' : 'badge-closed' ?>">
                        <?= $cl['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/clientes/editar/<?= $cl['id'] ?>" class="section-action" style="font-size:12px">
                            Ver <i class="fas fa-chevron-right"></i>
                        </a>
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
            <span style="font-size:13px;color:var(--gray-500)">La edición completa de este módulo se incorporará en la siguiente iteración.</span>
        </div>
    </div>
</div>



