<!-- =====================================================
     EMPRESAS
     ===================================================== -->

<div class="page-header">
    <div>
        <h2>Empresas</h2>
        <p>Empresas propias y externas que participan en las operaciones.</p>
    </div>
    <div class="d-flex gap-2 items-center">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar empresa..." id="search-empresas"
                   value="<?= htmlspecialchars($data['filtros']['busqueda'] ?? '') ?>"
                   onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=empresas&busqueda='+this.value">
        </div>
        <a href="javascript:void(0)" onclick="abrirGlobalModal('Nueva empresa', '<?= BASE_URL ?>/?page=empresas&action=crear&modal_iframe=1', 'fas fa-city', 'Registro de empresas propias y externas')" class="btn btn-primary" id="btn-crear">
            <i class="fas fa-plus"></i> Nueva empresa
        </a>
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-empresas">
        <thead>
            <tr>
                <th>RUC</th>
                <th>Razón social</th>
                <th>Tipo</th>
                <th>Contacto Administrativo</th>
                <th>Especialidad</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['empresas'])): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-city"></i><h3>No hay empresas</h3><p>Registra una nueva empresa.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['empresas'] as $emp): ?>
            <tr>
                <td><span class="cell-main"><?= htmlspecialchars($emp['ruc'] ?? '-') ?></span></td>
                <td><span class="cell-main"><?= htmlspecialchars($emp['razon_social']) ?></span></td>
                <td>
                    <span class="badge <?= $emp['tipo_empresa'] === 'propia' ? 'badge-detected' : 'badge-evaluation' ?>">
                        <?= ucfirst(htmlspecialchars($emp['tipo_empresa'])) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($emp['contacto_administrativo'] ?? '-') ?></td>
                <td><?= htmlspecialchars($emp['especialidad'] ?? '-') ?></td>
                <td>
                    <div class="table-actions">
                        <a href="javascript:void(0)" onclick="abrirGlobalModal('Editar empresa', '<?= BASE_URL ?>/?page=empresas&action=editar&id=<?= $emp['id'] ?>&modal_iframe=1', 'fas fa-city', 'Gestión de la empresa y sus profesionales')" class="section-action" style="font-size:12px">
                            Ver <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
