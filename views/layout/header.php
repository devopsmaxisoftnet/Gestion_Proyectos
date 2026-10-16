<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Proyectos - Oportunidades y Contratos. Centraliza convocatorias, responsables, propuestas y vencimientos.">
    <meta name="referrer" content="no-referrer">
<?php
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
require_once __DIR__ . '/../../helpers/PermisosHelper.php';
$csrfToken = CsrfHelper::getToken();
?>
    <meta name="csrf-token" content="<?= $csrfToken ?>">
    <title><?= $pageTitle ?? 'Gestión de Proyectos' ?> | Gestión de Proyectos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/styles.css') ?: '2.1' ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modals-pipeline.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/modals-pipeline.css') ?: '1.0' ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/pagination.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/pagination.css') ?: '1.0' ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>window.CSRF_TOKEN = "<?= $csrfToken ?>";</script>
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
        /* ─── Global Zero-Overflow Layout Safety & Chevron Responsive Rules ─── */
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }
        .app-layout {
            max-width: 100vw;
            overflow-x: hidden;
        }
        .main-content {
            min-width: 0 !important;
            max-width: calc(100vw - var(--sidebar-width)) !important;
            overflow-x: hidden !important;
            box-sizing: border-box !important;
        }
        .content-area {
            min-width: 0 !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
            box-sizing: border-box !important;
        }
        .process-pipeline-container {
            min-width: 0 !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
            box-sizing: border-box !important;
        }
        /* ─── Process Chevrons / Pipeline Selector (Ultra-Clear Active Selection) ─── */
        .process-chevrons-bar {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            width: 100% !important;
            box-sizing: border-box !important;
            background: #0f172a !important;
            border-radius: 12px 12px 0 0 !important;
            border: 1px solid #e2e8f0 !important;
            border-bottom: none !important;
            overflow: hidden !important;
            height: 56px !important;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08) !important;
        }
        .proc-chevron {
            position: relative !important;
            height: 100% !important;
            box-sizing: border-box !important;
            padding: 0 12px !important;
            cursor: pointer !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: flex !important;
            align-items: center !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
            user-select: none !important;
            min-width: 0 !important;
            overflow: hidden !important;
        }
        .proc-chevron:last-child {
            border-right: none !important;
        }

        /* Inactive: Clean, muted Dark Slate with top phase color stripe */
        .proc-chevron:not(.active) {
            background: #1e293b !important;
            opacity: 0.9 !important;
        }
        .proc-chevron:not(.active):hover {
            background: #273549 !important;
            opacity: 1 !important;
            filter: brightness(1.15) !important;
        }
        .proc-chevron:not(.active)::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 3px !important;
            opacity: 0.7 !important;
        }
        .proc-c1:not(.active)::before { background: #3b82f6 !important; }
        .proc-c2:not(.active)::before { background: #0284c7 !important; }
        .proc-c3:not(.active)::before { background: #0d9488 !important; }
        .proc-c4:not(.active)::before { background: #d97706 !important; }
        .proc-c5:not(.active)::before { background: #7c3aed !important; }

        .proc-chevron:not(.active) .proc-icon {
            font-size: 14px !important;
            width: 26px !important;
            height: 26px !important;
            border-radius: 7px !important;
            background: rgba(255, 255, 255, 0.06) !important;
            color: #94a3b8 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }
        .proc-chevron:not(.active) .proc-chev-title {
            font-size: 11.5px !important;
            font-weight: 600 !important;
            color: #cbd5e1 !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .proc-chevron:not(.active) .proc-chev-sub {
            font-size: 9px !important;
            color: #64748b !important;
            line-height: 1.2 !important;
            margin-top: 1px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        /* ACTIVE Chevron: RADIANT, HIGH-CONTRAST, UNMISTAKABLY SELECTED */
        .proc-chevron.active {
            opacity: 1 !important;
            z-index: 5 !important;
            box-shadow: inset 0 2px 0 0 rgba(255, 255, 255, 0.45), inset 0 -4px 0 0 #ffffff, 0 4px 20px rgba(0, 0, 0, 0.35) !important;
            transform: none !important;
            filter: none !important;
        }
        .proc-c1.active { background: linear-gradient(135deg, #1e40af 0%, #2563eb 55%, #3b82f6 100%) !important; }
        .proc-c2.active { background: linear-gradient(135deg, #075985 0%, #0284c7 55%, #38bdf8 100%) !important; }
        .proc-c3.active { background: linear-gradient(135deg, #115e59 0%, #0d9488 55%, #14b8a6 100%) !important; }
        .proc-c4.active { background: linear-gradient(135deg, #92400e 0%, #d97706 55%, #f59e0b 100%) !important; }
        .proc-c5.active { background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 55%, #a855f7 100%) !important; }

        .proc-chevron.active::after {
            content: 'ACTIVO' !important;
            position: absolute !important;
            top: 5px !important;
            right: 8px !important;
            background: rgba(255, 255, 255, 0.28) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
            font-size: 8px !important;
            font-weight: 800 !important;
            padding: 1px 5px !important;
            border-radius: 4px !important;
            letter-spacing: 0.5px !important;
            line-height: 1.1 !important;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2) !important;
        }

        .proc-chevron.active .proc-icon {
            font-size: 15px !important;
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.25) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.4) !important;
            flex-shrink: 0 !important;
            animation: none !important;
        }
        .proc-chevron.active .proc-chev-title {
            font-size: 12.5px !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            letter-spacing: -0.2px !important;
            line-height: 1.2 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .proc-chevron.active .proc-chev-sub {
            font-size: 9.5px !important;
            color: rgba(255, 255, 255, 0.95) !important;
            font-weight: 500 !important;
            line-height: 1.2 !important;
            margin-top: 1px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .proc-chev-inner {
            display: flex !important;
            align-items: center !important;
            gap: 9px !important;
            min-width: 0 !important;
            width: 100% !important;
        }
        .proc-chev-text {
            min-width: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            flex: 1 !important;
        }

        /* ─── Sidebar Styling (Natural Height, Elegant Spacing, Pinned Footer) ─── */
        .sidebar {
            width: 260px !important;
            height: 100vh !important;
            max-height: 100vh !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            box-sizing: border-box !important;
            padding: 0 !important;
            background: var(--bg-sidebar, linear-gradient(180deg, #1e1145 0%, #2d1b69 50%, #1a0e3e 100%)) !important;
            z-index: 100 !important;
            overflow: hidden !important;
        }
        .sidebar-header {
            padding: 16px 18px 14px !important;
            flex-shrink: 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        .sidebar-logo {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
        }
        .sidebar-logo-icon {
            width: 38px !important;
            height: 38px !important;
            font-size: 17px !important;
            border-radius: 10px !important;
            background: linear-gradient(135deg, #8b5cf6, #3b82f6) !important;
            color: white !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.35) !important;
            flex-shrink: 0 !important;
        }
        .sidebar-logo-text h2 {
            font-size: 14.5px !important;
            line-height: 1.2 !important;
            margin: 0 !important;
            font-weight: 700 !important;
            color: #ffffff !important;
        }
        .sidebar-logo-text span {
            font-size: 11px !important;
            line-height: 1.1 !important;
            color: rgba(255, 255, 255, 0.5) !important;
        }
        .sidebar-section-title {
            padding: 14px 16px 6px !important;
            font-size: 10px !important;
            letter-spacing: 0.08em !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            color: rgba(255, 255, 255, 0.45) !important;
            margin: 0 !important;
            flex-shrink: 0 !important;
        }
        .sidebar-nav {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding: 4px 12px 14px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 2px !important;
            scrollbar-width: thin !important;
            scrollbar-color: rgba(255, 255, 255, 0.15) transparent !important;
        }
        .sidebar-nav::-webkit-scrollbar {
            width: 4px !important;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15) !important;
            border-radius: 4px !important;
        }
        .sidebar-link {
            display: flex !important;
            align-items: center !important;
            padding: 9px 12px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            gap: 11px !important;
            margin-bottom: 2px !important;
            border-radius: 8px !important;
            line-height: 1.3 !important;
            box-sizing: border-box !important;
            color: rgba(255, 255, 255, 0.72) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            text-decoration: none !important;
        }
        .sidebar-link i {
            width: 18px !important;
            font-size: 14px !important;
            text-align: center !important;
            flex-shrink: 0 !important;
            color: rgba(255, 255, 255, 0.75) !important;
            transition: transform 0.2s, color 0.2s !important;
        }
        .sidebar-link span {
            font-size: 13px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .sidebar-link:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.1) !important;
        }
        .sidebar-link:hover i {
            color: #c4b5fd !important;
            transform: scale(1.1) !important;
        }
        .sidebar-link.active {
            color: #ffffff !important;
            background: linear-gradient(90deg, rgba(139, 92, 246, 0.4), rgba(139, 92, 246, 0.12)) !important;
            box-shadow: inset 3.5px 0 0 #a78bfa !important;
            font-weight: 600 !important;
        }
        .sidebar-link.active i {
            color: #a78bfa !important;
            transform: scale(1.1) !important;
        }
        .sidebar-divider {
            margin: 8px 12px !important;
            height: 1px !important;
            background: rgba(255, 255, 255, 0.08) !important;
            flex-shrink: 0 !important;
        }
        .sidebar-footer {
            padding: 12px 14px 14px !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
            background: rgba(18, 11, 40, 0.95) !important;
            backdrop-filter: blur(14px) !important;
            -webkit-backdrop-filter: blur(14px) !important;
            flex-shrink: 0 !important;
            position: sticky !important;
            bottom: 0 !important;
            z-index: 10 !important;
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.25) !important;
        }
        .sidebar-user-compact {
            display: flex !important;
            flex-direction: column !important;
            gap: 7px !important;
            width: 100% !important;
        }
        .sidebar-user-top-row {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            min-width: 0 !important;
        }
        .sidebar-user-avatar {
            width: 34px !important;
            height: 34px !important;
            min-width: 34px !important;
            max-width: 34px !important;
            border-radius: 50% !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: linear-gradient(135deg, #8b5cf6, #3b82f6) !important;
            color: #fff !important;
            flex-shrink: 0 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
            overflow: hidden !important;
            position: relative !important;
        }
        .sidebar-user-avatar img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block !important;
            border-radius: 50% !important;
        }
        .sidebar-user-details {
            flex: 1 !important;
            min-width: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 2px !important;
        }
        .sidebar-user-name-box {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 4px !important;
        }
        .sidebar-user-name-text {
            font-size: 12px !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 135px !important;
            line-height: 1.2 !important;
        }
        .sidebar-cog-btn {
            color: #94a3b8 !important;
            font-size: 12px !important;
            padding: 2px 5px !important;
            border-radius: 4px !important;
            transition: all 0.2s !important;
            line-height: 1 !important;
        }
        .sidebar-cog-btn:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.15) !important;
        }
        .sidebar-user-role-badge {
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
        }
        .sidebar-role-tag {
            font-size: 9px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 1.5px 6px !important;
            border-radius: 4px !important;
            background: rgba(139, 92, 246, 0.28) !important;
            color: #c4b5fd !important;
            border: 1px solid rgba(139, 92, 246, 0.45) !important;
            line-height: 1.1 !important;
        }
        .sidebar-btn-logout {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #fca5a5 !important;
            border: 1px solid rgba(239, 68, 68, 0.35) !important;
            border-radius: 7px !important;
            padding: 6px 10px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            width: 100% !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            margin-top: 4px !important;
        }
        .sidebar-btn-logout:hover {
            background: #ef4444 !important;
            color: #ffffff !important;
            border-color: #ef4444 !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4) !important;
            transform: translateY(-1px) !important;
        }

        /* ─── Ajustes de Alto Contraste y Legibilidad para Tema Claro (Colaborador) ─── */
        .sidebar-status-card {
            margin: 6px 12px;
            padding: 12px 14px;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.28);
            border-radius: 10px;
        }
        .sidebar-status-header {
            font-size: 11.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
            color: #fbbf24;
        }
        .sidebar-status-desc {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.75);
            line-height: 1.35;
        }

        .sidebar.sidebar-colaborador .sidebar-user-name-text {
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        .sidebar.sidebar-colaborador .sidebar-role-tag {
            background: #e0e7ff !important;
            color: #4338ca !important;
            border: 1px solid #c7d2fe !important;
        }
        .sidebar.sidebar-colaborador .sidebar-sin-acceso-tag {
            color: #b45309 !important;
        }
        .sidebar.sidebar-colaborador .sidebar-btn-logout {
            background: #fee2e2 !important;
            color: #b91c1c !important;
            border: 1px solid #fca5a5 !important;
            font-weight: 600 !important;
        }
        .sidebar.sidebar-colaborador .sidebar-btn-logout:hover {
            background: #dc2626 !important;
            color: #ffffff !important;
            border-color: #dc2626 !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25) !important;
        }
        .sidebar.sidebar-colaborador .sidebar-divider {
            background: #e2e8f0 !important;
        }
        .sidebar.sidebar-colaborador .sidebar-status-card {
            margin: 6px 12px !important;
            padding: 12px 14px !important;
            background: #fffbeb !important;
            border: 1px solid #fde68a !important;
            border-radius: 10px !important;
            box-shadow: 0 1px 3px rgba(217, 119, 6, 0.08) !important;
        }
        .sidebar.sidebar-colaborador .sidebar-status-header {
            font-size: 11.5px !important;
            font-weight: 800 !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            margin-bottom: 4px !important;
            color: #b45309 !important;
        }
        .sidebar.sidebar-colaborador .sidebar-status-desc {
            font-size: 11px !important;
            color: #92400e !important;
            line-height: 1.35 !important;
            font-weight: 500 !important;
        }

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
        body.is-modal-view {
            background: #ffffff !important;
            overflow-y: auto !important;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
            width: 100% !important;
            max-width: 100% !important;
        }
        body.is-modal-view::-webkit-scrollbar { width: 5px; }
        body.is-modal-view::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        body.is-modal-view .app-layout {
            min-height: auto !important;
            height: auto !important;
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        body.is-modal-view .sidebar { display: none !important; }
        body.is-modal-view .top-header { display: none !important; }
        body.is-modal-view .process-pipeline-container { display: none !important; }
        body.is-modal-view .mobile-menu-btn { display: none !important; visibility: hidden !important; opacity: 0 !important; pointer-events: none !important; }
        body.is-modal-view .solicitudes-panel,
        body.is-modal-view .solicitudes-panel-overlay,
        body.is-modal-view .global-modal-overlay,
        body.is-modal-view .side-modal,
        body.is-modal-view .side-modal-overlay,
        body.is-modal-view .access-modal-overlay {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        body.is-modal-view .main-content { 
            margin-left: 0 !important; 
            margin-right: 0 !important;
            padding: 0 !important; 
            min-height: auto !important;
            height: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
            box-sizing: border-box !important;
        }
        body.is-modal-view .content-area { 
            padding: 18px 24px 30px !important; 
            margin: 0 !important; 
            box-shadow: none !important; 
            border: none !important; 
            background: #ffffff !important; 
            width: 100% !important;
            max-width: 100% !important;
            animation: none !important;
            min-height: auto !important;
            box-sizing: border-box !important;
        }
        body.is-modal-view .page-header { display: none !important; }
        body.is-modal-view .form-card,
        body.is-modal-view .prof-form-container,
        body.is-modal-view .detail-card,
        body.is-modal-view .content-area > div { 
            box-shadow: none !important; 
            border: none !important; 
            margin: 0 !important; 
            padding: 0 !important; 
            background: transparent !important; 
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            animation: none !important;
        }
        body.is-modal-view form {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        
        /* Modern ultra-compact form inputs inside modal */
        body.is-modal-view .form-group {
            margin-bottom: 8px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        body.is-modal-view .form-group.full-width {
            grid-column: 1 / -1 !important;
            width: 100% !important;
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
        body.is-modal-view textarea.form-control {
            height: auto !important;
            min-height: 70px !important;
            resize: vertical !important;
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
            gap: 12px 18px !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
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
            width: 100% !important;
            box-sizing: border-box !important;
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

        /* ── Modern Animated Collaborator Access Modal & Live ID Badge ── */
        .access-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            animation: modalOverlayFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            padding: 18px;
            box-sizing: border-box;
        }
        .access-modal-overlay.visible { display: flex; }

        .access-modal {
            position: relative;
            background: #ffffff;
            border-radius: 24px;
            padding: 30px 36px 24px;
            max-width: 680px;
            width: 95%;
            text-align: center;
            box-shadow: 0 30px 80px -15px rgba(15, 23, 42, 0.5), 0 0 0 1px rgba(226, 232, 240, 0.9);
            animation: accessModalPop 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden !important;
            box-sizing: border-box;
        }
        @keyframes accessModalPop {
            0% { opacity: 0; transform: scale(0.92) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .access-modal-glow {
            position: absolute;
            top: -140px; left: 50%;
            transform: translateX(-50%);
            width: 380px; height: 380px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.22), rgba(168, 85, 247, 0.1) 50%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: glowPulse 4s ease-in-out infinite alternate;
        }
        @keyframes glowPulse {
            0% { opacity: 0.6; transform: translateX(-50%) scale(0.95); }
            100% { opacity: 1; transform: translateX(-50%) scale(1.08); }
        }
        .access-modal-icon-box {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.45);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
            animation: iconFloat 3.5s ease-in-out infinite;
        }
        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .access-modal-title {
            font-size: 21px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .access-modal-text {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 16px;
            max-width: 540px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── Live ID Badge Card Preview (High-end metallic corporate card) ── */
        .access-id-badge-card {
            background: linear-gradient(145deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%);
            border: 1px solid rgba(129, 140, 248, 0.35);
            border-radius: 16px;
            padding: 16px 20px;
            text-align: left;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 30px -8px rgba(15, 23, 42, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        .access-id-badge-card::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 200%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: skewX(-20deg);
            animation: idBadgeShine 6s infinite;
        }
        @keyframes idBadgeShine {
            0% { left: -100%; }
            20% { left: 100%; }
            100% { left: 100%; }
        }
        .access-id-badge-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .access-id-badge-corp {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 10.5px;
            font-weight: 700;
            color: #a5b4fc;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .access-id-badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 9px;
            border-radius: 9999px;
            background: rgba(245, 158, 11, 0.18);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
            letter-spacing: 0.3px;
        }
        .pulse-beacon {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 8px #f59e0b;
            animation: beaconPulse 1.8s infinite;
        }
        @keyframes beaconPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.4); }
        }
        .access-id-badge-body {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .access-id-badge-avatar {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            font-weight: 800;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
            overflow: hidden;
        }
        .access-id-badge-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .access-id-badge-details {
            flex: 1;
            min-width: 0;
        }
        .access-id-badge-name {
            font-size: 14.5px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .access-id-badge-email {
            font-size: 11.5px;
            color: #94a3b8;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .access-id-badge-tags {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .access-id-tag-admin {
            font-size: 10.5px;
            font-weight: 600;
            color: #e0e7ff;
            background: rgba(99, 102, 241, 0.25);
            border: 1px solid rgba(129, 140, 248, 0.35);
            border-radius: 7px;
            padding: 3px 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .access-id-tag-security {
            font-size: 10px;
            font-weight: 600;
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 7px;
            padding: 3px 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Form Inputs and Quick Chips ── */
        .access-input-group {
            text-align: left;
            margin-bottom: 12px;
        }
        .access-input-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 5px;
            letter-spacing: 0.2px;
        }
        .access-modal-input {
            width: 100%;
            padding: 10px 13px;
            border: 1.5px solid #cbd5e1;
            border-radius: 11px;
            font-size: 12.5px;
            font-weight: 500;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .access-modal-input:focus {
            background: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .access-chips-row {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .access-chip-btn {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 10.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .access-chip-btn:hover {
            background: #eef2ff;
            color: #4338ca;
            border-color: #c7d2fe;
            transform: translateY(-1px);
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
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
            width: 100%;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 4px;
        }
        .access-modal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(79, 70, 229, 0.45);
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
        }

        /* ── Pending Request Banner ── */
        .access-pending-state {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1.5px solid #fde68a;
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-align: left;
            margin-bottom: 12px;
        }
        .access-pending-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: #f59e0b;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
        }
        .access-pending-info h4 {
            margin: 0 0 3px 0;
            font-size: 13.5px;
            font-weight: 700;
            color: #92400e;
        }
        .access-pending-info p {
            margin: 0;
            font-size: 12px;
            color: #b45309;
            line-height: 1.4;
        }
        .btn-refresh-status {
            margin-left: auto;
            background: #ffffff;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .btn-refresh-status:hover {
            background: #fef3c7;
            border-color: #f59e0b;
            transform: scale(1.02);
        }

        /* ── Success box ── */
        .access-modal-success-box {
            background: #f0fdf4;
            border: 1.5px solid #bbf7d0;
            border-radius: 16px;
            padding: 18px 22px;
            text-align: center;
            animation: modalOverlayFadeIn 0.3s ease-out;
            margin-bottom: 10px;
        }
        .access-success-icon {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #10b981;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 8px;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }
        .access-modal-success-box h3 {
            font-size: 15.5px;
            font-weight: 700;
            color: #065f46;
            margin: 0 0 5px 0;
        }
        .access-modal-success-box p {
            font-size: 12.5px;
            color: #047857;
            margin: 0 0 10px 0;
        }
        .access-success-tip {
            font-size: 11.5px;
            color: #065f46;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .access-modal-logout-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #94a3b8;
            text-decoration: none;
            margin-top: 12px;
            transition: all 0.2s;
            font-weight: 600;
        }
        .access-modal-logout-link:hover {
            color: #ef4444;
        }
    </style>
</head>
<?php $isIframeModal = isset($_GET['modal_iframe']) && $_GET['modal_iframe'] == 1; ?>
<body class="<?= $isIframeModal ? 'is-modal-view' : '' ?>">

<?php
// Determinar qué macroproceso está activo según la página actual o GET
// Prioridad: 1) ?fase= explícito, 2) fase declarada por el controlador, 3) fase a la que pertenece la página.
$faseControlador = (isset($faseActiva) && is_int($faseActiva) && $faseActiva >= 1 && $faseActiva <= 5) ? $faseActiva : null;

// Páginas de cada fase (deben coincidir con los grupos del sidebar). Si una página figura en varias fases
// (contratos, profesionales), se respeta la fase en la que el usuario venía navegando.
$paginasPorFase = [
    1 => ['convocatorias', 'oportunidades', 'licitaciones', 'propuestas', 'contratos', 'clientes', 'empresas', 'profesionales'],
    2 => ['proyectos', 'contratos', 'tareas', 'presupuesto', 'asistencias'],
    3 => ['compras', 'almacenes', 'subcontratos', 'equipos', 'profesionales'],
    4 => ['finanzas', 'valorizaciones', 'facturacion'],
    5 => ['analisis', 'rentabilidad', 'reportes'],
];

$faseActiva = 1;
$_faseGet = isset($_GET['fase']) ? (int)$_GET['fase'] : 0;
if ($_faseGet >= 1 && $_faseGet <= 5) {
    $faseActiva = $_faseGet;
} elseif ($faseControlador !== null) {
    $faseActiva = $faseControlador;
} else {
    $_candidatas = [];
    foreach ($paginasPorFase as $_f => $_paginas) {
        if (in_array($currentPage ?? '', $_paginas, true)) { $_candidatas[] = $_f; }
    }
    if (count($_candidatas) === 1) {
        $faseActiva = $_candidatas[0];
    } elseif (count($_candidatas) > 1) {
        $_previa = (int)($_SESSION['fase_activa'] ?? 0);
        $faseActiva = in_array($_previa, $_candidatas, true) ? $_previa : $_candidatas[0];
    }
}

// Determinar si el usuario necesita el modal de acceso
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$uIdSesion = (int)($_SESSION['user']['id'] ?? 0);

// Sincronizar en caliente el estado del colaborador desde la base de datos
if ($userRol === 'usuario' && $uIdSesion > 0) {
    $uDbHeader = dbQueryOne("
        SELECT u.acceso_aprobado, u.rol, u.rol_id, u.tipo_usuario_id, u.area_id, u.cargo_id, u.org_area_id,
               COALESCE(oa.nombre, a.nombre) as area_nombre,
               oc.nombre as cargo_nombre
        FROM usuarios u
        LEFT JOIN org_areas oa ON COALESCE(u.org_area_id, u.area_id) = oa.id
        LEFT JOIN areas a ON u.area_id = a.id
        LEFT JOIN org_cargos oc ON u.cargo_id = oc.id
        WHERE u.id = ? LIMIT 1
    ", [$uIdSesion]);
    if ($uDbHeader) {
        $_SESSION['user']['acceso_aprobado'] = (int)($uDbHeader['acceso_aprobado'] ?? 0);
        if (!empty($uDbHeader['org_area_id']) || !empty($uDbHeader['area_id'])) {
            $_SESSION['user']['area_id'] = $uDbHeader['org_area_id'] ?? $uDbHeader['area_id'];
        }
        if (!empty($uDbHeader['cargo_id'])) {
            $_SESSION['user']['cargo_id'] = $uDbHeader['cargo_id'];
            $_SESSION['user']['cargo_nombre'] = $uDbHeader['cargo_nombre'];
        }
        if (!empty($uDbHeader['area_nombre'])) {
            $_SESSION['user']['area_nombre'] = $uDbHeader['area_nombre'];
        }
    }
}

$accesoAprobado = $_SESSION['user']['acceso_aprobado'] ?? 0;
$necesitaAcceso = ($userRol === 'usuario' && !$accesoAprobado);

// Si el colaborador no está habilitado, queda estrictamente fijado en la Fase 1
if ($necesitaAcceso) {
    $faseActiva = 1;
} elseif ($userRol === 'usuario' && $accesoAprobado && !isset($_GET['fase']) && ($currentPage ?? '') === 'dashboard') {
    // Si el colaborador está aprobado y entra al dashboard sin forzar fase, auto-seleccionar la fase de su área funcional
    $uAreaId = (int)($_SESSION['user']['area_id'] ?? 0);
    if ($uAreaId > 0) {
        $areaDb = dbQueryOne("SELECT codigo FROM org_areas WHERE id = ? LIMIT 1", [$uAreaId]);
        $codArea = strtoupper($areaDb['codigo'] ?? '');
        if (in_array($codArea, ['LOG'])) {
            $faseActiva = 3;
        } elseif (in_array($codArea, ['EJEC', 'PROY', 'SUPV', 'EXPT', 'RRHH', 'CST'])) {
            $faseActiva = 2;
        } elseif (in_array($codArea, ['TES', 'CONT', 'GAF'])) {
            $faseActiva = 4;
        } elseif (in_array($codArea, ['LIC'])) {
            $faseActiva = 1;
        }
    }
}

// Recordar la fase para resolver páginas compartidas entre fases (contratos, profesionales)
if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION['fase_activa'] = $faseActiva; }

// Contar solicitudes pendientes para admin o avisos para colaborador
$solicitudesPendientes = 0;
$notificacionesColaboradorCount = 0;

if (in_array($userRol, ['superadmin', 'admin'])) {
    // Sincronizar automáticamente cualquier colaborador pendiente que no tenga solicitud activa
    dbExecute("
        INSERT INTO solicitudes_acceso (usuario_id, mensaje, estado, created_at)
        SELECT u.id, 'Solicitud de activación y asignación de puesto corporativo.', 'pendiente', NOW()
        FROM usuarios u
        LEFT JOIN solicitudes_acceso sa ON u.id = sa.usuario_id AND sa.estado = 'pendiente'
        WHERE u.rol = 'usuario' AND (u.acceso_aprobado = 0 OR u.acceso_aprobado IS NULL) AND sa.id IS NULL
    ");
    $countResult = dbQueryOne("SELECT COUNT(*) as total FROM solicitudes_acceso WHERE estado = 'pendiente'");
    $solicitudesPendientes = (int)($countResult['total'] ?? 0);
} else {
    // Si es colaborador, contar solicitudes revisadas (aprobadas o rechazadas)
    $countColab = dbQueryOne("SELECT COUNT(*) as total FROM solicitudes_acceso WHERE usuario_id = ? AND estado IN ('aprobada', 'rechazada')", [$uIdSesion]);
    $notificacionesColaboradorCount = (int)($countColab['total'] ?? 0);
    if ($accesoAprobado && $notificacionesColaboradorCount === 0) {
        $notificacionesColaboradorCount = 1;
    }
}
?>

<div class="app-layout <?= (!in_array($userRol, ['superadmin', 'admin'])) ? 'is-colaborador' : '' ?>">
    <!-- Sidebar -->
    <aside class="sidebar <?= (!in_array($userRol, ['superadmin', 'admin'])) ? 'sidebar-colaborador' : '' ?>" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas <?= (!in_array($userRol, ['superadmin', 'admin'])) ? 'fa-user-tie' : 'fa-gem' ?>"></i>
                </div>
                <div class="sidebar-logo-text">
                    <h2>Gestión de Proyectos</h2>
                    <span><?= (!in_array($userRol, ['superadmin', 'admin'])) ? 'Portal Colaborador' : 'Oportunidades y contratos' ?></span>
                </div>
            </div>
            <?php if (!in_array($userRol, ['superadmin', 'admin'])): ?>
            <div class="colab-portal-pill">
                <i class="fas fa-id-badge"></i> Panel Colaborador
            </div>
            <?php endif; ?>
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
            <?php if (PermisosHelper::tieneAcceso('usuarios')): ?>
            <a href="<?= BASE_URL ?>/usuarios<?= $faseActiva > 1 ? '?fase='.$faseActiva : '' ?>" class="sidebar-link <?= ($currentPage ?? '') === 'usuarios' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                <i class="fas fa-users-cog"></i>
                <span>Usuarios</span>
            </a>
            <?php endif; ?>
            <?php if (PermisosHelper::tieneAcceso('organizacion')): ?>
            <a href="<?= BASE_URL ?>/organizacion<?= $faseActiva > 1 ? '?fase='.$faseActiva : '' ?>" class="sidebar-link <?= ($currentPage ?? '') === 'organizacion' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                <i class="fas fa-sitemap"></i>
                <span>Organización</span>
            </a>
            <?php endif; ?>
            
            <?php if ($necesitaAcceso): ?>
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-title">ESTADO DE CUENTA</div>
            <div class="sidebar-status-card">
                <div class="sidebar-status-header">
                    <i class="fas fa-hourglass-half"></i> En Revisión
                </div>
                <div class="sidebar-status-desc">
                    Esperando asignación de Cargo y Área en el Organigrama.
                </div>
            </div>
            <?php else: ?>
            
            <?php if (!in_array($userRol, ['superadmin', 'admin'])): ?>
            <!-- SECCIÓN DEDICADA: MI ÁREA ORGANIZACIONAL (ACCESO INMEDIATO PERMANENTE) -->
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-title" style="color: #059669; font-weight: 800; display:flex; align-items:center; gap:6px;">
                <i class="fas fa-briefcase"></i> MI ÁREA: <?= strtoupper(htmlspecialchars($_SESSION['user']['area_nombre'] ?? 'OPERACIONES')) ?>
            </div>
            
            <div class="sidebar-group" style="display:block;">
                <?php if (PermisosHelper::tieneAcceso('compras')): ?>
                <a href="<?= BASE_URL ?>/compras" class="sidebar-link <?= ($currentPage ?? '') === 'compras' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Órdenes de Compra</span>
                </a>
                <a href="<?= BASE_URL ?>/almacen" class="sidebar-link <?= ($currentPage ?? '') === 'almacenes' ? 'active' : '' ?>">
                    <i class="fas fa-warehouse"></i>
                    <span>Almacenes & Stock</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('proyectos')): ?>
                <a href="<?= BASE_URL ?>/proyectos" class="sidebar-link <?= ($currentPage ?? '') === 'proyectos' ? 'active' : '' ?>">
                    <i class="fas fa-project-diagram"></i>
                    <span>Proyectos</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('contratos')): ?>
                <a href="<?= BASE_URL ?>/contratos" class="sidebar-link <?= ($currentPage ?? '') === 'contratos' ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i>
                    <span>Contratos</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('tareas')): ?>
                <a href="<?= BASE_URL ?>/tareas" class="sidebar-link <?= ($currentPage ?? '') === 'tareas' ? 'active' : '' ?>">
                    <i class="fas fa-sitemap"></i>
                    <span>WBS / Tareas</span>
                </a>
                <a href="<?= BASE_URL ?>/presupuesto" class="sidebar-link <?= ($currentPage ?? '') === 'presupuesto' ? 'active' : '' ?>">
                    <i class="fas fa-balance-scale"></i>
                    <span>Control presupuestal</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('asistencias')): ?>
                <a href="<?= BASE_URL ?>/asistencias" class="sidebar-link <?= ($currentPage ?? '') === 'asistencias' ? 'active' : '' ?>">
                    <i class="fas fa-user-clock"></i>
                    <span>Tareo / RRHH</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('convocatorias')): ?>
                <a href="<?= BASE_URL ?>/convocatorias" class="sidebar-link <?= ($currentPage ?? '') === 'convocatorias' ? 'active' : '' ?>">
                    <i class="fas fa-search"></i>
                    <span>Convocatorias</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('oportunidades')): ?>
                <a href="<?= BASE_URL ?>/oportunidades" class="sidebar-link <?= ($currentPage ?? '') === 'oportunidades' ? 'active' : '' ?>">
                    <i class="fas fa-bullseye"></i>
                    <span>Oportunidades</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('licitaciones')): ?>
                <a href="<?= BASE_URL ?>/licitaciones" class="sidebar-link <?= ($currentPage ?? '') === 'licitaciones' ? 'active' : '' ?>">
                    <i class="fas fa-gavel"></i>
                    <span>Licitaciones</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('propuestas')): ?>
                <a href="<?= BASE_URL ?>/propuestas" class="sidebar-link <?= ($currentPage ?? '') === 'propuestas' ? 'active' : '' ?>">
                    <i class="fas fa-paper-plane"></i>
                    <span>Propuestas</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('clientes')): ?>
                <a href="<?= BASE_URL ?>/clientes" class="sidebar-link <?= ($currentPage ?? '') === 'clientes' ? 'active' : '' ?>">
                    <i class="fas fa-building"></i>
                    <span>Clientes</span>
                </a>
                <?php endif; ?>

                <?php if (PermisosHelper::tieneAcceso('profesionales')): ?>
                <a href="<?= BASE_URL ?>/profesionales" class="sidebar-link <?= ($currentPage ?? '') === 'profesionales' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Profesionales</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="sidebar-divider"></div>
            <div class="sidebar-section-title">Módulos de la Fase <?= $faseActiva ?></div>

            <!-- Fase 1: Gestión de oportunidades -->
            <div class="sidebar-group" id="sidebar-fase-1" style="<?= ($faseActiva === 1) ? 'display:block;' : 'display:none;' ?>">
                <?php if (PermisosHelper::tieneAcceso('convocatorias')): ?>
                <a href="<?= BASE_URL ?>/convocatorias" class="sidebar-link <?= ($currentPage ?? '') === 'convocatorias' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-search"></i>
                    <span>Convocatorias</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('oportunidades')): ?>
                <a href="<?= BASE_URL ?>/oportunidades" class="sidebar-link <?= ($currentPage ?? '') === 'oportunidades' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-bullseye"></i>
                    <span>Oportunidades</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('licitaciones')): ?>
                <a href="<?= BASE_URL ?>/licitaciones" class="sidebar-link <?= ($currentPage ?? '') === 'licitaciones' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-gavel"></i>
                    <span>Licitaciones</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('propuestas')): ?>
                <a href="<?= BASE_URL ?>/propuestas" class="sidebar-link <?= ($currentPage ?? '') === 'propuestas' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-paper-plane"></i>
                    <span>Propuestas</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('contratos')): ?>
                <a href="<?= BASE_URL ?>/contratos" class="sidebar-link <?= ($currentPage ?? '') === 'contratos' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-file-contract"></i>
                    <span>Contratos</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('clientes')): ?>
                <a href="<?= BASE_URL ?>/clientes" class="sidebar-link <?= ($currentPage ?? '') === 'clientes' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-building"></i>
                    <span>Clientes</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('empresas')): ?>
                <a href="<?= BASE_URL ?>/empresas" class="sidebar-link <?= ($currentPage ?? '') === 'empresas' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-city"></i>
                    <span>Empresas</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('profesionales')): ?>
                <a href="<?= BASE_URL ?>/profesionales" class="sidebar-link <?= ($currentPage ?? '') === 'profesionales' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-users-cog"></i>
                    <span>Profesionales</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Fase 2: Planificación y proyectos -->
            <div class="sidebar-group" id="sidebar-fase-2" style="<?= ($faseActiva === 2) ? 'display:block;' : 'display:none;' ?>">
                <?php if (PermisosHelper::tieneAcceso('proyectos')): ?>
                <a href="<?= BASE_URL ?>/proyectos" class="sidebar-link <?= ($currentPage ?? '') === 'proyectos' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-project-diagram"></i>
                    <span>Proyectos</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('contratos')): ?>
                <a href="<?= BASE_URL ?>/contratos?fase=2" class="sidebar-link <?= ($currentPage ?? '') === 'contratos' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-file-contract"></i>
                    <span>Contratos de Obra</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('tareas')): ?>
                <a href="<?= BASE_URL ?>/tareas" class="sidebar-link <?= ($currentPage ?? '') === 'tareas' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-sitemap"></i>
                    <span>WBS / Tareas</span>
                </a>
                <a href="<?= BASE_URL ?>/presupuesto" class="sidebar-link <?= ($currentPage ?? '') === 'presupuesto' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-balance-scale"></i>
                    <span>Control presupuestal</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('asistencias')): ?>
                <a href="<?= BASE_URL ?>/asistencias" class="sidebar-link <?= ($currentPage ?? '') === 'asistencias' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-user-clock"></i>
                    <span>Tareo / RRHH</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Fase 3: Ejecución y operación -->
            <div class="sidebar-group" id="sidebar-fase-3" style="<?= ($faseActiva === 3) ? 'display:block;' : 'display:none;' ?>">
                <?php if (PermisosHelper::tieneAcceso('compras')): ?>
                <a href="<?= BASE_URL ?>/compras" class="sidebar-link <?= ($currentPage ?? '') === 'compras' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-shopping-cart"></i>
                    <span>Órdenes de Compra</span>
                </a>
                <a href="<?= BASE_URL ?>/almacen" class="sidebar-link <?= ($currentPage ?? '') === 'almacenes' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-warehouse"></i>
                    <span>Almacenes & Stock</span>
                </a>
                <a href="<?= BASE_URL ?>/compras?tipo=servicio" class="sidebar-link <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-concierge-bell"></i>
                    <span>Servicios</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('personal')): ?>
                <a href="<?= BASE_URL ?>/profesionales" class="sidebar-link <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-user-friends"></i>
                    <span>Personal & Equipos</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('subcontratos')): ?>
                <a href="<?= BASE_URL ?>/subcontratos" class="sidebar-link <?= ($currentPage ?? '') === 'subcontratos' ? 'active' : '' ?> <?= $rLink ?>" <?= $rData ?>>
                    <i class="fas fa-handshake"></i>
                    <span>Subcontratos</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Fase 4: Control económico y financiero -->
            <div class="sidebar-group" id="sidebar-fase-4" style="<?= ($faseActiva === 4) ? 'display:block;' : 'display:none;' ?>">
                <?php if (PermisosHelper::tieneAcceso('tesoreria')): ?>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-arrow-down"></i>
                    <span>Ingresos / Egresos</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('valorizaciones')): ?>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-calculator"></i>
                    <span>Valorizaciones</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('facturacion')): ?>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Facturación</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('cuentas')): ?>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-wallet"></i>
                    <span>Cuentas por cobrar y pagar</span>
                </a>
                <?php endif; ?>
                <?php if (PermisosHelper::tieneAcceso('tesoreria')): ?>
                <a href="javascript:void(0)" class="sidebar-link disabled" style="opacity: 0.5; cursor: default;">
                    <i class="fas fa-balance-scale"></i>
                    <span>Tesorería & Presupuesto</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Fase 5: Análisis y rentabilidad -->
            <div class="sidebar-group" id="sidebar-fase-5" style="<?= ($faseActiva === 5) ? 'display:block;' : 'display:none;' ?>">
                <?php if (PermisosHelper::tieneAcceso('analisis')): ?>
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
                <?php endif; ?>
            </div>
            <?php endif; // Fin if (!$necesitaAcceso) ?>
        </nav>
        
        <div class="sidebar-footer">
            <?php
            $userName = $_SESSION['user']['nombre'] ?? 'Usuario';
            $initials = strtoupper(substr($userName, 0, 2));
            ?>
            <div class="sidebar-user-compact">
                <div class="sidebar-user-top-row">
                    <div class="sidebar-user-avatar">
                        <?php if(!empty($_SESSION['user']['avatar'])): ?>
                            <img src="<?= htmlspecialchars($_SESSION['user']['avatar']) ?>" alt="Avatar" referrerpolicy="no-referrer" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                            <span style="display:none;"><?= $initials ?></span>
                        <?php else: ?>
                            <span><?= $initials ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sidebar-user-details">
                        <div class="sidebar-user-name-box">
                            <span class="sidebar-user-name-text" title="<?= htmlspecialchars($userName) ?>"><?= htmlspecialchars($userName) ?></span>
                            <?php if (in_array($userRol, ['superadmin', 'admin'])): ?>
                            <a href="javascript:void(0)" onclick="abrirGlobalModal('Configuración del Sistema', '<?= BASE_URL ?>/configuracion?modal_iframe=1', 'fas fa-cog', 'Datos de la empresa y sincronización con Google Drive', 'lg')" 
                               class="sidebar-cog-btn" title="Configurar Empresa">
                                <i class="fas fa-cog"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="sidebar-user-role-badge">
                            <?php 
                            $badgeRolTexto = !empty($_SESSION['user']['cargo_nombre']) 
                                ? $_SESSION['user']['cargo_nombre'] 
                                : ($userRol === 'usuario' ? 'Colaborador' : $userRol);
                            ?>
                            <span class="sidebar-role-tag" title="<?= htmlspecialchars($badgeRolTexto) ?>"><?= htmlspecialchars($badgeRolTexto) ?></span>
                            <?php if ($necesitaAcceso): ?>
                                <span class="sidebar-sin-acceso-tag" style="font-size: 8.5px; font-weight: 700;">· Sin acceso</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/auth/logout" class="sidebar-btn-logout" id="btn-sidebar-logout" title="Cerrar sesión de la cuenta">
                    <i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span>
                </a>
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
                    <span class="header-subtitle"><?= (!in_array($userRol, ['superadmin', 'admin'])) ? 'Portal Colaborador · Gestión de Proyectos' : 'Datos de demostración · Versión Inicial' ?></span>
                </div>
            </div>
            <div class="top-header-right">
                <?php if (in_array($userRol, ['superadmin', 'admin'])): ?>
                    <button class="header-bell-btn" id="btn-solicitudes" onclick="abrirPanelSolicitudes()" title="Solicitudes de acceso">
                        <i class="fas fa-bell"></i>
                        <?php if ($solicitudesPendientes > 0): ?>
                            <span class="badge-count" id="badge-solicitudes"><?= $solicitudesPendientes ?></span>
                        <?php endif; ?>
                    </button>
                <?php else: ?>
                    <button class="header-bell-btn" id="btn-notificaciones-colab" onclick="abrirPanelNotificacionesColaborador()" title="Mis notificaciones de acceso" style="position: relative;">
                        <i class="fas fa-bell"></i>
                        <span class="badge-count" id="badge-notif-colab" style="<?= ($accesoAprobado || $notificacionesColaboradorCount > 0) ? 'display:flex;' : 'display:none;' ?> <?= $accesoAprobado ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%); width: 18px; height: 18px; font-size: 9px;' : '' ?>">
                            <?= $accesoAprobado ? '<i class="fas fa-check"></i>' : ($notificacionesColaboradorCount > 0 ? $notificacionesColaboradorCount : '') ?>
                        </span>
                    </button>
                <?php endif; ?>
                <?php if (in_array($currentPage ?? '', ['convocatorias','oportunidades','licitaciones','propuestas','contratos','proyectos','clientes','proveedores','usuarios']) && !$necesitaAcceso && ($currentPage !== 'usuarios' || in_array($userRol, ['superadmin', 'admin']))): ?>
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
                        'usuarios' => 'Configura el Área y Cargo oficial en el Organigrama corporativo.',
                    ];
                    $label = $createLabels[$currentPage] ?? 'Nuevo';
                    $modalTitle = ($currentPage === 'convocatorias') ? 'Registrar convocatoria detectada' : (($currentPage === 'usuarios') ? 'Nuevo Usuario Corporativo' : $label);
                    $sub = $createSubtitles[$currentPage] ?? 'Ventana de registro y configuración del sistema';
                    $icon = ($currentPage === 'convocatorias') ? 'fas fa-bullhorn' : (($currentPage === 'usuarios') ? 'fas fa-id-card-alt' : 'fas fa-plus-circle');
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
                <div class="proc-chevron proc-c1 <?= ($faseActiva === 1) ? 'active' : '' ?>" onclick="<?= $necesitaAcceso ? 'return false;' : "window.location.href='" . BASE_URL . "/dashboard?fase=1'" ?>" id="chev-fase-1" title="Gestión de Oportunidades" style="<?= $necesitaAcceso ? 'cursor: default;' : '' ?>">
                    <div class="proc-chev-inner">
                        <i class="far fa-file-alt proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">1. Gestión de oportunidades</span>
                            <span class="proc-chev-sub">Licitaciones · Propuestas · Contratos · Clientes</span>
                        </div>
                    </div>
                </div>

                <!-- Fase 2 -->
                <div class="proc-chevron proc-c2 <?= ($faseActiva === 2) ? 'active' : '' ?>" onclick="<?= $necesitaAcceso ? 'return false;' : "window.location.href='" . BASE_URL . "/dashboard?fase=2'" ?>" id="chev-fase-2" title="<?= $necesitaAcceso ? 'Bloqueado: Disponible tras la asignación de puesto en el Organigrama' : 'Clic para ver módulos de Planificación y Proyectos' ?>" style="<?= $necesitaAcceso ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : '' ?>">
                    <div class="proc-chev-inner">
                        <i class="far fa-calendar-alt proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">2. Planificación y proyectos</span>
                            <span class="proc-chev-sub">Proyectos · WBS / EDT · Cronograma</span>
                        </div>
                    </div>
                </div>

                <!-- Fase 3 -->
                <div class="proc-chevron proc-c3 <?= ($faseActiva === 3) ? 'active' : '' ?>" onclick="<?= $necesitaAcceso ? 'return false;' : "window.location.href='" . BASE_URL . "/dashboard?fase=3'" ?>" id="chev-fase-3" title="<?= $necesitaAcceso ? 'Bloqueado: Disponible tras la asignación de puesto en el Organigrama' : 'Clic para ver módulos de Ejecución y Operación' ?>" style="<?= $necesitaAcceso ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : '' ?>">
                    <div class="proc-chev-inner">
                        <i class="fas fa-cog proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">3. Ejecución y operación</span>
                            <span class="proc-chev-sub">Compras · Almacenes · Servicios · Equipos</span>
                        </div>
                    </div>
                </div>

                <!-- Fase 4 -->
                <div class="proc-chevron proc-c4 <?= ($faseActiva === 4) ? 'active' : '' ?>" onclick="<?= $necesitaAcceso ? 'return false;' : "window.location.href='" . BASE_URL . "/dashboard?fase=4'" ?>" id="chev-fase-4" title="<?= $necesitaAcceso ? 'Bloqueado: Disponible tras la asignación de puesto en el Organigrama' : 'Clic para ver módulos de Control Económico y Financiero' ?>" style="<?= $necesitaAcceso ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : '' ?>">
                    <div class="proc-chev-inner">
                        <i class="fas fa-coins proc-icon"></i>
                        <div class="proc-chev-text">
                            <span class="proc-chev-title">4. Control económico y financiero</span>
                            <span class="proc-chev-sub">Ingresos · Valorizaciones · Facturación</span>
                        </div>
                    </div>
                </div>

                <!-- Fase 5 -->
                <div class="proc-chevron proc-c5 <?= ($faseActiva === 5) ? 'active' : '' ?>" onclick="<?= $necesitaAcceso ? 'return false;' : "window.location.href='" . BASE_URL . "/dashboard?fase=5'" ?>" id="chev-fase-5" title="<?= $necesitaAcceso ? 'Bloqueado: Disponible tras la asignación de puesto en el Organigrama' : 'Clic para ver módulos de Análisis y Rentabilidad' ?>" style="<?= $necesitaAcceso ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : '' ?>">
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
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; margin-bottom:20px; display:flex; align-items:center; gap:12px; box-shadow:0 2px 8px rgba(239,68,68,0.08);">
                <i class="fas fa-exclamation-circle" style="font-size:18px;"></i>
                <span style="font-weight:500; font-size:13.5px;"><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; padding:12px 18px; border-radius:10px; margin-bottom:20px; display:flex; align-items:center; gap:12px; box-shadow:0 2px 8px rgba(16,185,129,0.08);">
                <i class="fas fa-check-circle" style="font-size:18px;"></i>
                <span style="font-weight:500; font-size:13.5px;"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

