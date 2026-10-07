<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Proyectos - Oportunidades y Contratos. Centraliza convocatorias, responsables, propuestas y vencimientos.">
    <title><?= $pageTitle ?? 'Gestión de Proyectos' ?> | Gestión de Proyectos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
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
            height: auto !important;
            min-height: auto !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-y: auto !important;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        body.is-modal-view::-webkit-scrollbar { width: 4px; }
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
            padding: 10px 18px 14px !important; 
            margin: 0 !important; 
            box-shadow: none !important; 
            border: none !important; 
            background: #ffffff !important; 
            max-width: 100% !important;
            animation: none !important;
            min-height: auto !important;
            height: auto !important;
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

<style>
/* ══════════════════════════════════════════════════════════
   SISTEMA DE MODALES Y PANELES - DESIGN SYSTEM MODERNO
   ══════════════════════════════════════════════════════════ */

/* ── 1. MODAL GLOBAL CON IFRAME ── */
.global-modal-overlay {
    position: fixed; 
    inset: 0; 
    background: rgba(15, 23, 42, 0.5); 
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 10000; 
    display: none; 
    align-items: center; 
    justify-content: flex-end;
    align-items: stretch;
    padding: 16px;
    animation: modalOverlayFadeIn 0.25s ease-out;
}
.global-modal-overlay.visible { display: flex; }

