<!-- =====================================================
     USUARIOS Y ACCESOS
     ===================================================== -->

<?php
// Calcular stats de usuarios
$totalUsuarios = count($data['usuarios']);
$activos = 0;
$pendientes = 0;
$suspendidos = 0;
foreach ($data['usuarios'] as $u) {
    if ($u['activo']) $activos++;
    else $suspendidos++;
}
?>

<div class="page-header">
    <div>
        <h2>Usuarios y accesos</h2>
        <p>Asigna responsables por empresa, línea de negocio o proyecto y controla su estado.</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Usuarios</span>
            <div class="stat-card-icon purple"><i class="fas fa-users"></i></div>
        </div>
        <div class="stat-card-value"><?= $totalUsuarios ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Activos</span>
            <div class="stat-card-icon teal"><i class="fas fa-user-check"></i></div>
        </div>
        <div class="stat-card-value"><?= $activos ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Pendientes</span>
            <div class="stat-card-icon amber"><i class="fas fa-user-clock"></i></div>
        </div>
        <div class="stat-card-value"><?= $pendientes ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Suspendidos</span>
            <div class="stat-card-icon rose"><i class="fas fa-user-slash"></i></div>
        </div>
        <div class="stat-card-value"><?= $suspendidos ?></div>
    </div>
</div>

<!-- Directorio de usuarios -->
<div class="data-card data-section full-width">
    <div class="data-card-header">
        <div class="section-header" style="margin-bottom:0">
            <div>
                <h3 class="section-title">Directorio de usuarios</h3>
                <p class="section-subtitle">Los registros marcados como Demo son ejemplos visuales.</p>
            </div>
            <div class="d-flex gap-2 items-center">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar usuario, rol o alcance" id="search-usuarios"
                           value="<?= htmlspecialchars($data['filtros']['busqueda']) ?>"
                           onkeyup="if(event.key==='Enter') window.location='<?= BASE_URL ?>/?page=usuarios&busqueda='+this.value">
                </div>
                <select onchange="window.location='<?= BASE_URL ?>/?page=usuarios&rol_id='+this.value" class="form-control" style="width:auto;padding:8px 12px;font-size:13px">
                    <option value="">Todos</option>
                    <?php foreach ($data['roles'] as $rol): ?>
                    <option value="<?= $rol['id'] ?>" <?= (($data['filtros']['rol_id'] ?? '') == $rol['id']) ? 'selected' : '' ?>><?= $rol['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <table class="data-table" id="tabla-usuarios">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Alcance</th>
                <th>Último acceso</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['usuarios'])): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-users"></i><h3>No hay usuarios</h3></div></td></tr>
            <?php else: ?>
            <?php foreach ($data['usuarios'] as $u): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="sidebar-user-avatar" style="width:32px;height:32px;font-size:11px;flex-shrink:0">
                            <?= strtoupper(substr($u['nombre'], 0, 1) . substr($u['apellido'], 0, 1)) ?>
                        </div>
                        <div>
                            <span class="cell-main"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></span>
                            <span class="cell-sub"><?= htmlspecialchars($u['email']) ?></span>
                        </div>
                    </div>
                </td>
                <td><?= htmlspecialchars($u['rol_nombre'] ?? '-') ?></td>
                <td>
                    <span class="cell-main">Toda la empresa</span>
                    <span class="cell-sub"><?= htmlspecialchars($u['rol_nombre'] ?? 'Empresa') ?></span>
                </td>
                <td>
                    <?php if ($u['ultimo_acceso']): ?>
                        <?= formatDate($u['ultimo_acceso']) ?>
                    <?php else: ?>
                        <span style="color:var(--gray-400)">Sin acceso</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= $u['activo'] ? 'badge-participate' : 'badge-suspended' ?>">
                        <?= $u['activo'] ? 'Activo' : 'Suspendido' ?>
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>/usuarios/editar/<?= $u['id'] ?>" class="section-action" style="font-size:12px">
                            <?= $u['activo'] ? 'Suspender' : 'Activar' ?>
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
            <span style="font-size:13px;color:var(--gray-500)">El rol define la responsabilidad inicial. La matriz detallada de permisos por módulo y acción se configurará en la siguiente iteración.</span>
        </div>
    </div>
</div>



