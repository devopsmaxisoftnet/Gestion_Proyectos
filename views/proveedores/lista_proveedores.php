<!-- =====================================================
     PROVEEDORES
     ===================================================== -->

<div class="page-header">
    <div>
        <h2>Proveedores</h2>
        <p>Empresas que respaldan cotizaciones, compras y ejecución.</p>
    </div>
    <div class="d-flex gap-2 items-center">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar proveedor..." id="search-proveedores"
                   value="<?= htmlspecialchars($data['filtros']['busqueda']) ?>"
                   onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=proveedores&busqueda='+this.value">
        </div>
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-proveedores">
        <thead>
            <tr>
                <th>RUC</th>
                <th>Razón social</th>
                <th>Especialidad</th>
                <th>Contacto</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['proveedores'])): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-truck"></i><h3>No hay proveedores</h3><p>Registra un nuevo proveedor.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['proveedores'] as $prov): ?>
            <tr>
                <td><span class="cell-main"><?= htmlspecialchars($prov['ruc'] ?? '-') ?></span></td>
                <td><span class="cell-main"><?= htmlspecialchars($prov['razon_social']) ?></span></td>
                <td><?= htmlspecialchars($prov['especialidad'] ?? '-') ?></td>
                <td><?= htmlspecialchars($prov['contacto_nombre'] ?? '-') ?></td>
                <td>
                    <span class="badge <?= $prov['activo'] ? 'badge-participate' : 'badge-closed' ?>">
                        <?= $prov['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/proveedores/editar/<?= $prov['id'] ?>" class="section-action" style="font-size:12px">
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



