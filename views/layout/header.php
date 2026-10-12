<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Proyectos - Oportunidades y Contratos. Centraliza convocatorias, responsables, propuestas y vencimientos.">
    <title><?= $pageTitle ?? 'Gestión de Proyectos' ?> | Gestión de Proyectos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/styles.css') ?: '2.1' ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modals-pipeline.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/modals-pipeline.css') ?: '1.0' ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pagination.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/pagination.css') ?: '1.0' ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        if (window !== window.parent) {
            if (window.location.href.indexOf('modal_iframe=1') === -1) {
                window.parent.location.reload();
            }
            // Auto-ajustar altura del modal en el padre según el contenido real
            function notificarPadreRedimension() {
                try {
                    if (window.parent && typeof window.parent.redimensionarModalIframe === 'function') {
                        window.parent.redimensionarModalIframe();
                    }
                } catch(e) {}
            }
            window.addEventListener('load', notificarPadreRedimension);
            document.addEventListener('DOMContentLoaded', notificarPadreRedimension);
            
            // ResizeObserver para vigilar cambios continuos en tamaño de formulario
            if (typeof ResizeObserver !== 'undefined') {
                const resizeObs = new ResizeObserver(function() {
                    notificarPadreRedimension();
                });
                document.addEventListener('DOMContentLoaded', function() {
                    const targetEl = document.querySelector('.content-area') || document.querySelector('.convocatoria-modal-wrap') || document.querySelector('.form-card') || document.body;
                    if (targetEl) resizeObs.observe(targetEl);
                });
            }

            // Interceptar botones de Cancelar o Volver dentro del modal iframe para cerrarlo suavemente
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.btn-secondary, a.btn, .form-actions a, .conv-btn-cancelar').forEach(function(btn) {
                    const txt = btn.textContent.trim().toLowerCase();
                    if (txt.includes('cancelar') || txt.includes('volver')) {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            if (window.parent && window.parent.cerrarGlobalModal) {
                                window.parent.cerrarGlobalModal();
                            }
                        });
                    }
                });
            });
        }
    </script>
    <style>
        body.is-modal-view,
        body.is-modal-view html { 
            background: #ffffff !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            height: 100% !important;
            min-height: 100% !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-y: auto !important;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        body.is-modal-view::-webkit-scrollbar { width: 5px; }
        body.is-modal-view::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        body.is-modal-view .app-layout {
            min-height: auto !important;
            height: auto !important;
            display: block !important;
        }
        body.is-modal-view .sidebar { display: none !important; }
        body.is-modal-view .top-header { display: none !important; }
        body.is-modal-view .process-pipeline-container { display: none !important; }
        body.is-modal-view .mobile-menu-btn { display: none !important; visibility: hidden !important; opacity: 0 !important; pointer-events: none !important; }
        body.is-modal-view .content-area { 
            padding: 14px 22px 60px !important; 
            margin: 0 !important; 
            box-shadow: none !important; 
            border: none !important; 
            background: #ffffff !important; 
            max-width: 100% !important;
            animation: none !important;
            min-height: auto !important;
            box-sizing: border-box !important;
        }
        body.is-modal-view .main-content { 
            margin-left: 0 !important; 
            padding: 0 !important; 
            min-height: auto !important;
            height: auto !important;
        }
        body.is-modal-view .page-header { display: none !important; }
        body.is-modal-view .form-card { 
            box-shadow: none !important; 
            border: none !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            background: transparent !important; 
        }
        
        /* Modern ultra-compact form inputs inside modal */
        body.is-modal-view .form-group {
            margin-bottom: 8px !important;
        }
        body.is-modal-view .form-group label { 
            display: block !important;
            font-size: 11px !important; 
            font-weight: 600 !important; 
            color: #334155 !important; 
            letter-spacing: 0.2px !important; 
            margin-bottom: 3px !important; 
        }
        body.is-modal-view .form-control { 
            background: #f8fafc !important; 
            border: 1.5px solid #e2e8f0 !important; 
            border-radius: 8px !important; 
            padding: 6px 11px !important; 
            height: 35px !important;
            font-size: 12.5px !important; 
            color: #0f172a !important; 
            box-shadow: none !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important; 
            width: 100% !important;
            box-sizing: border-box !important;
        }
        body.is-modal-view .form-control:focus { 
            background: #ffffff !important; 
            border-color: #0d9488 !important; 
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12) !important; 
            outline: none !important;
        }
        body.is-modal-view select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 10px center !important;
            background-size: 12px !important;
            padding-right: 32px !important;
            -webkit-appearance: none !important;
            appearance: none !important;
            cursor: pointer !important;
        }
        body.is-modal-view .form-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
            margin-bottom: 6px !important;
        }
        body.is-modal-view .form-actions {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 8px !important;
            margin-top: 14px !important;
            padding-top: 10px !important;
            border-top: 1px solid #f1f5f9 !important;
        }
        body.is-modal-view .btn-primary {
            background: #0f766e !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 7px 18px !important;
            font-weight: 600 !important;
            font-size: 12.5px !important;
            box-shadow: 0 2px 6px rgba(15, 118, 110, 0.2) !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
        }
        body.is-modal-view .btn-primary:hover {
            background: #0d9488 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 10px rgba(15, 118, 110, 0.3) !important;
        }
        body.is-modal-view .btn-secondary {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 7px 16px !important;
            font-weight: 600 !important;
            font-size: 12.5px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
        }
        body.is-modal-view .btn-secondary:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        
        /* Modal exit animation */
        @keyframes modalOverlayFadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes sidePanelSlideOut {
            from { transform: translateX(0); }
            to { transform: translateX(100%); }
        }
        .global-modal-overlay.is-closing {
            animation: modalOverlayFadeOut 0.25s ease-out forwards;
        }
        .global-modal-overlay.is-closing .global-modal {
            animation: sidePanelSlideOut 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        
        /* Sidebar link active state improvements */
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(139, 92, 246, 0.25), rgba(139, 92, 246, 0.05)) !important;
            color: #ffffff !important;
            box-shadow: inset 4px 0 0 #a78bfa !important;
            font-weight: 600;
            border-left: none !important;
        }
        .sidebar-link.active i {
            color: #a78bfa;
            transform: scale(1.1);
            transition: transform 0.2s;
        }
        .sidebar-link {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<?php $isIframeModal = isset($_GET['modal_iframe']) && $_GET['modal_iframe'] == 1; ?>
<body class="<?= $isIframeModal ? 'is-modal-view' : '' ?>">

<?php
// Determinar qué macroproceso está activo según la página actual o GET
$faseActiva = 1;
if (isset($_GET['fase'])) {
    $faseActiva = (int)$_GET['fase'];
} elseif (in_array($currentPage ?? '', ['proyectos'])) {
    $faseActiva = 2;
} elseif (in_array($currentPage ?? '', ['compras', 'almacenes', 'equipos'])) {
    $faseActiva = 3;
} elseif (in_array($currentPage ?? '', ['finanzas', 'valorizaciones', 'facturacion'])) {
    $faseActiva = 4;
} elseif (in_array($currentPage ?? '', ['analisis', 'rentabilidad', 'reportes'])) {
    $faseActiva = 5;
}

// Determinar si el usuario necesita el modal de acceso
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$accesoAprobado = $_SESSION['user']['acceso_aprobado'] ?? 0;
$necesitaAcceso = ($userRol === 'usuario' && !$accesoAprobado);

// Contar solicitudes pendientes (solo para admins)
$solicitudesPendientes = 0;
if (in_array($userRol, ['superadmin', 'admin'])) {
    $countResult = dbQueryOne("SELECT COUNT(*) as total FROM solicitudes_acceso WHERE estado = 'pendiente'");
    $solicitudesPendientes = $countResult['total'] ?? 0;
}
?>

<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <div class="sidebar-logo-text">
                    <h2>Gestión de Proyectos</h2>
                    <span>Oportunidades y contratos</span>
                </div>
            </div>
        </div>
        
        <div class="sidebar-section-title">Navegación general</div>
        
        <nav class="sidebar-nav" id="sidebar-nav-container">
            <?php
            $rLink = $necesitaAcceso ? 'restricted-link' : '';
            $rData = $necesitaAcceso ? 'data-restricted="true"' : '';
            ?>
            <!-- Top Global Links -->
            <a href="<?= BASE_URL ?>/dashboard<?= $faseActiva > 1 ? '?fase='.$faseActiva : '' ?>" class="sidebar-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                <i class="fas fa-th-large"></i>
                <span>Principal</span>
            </a>
            <a href="<?= BASE_URL ?>/usuarios<?= $faseActiva > 1 ? '?fase='.$faseActiva : '' ?>" class="sidebar-link <?= ($currentPage ?? '') === 'usuarios' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                <i class="fas fa-users-cog"></i>
                <span>Usuarios</span>
            </a>
            
            <div style="margin: 12px 20px; height: 1px; background: rgba(255,255,255,0.1);"></div>
            <div class="sidebar-section-title" style="margin-top:0;">Módulos de la Fase <?= $faseActiva ?></div>

            <!-- Fase 1: Gestión de oportunidades -->
            <div class="sidebar-group" id="sidebar-fase-1" style="<?= ($faseActiva === 1) ? 'display:block;' : 'display:none;' ?>">
                <a href="<?= BASE_URL ?>/convocatorias" class="sidebar-link <?= ($currentPage ?? '') === 'convocatorias' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-search"></i>
                    <span>Convocatorias</span>
                </a>
                <a href="<?= BASE_URL ?>/oportunidades" class="sidebar-link <?= ($currentPage ?? '') === 'oportunidades' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-bullseye"></i>
                    <span>Oportunidades</span>
                </a>
                <a href="<?= BASE_URL ?>/licitaciones" class="sidebar-link <?= ($currentPage ?? '') === 'licitaciones' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-gavel"></i>
                    <span>Licitaciones</span>
                </a>
                <a href="<?= BASE_URL ?>/propuestas" class="sidebar-link <?= ($currentPage ?? '') === 'propuestas' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-paper-plane"></i>
                    <span>Propuestas</span>
                </a>
                <a href="<?= BASE_URL ?>/contratos" class="sidebar-link <?= ($currentPage ?? '') === 'contratos' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-file-contract"></i>
                    <span>Contratos</span>
                </a>
                <a href="<?= BASE_URL ?>/clientes" class="sidebar-link <?= ($currentPage ?? '') === 'clientes' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-building"></i>
                    <span>Clientes</span>
                </a>
                <a href="<?= BASE_URL ?>/empresas" class="sidebar-link <?= ($currentPage ?? '') === 'empresas' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-city"></i>
                    <span>Empresas</span>
                </a>
                <a href="<?= BASE_URL ?>/profesionales" class="sidebar-link <?= ($currentPage ?? '') === 'profesionales' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-users-cog"></i>
                    <span>Profesionales</span>
                </a>
            </div>

            <!-- Fase 2: Planificación y proyectos -->
            <div class="sidebar-group" id="sidebar-fase-2" style="<?= ($faseActiva === 2) ? 'display:block;' : 'display:none;' ?>">
                <a href="<?= BASE_URL ?>/proyectos" class="sidebar-link <?= ($currentPage ?? '') === 'proyectos' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-project-diagram"></i>
                    <span>Proyectos</span>
                </a>
                <a href="<?= BASE_URL ?>/contratos?fase=2" class="sidebar-link <?= ($currentPage ?? '') === 'contratos' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-file-contract"></i>
                    <span>Contratos de Obra</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-sitemap"></i>
                    <span>WBS / EDT (Planeado)</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-calendar-check"></i>
                    <span>Cronograma y recursos</span>
                </a>
            </div>

            <!-- Fase 3: Ejecución y operación -->
            <div class="sidebar-group" id="sidebar-fase-3" style="<?= ($faseActiva === 3) ? 'display:block;' : 'display:none;' ?>">
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Compras</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-warehouse"></i>
                    <span>Almacenes</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Servicios</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-user-friends"></i>
                    <span>Personal & Equipos</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-handshake"></i>
                    <span>Subcontratos</span>
                </a>
            </div>

            <!-- Fase 4: Control económico y financiero -->
            <div class="sidebar-group" id="sidebar-fase-4" style="<?= ($faseActiva === 4) ? 'display:block;' : 'display:none;' ?>">
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-arrow-down"></i>
                    <span>Ingresos / Egresos</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-calculator"></i>
                    <span>Valorizaciones</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Facturación</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-wallet"></i>
                    <span>Cuentas por cobrar y pagar</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-balance-scale"></i>
                    <span>Tesorería & Presupuesto</span>
                </a>
            </div>

            <!-- Fase 5: Análisis y rentabilidad -->
            <div class="sidebar-group" id="sidebar-fase-5" style="<?= ($faseActiva === 5) ? 'display:block;' : 'display:none;' ?>">
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-receipt"></i>
                    <span>Costos por proyecto</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-percentage"></i>
                    <span>Rentabilidad por línea</span>
                </a>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Flujo de caja</span>
                </a>
                <a href="<?= BASE_URL ?>/dashboard?fase=5" class="sidebar-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-chart-pie"></i>
                    <span>Reportes y Dashboards</span>
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <?php
            $userName = $_SESSION['user']['nombre'] ?? 'Usuario';
            $initials = strtoupper(substr($userName, 0, 2));
            ?>
            <div class="sidebar-user">
                <?php if(!empty($_SESSION['user']['avatar'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['user']['avatar']) ?>" alt="Avatar" class="sidebar-user-avatar" style="padding:0; border-radius:50%; object-fit:cover;">
                <?php else: ?>
                    <div class="sidebar-user-avatar"><?= $initials ?></div>
                <?php endif; ?>
                <div class="sidebar-user-info">
                    <h4 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;" title="<?= htmlspecialchars($userName) ?>"><?= htmlspecialchars($userName) ?></h4>
                    <span style="font-size: 10px; color: #a78bfa; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">
                        <?= htmlspecialchars($userRol) ?>
                        <?php if ($necesitaAcceso): ?>
                            <span style="color: #f59e0b;">· Sin acceso</span>
                        <?php endif; ?>
                    </span>
                    <a href="<?= BASE_URL ?>/auth/logout" style="color: #ef4444; font-size: 11px; text-decoration: none; margin-top: 2px; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;">
                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="top-header-left">
                <i class="fas fa-layer-group"></i>
                <div>
                    <h1><?= $pageTitle ?? 'Gestión de Proyectos' ?></h1>
                    <span class="header-subtitle">Datos de demostración · Versión Inicial</span>
                </div>
            </div>
            <div class="top-header-right">
                <?php if ($solicitudesPendientes > 0): ?>
                    <button class="header-bell-btn" id="btn-solicitudes" onclick="abrirPanelSolicitudes()">
                        <i class="fas fa-bell"></i>
                        <span class="badge-count" id="badge-solicitudes"><?= $solicitudesPendientes ?></span>
                    </button>
                <?php else: ?>
                    <button class="header-bell-btn" onclick="abrirPanelSolicitudes()">
                        <i class="fas fa-bell"></i>
                    </button>
                <?php endif; ?>
                <?php if (in_array($currentPage ?? '', ['convocatorias','oportunidades','licitaciones','propuestas','contratos','proyectos','clientes','proveedores','usuarios']) && !$necesitaAcceso): ?>
                    <?php
                    $createLabels = [
                        'convocatorias' => 'Registrar convocatoria',
                        'oportunidades' => 'Nueva oportunidad',
                        'licitaciones' => 'Nueva licitación',
                        'propuestas' => 'Nueva propuesta',
                        'contratos' => 'Nuevo contrato',
                        'proyectos' => 'Nuevo proyecto',
                        'clientes' => 'Nuevo cliente',
                        'proveedores' => 'Nuevo proveedor',
                        'usuarios' => 'Nuevo usuario',
                    ];
                    $createSubtitles = [
                        'convocatorias' => 'Registra la información publicada en la fuente del concurso antes de evaluarla como oportunidad.',
                        'usuarios' => 'Configura los accesos, roles y áreas del usuario en el sistema.',
                    ];
                    $label = $createLabels[$currentPage] ?? 'Nuevo';
                    $modalTitle = ($currentPage === 'convocatorias') ? 'Registrar convocatoria detectada' : $label;
                    $sub = $createSubtitles[$currentPage] ?? 'Ventana de registro y configuración del sistema';
                    $icon = ($currentPage === 'convocatorias') ? 'fas fa-bullhorn' : 'fas fa-plus-circle';
                    ?>
                    <a href="javascript:void(0)" onclick="abrirGlobalModal('<?= $modalTitle ?>', '<?= BASE_URL ?>/?page=<?= $currentPage ?>&action=crear&modal_iframe=1', '<?= $icon ?>', '<?= addslashes($sub) ?>')" class="btn btn-primary" id="btn-crear">
                        <i class="fas fa-plus"></i> <?= $label ?>
                    </a>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if (!$isIframeModal): ?>
        <!-- ══════════════════════════════════════════════════════════
             MODELO DE INTEGRACIÓN: DE LA OPERACIÓN AL RESULTADO
             Superior: Procesos y secuencia (Pipeline de 5 fases)
             Abajo: Módulos para gestionar procesos
             (Requerimiento Ing. Palomino)
             ══════════════════════════════════════════════════════════ -->
        <?php
        // Determinar qué macroproceso está activo según la página actual
        // (Lógica movida a la parte superior para renderizar la sidebar correctamente)
        ?>
        <div class="process-pipeline-container">
            <div class="process-pipeline-top-label">
                <div class="proc-top-title">
                    <span class="proc-step-num"><i class="fas fa-layer-group"></i></span>
                    <strong style="color: #0f172a; font-size: 13.5px;">Modelo de integración</strong>
                    <span style="color: #64748b; font-size: 12.5px;">— De la operación al resultado</span>
                </div>
                <div class="proc-top-tag">
                    <span class="proc-tag-active">Secuencia de Procesos</span>
                </div>
            </div>

            <!-- PARTE SUPERIOR: PROCESOS Y SECUENCIA (CHEVRONS EN CADENA) -->
            <div class="process-chevrons-bar">
                <!-- Fase 1 -->
                <div class="proc-chevron proc-c1 <?= ($faseActiva === 1) ? 'active' : '' ?>" onclick="window.location.href='<?= BASE_URL ?>/dashboard?fase=1'" id="chev-fase-1" title="Clic para ver módulos de Gestión de Oportunidades">
                    <div class="proc-chev-inner">
                        <i class="far fa-file-alt proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">1. Gestión de oportunidades</span>
                            <span class="proc-chev-sub">Licitaciones · Propuestas · Contratos · Clientes</span>
                        </div>
                    </div>
                </div>

                <!-- Fase 2 -->
                <div class="proc-chevron proc-c2 <?= ($faseActiva === 2) ? 'active' : '' ?>" onclick="window.location.href='<?= BASE_URL ?>/dashboard?fase=2'" id="chev-fase-2" title="Clic para ver módulos de Planificación y Proyectos">
                    <div class="proc-chev-inner">
                        <i class="far fa-calendar-alt proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">2. Planificación y proyectos</span>
                            <span class="proc-chev-sub">Proyectos · WBS / EDT · Cronograma</span>
                        </div>
                    </div>
                </div>

                <!-- Fase 3 -->
                <div class="proc-chevron proc-c3 <?= ($faseActiva === 3) ? 'active' : '' ?>" onclick="window.location.href='<?= BASE_URL ?>/dashboard?fase=3'" id="chev-fase-3" title="Clic para ver módulos de Ejecución y Operación">
                    <div class="proc-chev-inner">
                        <i class="fas fa-cog proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">3. Ejecución y operación</span>
                            <span class="proc-chev-sub">Compras · Almacenes · Servicios · Equipos</span>
                        </div>
                    </div>
                </div>

                <!-- Fase 4 -->
                <div class="proc-chevron proc-c4 <?= ($faseActiva === 4) ? 'active' : '' ?>" onclick="window.location.href='<?= BASE_URL ?>/dashboard?fase=4'" id="chev-fase-4" title="Clic para ver módulos de Control Económico y Financiero">
                    <div class="proc-chev-inner">
                        <i class="fas fa-coins proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">4. Control económico y financiero</span>
                            <span class="proc-chev-sub">Ingresos · Valorizaciones · Facturación</span>
                        </div>
                    </div>
                </div>

                <!-- Fase 5 -->
                <div class="proc-chevron proc-c5 <?= ($faseActiva === 5) ? 'active' : '' ?>" onclick="window.location.href='<?= BASE_URL ?>/dashboard?fase=5'" id="chev-fase-5" title="Clic para ver módulos de Análisis y Rentabilidad">
                    <div class="proc-chev-inner">
                        <i class="fas fa-chart-line proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">5. Análisis y rentabilidad</span>
                            <span class="proc-chev-sub">Costos · Rentabilidad · BI Dashboards</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal submodules removed as requested -->
        </div>
        <?php endif; ?>
        
        <!-- Content Area -->
        <div class="content-area">

<!-- GLOBAL IFRAME MODAL -->
<div class="global-modal-overlay" id="globalIframeOverlay" onclick="if(event.target===this) cerrarGlobalModal()">
    <div class="global-modal">
        <div class="global-modal-header">
            <div class="global-modal-title-wrap">
                <div class="global-modal-icon-badge" id="globalModalIconBadge">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h3 id="globalModalTitle">Título</h3>
                    <span class="global-modal-subtitle">Ventana de edición y configuración</span>
                </div>
            </div>
            <button class="global-modal-close" onclick="cerrarGlobalModal()" title="Cerrar modal (Esc)">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="global-modal-body">
            <div class="global-modal-loader" id="globalModalLoader">
                <div class="modal-spinner"></div>
                <span>Cargando formulario...</span>
            </div>
            <iframe id="globalModalIframe" src="" frameborder="0"></iframe>
        </div>
    </div>
</div>

<!-- GLOBAL CONFIRM MODAL -->
<div class="global-modal-overlay" id="globalConfirmOverlay" onclick="if(event.target===this) cerrarConfirmModal()">
    <div class="global-confirm-modal">
        <div class="confirm-icon-box" id="confirmIconBox">
            <i class="fas fa-exclamation-triangle" id="confirmIcon"></i>
        </div>
        <h3 id="confirmTitle">¿Estás seguro?</h3>
        <p id="confirmText">Esta acción no se puede deshacer.</p>
        <div class="confirm-actions">
            <button class="btn-confirm-cancel" onclick="cerrarConfirmModal()">Cancelar</button>
            <a href="#" id="confirmBtn" class="btn-confirm-accept">Confirmar</a>
        </div>
    </div>
</div>

<?php if ($necesitaAcceso): ?>
<!-- MODAL: Acceso Restringido -->
<div class="access-modal-overlay" id="accessModalOverlay" onclick="if(event.target===this) this.classList.remove('visible')">
    <div class="access-modal">
        <button class="access-modal-close" onclick="document.getElementById('accessModalOverlay').classList.remove('visible')" title="Cerrar (Esc)"><i class="fas fa-times"></i></button>
        <div class="access-modal-glow"></div>
        <div class="access-modal-icon-box">
            <i class="fas fa-user-lock"></i>
        </div>
        <h2 class="access-modal-title">Acceso en Espera de Aprobación</h2>
        <p class="access-modal-text">
            Tu cuenta ha sido creada exitosamente. Para comenzar a trabajar, el administrador asignará tu rol y área de operaciones.
        </p>
        
        <div class="access-modal-form" id="accessRequestForm">
            <label class="access-modal-label">Mensaje para el administrador</label>
            <textarea id="accessMessage" class="access-modal-textarea" placeholder="Escribe un mensaje indicando tu puesto o proyecto (opcional)..." rows="3">Hola, solicito acceso al sistema de gestión de proyectos.</textarea>
            <button class="access-modal-btn" id="btnSolicitarAcceso" onclick="solicitarAcceso()">
                <i class="fas fa-paper-plane"></i>
                Enviar Solicitud
            </button>
        </div>
        
        <div class="access-modal-result" id="accessResult" style="display:none;">
            <div class="access-modal-success">
                <i class="fas fa-check-circle"></i>
                <span id="accessResultMsg"></span>
            </div>
        </div>

        <div class="access-modal-footer">
            <div class="access-modal-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Recibirás activación una vez validada la información</span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- PANEL LATERAL DE SOLICITUDES (ADMIN) -->
<?php if (in_array($userRol, ['superadmin', 'admin'])): ?>
<div class="solicitudes-panel-overlay" id="solPanelOverlay" onclick="cerrarPanelSolicitudes()"></div>
<div class="solicitudes-panel" id="solPanel">
    <div class="sol-panel-header">
        <div class="sol-panel-header-left">
            <div class="sol-panel-header-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div>
                <h3>Solicitudes de Acceso</h3>
                <span class="sol-panel-subtitle" id="solPanelSubtitle">Gestión de accesos y asignación de roles</span>
            </div>
        </div>
        <button class="sol-panel-close" onclick="cerrarPanelSolicitudes()" title="Cerrar panel (Esc)">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="sol-panel-body" id="solPanelBody">
        <div class="sol-empty">
            <div class="modal-spinner"></div>
            <p style="margin-top:12px;">Cargando solicitudes...</p>
        </div>
    </div>
</div>
<?php endif; ?>


<script>
const BASE_URL = '<?= BASE_URL ?>';
const necesitaAcceso = <?= $necesitaAcceso ? 'true' : 'false' ?>;

// Cambiar fase en la barra de procesos (Ing. Palomino)
function cambiarFasePipeline(faseNum) {
    for (let i = 1; i <= 5; i++) {
        const chev = document.getElementById('chev-fase-' + i);
        const mod = document.getElementById('modulos-fase-' + i);
        const sideGroup = document.getElementById('sidebar-fase-' + i);
        
        if (chev) {
            if (i === faseNum) chev.classList.add('active');
            else chev.classList.remove('active');
        }
        if (mod) {
            mod.style.display = (i === faseNum) ? 'flex' : 'none';
        }
        if (sideGroup) {
            sideGroup.style.display = (i === faseNum) ? 'block' : 'none';
        }
    }
}

// Función de escape para seguridad en renderizado
function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

// ── Modales Globales (Iframe) ──
function redimensionarModalIframe() {
    const iframe = document.getElementById('globalModalIframe');
    if (!iframe) return;
    // Ocupa el 100% de la altura del panel lateral cuadrado, permitiendo scroll fluido sin recortes
    iframe.style.height = '100%';
}

function abrirGlobalModal(titulo, url, icon = 'fas fa-plus-circle', subtitulo = 'Ventana de edición y configuración', size = '') {
    const titleEl = document.getElementById('globalModalTitle');
    const subtitleEl = document.querySelector('.global-modal-subtitle');
    const badgeEl = document.getElementById('globalModalIconBadge');
    const iframe = document.getElementById('globalModalIframe');
    const loader = document.getElementById('globalModalLoader');
    const overlay = document.getElementById('globalIframeOverlay');
    const modalEl = document.querySelector('.global-modal');
    
    if (modalEl) {
        modalEl.classList.remove('modal-wide');
        if (size === 'wide' || size === 'xl' || size === 'lg') {
            modalEl.classList.add('modal-wide');
        }
    }
    
    if (titleEl) titleEl.textContent = titulo;
    if (subtitleEl && subtitulo) subtitleEl.textContent = subtitulo;
    if (badgeEl) badgeEl.innerHTML = '<i class="' + icon + '"></i>';
    if (loader) loader.style.display = 'flex';
    
    if (iframe) {
        iframe.style.height = '100%';
        iframe.onload = function() {
            if (loader) loader.style.display = 'none';
        };
        iframe.src = url;
    }
    
    if (overlay) overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function cerrarGlobalModal() {
    const overlay = document.getElementById('globalIframeOverlay');
    const iframe = document.getElementById('globalModalIframe');
    const modalEl = document.querySelector('.global-modal');
    if (!overlay) return;
    
    // Add exit animation
    overlay.classList.add('is-closing');
    document.body.style.overflow = '';
    
    setTimeout(function() {
        overlay.classList.remove('visible');
        overlay.classList.remove('is-closing');
        if (modalEl) {
            modalEl.classList.remove('modal-wide');
        }
        if (iframe) {
            iframe.src = '';
            iframe.style.height = '100%';
        }
    }, 280);
}

// Reajuste en cambio de tamaño de pantalla
window.addEventListener('resize', function() {
    const overlay = document.getElementById('globalIframeOverlay');
    if (overlay && overlay.classList.contains('visible')) {
        redimensionarModalIframe();
    }
});

// ── Modal de Confirmación Global ──
function confirmarAccion(titulo, texto, url, btnColor = '#4f46e5', iconClass = 'fas fa-exclamation-triangle', isWarning = false) {
    const titleEl = document.getElementById('confirmTitle');
    const textEl = document.getElementById('confirmText');
    const btn = document.getElementById('confirmBtn');
    const iconBox = document.getElementById('confirmIconBox');
    const icon = document.getElementById('confirmIcon');
    const overlay = document.getElementById('globalConfirmOverlay');
    
    if (titleEl) titleEl.textContent = titulo;
    if (textEl) textEl.textContent = texto;
    if (btn) {
        btn.href = url;
        // Paleta elegante y armoniosa (no chillosa)
        if (btnColor.includes('ef4444') || btnColor.includes('danger') || iconClass.includes('slash') || iconClass.includes('trash')) {
            btn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            btn.style.boxShadow = '0 4px 14px rgba(239, 68, 68, 0.25)';
            if (iconBox) iconBox.className = 'confirm-icon-box danger';
        } else if (btnColor.includes('10b981') || btnColor.includes('success') || iconClass.includes('check')) {
            btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            btn.style.boxShadow = '0 4px 14px rgba(16, 185, 129, 0.25)';
            if (iconBox) iconBox.className = 'confirm-icon-box success';
        } else if (isWarning || btnColor.includes('f59e0b')) {
            btn.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
            btn.style.boxShadow = '0 4px 14px rgba(245, 158, 11, 0.25)';
            if (iconBox) iconBox.className = 'confirm-icon-box warning';
        } else {
            btn.style.background = 'linear-gradient(135deg, #4f46e5 0%, #4338ca 100%)';
            btn.style.boxShadow = '0 4px 14px rgba(79, 70, 229, 0.25)';
            if (iconBox) iconBox.className = 'confirm-icon-box primary';
        }
    }
    
    if (icon) icon.className = iconClass;
    if (overlay) overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function cerrarConfirmModal() {
    const overlay = document.getElementById('globalConfirmOverlay');
    if (overlay) overlay.classList.remove('visible');
    document.body.style.overflow = '';
}

// ── Interceptar clicks si necesita acceso ──
document.addEventListener('DOMContentLoaded', function() {
    if (necesitaAcceso) {
        document.querySelectorAll('.restricted-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('accessModalOverlay').classList.add('visible');
            });
        });
        
        const contentArea = document.querySelector('.content-area');
        if (contentArea) {
            contentArea.addEventListener('click', function(e) {
                const target = e.target.closest('a, button');
                if (target && !target.classList.contains('access-modal-btn') && !target.id.includes('access')) {
                    e.preventDefault();
                    document.getElementById('accessModalOverlay').classList.add('visible');
                }
            });
        }
    }
});

// ── Solicitar acceso ──
function solicitarAcceso() {
    const btn = document.getElementById('btnSolicitarAcceso');
    const mensaje = document.getElementById('accessMessage').value;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Enviando...';
    
    fetch(BASE_URL + '/auth/solicitarAcceso', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'mensaje=' + encodeURIComponent(mensaje)
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('accessRequestForm').style.display = 'none';
        document.getElementById('accessResult').style.display = 'block';
        document.getElementById('accessResultMsg').textContent = data.message;
        
        if (!data.success) {
            const successEl = document.querySelector('.access-modal-success');
            if (successEl) {
                successEl.style.background = '#fffbeb';
                successEl.style.borderColor = '#fef3c7';
                successEl.style.color = '#b45309';
                const icon = successEl.querySelector('i');
                if (icon) icon.style.color = '#f59e0b';
            }
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitud';
    });
}

// ── Panel de solicitudes (Admin) ──
function abrirPanelSolicitudes() {
    const overlay = document.getElementById('solPanelOverlay');
    const panel = document.getElementById('solPanel');
    
    if (overlay) {
        overlay.classList.add('visible');
        overlay.style.display = 'block';
    }
    if (panel) {
        panel.classList.add('visible');
        panel.style.right = '0';
    }
    
    // Ocultar la burbuja roja
    const badge = document.getElementById('badge-solicitudes');
    if (badge) badge.style.display = 'none';
    
    cargarSolicitudes();
}

function cerrarPanelSolicitudes() {
    const overlay = document.getElementById('solPanelOverlay');
    const panel = document.getElementById('solPanel');
    
    if (overlay) {
        overlay.classList.remove('visible');
        overlay.style.display = 'none';
    }
    if (panel) {
        panel.classList.remove('visible');
        panel.style.right = '-520px';
    }
}

function cargarSolicitudes() {
    fetch(BASE_URL + '/auth/solicitudes')
    .then(r => r.json())
    .then(data => {
        const body = document.getElementById('solPanelBody');
        const subtitle = document.getElementById('solPanelSubtitle');
        
        if (!data.solicitudes || data.solicitudes.length === 0) {
            if (subtitle) subtitle.textContent = 'Todas las solicitudes atendidas';
            body.innerHTML = `
                <div class="sol-empty">
                    <div class="sol-empty-icon"><i class="fas fa-check-double"></i></div>
                    <h4>Bandeja al día</h4>
                    <p>No tienes solicitudes de acceso pendientes en este momento.</p>
                </div>
            `;
            return;
        }
        
        const pendientes = data.solicitudes.filter(s => s.estado === 'pendiente').length;
        if (subtitle) {
            subtitle.innerHTML = pendientes > 0 
                ? '<span class="sol-pill-count">' + pendientes + ' pendiente' + (pendientes > 1 ? 's' : '') + '</span> Requieren asignación de rol'
                : 'Todas las solicitudes han sido gestionadas';
        }
        
        let html = '';
        data.solicitudes.forEach(function(sol) {
            const initials = (sol.usuario_nombre || 'U').substring(0, 2).toUpperCase();
            const isPendiente = sol.estado === 'pendiente';
            
            let statusClass = 'sol-status-' + sol.estado;
            let statusLabel = sol.estado.charAt(0).toUpperCase() + sol.estado.slice(1);
            
            html += '<div class="sol-card" id="sol-card-' + sol.id + '">';
            html += '<div class="sol-card-header">';
            html += '<div class="sol-card-user">';
            
            if (sol.usuario_avatar) {
                html += '<img src="' + sol.usuario_avatar + '" class="sol-card-avatar" alt="Avatar">';
            } else {
                html += '<div class="sol-card-avatar-placeholder">' + initials + '</div>';
            }
            
            html += '<div class="sol-card-info">';
            html += '<h4>' + escapeHtml(sol.usuario_nombre || 'Usuario') + '</h4>';
            html += '<span><i class="far fa-envelope"></i> ' + escapeHtml(sol.usuario_email || '') + '</span>';
            html += '</div>';
            html += '</div>';
            
            html += '<span class="sol-status-badge ' + statusClass + '"><span class="sol-badge-dot"></span>' + statusLabel + '</span>';
            html += '</div>';
            
            if (sol.mensaje) {
                html += '<div class="sol-card-message">';
                html += '<i class="fas fa-quote-left sol-quote-icon"></i>';
                html += '<span>' + escapeHtml(sol.mensaje) + '</span>';
                html += '</div>';
            }
            
            if (isPendiente) {
                html += '<div class="sol-grid-selects">';
                
                // Select tipo usuario
                html += '<div class="sol-select-group">';
                html += '<label><i class="fas fa-id-badge"></i> Rol / Tipo</label>';
                html += '<select class="sol-select" id="solTipo' + sol.id + '">';
                html += '<option value="">Seleccionar rol...</option>';
                data.tipos.forEach(function(t) {
                    html += '<option value="' + t.id + '">' + escapeHtml(t.nombre) + '</option>';
                });
                html += '</select>';
                html += '</div>';
                
                // Select área
                html += '<div class="sol-select-group">';
                html += '<label><i class="fas fa-building"></i> Área</label>';
                html += '<select class="sol-select" id="solArea' + sol.id + '">';
                html += '<option value="">Seleccionar área...</option>';
                data.areas.forEach(function(a) {
                    html += '<option value="' + a.id + '">' + escapeHtml(a.nombre) + '</option>';
                });
                html += '</select>';
                html += '</div>';
                
                html += '</div>';
                
                html += '<div class="sol-card-buttons">';
                html += '<button class="sol-btn-approve" id="btn-approve-' + sol.id + '" onclick="gestionarSolicitud(' + sol.id + ', \'aprobar\')"><i class="fas fa-check"></i> Aprobar Acceso</button>';
                html += '<button class="sol-btn-reject" id="btn-reject-' + sol.id + '" onclick="gestionarSolicitud(' + sol.id + ', \'rechazar\')"><i class="fas fa-times"></i> Rechazar</button>';
                html += '</div>';
            } else if (sol.estado === 'aprobada') {
                html += '<div class="sol-assigned-info">';
                if (sol.tipo_nombre) html += '<span class="sol-assigned-item"><i class="fas fa-user-tag"></i> ' + escapeHtml(sol.tipo_nombre) + '</span>';
                if (sol.area_nombre) html += '<span class="sol-assigned-item"><i class="fas fa-sitemap"></i> ' + escapeHtml(sol.area_nombre) + '</span>';
                html += '</div>';
            } else if (sol.estado === 'rechazada') {
                html += '<div class="sol-assigned-info">';
                html += '<span class="sol-assigned-item rejected"><i class="fas fa-ban"></i> Acceso rechazado</span>';
                html += '</div>';
            }
            
            html += '</div>';
        });
        
        body.innerHTML = html;
    })
    .catch(err => {
        console.error("Error cargando solicitudes:", err);
    });
}

function gestionarSolicitud(id, accion) {
    const tipoSelect = document.getElementById('solTipo' + id);
    const areaSelect = document.getElementById('solArea' + id);
    
    const tipoId = tipoSelect ? tipoSelect.value : '';
    const areaId = areaSelect ? areaSelect.value : '';
    
    if (accion === 'aprobar') {
        let hasError = false;
        if (!tipoId && tipoSelect) {
            tipoSelect.style.borderColor = '#ef4444';
            tipoSelect.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
            hasError = true;
        } else if (tipoSelect) {
            tipoSelect.style.borderColor = '#e2e8f0';
            tipoSelect.style.boxShadow = 'none';
        }
        
        if (!areaId && areaSelect) {
            areaSelect.style.borderColor = '#ef4444';
            areaSelect.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
            hasError = true;
        } else if (areaSelect) {
            areaSelect.style.borderColor = '#e2e8f0';
            areaSelect.style.boxShadow = 'none';
        }
        
        if (hasError) return;
    }
    
    const btnApprove = document.getElementById('btn-approve-' + id);
    const btnReject = document.getElementById('btn-reject-' + id);
    if (btnApprove) btnApprove.disabled = true;
    if (btnReject) btnReject.disabled = true;
    
    if (accion === 'aprobar' && btnApprove) {
        btnApprove.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Aprobando...';
    } else if (accion === 'rechazar' && btnReject) {
        btnReject.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Rechazando...';
    }
    
    const body = 'solicitud_id=' + id + '&accion=' + accion + '&tipo_usuario_id=' + tipoId + '&area_id=' + areaId;
    
    fetch(BASE_URL + '/auth/gestionarSolicitud', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarSolicitudes();
            // Actualizar badge en campanita
            const badge = document.querySelector('.badge-count');
            if (badge) {
                let count = parseInt(badge.textContent) - 1;
                if (count <= 0) {
                    badge.style.display = 'none';
                } else {
                    badge.textContent = count;
                }
            }
        } else {
            alert(data.message);
            if (btnApprove) { btnApprove.disabled = false; btnApprove.innerHTML = '<i class="fas fa-check"></i> Aprobar Acceso'; }
            if (btnReject) { btnReject.disabled = false; btnReject.innerHTML = '<i class="fas fa-times"></i> Rechazar'; }
        }
    })
    .catch(err => {
        console.error("Error al gestionar:", err);
        if (btnApprove) { btnApprove.disabled = false; btnApprove.innerHTML = '<i class="fas fa-check"></i> Aprobar Acceso'; }
        if (btnReject) { btnReject.disabled = false; btnReject.innerHTML = '<i class="fas fa-times"></i> Rechazar'; }
    });
}

// ── Atajo global Esc para cerrar modales y paneles ──
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarGlobalModal();
        cerrarConfirmModal();
        cerrarPanelSolicitudes();
        const access = document.getElementById('accessModalOverlay');
        if (access) access.classList.remove('visible');
    }
});

// ── Interceptar enlaces con onclick="return confirm(...)" para modal premium ──
document.addEventListener('click', function(e) {
    const link = e.target.closest('a[onclick*="confirm("]');
    if (link) {
        e.preventDefault();
        e.stopImmediatePropagation();
        const onclickAttr = link.getAttribute('onclick') || '';
        const match = onclickAttr.match(/confirm\(['"](.*?)['"]\)/);
        const msg = match ? match[1] : '¿Estás seguro de que deseas eliminar este registro?';
        const href = link.getAttribute('href');
        if (href && href !== '#' && !href.startsWith('javascript:')) {
            confirmarAccion('Confirmar eliminación', msg, href, '#ef4444', 'fas fa-trash-alt', true);
        }
    }
}, true);
</script>