<?php if (!$isIframeModal): ?>
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
<?php endif; ?>


<?php if (!$isIframeModal): ?>
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

<!-- PANEL LATERAL DE NOTIFICACIONES (COLABORADOR) -->
<?php if (!in_array($userRol, ['superadmin', 'admin'])): ?>
<div class="solicitudes-panel-overlay" id="colabNotifOverlay" onclick="cerrarPanelNotificacionesColaborador()"></div>
<div class="solicitudes-panel" id="colabNotifPanel">
    <div class="sol-panel-header">
        <div class="sol-panel-header-left">
            <div class="sol-panel-header-icon" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #059669; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.15);">
                <i class="fas fa-bell"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 16.5px; font-weight: 700; color: #0f172a;">Mis Notificaciones</h3>
                <span class="sol-panel-subtitle" id="colabNotifSubtitle">Estado de cuenta y autorizaciones</span>
            </div>
        </div>
        <button class="sol-panel-close" onclick="cerrarPanelNotificacionesColaborador()" title="Cerrar panel (Esc)">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="sol-panel-body" id="colabNotifBody">
        <div class="sol-empty">
            <div class="modal-spinner"></div>
            <p style="margin-top:12px;">Cargando notificaciones...</p>
        </div>
    </div>
</div>
<?php endif; ?>
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
        const csrfToken = window.CSRF_TOKEN || '';
        const separator = url.includes('?') ? '&' : '?';
        const finalUrl = url.includes('csrf_token=') ? url : (url + separator + 'csrf_token=' + encodeURIComponent(csrfToken));
        btn.href = finalUrl;
        
        btn.onclick = function(e) {
            e.preventDefault();
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = finalUrl;
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
        };

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