.global-modal {
    background: #ffffff; 
    border-radius: 20px; 
    width: 100%; 
    max-width: 600px;
    height: 100%; 
    margin: 0;
    display: flex; 
    flex-direction: column;
    box-shadow: -10px 0 40px rgba(15, 23, 42, 0.15), -1px 0 0 rgba(226, 232, 240, 0.8); 
    overflow: hidden;
    animation: sidePanelSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    transition: width 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes sidePanelSlideIn {
    0% { transform: translateX(100%); opacity: 0; }
    100% { transform: translateX(0); opacity: 1; }
}

.global-modal-header {
    flex-shrink: 0;
    display: flex; 
    justify-content: space-between; 
    align-items: flex-start;
    padding: 14px 20px 10px; 
    border-bottom: 1px solid #f1f5f9; 
    background: #ffffff;
}
.global-modal-title-wrap {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.global-modal-icon-badge {
    display: none !important;
}
.global-modal-header h3 { 
    font-size: 16px; 
    font-weight: 700; 
    color: #0f172a; 
    margin: 0; 
    letter-spacing: -0.2px;
}
.global-modal-subtitle {
    font-size: 11.5px; 
    color: #64748b; 
    font-weight: 400; 
    display: block; 
    margin-top: 2px;
}
.global-modal-close {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.global-modal-close:hover { 
    background: #f1f5f9; 
    color: #0f172a; 
    transform: none;
}

.global-modal-body {
    flex: 1 1 auto; 
    overflow: hidden; 
    position: relative;
    background: #ffffff;
    max-height: calc(94vh - 54px);
    display: flex;
    flex-direction: column;
}
.global-modal-body iframe {
    width: 100%;
    min-height: 160px;
    height: 220px;
    border: none;
    display: block;
    transition: height 0.2s ease;
}
.global-modal-loader {
    position: absolute;
    inset: 0;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
    z-index: 10;
    transition: opacity 0.2s;
}
.modal-spinner {
    width: 34px;
    height: 34px;
    border: 3px solid #e2e8f0;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: modalSpin 0.8s linear infinite;
}

/* ── 2. MODAL DE CONFIRMACIÓN ── */
.global-confirm-modal {
    background: #ffffff; 
    border-radius: 24px; 
    width: 100%; 
    max-width: 420px;
    padding: 36px 30px 30px; 
    text-align: center;
    box-shadow: 0 25px 65px -12px rgba(15, 23, 42, 0.3), 0 0 0 1px rgba(226, 232, 240, 0.8);
    animation: modalPopIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.confirm-icon-box {
    width: 68px; 
    height: 68px; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    font-size: 26px; 
    margin: 0 auto 20px;
    transition: all 0.3s;
}
.confirm-icon-box.danger {
    background: #fef2f2;
    color: #dc2626;
    box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.1);
}
.confirm-icon-box.warning {
    background: #fffbeb;
    color: #d97706;
    box-shadow: 0 0 0 8px rgba(245, 158, 11, 0.1);
}
.confirm-icon-box.success {
    background: #ecfdf5;
    color: #059669;
    box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.1);
}
.confirm-icon-box.primary {
    background: #e0e7ff;
    color: #4f46e5;
    box-shadow: 0 0 0 8px rgba(99, 102, 241, 0.1);
}

.global-confirm-modal h3 { 
    font-size: 19px; 
    font-weight: 700; 
    color: #0f172a; 
    margin-bottom: 8px; 
    letter-spacing: -0.2px;
}
.global-confirm-modal p { 
    font-size: 13.5px; 
    color: #64748b; 
    margin-bottom: 26px; 
    line-height: 1.55; 
}
.confirm-actions { 
    display: flex; 
    gap: 12px; 
    justify-content: center; 
}
.btn-confirm-cancel {
    flex: 1;
    padding: 12px 18px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    color: #475569;
    font-weight: 600;
    font-size: 13.5px;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-confirm-cancel:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}
.btn-confirm-accept {
    flex: 1;
    padding: 12px 18px;
    border-radius: 12px;
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: #ffffff;
    font-weight: 600;
    font-size: 13.5px;
    text-decoration: none;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
    transition: all 0.2s;
    cursor: pointer;
}
.btn-confirm-accept:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
}

/* ── 3. BOTÓN CAMPANITA EN TOP-HEADER ── */
.header-bell-btn {
    background: #ffffff; 
    border: 1.5px solid #e2e8f0; 
    width: 42px; 
    height: 42px; 
    border-radius: 12px;
    display: flex; 
    align-items: center; 
    justify-content: center;
    color: #475569; 
    font-size: 17px; 
    cursor: pointer; 
    position: relative;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
    margin-right: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.header-bell-btn:hover { 
    background: #f8fafc; 
    border-color: #cbd5e1; 
    color: #4f46e5; 
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.header-bell-btn .badge-count {
    position: absolute; 
    top: -5px; 
    right: -5px;
    background: #ef4444; 
    color: white; 
    font-size: 10px; 
    font-weight: 700;
    min-width: 19px; 
    height: 19px; 
    border-radius: 9999px;
    padding: 0 5px;
    display: flex; 
    align-items: center; 
    justify-content: center;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35);
    animation: pulseBadge 2.5s infinite;
}

/* ── 4. MODAL ACCESO RESTRINGIDO ── */
.access-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    animation: modalOverlayFadeIn 0.25s ease-out;
    padding: 20px;
}
.access-modal-overlay.visible { display: flex; }

.access-modal {
    position: relative;
    background: #ffffff;
    border-radius: 24px;
    padding: 38px 34px 30px;
    max-width: 480px;
    width: 100%;
    text-align: center;
    box-shadow: 0 25px 70px -15px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(226, 232, 240, 0.8);
    animation: modalPopIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    overflow: hidden;
}
.access-modal-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 32px;
    height: 32px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.access-modal-close:hover {
    background: #fee2e2;
    border-color: #fecaca;
    color: #ef4444;
}
.access-modal-glow {
    position: absolute;
    top: -90px; left: 50%;
    transform: translateX(-50%);
    width: 260px; height: 260px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.12), transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.access-modal-icon-box {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: #fffbeb;
    color: #d97706;
    box-shadow: 0 0 0 8px rgba(245, 158, 11, 0.1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    margin-bottom: 18px;
}
.access-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    letter-spacing: -0.2px;
}
.access-modal-text {
    font-size: 13.5px;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 22px;
    max-width: 390px;
    margin-left: auto;
    margin-right: auto;
}
.access-modal-label {
    display: block;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.access-modal-textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #1e293b;
    resize: none;
    transition: all 0.2s;
    background: #f8fafc;
    margin-bottom: 16px;
    line-height: 1.5;
    box-sizing: border-box;
}
.access-modal-textarea:focus {
    outline: none;
    background: #ffffff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}
.access-modal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
    width: 100%;
    transition: all 0.2s;
}
.access-modal-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
}
.access-modal-success {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 12px;
    color: #065f46;
    font-size: 13.5px;
    font-weight: 600;
}
.access-modal-footer {
    margin-top: 20px;
}
.access-modal-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    padding: 6px 14px;
}
.access-modal-badge i { color: #6366f1; font-size: 13px; }

/* ── 5. PANEL LATERAL DE SOLICITUDES (DRAWER) ── */
.solicitudes-panel-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 9998;
    animation: modalOverlayFadeIn 0.25s ease-out;
}
.solicitudes-panel-overlay.visible { display: block; }

