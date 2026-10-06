<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión Comercial - Oportunidades y Contratos. Centraliza convocatorias, responsables, propuestas y vencimientos.">
    <title><?= $pageTitle ?? 'Gestión Comercial' ?> | Gestión Comercial</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <div class="sidebar-logo-text">
                    <h2>Gestión Comercial</h2>
                    <span>Oportunidades y contratos</span>
                </div>
            </div>
        </div>
        
        <div class="sidebar-section-title">Gestión de oportunidades</div>
        
        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>/dashboard" class="sidebar-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>" id="nav-dashboard">
                <i class="fas fa-th-large"></i>
                <span>Resumen</span>
            </a>
            <a href="<?= BASE_URL ?>/convocatorias" class="sidebar-link <?= ($currentPage ?? '') === 'convocatorias' ? 'active' : '' ?>" id="nav-convocatorias">
                <i class="fas fa-search"></i>
                <span>Búsqueda de licitaciones</span>
            </a>
            <a href="<?= BASE_URL ?>/oportunidades" class="sidebar-link <?= ($currentPage ?? '') === 'oportunidades' ? 'active' : '' ?>" id="nav-oportunidades">
                <i class="fas fa-bullseye"></i>
                <span>Oportunidades</span>
            </a>
            <a href="<?= BASE_URL ?>/licitaciones" class="sidebar-link <?= ($currentPage ?? '') === 'licitaciones' ? 'active' : '' ?>" id="nav-licitaciones">
                <i class="fas fa-gavel"></i>
                <span>Licitaciones</span>
            </a>
            <a href="<?= BASE_URL ?>/propuestas" class="sidebar-link <?= ($currentPage ?? '') === 'propuestas' ? 'active' : '' ?>" id="nav-propuestas">
                <i class="fas fa-paper-plane"></i>
                <span>Propuestas</span>
            </a>
            <a href="<?= BASE_URL ?>/contratos" class="sidebar-link <?= ($currentPage ?? '') === 'contratos' ? 'active' : '' ?>" id="nav-contratos">
                <i class="fas fa-file-contract"></i>
                <span>Contratos</span>
            </a>
            <a href="<?= BASE_URL ?>/proyectos" class="sidebar-link <?= ($currentPage ?? '') === 'proyectos' ? 'active' : '' ?>" id="nav-proyectos">
                <i class="fas fa-project-diagram"></i>
                <span>Proyectos</span>
            </a>
            <a href="<?= BASE_URL ?>/clientes" class="sidebar-link <?= ($currentPage ?? '') === 'clientes' ? 'active' : '' ?>" id="nav-clientes">
                <i class="fas fa-building"></i>
                <span>Clientes</span>
            </a>
            <a href="<?= BASE_URL ?>/proveedores" class="sidebar-link <?= ($currentPage ?? '') === 'proveedores' ? 'active' : '' ?>" id="nav-proveedores">
                <i class="fas fa-truck"></i>
                <span>Proveedores</span>
            </a>
            <a href="<?= BASE_URL ?>/usuarios" class="sidebar-link <?= ($currentPage ?? '') === 'usuarios' ? 'active' : '' ?>" id="nav-usuarios">
                <i class="fas fa-users-cog"></i>
                <span>Usuarios</span>
            </a>
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
                    <h1><?= $pageTitle ?? 'Gestión Comercial' ?></h1>
                    <span class="header-subtitle">Datos de demostración · Versión Inicial</span>
                </div>
            </div>
            <div class="top-header-right">
                <?php if (in_array($currentPage ?? '', ['convocatorias','oportunidades','licitaciones','propuestas','contratos','proyectos','clientes','proveedores','usuarios'])): ?>
                    <?php
                    $createLabels = [
                        'convocatorias' => 'Nueva convocatoria',
                        'oportunidades' => 'Nueva oportunidad',
                        'licitaciones' => 'Nueva licitación',
                        'propuestas' => 'Nueva propuesta',
                        'contratos' => 'Nuevo contrato',
                        'proyectos' => 'Nuevo proyecto',
                        'clientes' => 'Nuevo cliente',
                        'proveedores' => 'Nuevo proveedor',
                        'usuarios' => 'Nuevo usuario',
                    ];
                    $label = $createLabels[$currentPage] ?? 'Nuevo';
                    ?>
                    <a href="<?= BASE_URL ?>/?page=<?= $currentPage ?>&action=crear" class="btn btn-primary" id="btn-crear">
                        <i class="fas fa-plus"></i> <?= $label ?>
                    </a>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Content Area -->
        <div class="content-area">



