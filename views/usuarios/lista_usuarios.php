<!-- =====================================================
     USUARIOS Y ACCESOS
     ===================================================== -->

<?php
// Calcular stats de usuarios considerando estado de aprobación y suspensión
$totalUsuarios = count($data['usuarios']);
$activos = 0;
$pendientes = 0;
$suspendidos = 0;
foreach ($data['usuarios'] as $u) {
    if (!$u['activo']) {
        $suspendidos++;
    } elseif (isset($u['acceso_aprobado']) && empty($u['acceso_aprobado'])) {
        $pendientes++;
    } else {
        $activos++;
    }
}
?>

<?php
$activeUserTab = 'directorio';
include __DIR__ . '/navbar_usuarios.php';
?>

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
                <td>
                    <span class="cell-main" style="font-weight:600;"><?= htmlspecialchars($u['cargo_nombre'] ?? $u['rol_nombre'] ?? '-') ?></span>
                    <?php if (!empty($u['cargo_nivel'])): ?>
                        <span class="cell-sub" style="color:#6366f1; font-weight:700; font-size:11px;">Nivel <?= $u['cargo_nivel'] ?> · <?= htmlspecialchars($u['cargo_codigo'] ?? '') ?></span>
                    <?php else: ?>
                        <span class="cell-sub"><?= htmlspecialchars($u['rol_nombre'] ?? '') ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="cell-main"><?= htmlspecialchars($u['area_nombre'] ?? 'Toda la empresa') ?></span>
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
                    <?php if (!$u['activo']): ?>
                        <span class="badge badge-suspended"><span style="width:6px;height:6px;border-radius:50%;background:#ef4444;display:inline-block;margin-right:4px;"></span>Suspendido</span>
                    <?php elseif (isset($u['acceso_aprobado']) && !$u['acceso_aprobado']): ?>
                        <span class="badge" style="background:#fffbeb; color:#b45309; border:1px solid #fef3c7; display:inline-flex; align-items:center; gap:5px;"><span style="width:6px;height:6px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>Pendiente</span>
                    <?php else: ?>
                        <span class="badge badge-participate"><span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;margin-right:4px;"></span>Activo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <?php 
                        $esRaiz = in_array(strtolower($u['email'] ?? ''), ['devops.maxisoftnet@gmail.com', 'asconsultti@gmail.com']);
                        ?>
                        <?php if ($esRaiz): ?>
                            <span style="font-size:11px; font-weight:800; color:#7c3aed; background:#f3e8ff; border:1px solid #d8b4fe; padding:3px 9px; border-radius:6px; display:inline-flex; align-items:center; gap:5px;" title="Administrador Global Maestro (Acceso Total Permanente)">
                                <i class="fas fa-crown"></i> Cuenta Raíz
                            </span>
                            <a href="javascript:void(0)" onclick="abrirGlobalModal('Cuenta Raíz Protegida', '<?= BASE_URL ?>/usuarios/editar/<?= $u['id'] ?>?modal_iframe=1', 'fas fa-crown', 'Director General / CEO · Control Administrativo Total Permanente')" class="btn-icon" style="width:30px; height:30px; border-radius:8px; border:1px solid #d8b4fe; background:#faf5ff; color:#7c3aed; margin-left:6px; display:inline-flex; align-items:center; justify-content:center; transition:all 0.2s;" title="Ver cuenta protegida">
                                <i class="fas fa-shield-alt" style="font-size:12px;"></i>
                            </a>
                        <?php elseif (isset($u['acceso_aprobado']) && !$u['acceso_aprobado']): ?>
                            <a href="javascript:void(0)" onclick="confirmarAccion('Aprobar Cuenta', '¿Estás seguro de activar la cuenta de <?= htmlspecialchars($u['nombre']) ?> para otorgarle acceso al sistema?', '<?= BASE_URL ?>/usuarios/alternar_estado/<?= $u['id'] ?>', '#10b981', 'fas fa-check-circle', false)" class="section-action" style="font-size:12px; color:#059669; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                <i class="fas fa-user-check"></i> Activar cuenta
                            </a>
                            <a href="javascript:void(0)" onclick="abrirGlobalModal('Editar Usuario Corporativo', '<?= BASE_URL ?>/usuarios/editar/<?= $u['id'] ?>?modal_iframe=1', 'fas fa-id-card-alt', 'Configura el Área y Cargo oficial en el Organigrama corporativo')" class="btn-icon" style="width:30px; height:30px; border-radius:8px; border:1px solid #e2e8f0; background:#ffffff; color:#64748b; margin-left:6px; display:inline-flex; align-items:center; justify-content:center; transition:all 0.2s;" title="Editar usuario">
                                <i class="fas fa-pen" style="font-size:11px;"></i>
                            </a>
                        <?php elseif ($u['activo']): ?>
                            <a href="javascript:void(0)" onclick="confirmarAccion('Suspender Usuario', '¿Estás seguro de suspender a <?= htmlspecialchars($u['nombre']) ?>? Perderá acceso temporalmente al sistema.', '<?= BASE_URL ?>/usuarios/alternar_estado/<?= $u['id'] ?>', '#ef4444', 'fas fa-user-slash', true)" class="section-action" style="font-size:12px; color:#dc2626; font-weight:500; display:inline-flex; align-items:center; gap:4px;">
                                <i class="fas fa-ban"></i> Suspender
                            </a>
                            <a href="javascript:void(0)" onclick="abrirGlobalModal('Editar Usuario Corporativo', '<?= BASE_URL ?>/usuarios/editar/<?= $u['id'] ?>?modal_iframe=1', 'fas fa-id-card-alt', 'Configura el Área y Cargo oficial en el Organigrama corporativo')" class="btn-icon" style="width:30px; height:30px; border-radius:8px; border:1px solid #e2e8f0; background:#ffffff; color:#64748b; margin-left:6px; display:inline-flex; align-items:center; justify-content:center; transition:all 0.2s;" title="Editar usuario">
                                <i class="fas fa-pen" style="font-size:11px;"></i>
                            </a>
                        <?php else: ?>
                            <a href="javascript:void(0)" onclick="confirmarAccion('Activar Usuario', '¿Estás seguro de reactivar a <?= htmlspecialchars($u['nombre']) ?>? Recuperará su acceso al sistema.', '<?= BASE_URL ?>/usuarios/alternar_estado/<?= $u['id'] ?>', '#10b981', 'fas fa-user-check', false)" class="section-action" style="font-size:12px; color:#059669; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                <i class="fas fa-power-off"></i> Activar
                            </a>
                            <a href="javascript:void(0)" onclick="abrirGlobalModal('Editar Usuario Corporativo', '<?= BASE_URL ?>/usuarios/editar/<?= $u['id'] ?>?modal_iframe=1', 'fas fa-id-card-alt', 'Configura el Área y Cargo oficial en el Organigrama corporativo')" class="btn-icon" style="width:30px; height:30px; border-radius:8px; border:1px solid #e2e8f0; background:#ffffff; color:#64748b; margin-left:6px; display:inline-flex; align-items:center; justify-content:center; transition:all 0.2s;" title="Editar usuario">
                                <i class="fas fa-pen" style="font-size:11px;"></i>
                            </a>
                        <?php endif; ?>
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