function cerrarAccessModal(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const overlay = document.getElementById('accessModalOverlay');
    if (overlay) {
        overlay.classList.remove('visible');
    }
}

// Interceptores de modal desactivados para evitar popups invasivos

// ── Helpers interactivos del Modal de Acceso Colaborador ──
function alCambiarAreaModal(areaSel) {
    const areaId = areaSel ? areaSel.value : '';
    const cargoSel = document.getElementById('accessCargoId');
    if (!cargoSel) return;
    
    // Si se seleccionó un área, filtrar u orientar los cargos
    Array.from(cargoSel.options).forEach(function(opt) {
        if (!opt.value) return;
        const cArea = opt.getAttribute('data-area');
        if (!areaId || cArea === areaId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
    
    // Si el cargo actual no coincide con la nueva área, deseleccionarlo
    const optActual = cargoSel.options[cargoSel.selectedIndex];
    if (areaId && optActual && optActual.value && optActual.getAttribute('data-area') !== areaId) {
        cargoSel.value = '';
    }
    actualizarPreviewCredencial();
}

function alCambiarCargoModal(cargoSel) {
    if (!cargoSel) return;
    const opt = cargoSel.options[cargoSel.selectedIndex];
    if (opt && opt.value) {
        const areaId = opt.getAttribute('data-area');
        const areaSel = document.getElementById('accessAreaId');
        if (areaSel && areaId) {
            areaSel.value = areaId;
        }
    }
    actualizarPreviewCredencial();
}

function actualizarPreviewCredencial() {
    const cargoSel = document.getElementById('accessCargoId');
    const areaSel = document.getElementById('accessAreaId');
    const previewCargo = document.getElementById('previewCargoNombre');
    const previewArea = document.getElementById('previewAreaNombre');
    const previewNivel = document.getElementById('previewBadgeNivel');
    const previewCard = document.getElementById('accessIdCardPreview');
    
    const optCargo = cargoSel && cargoSel.selectedIndex >= 0 ? cargoSel.options[cargoSel.selectedIndex] : null;
    const optArea = areaSel && areaSel.selectedIndex >= 0 ? areaSel.options[areaSel.selectedIndex] : null;
    
    if (optCargo && optCargo.value) {
        const cNombre = optCargo.getAttribute('data-nombre') || optCargo.text;
        const cCodigo = optCargo.getAttribute('data-codigo') || '';
        const aNombre = optCargo.getAttribute('data-area-nombre') || (optArea ? optArea.text : '');
        const cNivel = optCargo.getAttribute('data-nivel') || '';
        
        if (previewCargo) previewCargo.textContent = cNombre + (cCodigo ? ' [' + cCodigo + ']' : '');
        if (previewArea) previewArea.textContent = aNombre;
        if (previewNivel) {
            previewNivel.style.display = 'inline-block';
            previewNivel.textContent = 'NIVEL ' + cNivel;
        }
        if (previewCard) {
            previewCard.style.borderColor = 'rgba(129, 140, 248, 0.7)';
            previewCard.style.boxShadow = '0 12px 30px -5px rgba(99, 102, 241, 0.4)';
        }
    } else {
        if (previewCargo) previewCargo.textContent = 'Selecciona tu Cargo';
        if (previewArea) previewArea.textContent = optArea && optArea.value ? optArea.text.split('[')[0].trim() : 'Área no seleccionada';
        if (previewNivel) previewNivel.style.display = 'none';
        if (previewCard) {
            previewCard.style.borderColor = 'rgba(129, 140, 248, 0.35)';
            previewCard.style.boxShadow = '0 10px 25px -5px rgba(15, 23, 42, 0.35)';
        }
    }
}

function agregarSugerenciaMensaje(texto) {
    const txt = document.getElementById('accessMessage');
    if (!txt) return;
    txt.value = 'Hola, solicito activación para ' + texto + '.';
    txt.focus();
}

// ── Solicitar acceso (El colaborador solicita activación; Admin delega Cargo y Área) ──
function solicitarAcceso() {
    const btn = document.getElementById('btnSolicitarAcceso');
    const msgInput = document.getElementById('accessMessage');
    const mensaje = msgInput ? msgInput.value.trim() : '';
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Enviando solicitud...';
    }
    
    fetch(BASE_URL + '/auth/solicitarAcceso', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'mensaje=' + encodeURIComponent(mensaje)
    })
    .then(r => r.json())
    .then(data => {
        const formEl = document.getElementById('accessRequestForm');
        if (formEl) formEl.style.display = 'none';
        
        const resultEl = document.getElementById('accessResult');
        if (resultEl) {
            resultEl.style.display = 'block';
            if (data.success) {
                resultEl.innerHTML = `
                    <div class="access-modal-success-box">
                        <div class="access-success-icon"><i class="fas fa-check"></i></div>
                        <h3>¡Solicitud Enviada con Éxito!</h3>
                        <p>${escapeHtml(data.message)}</p>
                        <div class="access-success-tip">
                            <i class="fas fa-shield-alt"></i>
                            <span>El Administrador te asignará tu Área y Cargo en el ERP. Te notificaremos al habilitar tu acceso.</span>
                        </div>
                    </div>
                `;
            } else {
                resultEl.innerHTML = `
                    <div class="access-modal-success-box" style="background:#fffbeb; border-color:#fef3c7; color:#b45309;">
                        <div class="access-success-icon" style="background:#f59e0b;"><i class="fas fa-info"></i></div>
                        <h3 style="color:#92400e;">Aviso</h3>
                        <p style="color:#b45309;">${escapeHtml(data.message)}</p>
                    </div>
                `;
            }
        }
    })
    .catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Reintentar Solicitud';
        }
        alert('Ocurrió un error al enviar la solicitud. Por favor intenta nuevamente.');
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

            if (sol.cargo_nombre) {
                html += '<div style="margin-top:6px; font-size:11.5px; color:#4338ca; background:#eef2ff; border:1px solid #c7d2fe; border-radius:8px; padding:4px 9px; display:inline-flex; align-items:center; gap:6px;">';
                html += '<i class="fas fa-id-badge" style="color:#6366f1;"></i> Puesto Solicitado: <strong>' + escapeHtml(sol.cargo_nombre) + '</strong> (' + escapeHtml(sol.area_nombre || '') + ')';
                html += '</div>';
            } else if (isPendiente) {
                html += '<div style="margin-top:6px; font-size:11.5px; color:#b45309; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:4px 9px; display:inline-flex; align-items:center; gap:6px;">';
                html += '<i class="fas fa-user-tag" style="color:#f59e0b;"></i> <span>Asignar Cargo y Área abajo para aprobar</span>';
                html += '</div>';
            }
            
            if (isPendiente) {
                html += '<div class="sol-grid-selects" style="margin-top:10px;">';
                
                // Select Cargo Corporativo (Matriz de Permisos)
                html += '<div class="sol-select-group" style="grid-column: 1 / -1;">';
                html += '<label style="font-weight:700; color:#1e293b; font-size:11px; display:flex; align-items:center; gap:5px;"><i class="fas fa-briefcase" style="color:#6366f1;"></i> Cargo Corporativo (Asigna Permisos)</label>';
                html += '<select class="sol-select" id="solCargo' + sol.id + '" onchange="alCambiarCargoSolicitud(this, ' + sol.id + ')" style="font-weight:600;">';
                html += '<option value="">-- Asignar Cargo Corporativo --</option>';
                if (data.cargos && data.cargos.length) {
                    data.cargos.forEach(function(c) {
                        const sel = (sol.cargo_id && sol.cargo_id == c.id) ? 'selected' : '';
                        html += '<option value="' + c.id + '" data-area="' + c.area_id + '" ' + sel + '>Nivel ' + c.nivel_jerarquico + ' · ' + escapeHtml(c.nombre) + ' (' + escapeHtml(c.area_nombre) + ')</option>';
                    });
                }
                html += '</select>';
                html += '</div>';

                // Select área
                html += '<div class="sol-select-group">';
                html += '<label style="font-weight:700; color:#1e293b; font-size:11px; display:flex; align-items:center; gap:5px;"><i class="fas fa-building" style="color:#0284c7;"></i> Área Organizacional</label>';
                html += '<select class="sol-select" id="solArea' + sol.id + '">';
                html += '<option value="">Seleccionar área...</option>';
                if (data.areas && data.areas.length) {
                    data.areas.forEach(function(a) {
                        const sel = (sol.area_id && sol.area_id == a.id) ? 'selected' : '';
                        html += '<option value="' + a.id + '" ' + sel + '>' + escapeHtml(a.nombre) + '</option>';
                    });
                }
                html += '</select>';
                html += '</div>';

                // Select tipo usuario
                html += '<div class="sol-select-group">';
                html += '<label style="font-weight:700; color:#1e293b; font-size:11px; display:flex; align-items:center; gap:5px;"><i class="fas fa-id-badge" style="color:#10b981;"></i> Nivel de Sistema</label>';
                html += '<select class="sol-select" id="solTipo' + sol.id + '">';
                data.tipos.forEach(function(t) {
                    const sel = (t.nombre.toLowerCase().includes('colaborador') || t.id == 2) ? 'selected' : '';
                    html += '<option value="' + t.id + '" ' + sel + '>' + escapeHtml(t.nombre) + '</option>';
                });
                html += '</select>';
                html += '</div>';
                
                html += '</div>';
                
                html += '<div class="sol-card-buttons">';
                html += '<button class="sol-btn-approve" id="btn-approve-' + sol.id + '" onclick="gestionarSolicitud(' + sol.id + ', \'aprobar\')"><i class="fas fa-check"></i> Aprobar y Asignar Cargo</button>';
                html += '<button class="sol-btn-reject" id="btn-reject-' + sol.id + '" onclick="gestionarSolicitud(' + sol.id + ', \'rechazar\')"><i class="fas fa-times"></i> Rechazar</button>';
                html += '</div>';
            } else if (sol.estado === 'aprobada') {
                html += '<div class="sol-assigned-info" style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">';
                if (sol.cargo_nombre) html += '<span class="sol-assigned-item" style="background:#ede9fe; color:#6d28d9; border:1px solid #ddd6fe; border-radius:6px; padding:3px 8px; font-size:11px; font-weight:600;"><i class="fas fa-briefcase"></i> ' + escapeHtml(sol.cargo_nombre) + '</span>';
                if (sol.area_nombre) html += '<span class="sol-assigned-item" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; border-radius:6px; padding:3px 8px; font-size:11px; font-weight:600;"><i class="fas fa-sitemap"></i> ' + escapeHtml(sol.area_nombre) + '</span>';
                if (sol.tipo_nombre) html += '<span class="sol-assigned-item" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:6px; padding:3px 8px; font-size:11px; font-weight:600;"><i class="fas fa-user-tag"></i> ' + escapeHtml(sol.tipo_nombre) + '</span>';
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

function alCambiarCargoSolicitud(sel, solId) {
    const opt = sel.options[sel.selectedIndex];
    const aId = opt ? opt.getAttribute('data-area') : '';
    if (aId) {
        const aSelect = document.getElementById('solArea' + solId);
        if (aSelect) aSelect.value = aId;
    }
}

function gestionarSolicitud(id, accion) {
    const cargoSelect = document.getElementById('solCargo' + id);
    const tipoSelect = document.getElementById('solTipo' + id);
    const areaSelect = document.getElementById('solArea' + id);
    
    const cargoId = cargoSelect ? cargoSelect.value : '';
    const tipoId = tipoSelect ? tipoSelect.value : '';
    const areaId = areaSelect ? areaSelect.value : '';
    
    if (accion === 'aprobar') {
        let hasError = false;
        if (!cargoId && cargoSelect) {
            cargoSelect.style.borderColor = '#ef4444';
            cargoSelect.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
            hasError = true;
        } else if (cargoSelect) {
            cargoSelect.style.borderColor = '#e2e8f0';
            cargoSelect.style.boxShadow = 'none';
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
    
    const body = 'solicitud_id=' + id + '&accion=' + accion + '&tipo_usuario_id=' + tipoId + '&area_id=' + areaId + '&cargo_id=' + cargoId;
    
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

// ── Panel de notificaciones (Colaborador) ──
function abrirPanelNotificacionesColaborador() {
    const overlay = document.getElementById('colabNotifOverlay');
    const panel = document.getElementById('colabNotifPanel');
    
    if (overlay) {
        overlay.classList.add('visible');
        overlay.style.display = 'block';
    }
    if (panel) {
        panel.classList.add('visible');
        panel.style.right = '0';
    }
    
    cargarNotificacionesColaborador();
}

function cerrarPanelNotificacionesColaborador() {
    const overlay = document.getElementById('colabNotifOverlay');
    const panel = document.getElementById('colabNotifPanel');
    
    if (overlay) {
        overlay.classList.remove('visible');
        overlay.style.display = 'none';
    }
    if (panel) {
        panel.classList.remove('visible');
        panel.style.right = '-520px';
    }
}

function cargarNotificacionesColaborador() {
    const body = document.getElementById('colabNotifBody');
    const subtitle = document.getElementById('colabNotifSubtitle');
    if (!body) return;

    fetch(BASE_URL + '/auth/notificacionesColaborador')
    .then(r => r.json())
    .then(data => {
        const esAprobado = (parseInt(data.acceso_aprobado) === 1);
        const solicitudes = data.solicitudes || [];
        const ultimaSol = solicitudes.length > 0 ? solicitudes[0] : null;

        if (subtitle) {
            subtitle.textContent = esAprobado 
                ? 'Cuenta aprobada y verificada' 
                : (ultimaSol && ultimaSol.estado === 'pendiente' ? 'Solicitud pendiente de revisión' : 'Bandeja de avisos');
        }

        // Actualizar icono/badge en la campanita
        const bellBtn = document.getElementById('btn-notificaciones-colab');
        let bellBadge = document.getElementById('badge-notif-colab');
        if (!bellBadge && bellBtn) {
            bellBadge = document.createElement('span');
            bellBadge.id = 'badge-notif-colab';
            bellBadge.className = 'badge-count';
            bellBtn.appendChild(bellBadge);
        }
        if (bellBadge) {
            if (esAprobado) {
                bellBadge.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                bellBadge.style.width = '18px';
                bellBadge.style.height = '18px';
                bellBadge.innerHTML = '<i class="fas fa-check" style="font-size:8px;"></i>';
                bellBadge.style.display = 'flex';
            } else if (solicitudes.length > 0) {
                const count = solicitudes.filter(s => s.estado === 'aprobada' || s.estado === 'rechazada').length;
                if (count > 0) {
                    bellBadge.textContent = count;
                    bellBadge.style.display = 'flex';
                }
            }
        }

        let html = '';

        if (esAprobado) {
            html += `
                <div class="sol-card" style="border-left: 4px solid #10b981; background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);">
                    <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 12px;">
                        <div style="width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #065f46;">¡Acceso Autorizado!</h4>
                                <span class="sol-status-badge sol-status-aprobada" style="margin:0;"><span class="sol-badge-dot"></span>Aprobado</span>
                            </div>
                            <p style="margin: 4px 0 0 0; font-size: 12.5px; color: #047857; line-height: 1.4;">
                                El administrador ha revisado y habilitado tu cuenta para colaborar en el sistema.
                            </p>
                        </div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.9); border: 1px solid #d1fae5; border-radius: 14px; padding: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 13px;">
                                <i class="fas fa-id-badge"></i>
                            </div>
                            <div>
                                <span style="font-size: 10.5px; color: #64748b; font-weight: 600; text-transform: uppercase; display: block;">Cargo / Puesto Asignado</span>
                                <strong style="font-size: 13px; color: #1e293b;">${escapeHtml(data.cargo_nombre || data.tipo_nombre || 'Colaborador Oficial')}</strong>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 13px;">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <span style="font-size: 10.5px; color: #64748b; font-weight: 600; text-transform: uppercase; display: block;">Área Asignada</span>
                                <strong style="font-size: 13px; color: #1e293b;">${escapeHtml(data.area_nombre || 'General')}</strong>
                            </div>
                        </div>
                    </div>
            `;

            if (ultimaSol && ultimaSol.admin_nombre) {
                html += `
                    <div style="margin-top: 12px; font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-user-shield" style="color: #10b981;"></i>
                        <span>Aprobado por: <strong style="color: #334155;">${escapeHtml(ultimaSol.admin_nombre)}</strong></span>
                        ${ultimaSol.fecha_revision ? `<span style="margin-left: auto; color: #94a3b8;">${escapeHtml(ultimaSol.fecha_revision)}</span>` : ''}
                    </div>
                `;
            }

            if (necesitaAcceso) {
                html += `
                    <button onclick="window.location.reload()" style="margin-top: 14px; width: 100%; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 11px; border-radius: 12px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); transition: all 0.2s;">
                        <i class="fas fa-check-double"></i> Actualizar ventana para comenzar
                    </button>
                `;
            } else {
                html += `
                    <div style="margin-top: 12px; padding: 8px 12px; background: rgba(16, 185, 129, 0.08); border-radius: 10px; font-size: 12px; color: #065f46; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-shield-alt"></i> Permisos activos para navegar y gestionar en tu área.
                    </div>
                `;
            }

            html += `</div>`;
        } else if (ultimaSol && ultimaSol.estado === 'pendiente') {
            html += `
                <div class="sol-card" style="border-left: 4px solid #f59e0b; background: linear-gradient(180deg, #ffffff 0%, #fffbeb 100%);">
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <div style="width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #92400e;">Solicitud en Revisión</h4>
                                <span class="sol-status-badge sol-status-pendiente" style="margin:0;"><span class="sol-badge-dot"></span>Pendiente</span>
                            </div>
                            <p style="margin: 4px 0 0 0; font-size: 12.5px; color: #b45309; line-height: 1.4;">
                                Tu solicitud de acceso fue enviada. El administrador asignará tu puesto y área de trabajo.
                            </p>
                        </div>
                    </div>
                    ${ultimaSol.mensaje ? `
                        <div class="sol-card-message" style="margin-top: 14px; background: rgba(255, 255, 255, 0.9); border: 1px dashed #fde68a;">
                            <i class="fas fa-quote-left sol-quote-icon" style="color: #f59e0b;"></i>
                            <span>${escapeHtml(ultimaSol.mensaje)}</span>
                        </div>
                    ` : ''}
                    <div style="margin-top: 12px; font-size: 11.5px; color: #92400e; display: flex; align-items: center; gap: 6px;">
                        <i class="far fa-clock"></i>
                        <span>Enviada el: ${escapeHtml(ultimaSol.created_at || 'Recientemente')}</span>
                    </div>
                </div>
            `;
        } else if (ultimaSol && ultimaSol.estado === 'rechazada') {
            html += `
                <div class="sol-card" style="border-left: 4px solid #ef4444; background: linear-gradient(180deg, #ffffff 0%, #fef2f2 100%);">
                    <div style="display: flex; align-items: flex-start; gap: 14px;">
                        <div style="width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #991b1b;">Acceso No Aprobado</h4>
                                <span class="sol-status-badge sol-status-rechazada" style="margin:0;"><span class="sol-badge-dot"></span>Rechazada</span>
                            </div>
                            <p style="margin: 4px 0 0 0; font-size: 12.5px; color: #b91c1c; line-height: 1.4;">
                                El administrador ha denegado la solicitud de acceso para esta cuenta.
                            </p>
                            ${ultimaSol.observaciones ? `<p style="margin: 8px 0 0 0; font-size: 12px; color: #7f1d1d; background: #fee2e2; padding: 6px 10px; border-radius: 8px;"><strong>Observación:</strong> ${escapeHtml(ultimaSol.observaciones)}</p>` : ''}
                        </div>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="sol-empty">
                    <div class="sol-empty-icon" style="background:#e0e7ff; color:#4338ca;"><i class="fas fa-bell"></i></div>
                    <h4>Bandeja al día</h4>
                    <p>No tienes notificaciones o avisos pendientes en este momento.</p>
                </div>
            `;
        }

        // Historial completo de solicitudes
        if (solicitudes.length > 1 || (solicitudes.length === 1 && esAprobado)) {
            html += `
                <div style="margin-top: 10px;">
                    <h5 style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin: 18px 0 10px 0; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-history"></i> Historial de solicitudes (${solicitudes.length})
                    </h5>
            `;
            solicitudes.forEach(function(s) {
                const sStatus = s.estado || 'pendiente';
                let sBadge = 'sol-status-' + sStatus;
                let sLabel = sStatus.charAt(0).toUpperCase() + sStatus.slice(1);
                html += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; margin-bottom: 8px; font-size: 12px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-weight: 600; color: #334155;">Solicitud #${s.id}</span>
                            <span class="sol-status-badge ${sBadge}" style="font-size: 10.5px; padding: 2px 8px;"><span class="sol-badge-dot"></span>${sLabel}</span>
                        </div>
                        <div style="color: #64748b; font-size: 11.5px;">
                            ${s.mensaje ? `<p style="margin: 0 0 4px 0; font-style: italic;">"${escapeHtml(s.mensaje)}"</p>` : ''}
                            <span style="color: #94a3b8;"><i class="far fa-calendar-alt"></i> ${escapeHtml(s.created_at || '')}</span>
                            ${s.admin_nombre ? ` · Revisado por ${escapeHtml(s.admin_nombre)}` : ''}
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
        }

        body.innerHTML = html;
    })
    .catch(err => {
        console.error("Error al cargar notificaciones:", err);
        body.innerHTML = `
            <div class="sol-empty">
                <div class="sol-empty-icon" style="background:#fee2e2; color:#ef4444;"><i class="fas fa-exclamation-triangle"></i></div>
                <h4>Error al cargar</h4>
                <p>No se pudieron obtener tus notificaciones. Por favor intenta de nuevo.</p>
            </div>
        `;
    });
}

// ── Atajo global Esc para cerrar modales y paneles ──
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarGlobalModal();
        cerrarConfirmModal();
        cerrarPanelSolicitudes();
        cerrarPanelNotificacionesColaborador();
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

// ── Auto-inyectar token CSRF en todos los formularios POST del DOM ──
function inyectarCsrfEnFormularios() {
    if (!window.CSRF_TOKEN) return;
    document.querySelectorAll('form').forEach(function(form) {
        const method = (form.getAttribute('method') || '').toUpperCase();
        if (method === 'POST' && !form.querySelector('input[name="csrf_token"]')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = window.CSRF_TOKEN;
            form.appendChild(input);
        }
    });
}
document.addEventListener('DOMContentLoaded', inyectarCsrfEnFormularios);
window.addEventListener('load', inyectarCsrfEnFormularios);

<?php if (!in_array($userRol, ['superadmin', 'admin'])): ?>
// Sincronización automática de notificaciones para colaborador al cargar
document.addEventListener('DOMContentLoaded', function() {
    fetch(BASE_URL + '/auth/notificacionesColaborador')
    .then(r => r.json())
    .then(data => {
        if (!data) return;
        const esAprobado = (parseInt(data.acceso_aprobado) === 1);
        const bellBtn = document.getElementById('btn-notificaciones-colab');
        let bellBadge = document.getElementById('badge-notif-colab');
        if (!bellBadge && bellBtn) {
            bellBadge = document.createElement('span');
            bellBadge.id = 'badge-notif-colab';
            bellBadge.className = 'badge-count';
            bellBtn.appendChild(bellBadge);
        }
        if (bellBadge) {
            if (esAprobado) {
                bellBadge.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                bellBadge.style.width = '18px';
                bellBadge.style.height = '18px';
                bellBadge.innerHTML = '<i class="fas fa-check" style="font-size:8px;"></i>';
                bellBadge.style.display = 'flex';
            } else if (data.solicitudes && data.solicitudes.length > 0) {
                const count = data.solicitudes.filter(s => s.estado === 'aprobada' || s.estado === 'rechazada').length;
                if (count > 0) {
                    bellBadge.textContent = count;
                    bellBadge.style.display = 'flex';
                }
            }
        }
    })
    .catch(() => {});
});
<?php endif; ?>
</script>
