<div class="page-header">
    <div>
        <h2>Profesionales</h2>
        <p>Gestión del staff, especialistas y técnicos para proyectos y licitaciones.</p>
    </div>
    <div class="header-actions">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar profesional..." id="search-prof"
                   value="<?= htmlspecialchars($data['filtros']['busqueda'] ?? '') ?>"
                   onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=profesionales&busqueda='+this.value">
        </div>
        <a href="<?= BASE_URL ?>/profesionales/crear" data-side-modal title="Nuevo Profesional" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Añadir profesional
        </a>
    </div>
</div>

<div class="data-card data-section full-width">
    <table class="data-table" id="tabla-profesionales">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Especialidad</th>
                <th>Contacto</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['profesionales'])): ?>
            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-users-slash"></i><h3>No hay profesionales registrados</h3></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['profesionales'] as $prof): ?>
            <tr>
                <td><span class="cell-main" style="font-weight:600; color:var(--slate-800)"><?= htmlspecialchars($prof['nombre']) ?></span></td>
                <td><span class="badge" style="background:var(--slate-100); color:var(--slate-700); font-weight:500;"><?= htmlspecialchars($prof['tipo_profesional'] ?? '-') ?></span></td>
                <td><span class="cell-sub"><?= htmlspecialchars($prof['especialidad'] ?? '-') ?></span></td>
                <td>
                    <?php if(!empty($prof['telefono'])): ?>
                    <div style="font-size:12px; color:var(--slate-600);"><i class="fas fa-phone-alt" style="margin-right:4px;color:var(--slate-400)"></i><?= htmlspecialchars($prof['telefono']) ?></div>
                    <?php endif; ?>
                    <?php if(!empty($prof['email'])): ?>
                    <div style="font-size:12px; color:var(--slate-600);"><i class="fas fa-envelope" style="margin-right:4px;color:var(--slate-400)"></i><?= htmlspecialchars($prof['email']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/profesionales/editar/<?= $prof['id'] ?>" data-side-modal title="Editar Profesional" class="btn-icon"><i class="fas fa-pen"></i></a>
                        <a href="<?= BASE_URL ?>/profesionales/eliminar/<?= $prof['id'] ?>" class="btn-icon" style="color:var(--rose-500)" onclick="return confirm('¿Eliminar este profesional?');"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
