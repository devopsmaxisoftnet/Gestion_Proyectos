<?php
/**
 * Componente: Barra de Navegación Unificada del Módulo de Organización
 * Mantiene las pestañas fijas generales a la izquierda y las acciones dinámicas a la derecha.
 * 
 * Parámetros esperados (opcionales):
 * @var string $activeTab ('tablero' | 'areas' | 'cargos' | 'roles' | 'delegaciones')
 * @var string $orgNavTitle (Título principal)
 * @var string $orgNavSubtitle (Subtítulo explicativo)
 * @var string $customOrgActions (HTML adicional de acciones dinámicas a la derecha)
 */
$activeTab = $activeTab ?? 'tablero';
$orgNavTitle = $orgNavTitle ?? 'Organigrama & Estructura Corporativa';
$orgNavSubtitle = $orgNavSubtitle ?? 'Flujo de integración ERP · Áreas, cargos y control de permisos jerárquicos';

$userRolNav = $_SESSION['user']['rol'] ?? 'usuario';
$isAdminNav = in_array($userRolNav, ['superadmin', 'admin']);
?>

<style>
/* ─── Unified Org Navigation Bar Styles ─── */
.org-navbar-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
    background: #ffffff;
    padding: 16px 22px;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.025);
    position: relative;
    z-index: 10;
}

.org-navbar-left-brand {
    display: flex;
    align-items: center;
    gap: 14px;
}

.org-navbar-brand-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 10px rgba(124, 58, 237, 0.15);
    flex-shrink: 0;
}

.org-navbar-brand-text h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
    line-height: 1.2;
}

.org-navbar-brand-text span {
    font-size: 12.5px;
    color: #64748b;
    margin-top: 3px;
    display: block;
}

/* Contenedor de Navegación + Acciones */
.org-navbar-nav-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    flex-wrap: wrap;
    gap: 14px;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
}

/* Pestañas fijas generales (Izquierda) */
.org-general-tabs {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 12px;
    gap: 4px;
    flex-wrap: wrap;
}

.org-tab-pill {
    border: none;
    background: transparent;
    padding: 8px 14px;
    border-radius: 9px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    text-decoration: none;
    white-space: nowrap;
}

.org-tab-pill i {
    font-size: 13.5px;
    color: #64748b;
    transition: color 0.2s;
}

.org-tab-pill:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
}

.org-tab-pill:hover i {
    color: #4f46e5;
}

.org-tab-pill.active {
    background: #ffffff;
    color: #4f46e5;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.org-tab-pill.active i {
    color: #4f46e5;
}

/* Badge contador de solicitudes */
.org-tab-badge {
    background: #ef4444;
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 99px;
    line-height: 1;
    box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3);
    animation: pulse 2s infinite;
}

/* Acciones dinámicas (Derecha) */
.org-dynamic-actions-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-left: auto;
}

.btn-nav-primary {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: #ffffff !important;
    border: none;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    transition: all 0.2s ease;
    text-decoration: none;
    white-space: nowrap;
}

.btn-nav-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
}

.btn-nav-secondary {
    background: #ffffff;
    color: #334155 !important;
    border: 1.5px solid #cbd5e1;
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s ease;
    text-decoration: none;
    white-space: nowrap;
}

.btn-nav-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a !important;
    transform: translateY(-1px);
}
</style>

<div class="org-navbar-card animate__animated animate__fadeIn">
    <!-- Fila 1: Título e Identidad del Módulo -->
    <div class="org-navbar-left-brand">
        <div class="org-navbar-brand-icon">
            <i class="fas fa-sitemap animate__animated animate__pulse animate__infinite"></i>
        </div>
        <div class="org-navbar-brand-text">
            <h2><?= htmlspecialchars($orgNavTitle) ?></h2>
            <span><?= htmlspecialchars($orgNavSubtitle) ?></span>
        </div>
    </div>

    <!-- Fila 2: Pestañas Generales Fijas (Izquierda) + Acciones Dinámicas (Derecha) -->
    <div class="org-navbar-nav-row">
        
        <!-- Pestañas Generales FIJAS (Estructura Organizacional) -->
        <div class="org-general-tabs" id="orgGeneralNavTabs">
            <a href="<?= BASE_URL ?>/organizacion" class="org-tab-pill <?= ($activeTab === 'tablero') ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Tablero
            </a>
            <a href="<?= BASE_URL ?>/organizacion/areas" class="org-tab-pill <?= ($activeTab === 'areas') ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i> Áreas
            </a>
            <a href="<?= BASE_URL ?>/organizacion/cargos" class="org-tab-pill <?= ($activeTab === 'cargos') ? 'active' : '' ?>">
                <i class="fas fa-id-badge"></i> Cargos
            </a>
            <a href="<?= BASE_URL ?>/organizacion/delegaciones" class="org-tab-pill <?= ($activeTab === 'delegaciones') ? 'active' : '' ?>">
                <i class="fas fa-clock"></i> Delegaciones
            </a>
        </div>

        <!-- Acciones Dinámicas de Contexto (A la DERECHA) -->
        <div class="org-dynamic-actions-group">
            <?php if (!empty($customOrgActions)): ?>
                <?= $customOrgActions ?>
            <?php elseif ($isAdminNav): ?>
                <?php if ($activeTab === 'tablero' || $activeTab === 'areas'): ?>
                    <button class="btn-nav-primary" onclick="triggerModalNuevaArea()">
                        <i class="fas fa-plus"></i> Nueva Área
                    </button>
                <?php elseif ($activeTab === 'cargos'): ?>
                    <button class="btn-nav-primary" onclick="abrirModalCargo()">
                        <i class="fas fa-plus"></i> Nuevo Cargo
                    </button>
                <?php elseif ($activeTab === 'delegaciones'): ?>
                    <button class="btn-nav-primary" onclick="abrirModalDelegacion()">
                        <i class="fas fa-plus"></i> Nueva Delegación
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
// Disparador inteligente para crear área según la vista
function triggerModalNuevaArea() {
    if (typeof abrirModalCrearArea === 'function') {
        abrirModalCrearArea();
    } else if (typeof abrirModalArea === 'function') {
        abrirModalArea();
    } else {
        window.location.href = '<?= BASE_URL ?>/organizacion/crearArea';
    }
}
</script>
