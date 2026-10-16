<?php
/**
 * Barra Superior Unificada para el Módulo de Usuarios y Accesos
 * @var string $activeUserTab ('directorio' | 'solicitudes' | 'crear')
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$activeUserTab = $activeUserTab ?? 'directorio';

// Conteo dinámico y seguro de solicitudes pendientes
$solicitudesPendientesBadge = (int)(dbQueryOne("SELECT COUNT(*) as total FROM solicitudes_acceso WHERE estado = 'pendiente'")['total'] ?? 0);
?>
<style>
.user-nav-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px 20px;
    margin-bottom: 22px;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
}
.user-nav-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 14px;
}
.user-nav-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.user-nav-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
}
.user-nav-text h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.2px;
}
.user-nav-text p {
    margin: 2px 0 0;
    font-size: 12.5px;
    color: #64748b;
}
.user-nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    border-top: 1px solid #f1f5f9;
    padding-top: 14px;
}
.user-tabs-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.user-tab-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.user-tab-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.user-tab-pill.active {
    background: #059669;
    color: #ffffff;
    border-color: #059669;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
}
.user-tab-badge {
    background: #ef4444;
    color: #ffffff;
    padding: 2px 7px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
}
.user-tab-pill.active .user-tab-badge {
    background: #ffffff;
    color: #059669;
}
.btn-user-action {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}
.btn-user-primary {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #ffffff;
    box-shadow: 0 3px 8px rgba(5, 150, 105, 0.25);
}
.btn-user-primary:hover {
    background: #047857;
    color: #ffffff;
    transform: translateY(-1px);
}
.btn-user-secondary {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
}
.btn-user-secondary:hover {
    background: #e2e8f0;
    color: #0f172a;
}
</style>

<div class="user-nav-card animate__animated animate__fadeIn">
    <div class="user-nav-header">
        <div class="user-nav-title">
            <div class="user-nav-icon">
                <i class="fas fa-users-cog"></i>
            </div>
            <div class="user-nav-text">
                <h2>Gestión de Usuarios y Accesos</h2>
                <p>Directorio de colaboradores, administración de cuentas y bandeja de solicitudes de ingreso</p>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <a href="<?= BASE_URL ?>/organizacion" class="btn-user-action btn-user-secondary" title="Ir al Organigrama Corporativo">
                <i class="fas fa-sitemap" style="color:#6366f1;"></i> Organigrama & Roles
            </a>
            <?php if ($activeUserTab !== 'crear'): ?>
                <a href="<?= BASE_URL ?>/usuarios/crear" class="btn-user-action btn-user-primary">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pestañas de Navegación del Panel de Usuarios -->
    <div class="user-nav-bar">
        <div class="user-tabs-group">
            <a href="<?= BASE_URL ?>/usuarios" class="user-tab-pill <?= ($activeUserTab === 'directorio') ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Directorio de Usuarios
            </a>
            <a href="<?= BASE_URL ?>/usuarios/solicitudes" class="user-tab-pill <?= ($activeUserTab === 'solicitudes') ? 'active' : '' ?>">
                <i class="fas fa-user-clock"></i> Solicitudes de Acceso
                <?php if ($solicitudesPendientesBadge > 0): ?>
                    <span class="user-tab-badge"><?= $solicitudesPendientesBadge ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div style="font-size:12px; color:#64748b; font-weight:600;">
            <i class="fas fa-shield-alt" style="color:#10b981; margin-right:4px;"></i> Control de Acceso Centralizado
        </div>
    </div>
</div>