.solicitudes-panel {
    position: fixed;
    top: 0; 
    right: -520px;
    width: 490px;
    max-width: 92vw;
    height: 100vh;
    background: #f8fafc;
    box-shadow: -15px 0 50px rgba(15, 23, 42, 0.15), -1px 0 0 rgba(226, 232, 240, 0.8);
    z-index: 9999;
    transition: right 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.solicitudes-panel.visible { right: 0; }

.sol-panel-header {
    padding: 20px 24px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.sol-panel-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.sol-panel-header-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #fffbeb;
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    box-shadow: 0 2px 6px rgba(245, 158, 11, 0.15);
}
.sol-panel-header h3 {
    font-size: 16.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.2px;
}
.sol-panel-subtitle {
    font-size: 12px;
    color: #64748b;
    display: block;
    margin-top: 1px;
}
.sol-pill-count {
    display: inline-flex;
    align-items: center;
    background: #e0e7ff;
    color: #4f46e5;
    font-size: 11px;
    font-weight: 600;
    padding: 1px 7px;
    border-radius: 9999px;
    margin-right: 4px;
}
.sol-panel-close {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
}
.sol-panel-close:hover { 
    background: #fee2e2; 
    border-color: #fee2e2; 
    color: #ef4444; 
    transform: rotate(90deg);
}

.sol-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Tarjeta individual de solicitud */
.sol-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    transition: box-shadow 0.25s, border-color 0.25s, transform 0.25s;
    animation: cardSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}
.sol-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.08);
    transform: translateY(-2px);
}
.sol-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    gap: 12px;
}
.sol-card-user {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.sol-card-avatar {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    object-fit: cover;
    border: 1.5px solid #e2e8f0;
    flex-shrink: 0;
}
.sol-card-avatar-placeholder {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.5px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
}
.sol-card-info {
    min-width: 0;
}
.sol-card-info h4 {
    font-size: 14.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sol-card-info span {
    font-size: 12px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Status Pill elegante */
.sol-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}
.sol-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.sol-status-pendiente { 
    background: #fffbeb; 
    color: #b45309; 
    border: 1px solid #fef3c7;
}
.sol-status-pendiente .sol-badge-dot { 
    background: #f59e0b; 
    animation: pulseDot 2s infinite; 
}
.sol-status-aprobada { 
    background: #ecfdf5; 
    color: #065f46; 
    border: 1px solid #a7f3d0; 
}
.sol-status-aprobada .sol-badge-dot { 
    background: #10b981; 
}
.sol-status-rechazada { 
    background: #fef2f2; 
    color: #991b1b; 
    border: 1px solid #fecaca; 
}
.sol-status-rechazada .sol-badge-dot { 
    background: #ef4444; 
}

/* Mensaje con diseño de cita ejecutiva */
.sol-card-message {
    background: #f8fafc;
    border-left: 3px solid #6366f1;
    border-radius: 0 10px 10px 0;
    padding: 10px 14px;
    font-size: 13px;
    color: #334155;
    margin-bottom: 16px;
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.sol-quote-icon {
    color: #a5b4fc;
    font-size: 11px;
    margin-top: 3px;
    flex-shrink: 0;
}

/* Selector Grid */
.sol-grid-selects {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 14px;
}
.sol-select-group label {
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}
.sol-select {
    width: 100%;
    padding: 9px 30px 9px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 12.5px;
    font-weight: 500;
    color: #1e293b;
    background-color: #f8fafc;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
    -webkit-appearance: none;
    appearance: none;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
    box-sizing: border-box;
    position: relative;
    z-index: 10;
}
.sol-select:focus {
    outline: none;
    border-color: #6366f1;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

/* Botones de acción */
.sol-card-buttons {
    display: flex;
    gap: 10px;
}
.sol-btn-approve {
    flex: 1.2;
    padding: 10px 16px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}
.sol-btn-approve:hover { 
    transform: translateY(-1px); 
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3); 
}
.sol-btn-approve:active { transform: translateY(0); }

.sol-btn-reject {
    flex: 1;
    padding: 10px 16px;
    background: #f8fafc;
    color: #dc2626;
    border: 1.5px solid #fee2e2;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.sol-btn-reject:hover { 
    background: #fef2f2; 
    border-color: #fca5a5; 
    color: #b91c1c; 
    transform: translateY(-1px);
}
.sol-btn-reject:active { transform: translateY(0); }

/* Etiquetas de información aprobada/rechazada */
.sol-assigned-info {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}
.sol-assigned-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    background: #ecfdf5;
    color: #065f46;
    font-size: 11.5px;
    font-weight: 600;
    border: 1px solid #a7f3d0;
}
.sol-assigned-item.rejected {
    background: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
}
.sol-assigned-item i { color: #10b981; }
.sol-assigned-item.rejected i { color: #ef4444; }

/* Estado vacío elegante */
.sol-empty {
    text-align: center;
    padding: 60px 24px;
    color: #64748b;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.sol-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}
.sol-empty h4 {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}
.sol-empty p {
    font-size: 13px;
    color: #64748b;
    max-width: 280px;
    line-height: 1.5;
}

/* Animaciones */
@keyframes modalOverlayFadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes modalPopIn { 
    from { opacity: 0; transform: scale(0.95) translateY(12px); } 
    to { opacity: 1; transform: scale(1) translateY(0); } 
}
@keyframes cardSlideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulseDot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}
@keyframes modalSpin { to { transform: rotate(360deg); } }
/* ── 6. MODELO DE INTEGRACIÓN: PROCESOS Y SECUENCIA (ING. PALOMINO) ── */
.process-pipeline-container {
    margin: 18px 30px 14px;
    animation: fadeInRow 0.3s ease-out;
}
.process-pipeline-top-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 0 4px;
}
.proc-top-title {
    display: flex;
    align-items: center;
    gap: 8px;
}
.proc-step-num {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    background: #e0e7ff;
    color: #4f46e5;
    font-size: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.proc-tag-active {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0f766e;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    padding: 2px 8px;
    border-radius: 9999px;
}

/* Barra de Chevrons (5 Fases en Secuencia) */
.process-chevrons-bar {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    border-radius: 12px 12px 0 0;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
    background: #0f172a;
    height: 56px;
    align-items: stretch;
}
.proc-chevron {
    position: relative;
    height: 100%;
    box-sizing: border-box;
    padding: 0 14px;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0.85;
    display: flex;
    align-items: center;
    border-right: 1px solid rgba(255, 255, 255, 0.2);
}
.proc-chevron:last-child {
    border-right: none;
}
.proc-chevron:hover {
    opacity: 1;
    filter: brightness(1.12);
}
.proc-chevron.active {
    opacity: 1;
    box-shadow: inset 0 -4px 0 0 #ffffff, 0 0 20px rgba(255, 255, 255, 0.15);
    transform: scale(1.03);
    z-index: 2;
    filter: brightness(1.2) saturate(1.2);
}
.proc-chevron.active .proc-icon {
    animation: pulseIcon 2s ease-in-out infinite;
}
@keyframes pulseIcon {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}
.proc-chevron.active .proc-chev-title {
    font-weight: 800;
    text-shadow: 0 1px 6px rgba(255,255,255,0.3);
}
.proc-chevron.active .proc-chev-sub {
    color: #ffffff;
    opacity: 0.95;
}

/* Colores de gradiente según la secuencia del flujo de valor */
.proc-c1 { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); } /* 1. Oportunidades: Azul corporativo */
.proc-c2 { background: linear-gradient(135deg, #0369a1 0%, #0284c7 100%); } /* 2. Proyectos: Azul cielo/teal */
.proc-c3 { background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); } /* 3. Ejecución: Teal verdoso */
.proc-c4 { background: linear-gradient(135deg, #047857 0%, #059669 100%); } /* 4. Control Financiero: Esmeralda */
.proc-c5 { background: linear-gradient(135deg, #15803d 0%, #16a34a 100%); } /* 5. Rentabilidad: Verde éxito */

.proc-chev-inner {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    width: 100%;
}
.proc-icon {
    font-size: 17px;
    width: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    opacity: 0.95;
    flex-shrink: 0;
}
.proc-chev-text {
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.proc-chev-title {
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: -0.1px;
    line-height: 1.25;
}
.proc-chev-sub {
    font-size: 9.5px;
    color: rgba(255, 255, 255, 0.8);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
    line-height: 1.2;
}

/* Sub-panel de Módulos (Parte de Abajo) */
.process-submodules-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 14px 14px;
    padding: 10px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05);
}
.submod-header-tag {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    flex-shrink: 0;
}
.submod-items-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    flex: 1;
}
.submod-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.submod-btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-1px);
}
.submod-btn.active {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12);
}
.submod-btn-planned {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    color: #94a3b8;
    font-size: 11.5px;
    cursor: default;
}
.submod-badge-info {
    font-size: 9.5px;
    font-weight: 700;
    background: #f1f5f9;
    color: #64748b;
    padding: 1px 5px;
    border-radius: 4px;
}
.submod-erp-badge {
    margin-left: auto;
    font-size: 11px;
    font-weight: 700;
    color: #0284c7;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    padding: 4px 10px;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

@keyframes fadeInRow {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

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
    try {
        const doc = iframe.contentDocument || (iframe.contentWindow ? iframe.contentWindow.document : null);
        if (!doc || !doc.body) return;
        
        // Elementos clave del contenido
        const contentArea = doc.querySelector('.content-area') || doc.querySelector('.convocatoria-modal-wrap');
        const formCard = doc.querySelector('.form-card');
        const form = doc.querySelector('form');
        
        let targetHeight = 0;
        if (contentArea) {
            targetHeight = contentArea.scrollHeight;
        } else if (formCard) {
            targetHeight = formCard.scrollHeight + 15;
        } else if (form) {
            targetHeight = form.scrollHeight + 20;
        } else {
            targetHeight = doc.body.scrollHeight;
        }
        
        // Medir scrollHeight del body
        const bodyH = doc.body.scrollHeight || doc.body.offsetHeight;
        if (bodyH > 0 && bodyH > targetHeight) {
            targetHeight = bodyH;
        }
        
        // Margen de seguridad para evitar barras de scroll parásitas
        targetHeight += 8;
        
        // Altura máxima acotada al viewport del usuario
        const headerEl = document.querySelector('.global-modal-header');
        const headerH = headerEl ? headerEl.offsetHeight : 54;
        const maxH = Math.floor(window.innerHeight * 0.94 - headerH);
        
        // Altura final ajustada: entre 160px y maxH
        const finalH = Math.min(Math.max(targetHeight, 160), maxH);
        
        iframe.style.height = finalH + 'px';
    } catch(err) {
        console.warn('Autoajuste modal iframe:', err);
    }
}

function abrirGlobalModal(titulo, url, icon = 'fas fa-plus-circle', subtitulo = 'Ventana de edición y configuración') {
    const titleEl = document.getElementById('globalModalTitle');
    const subtitleEl = document.querySelector('.global-modal-subtitle');
    const badgeEl = document.getElementById('globalModalIconBadge');
    const iframe = document.getElementById('globalModalIframe');
    const loader = document.getElementById('globalModalLoader');
    const overlay = document.getElementById('globalIframeOverlay');
    
    if (titleEl) titleEl.textContent = titulo;
    if (subtitleEl && subtitulo) subtitleEl.textContent = subtitulo;
    if (badgeEl) badgeEl.innerHTML = '<i class="' + icon + '"></i>';
    if (loader) loader.style.display = 'flex';
    
    if (iframe) {
        // Inicializar con altura base compacta
        iframe.style.height = '200px';
        iframe.onload = function() {
            if (loader) loader.style.display = 'none';
            redimensionarModalIframe();
            setTimeout(redimensionarModalIframe, 60);
            setTimeout(redimensionarModalIframe, 150);
            setTimeout(redimensionarModalIframe, 300);
        };
        iframe.src = url;
    }
    
    if (overlay) overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function cerrarGlobalModal() {
    const overlay = document.getElementById('globalIframeOverlay');
    const iframe = document.getElementById('globalModalIframe');
    if (!overlay) return;
    
    // Add exit animation
    overlay.classList.add('is-closing');
    document.body.style.overflow = '';
    
    setTimeout(function() {
        overlay.classList.remove('visible');
        overlay.classList.remove('is-closing');
        if (iframe) {
            iframe.src = '';
            iframe.style.height = '200px';
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
