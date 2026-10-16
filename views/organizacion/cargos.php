<?php
/**
 * Vista de Cargos y Puestos Corporativos
 * Estructura Jerárquica Organizada por Niveles (Nivel 1 -> Nivel 5)
 * Gran Modal de Matriz de Permisos (92 Permisos organizados en Fases ERP)
 * Diseño Ultra-Ejecutivo, 100% en Pantalla (Zero Scroll Horizontal), Moderno y Altamente Animado
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$cargos = $data['cargos'] ?? [];
$areas = $data['areas'] ?? [];
$selectedAreaId = $data['selectedAreaId'] ?? null;
$selectedNivel = $data['selectedNivel'] ?? null;
$selectedAreaNombre = '';
if (!empty($selectedAreaId)) {
    foreach ($areas as $a) {
        if ((int)$a['id'] === (int)$selectedAreaId) {
            $selectedAreaNombre = $a['nombre'];
            break;
        }
    }
}
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$isAdmin = in_array($userRol, ['superadmin', 'admin']);

// Contadores KPI
$totalCargos = count($cargos);
$totalAprobadores = 0;
$totalMandos = 0;
$totalPermisosSuma = 0;
foreach ($cargos as $c) {
    if (!empty($c['puede_aprobar'])) $totalAprobadores++;
    if ((int)$c['nivel_jerarquico'] <= 3) $totalMandos++;
    $totalPermisosSuma += (int)($c['total_permisos'] ?? 0);
}

// Agrupación de cargos por nivel jerárquico
$cargosPorNivel = [];
foreach ($cargos as $c) {
    $nivel = (int)$c['nivel_jerarquico'];
    $cargosPorNivel[$nivel][] = $c;
}
ksort($cargosPorNivel);

// Metadatos descriptivos y estilos para cada Nivel Jerárquico
$nivelesMeta = [
    1 => [
        'titulo' => 'Nivel 1 · Alta Dirección & Dirección General (CEO)',
        'subtitulo' => 'Máxima autoridad ejecutiva corporativa, gobernanza y facultades plenarias',
        'badge' => 'Dirección Estratégica',
        'badge_bg' => '#faf5ff',
        'badge_color' => '#7c3aed',
        'badge_border' => '#ddd6fe',
        'icon' => 'fas fa-crown',
        'icon_bg' => 'linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%)',
        'icon_color' => '#7c3aed'
    ],
    2 => [
        'titulo' => 'Nivel 2 · Gerencias de Área & Mando Ejecutivo',
        'subtitulo' => 'Responsables de divisiones operativas, administración y control de proyectos',
        'badge' => 'Mando Táctico & Presupuestos',
        'badge_bg' => '#eff6ff',
        'badge_color' => '#2563eb',
        'badge_border' => '#bfdbfe',
        'icon' => 'fas fa-briefcase',
        'icon_bg' => 'linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%)',
        'icon_color' => '#2563eb'
    ],
    3 => [
        'titulo' => 'Nivel 3 · Jefaturas de Departamento & Operaciones',
        'subtitulo' => 'Liderazgo departamental, control de calidad y facultades de aprobación operativa',
        'badge' => 'Control Operativo & Aprobación',
        'badge_bg' => '#f0fdfa',
        'badge_color' => '#0d9488',
        'badge_border' => '#99f6e4',
        'icon' => 'fas fa-user-tie',
        'icon_bg' => 'linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%)',
        'icon_color' => '#0d9488'
    ],
    4 => [
        'titulo' => 'Nivel 4 · Coordinaciones & Supervisores de Campo',
        'subtitulo' => 'Supervisión directa en obra, expedientes técnicos y seguimiento operativo',
        'badge' => 'Supervisión Técnica',
        'badge_bg' => '#fffbeb',
        'badge_color' => '#d97706',
        'badge_border' => '#fde68a',
        'icon' => 'fas fa-hard-hat',
        'icon_bg' => 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)',
        'icon_color' => '#d97706'
    ],
    5 => [
        'titulo' => 'Nivel 5 · Analistas, Especialistas & Asistentes',
        'subtitulo' => 'Soporte técnico, contable, licitaciones y asistencia ejecutiva a gerencias',
        'badge' => 'Soporte Operativo & Análisis',
        'badge_bg' => '#f8fafc',
        'badge_color' => '#475569',
        'badge_border' => '#e2e8f0',
        'icon' => 'fas fa-laptop-code',
        'icon_bg' => 'linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%)',
        'icon_color' => '#475569'
    ],
    6 => [
        'titulo' => 'Nivel 6 · Personal Operativo & Técnicos',
        'subtitulo' => 'Ejecución en campo, tareas auxiliares y apoyo operativo general',
        'badge' => 'Operativo de Campo',
        'badge_bg' => '#f8fafc',
        'badge_color' => '#64748b',
        'badge_border' => '#cbd5e1',
        'icon' => 'fas fa-tools',
        'icon_bg' => 'linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%)',
        'icon_color' => '#64748b'
    ]
];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
/* ─── Variables y Animaciones ─── */
:root {
    --cargo-primary: #d97706;
    --cargo-primary-light: #fef3c7;
    --cargo-border: #e2e8f0;
}

@keyframes slideInUpFade {
    0% { opacity: 0; transform: translateY(12px); }
    100% { opacity: 1; transform: translateY(0); }
}

.org-dashboard-wrapper {
    padding: 16px 20px 40px;
    max-width: 100%;
    width: 100%;
    margin: 0 auto;
    box-sizing: border-box;
    overflow-x: hidden;
    animation: slideInUpFade 0.35s ease-out;
}

/* ─── Top Bar Unificado ─── */
.org-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 18px;
    background: #ffffff;
    padding: 14px 20px;
    border-radius: 16px;
    border: 1px solid var(--cargo-border);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    box-sizing: border-box;
    width: 100%;
}

.org-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.org-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.15);
    flex-shrink: 0;
}

.org-header-text {
    min-width: 0;
}

.org-header-text h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.org-header-text span {
    font-size: 12.5px;
    color: #64748b;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.org-top-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

/* ─── Mode Switch Tabs ─── */
.org-view-modes {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 12px;
    gap: 4px;
}

.view-mode-btn {
    border: none;
    background: transparent;
    padding: 7px 15px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    text-decoration: none;
    white-space: nowrap;
}

.view-mode-btn.active {
    background: #ffffff;
    color: #d97706;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* ─── Botones de Acción ─── */
.btn-cargo-brand {
    background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
    color: #ffffff;
    border: none;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 3px 10px rgba(217, 119, 6, 0.22);
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-cargo-brand:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(217, 119, 6, 0.32);
    color: #ffffff;
}

.btn-cargo-light {
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-cargo-light:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

/* ─── KPI Bar (Grid exacto de 4 columnas) ─── */
.cargo-kpi-bar {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 900px) {
    .cargo-kpi-bar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.cargo-kpi-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: all 0.25s ease;
    box-sizing: border-box;
    min-width: 0;
}

.cargo-kpi-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e1;
}

.cargo-kpi-pill-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.cargo-kpi-pill-content {
    min-width: 0;
    overflow: hidden;
}

.cargo-kpi-pill-val {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cargo-kpi-pill-lbl {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    margin-top: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ─── Toolbar Unificada de Filtros y Búsqueda de Cargos ─── */
.cargo-filter-toolbar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 12px 18px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.025);
    box-sizing: border-box;
}

.filter-search-box {
    position: relative;
    flex: 1 1 260px;
    min-width: 220px;
}

.filter-search-box i.fa-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
    pointer-events: none;
}

.filter-search-input {
    width: 100%;
    padding: 8px 32px 8px 34px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    font-size: 12.5px;
    color: #0f172a;
    transition: all 0.2s;
    box-sizing: border-box;
}

.filter-search-input:focus {
    outline: none;
    background: #ffffff;
    border-color: #d97706;
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.12);
}

.btn-clear-search {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 11px;
    padding: 4px;
}

.filter-controls-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.filter-select-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.filter-select-wrapper label {
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    white-space: nowrap;
    margin: 0;
}

.filter-select {
    padding: 7px 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
    transition: all 0.2s;
    max-width: 240px;
}

.filter-select:focus {
    outline: none;
    border-color: #d97706;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.12);
}

.btn-open-modal-area {
    padding: 7px 14px;
    border-radius: 10px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #b45309;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-open-modal-area:hover {
    background: #fef3c7;
    border-color: #f59e0b;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.12);
}

.btn-reset-filters {
    padding: 7px 12px;
    border-radius: 10px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
}

.btn-reset-filters:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* ─── Quick Segment Bar & Counters ─── */
.cargo-quick-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
    padding: 2px 4px;
}

.quick-level-segments {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    gap: 4px;
    overflow-x: auto;
    max-width: 100%;
}

.quick-segment-btn {
    border: none;
    background: transparent;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.quick-segment-btn:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
}

.quick-segment-btn.active {
    background: #ffffff;
    color: #0f172a;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

.quick-status-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.active-area-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.active-area-chip i.fa-times {
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.active-area-chip i.fa-times:hover {
    opacity: 1;
}

.filtered-count-badge {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}

.cargo-area-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ─── Modal de Selección de Área ─── */
.modal-area-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 10px;
    max-height: 420px;
    overflow-y: auto;
    padding: 2px;
}

.modal-area-card {
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 11px 14px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.modal-area-card:hover {
    border-color: #d97706;
    background: #fffdf5;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(217, 119, 6, 0.08);
}

.modal-area-card.selected {
    border-color: #d97706;
    background: #fef3c7;
    box-shadow: 0 0 0 1px #d97706;
}

/* ─── Estado Vacío al Filtrar ─── */
.empty-cargos-filter {
    display: none;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    padding: 40px 20px;
    text-align: center;
    margin: 20px 0 40px;
}

.empty-cargos-filter i {
    font-size: 38px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.empty-cargos-filter h4 {
    margin: 0 0 6px;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.empty-cargos-filter p {
    margin: 0 0 16px;
    font-size: 13px;
    color: #64748b;
}

/* ─── Secciones por Nivel Jerárquico ─── */
.nivel-section {
    margin-bottom: 28px;
    transition: all 0.3s ease;
}

.nivel-tier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 18px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    margin-bottom: 14px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.tier-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.tier-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.tier-info {
    min-width: 0;
}

.tier-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.tier-title-row h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.2px;
}

.tier-badge {
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.tier-count {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 600;
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 12px;
}

.tier-sub {
    margin: 2px 0 0;
    font-size: 11.5px;
    color: #64748b;
}

/* ─── Grid de Cargos ─── */
.cargos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
    gap: 16px;
    width: 100%;
    box-sizing: border-box;
}

.cargo-card {
    background: #ffffff;
    border: 1px solid var(--cargo-border);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    min-width: 0;
}

.cargo-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 22px -4px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}

.cargo-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.cargo-level-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}

.level-1 { background: #faf5ff; color: #7c3aed; border: 1px solid #ddd6fe; }
.level-2 { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.level-3 { background: #f0fdfa; color: #0d9488; border: 1px solid #99f6e4; }
.level-4 { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.level-5 { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

.cargo-title {
    margin: 0 0 5px;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cargo-area-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cargo-desc {
    font-size: 12px;
    color: #64748b;
    line-height: 1.45;
    margin-bottom: 12px;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.cargo-stats-row {
    display: flex;
    gap: 4px;
    padding: 8px 6px;
    background: #f8fafc;
    border-radius: 10px;
    margin-bottom: 12px;
    font-size: 11px;
    color: #334155;
    justify-content: space-around;
    text-align: center;
}

.cargo-stat-item {
    flex: 1;
    min-width: 0;
}

.cargo-stat-item .num {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    display: block;
    line-height: 1.1;
}

.cargo-stat-item span:last-child {
    font-size: 10px;
    color: #64748b;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cargo-footer-actions {
    display: flex;
    gap: 6px;
    align-items: center;
}

.btn-permisos-matriz {
    flex: 1;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: #ffffff;
    border: none;
    padding: 7px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
    white-space: nowrap;
    cursor: pointer;
}

.btn-permisos-matriz:hover {
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
    transform: translateY(-1px);
    color: #ffffff;
}

.btn-icon-subtle {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    flex-shrink: 0;
}

.btn-icon-subtle:hover {
    background: #f1f5f9;
    color: #0f172a;
}

/* ─── Modales Base ─── */
.org-modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.org-modal-dialog {
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 540px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    border: 1px solid #e2e8f0;
    animation: slideInUpFade 0.25s ease-out;
    overflow: hidden;
    box-sizing: border-box;
}

.org-modal-header {
    padding: 16px 22px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.org-modal-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 9px;
}

.org-modal-close {
    background: transparent;
    border: none;
    font-size: 20px;
    color: #94a3b8;
    cursor: pointer;
    transition: color 0.2s;
}

.org-modal-close:hover {
    color: #ef4444;
}

.org-modal-body {
    padding: 20px 22px;
    max-height: 75vh;
    overflow-y: auto;
    box-sizing: border-box;
}

.form-group-org {
    margin-bottom: 14px;
}

.form-group-org label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 5px;
}

.form-group-org input,
.form-group-org select,
.form-group-org textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid #cbd5e1;
    border-radius: 9px;
    font-size: 13px;
    color: #0f172a;
    box-sizing: border-box;
    transition: all 0.2s;
}

.form-group-org input:focus,
.form-group-org select:focus,
.form-group-org textarea:focus {
    border-color: #d97706;
    box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.15);
    outline: none;
}

.org-modal-footer {
    padding: 14px 22px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* ═══════════════════════════════════════════════════════════════════
   GRAN MODAL: MATRIZ DE PERMISOS & SEGURIDAD GRANULAR
   Spacious, Ultra-Organizado en Fases ERP, Switches iOS Animados
   ═══════════════════════════════════════════════════════════════════ */
.perm-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(10px);
    z-index: 100000;
    align-items: center;
    justify-content: center;
    padding: 14px;
    box-sizing: border-box;
}

.perm-modal-dialog {
    background: #ffffff;
    border-radius: 20px;
    width: 96vw;
    max-width: 1320px;
    height: 92vh;
    max-height: 92vh;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideInUpFade 0.25s ease-out;
}

.perm-modal-header {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    color: #ffffff;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.perm-modal-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.perm-modal-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(124, 58, 237, 0.25);
    border: 1px solid rgba(167, 139, 250, 0.4);
    color: #c4b5fd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 0 20px rgba(124, 58, 237, 0.3);
}

.perm-modal-header-text h3 {
    margin: 0;
    font-size: 17.5px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.2px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.perm-modal-header-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
    flex-wrap: wrap;
}

.perm-header-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.12);
    color: #e2e8f0;
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.perm-modal-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.perm-cargo-switch-select {
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 10px;
    padding: 7px 12px;
    font-size: 13px;
    font-weight: 600;
    outline: none;
    cursor: pointer;
}
.perm-cargo-switch-select option {
    background: #1e1b4b;
    color: #ffffff;
}

.perm-modal-close-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #cbd5e1;
    font-size: 22px;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.perm-modal-close-btn:hover {
    background: #ef4444;
    color: #ffffff;
}

/* ─── Toolbar Filtros por Fase ERP & Búsqueda ─── */
.perm-modal-toolbar {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-shrink: 0;
}

.perm-fase-tabs {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.perm-fase-btn {
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    white-space: nowrap;
}

.perm-fase-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.perm-fase-btn.active {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
}

.perm-toolbar-subrow {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.perm-search-wrap {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 460px;
}
.perm-search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}
.perm-search-input {
    width: 100%;
    padding: 7px 12px 7px 34px;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    font-size: 12.5px;
    outline: none;
    box-sizing: border-box;
    transition: all 0.2s;
}
.perm-search-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.perm-bulk-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-bulk-subtle {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-bulk-subtle:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* ─── Grid de Módulos (Cuerpo del Modal) ─── */
.perm-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 18px 24px;
    background: #f8fafc;
    box-sizing: border-box;
}

.perm-modules-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 16px;
    box-sizing: border-box;
}

.pm-module-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    transition: all 0.2s;
}

.pm-module-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
}

.pm-module-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.pm-module-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    text-transform: capitalize;
}

.pm-module-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}

.pm-module-toggle-wrap {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    user-select: none;
}

.pm-items-list {
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.pm-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 8px;
    border-radius: 8px;
    transition: background 0.15s;
    gap: 10px;
}

.pm-item-row:hover {
    background: #f1f5f9;
}

.pm-item-info {
    flex: 1;
    min-width: 0;
}

.pm-item-name {
    font-size: 12.5px;
    font-weight: 700;
    color: #1e293b;
    display: block;
    text-transform: capitalize;
}

.pm-item-desc {
    font-size: 11px;
    color: #64748b;
    display: block;
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ─── Switch Animado iOS ─── */
.sw-perm {
    position: relative;
    display: inline-block;
    width: 38px;
    height: 20px;
    flex-shrink: 0;
}
.sw-perm input {
    opacity: 0;
    width: 0;
    height: 0;
}
.sw-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #cbd5e1;
    border-radius: 20px;
    transition: .25s ease;
}
.sw-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background: white;
    border-radius: 50%;
    transition: .25s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
}
input:checked + .sw-slider {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
input:checked + .sw-slider:before {
    transform: translateX(18px);
}

/* ─── Footer del Modal ─── */
.perm-modal-footer {
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    padding: 12px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.perm-footer-counter-pill {
    background: #ede9fe;
    color: #6d28d9;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.perm-btn-save {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: #ffffff;
    border: none;
    padding: 9px 22px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    transition: all 0.2s;
}

.perm-btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    color: #ffffff;
}
</style>

<div class="org-dashboard-wrapper">
    <!-- Top Unified Navigation Bar -->
    <?php
    $activeTab = 'cargos';
    $orgNavTitle = 'Cargos y Puestos Corporativos';
    $orgNavSubtitle = 'Flujo de integración ERP · Perfiles de puesto ordenados por jerarquía y matriz de autorizaciones';
    include __DIR__ . '/navbar_organizacion.php';
    ?>

    <!-- KPI Bar (Grid exacto de 4 columnas) -->
    <div class="cargo-kpi-bar">
        <div class="cargo-kpi-pill">
            <div class="cargo-kpi-pill-icon" style="background:#fef3c7; color:#d97706;">
                <i class="fas fa-id-card-alt"></i>
            </div>
            <div class="cargo-kpi-pill-content">
                <div class="cargo-kpi-pill-val"><?= $totalCargos ?></div>
                <div class="cargo-kpi-pill-lbl">Puestos Registrados</div>
            </div>
        </div>

        <div class="cargo-kpi-pill">
            <div class="cargo-kpi-pill-icon" style="background:#ede9fe; color:#7c3aed;">
                <i class="fas fa-crown"></i>
            </div>
            <div class="cargo-kpi-pill-content">
                <div class="cargo-kpi-pill-val"><?= $totalMandos ?></div>
                <div class="cargo-kpi-pill-lbl">Puestos de Mando (Niv. 1-3)</div>
            </div>
        </div>

        <div class="cargo-kpi-pill">
            <div class="cargo-kpi-pill-icon" style="background:#dcfce7; color:#15803d;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="cargo-kpi-pill-content">
                <div class="cargo-kpi-pill-val"><?= $totalAprobadores ?></div>
                <div class="cargo-kpi-pill-lbl">Facultad de Aprobación</div>
            </div>
        </div>

        <div class="cargo-kpi-pill">
            <div class="cargo-kpi-pill-icon" style="background:#e0e7ff; color:#4338ca;">
                <i class="fas fa-key"></i>
            </div>
            <div class="cargo-kpi-pill-content">
                <div class="cargo-kpi-pill-val"><?= $totalPermisosSuma ?></div>
                <div class="cargo-kpi-pill-lbl">Permisos Asignados Totales</div>
            </div>
        </div>
    </div>

    <!-- Toolbar Ejecutiva de Filtros: Búsqueda, Select Nivel, Select Área y Modal -->
    <div class="cargo-filter-toolbar">
        <!-- Búsqueda Instantánea -->
        <div class="filter-search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="filtroTextoCargo" class="filter-search-input" placeholder="Buscar cargo o código (ej. Residente, GAF)..." oninput="aplicarFiltrosCargos()">
            <button type="button" id="btnLimpiarTexto" class="btn-clear-search" onclick="limpiarTextoCargo()" style="display:none;" title="Borrar búsqueda">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <!-- Grupo de Selectores y Acciones -->
        <div class="filter-controls-group">
            <!-- Selector de Nivel Jerárquico -->
            <div class="filter-select-wrapper">
                <label for="selectFiltroNivel"><i class="fas fa-layer-group" style="color:#6366f1;"></i> Nivel:</label>
                <select id="selectFiltroNivel" class="filter-select" onchange="onNivelSelectChange(this.value)">
                    <option value="all" <?= empty($selectedNivel) ? 'selected' : '' ?>>Todos los Niveles (<?= $totalCargos ?>)</option>
                    <option value="1" <?= ($selectedNivel == 1) ? 'selected' : '' ?>>Nivel 1 · Dirección (CEO) (<?= count($cargosPorNivel[1] ?? []) ?>)</option>
                    <option value="2" <?= ($selectedNivel == 2) ? 'selected' : '' ?>>Nivel 2 · Gerencias (<?= count($cargosPorNivel[2] ?? []) ?>)</option>
                    <option value="3" <?= ($selectedNivel == 3) ? 'selected' : '' ?>>Nivel 3 · Jefaturas (<?= count($cargosPorNivel[3] ?? []) ?>)</option>
                    <option value="4" <?= ($selectedNivel == 4) ? 'selected' : '' ?>>Nivel 4 · Supervisores (<?= count($cargosPorNivel[4] ?? []) ?>)</option>
                    <option value="5" <?= ($selectedNivel == 5) ? 'selected' : '' ?>>Nivel 5 · Asistentes (<?= count($cargosPorNivel[5] ?? []) ?>)</option>
                </select>
            </div>

            <!-- Selector de Área Corporativa -->
            <div class="filter-select-wrapper">
                <label for="selectFiltroArea"><i class="fas fa-sitemap" style="color:#d97706;"></i> Área:</label>
                <select id="selectFiltroArea" class="filter-select" onchange="seleccionarArea(this.value)">
                    <option value="all" <?= empty($selectedAreaId) ? 'selected' : '' ?>>Todas las Áreas (<?= count($areas) ?>)</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= ($selectedAreaId == $a['id']) ? 'selected' : '' ?>>
                            <?= ((int)$a['nivel'] > 1) ? '↳ ' : '' ?><?= htmlspecialchars($a['nombre']) ?> (<?= htmlspecialchars($a['codigo']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Botón para abrir el Modal Visual de Áreas -->
            <button type="button" class="btn-open-modal-area" onclick="abrirModalSeleccionarArea()" title="Explorar estructura y seleccionar área en un modal">
                <i class="fas fa-th-large"></i> Modal de Áreas
            </button>

            <!-- Botón Limpiar Filtros -->
            <button type="button" id="btnResetFiltros" class="btn-reset-filters" onclick="resetearTodosFiltros()" title="Restablecer todos los filtros" style="<?= (empty($selectedAreaId) && empty($selectedNivel)) ? 'display:none;' : '' ?>">
                <i class="fas fa-undo"></i> Limpiar
            </button>
        </div>
    </div>

    <!-- Píldoras Rápidas de Nivel & Badge de Estado -->
    <div class="cargo-quick-bar">
        <div class="quick-level-segments" id="quickLevelSegments">
            <button type="button" class="quick-segment-btn <?= empty($selectedNivel) ? 'active' : '' ?>" data-level="all" onclick="setQuickNivel('all')">
                <i class="fas fa-th"></i> Todos (<?= $totalCargos ?>)
            </button>
            <button type="button" class="quick-segment-btn <?= ($selectedNivel == 1) ? 'active' : '' ?>" data-level="1" onclick="setQuickNivel(1)">
                <i class="fas fa-crown" style="color:#7c3aed;"></i> Niv. 1 (<?= count($cargosPorNivel[1] ?? []) ?>)
            </button>
            <button type="button" class="quick-segment-btn <?= ($selectedNivel == 2) ? 'active' : '' ?>" data-level="2" onclick="setQuickNivel(2)">
                <i class="fas fa-briefcase" style="color:#2563eb;"></i> Niv. 2 (<?= count($cargosPorNivel[2] ?? []) ?>)
            </button>
            <button type="button" class="quick-segment-btn <?= ($selectedNivel == 3) ? 'active' : '' ?>" data-level="3" onclick="setQuickNivel(3)">
                <i class="fas fa-user-tie" style="color:#0d9488;"></i> Niv. 3 (<?= count($cargosPorNivel[3] ?? []) ?>)
            </button>
            <button type="button" class="quick-segment-btn <?= ($selectedNivel == 4) ? 'active' : '' ?>" data-level="4" onclick="setQuickNivel(4)">
                <i class="fas fa-hard-hat" style="color:#d97706;"></i> Niv. 4 (<?= count($cargosPorNivel[4] ?? []) ?>)
            </button>
            <button type="button" class="quick-segment-btn <?= ($selectedNivel == 5) ? 'active' : '' ?>" data-level="5" onclick="setQuickNivel(5)">
                <i class="fas fa-laptop-code" style="color:#475569;"></i> Niv. 5 (<?= count($cargosPorNivel[5] ?? []) ?>)
            </button>
        </div>

        <div class="quick-status-info">
            <span id="badgeFiltroAreaActiva" class="active-area-chip" style="<?= empty($selectedAreaId) ? 'display:none;' : '' ?>">
                <i class="fas fa-sitemap" style="font-size:10px;"></i>
                <span id="lblAreaActiva"><?= htmlspecialchars($selectedAreaNombre ?: 'Área filtrada') ?></span>
                <i class="fas fa-times" onclick="seleccionarArea('all')" title="Quitar filtro de área"></i>
            </span>
            <span class="filtered-count-badge" id="lblConteoCargos">
                Mostrando <strong><?= $totalCargos ?></strong> puestos
            </span>
        </div>
    </div>

    <!-- Estado Vacío cuando ningún cargo coincide con los filtros -->
    <div id="emptyCargosFilter" class="empty-cargos-filter">
        <i class="fas fa-search-minus"></i>
        <h4>No se encontraron puestos corporativos</h4>
        <p>No existen cargos que coincidan simultáneamente con el nivel, área o término de búsqueda ingresado.</p>
        <button type="button" class="btn-user-action btn-user-primary" onclick="resetearTodosFiltros()" style="font-size:12.5px; padding:7px 16px;">
            <i class="fas fa-redo"></i> Restablecer Filtros
        </button>
    </div>

    <!-- Contenido Agrupado por Niveles Jerárquicos -->
    <?php if (!empty($cargosPorNivel)): ?>
        <?php foreach ($cargosPorNivel as $nivel => $cargosDelNivel): ?>
            <?php 
            $meta = $nivelesMeta[$nivel] ?? [
                'titulo' => "Nivel $nivel · Cargos Organizacionales",
                'subtitulo' => 'Puestos corporativos registrados en este estrato',
                'badge' => "Nivel $nivel",
                'badge_bg' => '#f8fafc',
                'badge_color' => '#475569',
                'badge_border' => '#e2e8f0',
                'icon' => 'fas fa-id-badge',
                'icon_bg' => 'linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%)',
                'icon_color' => '#475569'
            ]; 
            ?>
            <section class="nivel-section animate__animated animate__fadeIn" data-nivel="<?= $nivel ?>" id="section-nivel-<?= $nivel ?>">
                <!-- Tier Header Banner -->
                <div class="nivel-tier-header">
                    <div class="tier-left">
                        <div class="tier-icon" style="background: <?= $meta['icon_bg'] ?>; color: <?= $meta['icon_color'] ?>;">
                            <i class="<?= $meta['icon'] ?>"></i>
                        </div>
                        <div class="tier-info">
                            <div class="tier-title-row">
                                <h4><?= $meta['titulo'] ?></h4>
                                <span class="tier-badge" style="background: <?= $meta['badge_bg'] ?>; color: <?= $meta['badge_color'] ?>; border: 1px solid <?= $meta['badge_border'] ?>;">
                                    <?= $meta['badge'] ?>
                                </span>
                                <span class="tier-count"><?= count($cargosDelNivel) ?> <?= count($cargosDelNivel) === 1 ? 'puesto' : 'puestos' ?></span>
                            </div>
                            <p class="tier-sub"><?= $meta['subtitulo'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Grid de Tarjetas del Nivel -->
                <div class="cargos-grid">
                    <?php foreach ($cargosDelNivel as $c): ?>
                        <div class="cargo-card"
                             data-id="<?= (int)$c['id'] ?>"
                             data-area-id="<?= (int)$c['area_id'] ?>"
                             data-area-nombre="<?= htmlspecialchars($c['area_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                             data-nivel="<?= (int)$nivel ?>"
                             data-search="<?= htmlspecialchars(strtolower(($c['nombre'] ?? '') . ' ' . ($c['codigo'] ?? '') . ' ' . ($c['area_nombre'] ?? '') . ' ' . ($c['descripcion'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="cargo-top">
                                <span class="cargo-level-badge level-<?= min(5, $nivel) ?>">
                                    Nivel <?= $nivel ?> · <?= explode(' · ', $meta['titulo'])[1] ?? "Nivel $nivel" ?>
                                </span>
                                <?php if (!empty($c['puede_aprobar'])): ?>
                                    <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 6px; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fas fa-check-double"></i> Aprobador
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="cargo-title" title="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></h3>

                            <div class="cargo-area-tag" title="<?= htmlspecialchars($c['area_nombre']) ?>">
                                <span class="cargo-area-dot" style="background: <?= htmlspecialchars($c['area_color'] ?: '#6366f1') ?>;"></span>
                                <span><?= htmlspecialchars($c['area_nombre']) ?> (<?= htmlspecialchars($c['codigo']) ?>)</span>
                            </div>

                            <div class="cargo-desc">
                                <?= htmlspecialchars($c['descripcion'] ?: 'Sin descripción detallada de funciones.') ?>
                            </div>

                            <div class="cargo-stats-row">
                                <div class="cargo-stat-item">
                                    <span class="num" id="cargo-perm-count-<?= $c['id'] ?>"><?= (int)$c['total_permisos'] ?></span>
                                    <span>Permisos</span>
                                </div>
                                <div class="cargo-stat-item" style="border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; padding: 0 4px;">
                                    <span class="num"><?= (int)$c['total_usuarios'] ?></span>
                                    <span>Colaboradores</span>
                                </div>
                                <div class="cargo-stat-item">
                                    <span class="num"><?= $c['puede_aprobar'] ? 'SÍ' : 'NO' ?></span>
                                    <span>Aprobación</span>
                                </div>
                            </div>

                            <div class="cargo-footer-actions">
                                <button type="button" class="btn-permisos-matriz" onclick="abrirModalPermisos(<?= $c['id'] ?>)" title="Configurar Matriz de Permisos en Modal">
                                    <i class="fas fa-key"></i> Matriz Permisos
                                </button>
                                <?php if ($isAdmin): ?>
                                <button class="btn-icon-subtle" title="Editar Cargo" onclick="editarCargoModal(<?= htmlspecialchars(json_encode($c)) ?>)">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST" action="<?= BASE_URL ?>/organizacion/eliminarCargo/<?= $c['id'] ?>" style="margin:0;" onsubmit="return confirm('¿Seguro que deseas eliminar este cargo?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" class="btn-icon-subtle" title="Eliminar Cargo" style="color:#ef4444;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 16px; border: 1px solid #e2e8f0; margin-top: 10px;">
            <i class="fas fa-id-badge" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
            <h4 style="color: #334155; margin: 0 0 8px;">No hay cargos registrados en esta área</h4>
            <p style="color: #64748b; margin: 0;">Selecciona otra categoría o registra un nuevo puesto con el botón superior.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Selección de Área Organizacional -->
<div class="org-modal-backdrop" id="modalSelectAreaBackdrop">
    <div class="org-modal-dialog" style="max-width: 680px;">
        <div class="org-modal-header" style="background:#fffbeb; border-bottom:1px solid #fde68a;">
            <div>
                <h3 style="color:#b45309; display:flex; align-items:center; gap:8px; font-size:15.5px;">
                    <i class="fas fa-sitemap"></i> Seleccionar Área Organizacional
                </h3>
                <p style="margin:2px 0 0; font-size:12px; color:#92400e;">Filtra la estructura de puestos según el organigrama oficial</p>
            </div>
            <button class="org-modal-close" onclick="cerrarModalSelectArea()">&times;</button>
        </div>
        <div class="org-modal-body" style="padding: 16px 20px;">
            <!-- Input de búsqueda dentro del modal -->
            <div style="position:relative; margin-bottom:14px;">
                <i class="fas fa-search" style="position:absolute; left:12px; top:11px; color:#94a3b8; font-size:13px;"></i>
                <input type="text" id="busquedaAreaModal" placeholder="Buscar área por nombre o código (ej. Tesorería, Consultoría)..." 
                       style="width:100%; padding:9px 12px 9px 34px; border:1px solid #cbd5e1; border-radius:10px; font-size:13px; box-sizing:border-box;"
                       oninput="filtrarAreasEnModal()">
            </div>

            <!-- Opción: Todas las Áreas -->
            <div class="modal-area-card <?= empty($selectedAreaId) ? 'selected' : '' ?>" id="cardAreaModalAll" onclick="seleccionarAreaModal('all')" style="margin-bottom:12px; border-style:dashed;">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:34px; height:34px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#475569;">
                            <i class="fas fa-th-large"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:13px; color:#0f172a;">Todas las Áreas Corporativas</div>
                            <div style="font-size:11.5px; color:#64748b;">Ver la totalidad de los <?= $totalCargos ?> puestos sin restricción de departamento</div>
                        </div>
                    </div>
                    <span style="font-size:12px; font-weight:700; color:#475569; background:#f1f5f9; padding:3px 9px; border-radius:12px;"><?= $totalCargos ?> puestos</span>
                </div>
            </div>

            <!-- Grid de Áreas -->
            <div class="modal-area-grid" id="modalAreaGrid">
                <?php foreach ($areas as $a): ?>
                    <div class="modal-area-card item-area-modal <?= ($selectedAreaId == $a['id']) ? 'selected' : '' ?>" 
                         id="cardAreaModal_<?= $a['id'] ?>"
                         data-id="<?= (int)$a['id'] ?>"
                         data-nombre="<?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                         data-search="<?= htmlspecialchars(strtolower($a['nombre'] . ' ' . $a['codigo'] . ' ' . ($a['padre_nombre'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                         onclick="seleccionarAreaModal(<?= (int)$a['id'] ?>)">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="cargo-area-dot" style="background:<?= htmlspecialchars($a['color'] ?: '#6366f1') ?>; width:9px; height:9px;"></span>
                                <span style="font-weight:700; font-size:12.5px; color:#0f172a;"><?= htmlspecialchars($a['nombre']) ?></span>
                            </div>
                            <span style="font-size:10px; font-weight:700; color:#64748b; background:#f1f5f9; padding:2px 6px; border-radius:6px;"><?= htmlspecialchars($a['codigo']) ?></span>
                        </div>
                        <div style="display:flex; align-items:center; justify-content:space-between; font-size:11px; color:#64748b;">
                            <span><?= !empty($a['padre_nombre']) ? '↳ ' . htmlspecialchars($a['padre_nombre']) : 'Dirección Corporativa' ?></span>
                            <span style="font-weight:600; color:#d97706;"><?= (int)$a['total_cargos'] ?> <?= (int)$a['total_cargos'] === 1 ? 'puesto' : 'puestos' ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="org-modal-footer" style="padding:12px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
            <button type="button" class="btn-user-action btn-user-secondary" onclick="cerrarModalSelectArea()" style="padding:6px 16px; font-size:12.5px;">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Crear / Editar Cargo -->
<div class="org-modal-backdrop" id="modalCargoBackdrop">
    <div class="org-modal-dialog">
        <div class="org-modal-header">
            <h3 id="modalCargoTitle"><i class="fas fa-id-badge" style="color:#d97706;"></i> Nuevo Cargo Corporativo</h3>
            <button class="org-modal-close" onclick="cerrarModalCargo()">&times;</button>
        </div>
        <form id="formCargo" method="POST" action="<?= BASE_URL ?>/organizacion/crearCargo" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="cargo_id" id="cargo_id" value="">
            <div class="org-modal-body">
                <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:14px;">
                    <div class="form-group-org">
                        <label>Nombre del Cargo *</label>
                        <input type="text" name="nombre" id="cargo_nombre" required placeholder="Ej: Jefe de Compras">
                    </div>
                    <div class="form-group-org">
                        <label>Código *</label>
                        <input type="text" name="codigo" id="cargo_codigo" required placeholder="Ej: JEFE-COMPRAS" style="text-transform: uppercase;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div class="form-group-org">
                        <label>Área a la que Pertenece *</label>
                        <select name="area_id" id="cargo_area_id" required>
                            <option value="">-- Seleccionar Área --</option>
                            <?php foreach ($areas as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= str_repeat('— ', (int)$a['nivel']) . htmlspecialchars($a['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group-org">
                        <label>Nivel Jerárquico *</label>
                        <select name="nivel_jerarquico" id="cargo_nivel_jerarquico" required>
                            <option value="1">Nivel 1 - CEO / Dirección General</option>
                            <option value="2">Nivel 2 - Gerente de Área</option>
                            <option value="3" selected>Nivel 3 - Jefe de Departamento / Obra</option>
                            <option value="4">Nivel 4 - Coordinador / Supervisor</option>
                            <option value="5">Nivel 5 - Analista / Asistente</option>
                            <option value="6">Nivel 6 - Operativo / Técnico</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-org" style="margin-top: 4px; margin-bottom:14px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="puede_aprobar" id="cargo_puede_aprobar" value="1" style="width: auto;">
                        <span style="font-size:12.5px;"><strong>Facultad de Aprobación:</strong> ¿Puede autorizar órdenes de compra o planillas?</span>
                    </label>
                </div>

                <div class="form-group-org" style="margin-bottom:6px;">
                    <label>Descripción de Funciones</label>
                    <textarea name="descripcion" id="cargo_descripcion" rows="3" placeholder="Alcance y responsabilidades principales..."></textarea>
                </div>
            </div>
            <div class="org-modal-footer">
                <button type="button" onclick="cerrarModalCargo()" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:8px 16px; border-radius:9px; font-weight:600; font-size:13px; cursor:pointer;">Cancelar</button>
                <button type="submit" class="btn-cargo-brand"><i class="fas fa-save"></i> Guardar Puesto</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Unificado Asignar Puesto a Colaborador -->
<?php include __DIR__ . '/modal_asignar_puesto.php'; ?>

<!-- ═══════════════════════════════════════════════════════════════════
     GRAN MODAL: MATRIZ DE PERMISOS & SEGURIDAD GRANULAR
     Spacious (96vw x 92vh), Organizado en Fases ERP, Switches iOS Animados
     ═══════════════════════════════════════════════════════════════════ -->
<div class="perm-modal-backdrop" id="modalPermisosBackdrop">
    <div class="perm-modal-dialog" id="modalPermisosDialog">
        <!-- Header del Modal -->
        <div class="perm-modal-header">
            <div class="perm-modal-header-left">
                <div class="perm-modal-header-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="perm-modal-header-text">
                    <h3>
                        Matriz de Permisos: <span id="mpCargoNombre" style="color: #fbbf24;">Cargando...</span>
                    </h3>
                    <div class="perm-modal-header-badges">
                        <span class="perm-header-badge" id="mpCargoArea">Área: -</span>
                        <span class="perm-header-badge" id="mpCargoCodigo">Código: -</span>
                        <span class="perm-header-badge" id="mpCargoNivel">Nivel: -</span>
                        <span class="perm-header-badge" id="mpCargoAprobador" style="background: rgba(16, 185, 129, 0.2); color: #34d399; border-color: rgba(16, 185, 129, 0.3);">Facultad: -</span>
                    </div>
                </div>
            </div>

            <div class="perm-modal-header-actions">
                <label style="font-size: 11.5px; font-weight: 600; color: #cbd5e1; display:flex; align-items:center; gap:6px;">
                    <span>Cambiar Puesto:</span>
                    <select class="perm-cargo-switch-select" id="mpCargoSelect" onchange="cargarPermisosCargo(this.value)">
                        <!-- Se llena vía JS -->
                    </select>
                </label>
                <button type="button" class="perm-modal-close-btn" onclick="cerrarModalPermisos()" title="Cerrar modal (Esc)">&times;</button>
            </div>
        </div>

        <!-- Sub-Header: Filtros por Fase ERP & Búsqueda -->
        <div class="perm-modal-toolbar">
            <!-- Pestañas de Fases ERP -->
            <div class="perm-fase-tabs">
                <span style="font-size: 11.5px; font-weight: 700; color: #475569; margin-right: 4px; display:inline-flex; align-items:center; gap:4px;">
                    <i class="fas fa-layer-group" style="color:#6366f1;"></i> Fases ERP:
                </span>
                <button type="button" class="perm-fase-btn active" onclick="filtrarPorFasePermisos('all', this)">
                    Todas las Fases (26 módulos)
                </button>
                <button type="button" class="perm-fase-btn" onclick="filtrarPorFasePermisos(1, this)">
                    <i class="far fa-file-alt" style="color:#2563eb;"></i> 1. Comercial & Licitaciones
                </button>
                <button type="button" class="perm-fase-btn" onclick="filtrarPorFasePermisos(2, this)">
                    <i class="far fa-calendar-alt" style="color:#0284c7;"></i> 2. Planificación & WBS
                </button>
                <button type="button" class="perm-fase-btn" onclick="filtrarPorFasePermisos(3, this)">
                    <i class="fas fa-cog" style="color:#0d9488;"></i> 3. Operaciones & Almacenes
                </button>
                <button type="button" class="perm-fase-btn" onclick="filtrarPorFasePermisos(4, this)">
                    <i class="fas fa-coins" style="color:#d97706;"></i> 4. Control Financiero
                </button>
                <button type="button" class="perm-fase-btn" onclick="filtrarPorFasePermisos(5, this)">
                    <i class="fas fa-chart-line" style="color:#7c3aed;"></i> 5. BI & Seguridad
                </button>
            </div>

            <!-- Búsqueda en vivo + Botones masivos -->
            <div class="perm-toolbar-subrow">
                <div class="perm-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" class="perm-search-input" id="mpSearchInput" placeholder="Buscar por módulo o acción (ej: compras, aprobar, crear, ver)..." oninput="filtrarPermisosModal()">
                </div>
                <div class="perm-bulk-actions">
                    <button type="button" class="btn-bulk-subtle" onclick="marcarTodosPermisos(true)">
                        <i class="fas fa-check-double" style="color:#10b981;"></i> Seleccionar Todos
                    </button>
                    <button type="button" class="btn-bulk-subtle" onclick="marcarTodosPermisos(false)">
                        <i class="fas fa-times" style="color:#ef4444;"></i> Desmarcar Todos
                    </button>
                </div>
            </div>
        </div>

        <!-- Cuerpo del Modal (Scrollable con Grid de Módulos) -->
        <div class="perm-modal-body" id="mpModalBody">
            <div style="text-align:center; padding: 60px;" id="mpLoadingState">
                <i class="fas fa-spinner fa-spin" style="font-size:38px; color:#6366f1;"></i>
                <p style="margin-top: 14px; color: #64748b; font-weight: 600; font-size: 14px;">Cargando catálogo completo de autorizaciones...</p>
            </div>
            <div class="perm-modules-container" id="mpModulesContainer" style="display:none;">
                <!-- Se llena dinámicamente vía renderPermisosModulos() -->
            </div>
        </div>

        <!-- Footer del Modal (Sticky) -->
        <div class="perm-modal-footer">
            <div style="display:flex; align-items:center; gap: 10px;">
                <span class="perm-footer-counter-pill">
                    <i class="fas fa-key"></i> Permisos Asignados: <strong id="mpCounterAsignados">0</strong> de <span id="mpCounterTotal">92</span>
                </span>
                <span id="mpStatusMsg" style="font-size: 12.5px; font-weight: 600;"></span>
            </div>
            <div style="display:flex; align-items:center; gap: 10px;">
                <button type="button" onclick="cerrarModalPermisos()" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:9px 18px; border-radius:10px; font-weight:600; font-size:13px; cursor:pointer;">Cerrar</button>
                <button type="button" class="perm-btn-save" id="btnGuardarPermisosModal" onclick="guardarPermisosModal()">
                    <i class="fas fa-save"></i> Guardar Matriz de Permisos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════════
// ESTADO GLOBAL Y DEFINICIONES DE FASES DEL ERP
// ═══════════════════════════════════════════════════════════════════
let currentCargoId = null;
let currentPermisosAsignados = new Set();
let catalogoPermisosGlobal = null;
let listaCargosGlobal = [];
let csrfTokenGlobal = "<?= $csrfToken ?>";
let currentFaseFilter = 'all';

// Mapeo oficial de módulos a las 5 Fases del ERP
const MODULOS_FASES = {
    // Fase 1: Oportunidades & Comercial
    'convocatorias': 1,
    'oportunidades': 1,
    'licitaciones': 1,
    'propuestas': 1,
    'contratos': 1,
    'clientes': 1,
    'empresas': 1,

    // Fase 2: Planificación & Proyectos
    'proyectos': 2,
    'edt': 2,
    'profesionales': 2,

    // Fase 3: Operaciones & Logística
    'compras': 3,
    'almacenes': 3,
    'proveedores': 3,
    'materiales': 3,
    'equipos': 3,
    'personal': 3,
    'subcontratos': 3,

    // Fase 4: Control Financiero & Económico
    'centros_costo': 4,
    'valorizaciones': 4,
    'facturacion': 4,
    'cuentas': 4,
    'tesoreria': 4,

    // Fase 5: Estratégico, BI & Seguridad
    'analisis': 5,
    'configuracion': 5,
    'organizacion': 5,
    'usuarios': 5
};

// Iconos y colores representativos por módulo
const MODULO_ICONS = {
    'convocatorias': { icon: 'fas fa-bullhorn', color: '#2563eb', bg: '#eff6ff' },
    'oportunidades': { icon: 'fas fa-crosshairs', color: '#2563eb', bg: '#eff6ff' },
    'licitaciones': { icon: 'fas fa-gavel', color: '#2563eb', bg: '#eff6ff' },
    'propuestas': { icon: 'fas fa-file-signature', color: '#2563eb', bg: '#eff6ff' },
    'contratos': { icon: 'fas fa-file-contract', color: '#2563eb', bg: '#eff6ff' },
    'clientes': { icon: 'fas fa-users', color: '#2563eb', bg: '#eff6ff' },
    'empresas': { icon: 'fas fa-building', color: '#2563eb', bg: '#eff6ff' },

    'proyectos': { icon: 'fas fa-project-diagram', color: '#0284c7', bg: '#e0f2fe' },
    'edt': { icon: 'fas fa-sitemap', color: '#0284c7', bg: '#e0f2fe' },
    'profesionales': { icon: 'fas fa-user-graduate', color: '#0284c7', bg: '#e0f2fe' },

    'compras': { icon: 'fas fa-shopping-cart', color: '#0d9488', bg: '#ccfbf1' },
    'almacenes': { icon: 'fas fa-warehouse', color: '#0d9488', bg: '#ccfbf1' },
    'proveedores': { icon: 'fas fa-truck', color: '#0d9488', bg: '#ccfbf1' },
    'materiales': { icon: 'fas fa-cubes', color: '#0d9488', bg: '#ccfbf1' },
    'equipos': { icon: 'fas fa-tools', color: '#0d9488', bg: '#ccfbf1' },
    'personal': { icon: 'fas fa-user-friends', color: '#0d9488', bg: '#ccfbf1' },
    'subcontratos': { icon: 'fas fa-handshake', color: '#0d9488', bg: '#ccfbf1' },

    'centros_costo': { icon: 'fas fa-calculator', color: '#d97706', bg: '#fef3c7' },
    'valorizaciones': { icon: 'fas fa-chart-pie', color: '#d97706', bg: '#fef3c7' },
    'facturacion': { icon: 'fas fa-file-invoice-dollar', color: '#d97706', bg: '#fef3c7' },
    'cuentas': { icon: 'fas fa-wallet', color: '#d97706', bg: '#fef3c7' },
    'tesoreria': { icon: 'fas fa-coins', color: '#d97706', bg: '#fef3c7' },

    'analisis': { icon: 'fas fa-chart-line', color: '#7c3aed', bg: '#ede9fe' },
    'configuracion': { icon: 'fas fa-cog', color: '#7c3aed', bg: '#ede9fe' },
    'organizacion': { icon: 'fas fa-shield-alt', color: '#7c3aed', bg: '#ede9fe' },
    'usuarios': { icon: 'fas fa-user-shield', color: '#7c3aed', bg: '#ede9fe' }
};

// ═══════════════════════════════════════════════════════════════════
// APERTURA Y CARGA DE LA MATRIZ EN EL GRAN MODAL
// ═══════════════════════════════════════════════════════════════════
function abrirModalPermisos(cargoId) {
    document.getElementById('modalPermisosBackdrop').style.display = 'flex';
    document.getElementById('mpLoadingState').style.display = 'block';
    document.getElementById('mpModulesContainer').style.display = 'none';
    document.getElementById('mpSearchInput').value = '';
    document.getElementById('mpStatusMsg').textContent = '';
    
    // Resetear filtro a Todas las Fases
    document.querySelectorAll('.perm-fase-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('.perm-fase-btn').classList.add('active');
    currentFaseFilter = 'all';

    cargarPermisosCargo(cargoId);
}

function cargarPermisosCargo(cargoId) {
    currentCargoId = parseInt(cargoId);
    document.getElementById('mpLoadingState').style.display = 'block';
    document.getElementById('mpModulesContainer').style.display = 'none';

    fetch('<?= BASE_URL ?>/organizacion/apiPermisosCargo/' + cargoId)
        .then(response => {
            if (!response.ok) throw new Error('Error al obtener datos');
            return response.json();
        })
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Error');

            currentPermisosAsignados = new Set(data.asignados.map(id => parseInt(id)));
            catalogoPermisosGlobal = data.catalogo;
            listaCargosGlobal = data.cargos;
            if (data.csrf_token) csrfTokenGlobal = data.csrf_token;

            // Encabezado del modal
            document.getElementById('mpCargoNombre').textContent = data.cargo.nombre;
            document.getElementById('mpCargoArea').textContent = 'Área: ' + data.cargo.area_nombre;
            document.getElementById('mpCargoCodigo').textContent = 'Cód: ' + data.cargo.codigo;
            document.getElementById('mpCargoNivel').textContent = 'Nivel ' + data.cargo.nivel_jerarquico;
            
            const badgeAprobador = document.getElementById('mpCargoAprobador');
            if (data.cargo.puede_aprobar == 1) {
                badgeAprobador.innerHTML = '<i class="fas fa-check-double"></i> Puede Aprobar';
                badgeAprobador.style.background = 'rgba(16, 185, 129, 0.2)';
                badgeAprobador.style.color = '#34d399';
            } else {
                badgeAprobador.innerHTML = '<i class="fas fa-ban"></i> Sin Aprobación';
                badgeAprobador.style.background = 'rgba(148, 163, 184, 0.2)';
                badgeAprobador.style.color = '#cbd5e1';
            }

            // Poblar dropdown de puestos si no está poblado
            const selectCargo = document.getElementById('mpCargoSelect');
            selectCargo.innerHTML = '';
            data.cargos.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `Niv. ${c.nivel_jerarquico} · ${c.nombre} (${c.area_nombre})`;
                if (c.id === currentCargoId) opt.selected = true;
                selectCargo.appendChild(opt);
            });

            // Renderizar módulos
            renderPermisosModulos();

            document.getElementById('mpLoadingState').style.display = 'none';
            document.getElementById('mpModulesContainer').style.display = 'grid';
        })
        .catch(err => {
            document.getElementById('mpLoadingState').innerHTML = `
                <div style="color: #ef4444; font-size: 15px; font-weight: 700;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                    Error al cargar permisos: ${err.message}
                </div>
            `;
        });
}

function renderPermisosModulos() {
    const container = document.getElementById('mpModulesContainer');
    container.innerHTML = '';
    let totalPermisosCount = 0;

    for (const [modulo, permisos] of Object.entries(catalogoPermisosGlobal)) {
        totalPermisosCount += permisos.length;
        const faseNum = MODULOS_FASES[modulo] || 5;
        const iconMeta = MODULO_ICONS[modulo] || { icon: 'fas fa-id-badge', color: '#6366f1', bg: '#ede9fe' };
        
        // Contar cuántos están activos en este módulo
        const activosEnModulo = permisos.filter(p => currentPermisosAsignados.has(parseInt(p.id))).length;
        const todosActivos = activosEnModulo === permisos.length;

        const card = document.createElement('div');
        card.className = 'pm-module-card';
        card.setAttribute('data-modulo', modulo);
        card.setAttribute('data-fase', faseNum);

        let itemsHtml = '';
        permisos.forEach(p => {
            const isChecked = currentPermisosAsignados.has(parseInt(p.id));
            const moduloLabel = (p.nombre || modulo || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            const accionUpper = (p.accion || '').toUpperCase();
            const desc = p.descripcion || `${accionUpper} en ${moduloLabel}`;
            itemsHtml += `
                <div class="pm-item-row" data-perm-id="${p.id}" data-search="${modulo} ${p.accion} ${moduloLabel} ${desc}">
                    <div class="pm-item-info">
                        <span class="pm-item-name">${accionUpper} · ${moduloLabel}</span>
                        <span class="pm-item-desc" title="${desc}">${desc}</span>
                    </div>
                    <label class="sw-perm">
                        <input type="checkbox" class="sw-input-perm" data-perm-id="${p.id}" data-modulo="${modulo}" ${isChecked ? 'checked' : ''} onchange="onPermisoSwitchChange(this, ${p.id}, '${modulo}')">
                        <span class="sw-slider"></span>
                    </label>
                </div>
            `;
        });

        card.innerHTML = `
            <div class="pm-module-header">
                <div class="pm-module-title">
                    <div class="pm-module-icon" style="background: ${iconMeta.bg}; color: ${iconMeta.color};">
                        <i class="${iconMeta.icon}"></i>
                    </div>
                    <span>${modulo.replace('_', ' ')}</span>
                    <span id="badge-count-${modulo}" style="font-size: 10.5px; font-weight: 700; color: #64748b; background: #e2e8f0; padding: 2px 7px; border-radius: 10px;">
                        ${activosEnModulo}/${permisos.length}
                    </span>
                </div>
                <label class="pm-module-toggle-wrap">
                    <input type="checkbox" id="chk-mod-${modulo}" onchange="toggleModuloPermisos(this, '${modulo}')" ${todosActivos ? 'checked' : ''}>
                    <span>Marcar módulo</span>
                </label>
            </div>
            <div class="pm-items-list">
                ${itemsHtml}
            </div>
        `;

        container.appendChild(card);
    }

    document.getElementById('mpCounterTotal').textContent = totalPermisosCount;
    actualizarContadorPermisos();
}

function onPermisoSwitchChange(input, permId, modulo) {
    permId = parseInt(permId);
    if (input.checked) {
        currentPermisosAsignados.add(permId);
    } else {
        currentPermisosAsignados.delete(permId);
    }

    // Actualizar badge del módulo
    const permisosModulo = catalogoPermisosGlobal[modulo] || [];
    const activosEnModulo = permisosModulo.filter(p => currentPermisosAsignados.has(parseInt(p.id))).length;
    const badge = document.getElementById('badge-count-' + modulo);
    if (badge) badge.textContent = `${activosEnModulo}/${permisosModulo.length}`;

    const chkMod = document.getElementById('chk-mod-' + modulo);
    if (chkMod) chkMod.checked = (activosEnModulo === permisosModulo.length);

    actualizarContadorPermisos();
}

function toggleModuloPermisos(checkbox, modulo) {
    const isChecked = checkbox.checked;
    const permisosModulo = catalogoPermisosGlobal[modulo] || [];

    permisosModulo.forEach(p => {
        const pId = parseInt(p.id);
        if (isChecked) {
            currentPermisosAsignados.add(pId);
        } else {
            currentPermisosAsignados.delete(pId);
        }
    });

    // Actualizar switches en pantalla de ese módulo
    const switches = document.querySelectorAll(`.sw-input-perm[data-modulo="${modulo}"]`);
    switches.forEach(sw => sw.checked = isChecked);

    const badge = document.getElementById('badge-count-' + modulo);
    if (badge) badge.textContent = isChecked ? `${permisosModulo.length}/${permisosModulo.length}` : `0/${permisosModulo.length}`;

    actualizarContadorPermisos();
}

function marcarTodosPermisos(marcar) {
    if (!catalogoPermisosGlobal) return;

    for (const [modulo, permisos] of Object.entries(catalogoPermisosGlobal)) {
        permisos.forEach(p => {
            const pId = parseInt(p.id);
            if (marcar) {
                currentPermisosAsignados.add(pId);
            } else {
                currentPermisosAsignados.delete(pId);
            }
        });
        const chkMod = document.getElementById('chk-mod-' + modulo);
        if (chkMod) chkMod.checked = marcar;

        const badge = document.getElementById('badge-count-' + modulo);
        if (badge) badge.textContent = marcar ? `${permisos.length}/${permisos.length}` : `0/${permisos.length}`;
    }

    document.querySelectorAll('.sw-input-perm').forEach(sw => sw.checked = marcar);
    actualizarContadorPermisos();
}

function actualizarContadorPermisos() {
    const counter = document.getElementById('mpCounterAsignados');
    if (counter) counter.textContent = currentPermisosAsignados.size;
}

// ═══════════════════════════════════════════════════════════════════
// FILTROS EN TIEMPO REAL: FASES ERP Y BÚSQUEDA
// ═══════════════════════════════════════════════════════════════════
function filtrarPorFasePermisos(fase, btn) {
    currentFaseFilter = fase;
    document.querySelectorAll('.perm-fase-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    aplicarFiltrosCombinados();
}

function filtrarPermisosModal() {
    aplicarFiltrosCombinados();
}

function aplicarFiltrosCombinados() {
    const q = document.getElementById('mpSearchInput').value.trim().toLowerCase();
    const cards = document.querySelectorAll('.pm-module-card');

    cards.forEach(card => {
        const cardFase = card.getAttribute('data-fase');
        const modulo = card.getAttribute('data-modulo');
        const cumpleFase = (currentFaseFilter === 'all' || cardFase == currentFaseFilter);

        let cumpleBusqueda = false;
        if (!q) {
            cumpleBusqueda = true;
            // Restaurar todas las filas visibles dentro de la tarjeta
            card.querySelectorAll('.pm-item-row').forEach(row => row.style.display = 'flex');
        } else {
            // Verificar si el módulo coincide o alguna fila interna coincide
            const filas = card.querySelectorAll('.pm-item-row');
            let coincidencias = 0;
            filas.forEach(row => {
                const searchData = (row.getAttribute('data-search') || '').toLowerCase();
                if (searchData.includes(q)) {
                    row.style.display = 'flex';
                    coincidencias++;
                } else {
                    row.style.display = 'none';
                }
            });
            cumpleBusqueda = coincidencias > 0 || modulo.toLowerCase().includes(q);
        }

        if (cumpleFase && cumpleBusqueda) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

// ═══════════════════════════════════════════════════════════════════
// GUARDADO ASÍNCRONO DE LA MATRIZ DE PERMISOS
// ═══════════════════════════════════════════════════════════════════
function guardarPermisosModal() {
    if (!currentCargoId) return;

    const btn = document.getElementById('btnGuardarPermisosModal');
    const statusMsg = document.getElementById('mpStatusMsg');
    const originalHtml = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    statusMsg.style.color = '#6366f1';
    statusMsg.textContent = 'Sincronizando permisos en base de datos...';

    const formData = new URLSearchParams();
    formData.append('cargo_id', currentCargoId);
    formData.append('csrf_token', csrfTokenGlobal);
    Array.from(currentPermisosAsignados).forEach(id => {
        formData.append('permisos[]', id);
    });

    fetch('<?= BASE_URL ?>/organizacion/guardarPermisos?ajax=1', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData.toString()
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;

        if (res.success) {
            statusMsg.style.color = '#15803d';
            statusMsg.innerHTML = '<i class="fas fa-check-circle"></i> ¡Permisos guardados con éxito!';

            // Actualizar el número de permisos en la tarjeta de la página principal
            const cardCounter = document.getElementById('cargo-perm-count-' + currentCargoId);
            if (cardCounter) cardCounter.textContent = currentPermisosAsignados.size;

            setTimeout(() => {
                if (statusMsg) statusMsg.textContent = '';
            }, 3000);
        } else {
            statusMsg.style.color = '#dc2626';
            statusMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (res.message || 'Error al guardar');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        statusMsg.style.color = '#dc2626';
        statusMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error de conexión: ' + err.message;
    });
}

function cerrarModalPermisos() {
    document.getElementById('modalPermisosBackdrop').style.display = 'none';
}

// ═══════════════════════════════════════════════════════════════════
// FILTRADO DINÁMICO REACTIVO (TEXTO, NIVEL Y ÁREA)
// ═══════════════════════════════════════════════════════════════════
let currentFilterState = {
    texto: '',
    nivel: '<?= !empty($selectedNivel) ? $selectedNivel : "all" ?>',
    areaId: '<?= !empty($selectedAreaId) ? $selectedAreaId : "all" ?>'
};

function aplicarFiltrosCargos() {
    const textoInput = document.getElementById('filtroTextoCargo');
    const texto = (textoInput ? textoInput.value : '').toLowerCase().trim();
    currentFilterState.texto = texto;

    // Mostrar/ocultar botón limpiar texto
    const btnClear = document.getElementById('btnLimpiarTexto');
    if (btnClear) btnClear.style.display = texto ? 'block' : 'none';

    const nivel = currentFilterState.nivel;
    const areaId = currentFilterState.areaId;

    let totalVisibles = 0;
    const allSections = document.querySelectorAll('.nivel-section');

    allSections.forEach(sec => {
        const secNivel = sec.getAttribute('data-nivel');
        let secCardsVisibles = 0;

        // Si el nivel general no coincide, ocultamos toda la sección
        if (nivel !== 'all' && secNivel != nivel) {
            sec.style.display = 'none';
            return;
        }

        const cards = sec.querySelectorAll('.cargo-card');
        cards.forEach(card => {
            const cardAreaId = card.getAttribute('data-area-id');
            const cardSearch = (card.getAttribute('data-search') || '').toLowerCase();

            // Validación por área
            const coincideArea = (areaId === 'all' || cardAreaId == areaId);

            // Validación por texto
            const coincideTexto = (!texto || cardSearch.includes(texto));

            if (coincideArea && coincideTexto) {
                card.style.display = 'flex';
                secCardsVisibles++;
                totalVisibles++;
            } else {
                card.style.display = 'none';
            }
        });

        // Ocultar cabecera de nivel si ninguna tarjeta dentro de ella es visible
        if (secCardsVisibles > 0) {
            sec.style.display = 'block';
            const countBadge = sec.querySelector('.tier-count');
            if (countBadge) {
                countBadge.textContent = `${secCardsVisibles} ${secCardsVisibles === 1 ? 'puesto' : 'puestos'}`;
            }
        } else {
            sec.style.display = 'none';
        }
    });

    // Estado vacío si no hay coincidencias
    const emptyState = document.getElementById('emptyCargosFilter');
    if (emptyState) {
        emptyState.style.display = (totalVisibles === 0) ? 'block' : 'none';
    }

    // Actualizar badge de conteo en la barra rápida
    const lblConteo = document.getElementById('lblConteoCargos');
    if (lblConteo) {
        lblConteo.innerHTML = `Mostrando <strong>${totalVisibles}</strong> de <?= $totalCargos ?> puestos`;
    }

    // Mostrar/ocultar botón de resetear
    const btnReset = document.getElementById('btnResetFiltros');
    if (btnReset) {
        const hayFiltrosActivos = (texto !== '' || nivel !== 'all' || areaId !== 'all');
        btnReset.style.display = hayFiltrosActivos ? 'inline-flex' : 'none';
    }
}

function onNivelSelectChange(val) {
    currentFilterState.nivel = val;
    // Sincronizar botones de píldoras rápidas
    document.querySelectorAll('#quickLevelSegments .quick-segment-btn').forEach(btn => {
        if (btn.getAttribute('data-level') == val) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    aplicarFiltrosCargos();
}

function setQuickNivel(val) {
    currentFilterState.nivel = val;
    const select = document.getElementById('selectFiltroNivel');
    if (select) select.value = val;
    onNivelSelectChange(val);
}

function seleccionarArea(areaId, areaNombre = null) {
    currentFilterState.areaId = areaId;

    const select = document.getElementById('selectFiltroArea');
    if (select) select.value = areaId;

    // Actualizar chip de área activa
    const chip = document.getElementById('badgeFiltroAreaActiva');
    const lbl = document.getElementById('lblAreaActiva');

    if (areaId === 'all') {
        if (chip) chip.style.display = 'none';
    } else {
        if (!areaNombre && select && select.selectedIndex >= 0) {
            areaNombre = select.options[select.selectedIndex].text.replace(/^[↳\s]+/, '');
        }
        if (lbl) lbl.textContent = areaNombre || 'Área seleccionada';
        if (chip) chip.style.display = 'inline-flex';
    }

    // Actualizar estados seleccionados en el modal
    document.querySelectorAll('.item-area-modal').forEach(card => {
        if (card.getAttribute('data-id') == areaId) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
    const cardAll = document.getElementById('cardAreaModalAll');
    if (cardAll) {
        if (areaId === 'all') cardAll.classList.add('selected');
        else cardAll.classList.remove('selected');
    }

    // Sincronizar URL sin recargar
    try {
        const url = new URL(window.location);
        if (areaId === 'all') {
            url.searchParams.delete('area_id');
        } else {
            url.searchParams.set('area_id', areaId);
        }
        window.history.replaceState({}, '', url);
    } catch (e) {}

    aplicarFiltrosCargos();
}

function seleccionarAreaModal(areaId) {
    let areaNombre = '';
    if (areaId !== 'all') {
        const card = document.getElementById('cardAreaModal_' + areaId);
        if (card) {
            areaNombre = card.getAttribute('data-nombre') || '';
        }
    }
    seleccionarArea(areaId, areaNombre);
    cerrarModalSelectArea();
}

function abrirModalSeleccionarArea() {
    const inputModal = document.getElementById('busquedaAreaModal');
    if (inputModal) {
        inputModal.value = '';
        filtrarAreasEnModal();
    }
    document.getElementById('modalSelectAreaBackdrop').style.display = 'flex';
    if (inputModal) setTimeout(() => inputModal.focus(), 100);
}

function cerrarModalSelectArea() {
    document.getElementById('modalSelectAreaBackdrop').style.display = 'none';
}

function filtrarAreasEnModal() {
    const txt = (document.getElementById('busquedaAreaModal').value || '').toLowerCase().trim();
    const items = document.querySelectorAll('#modalAreaGrid .item-area-modal');
    items.forEach(it => {
        const searchVal = (it.getAttribute('data-search') || '').toLowerCase();
        if (!txt || searchVal.includes(txt)) {
            it.style.display = 'block';
        } else {
            it.style.display = 'none';
        }
    });
}

function limpiarTextoCargo() {
    const input = document.getElementById('filtroTextoCargo');
    if (input) {
        input.value = '';
        aplicarFiltrosCargos();
        input.focus();
    }
}

function resetearTodosFiltros() {
    const txtInput = document.getElementById('filtroTextoCargo');
    if (txtInput) txtInput.value = '';
    currentFilterState.texto = '';
    currentFilterState.nivel = 'all';
    currentFilterState.areaId = 'all';

    const selectNivel = document.getElementById('selectFiltroNivel');
    if (selectNivel) selectNivel.value = 'all';

    const selectArea = document.getElementById('selectFiltroArea');
    if (selectArea) selectArea.value = 'all';

    document.querySelectorAll('#quickLevelSegments .quick-segment-btn').forEach(btn => {
        if (btn.getAttribute('data-level') === 'all') btn.classList.add('active');
        else btn.classList.remove('active');
    });

    const chip = document.getElementById('badgeFiltroAreaActiva');
    if (chip) chip.style.display = 'none';

    document.querySelectorAll('.item-area-modal').forEach(c => c.classList.remove('selected'));
    const cardAll = document.getElementById('cardAreaModalAll');
    if (cardAll) cardAll.classList.add('selected');

    try {
        const url = new URL(window.location);
        url.searchParams.delete('area_id');
        url.searchParams.delete('nivel');
        window.history.replaceState({}, '', url);
    } catch (e) {}

    aplicarFiltrosCargos();
}

// ═══════════════════════════════════════════════════════════════════
// MODALES ADICIONALES (CREAR CARGO Y ASIGNAR PUESTO)
// ═══════════════════════════════════════════════════════════════════
function abrirModalCargo() {
    document.getElementById('modalCargoTitle').innerHTML = '<i class="fas fa-id-badge" style="color:#d97706;"></i> Nuevo Cargo Corporativo';
    document.getElementById('formCargo').action = '<?= BASE_URL ?>/organizacion/crearCargo';
    document.getElementById('cargo_id').value = '';
    document.getElementById('cargo_nombre').value = '';
    document.getElementById('cargo_codigo').value = '';
    document.getElementById('cargo_area_id').value = '';
    document.getElementById('cargo_nivel_jerarquico').value = '3';
    document.getElementById('cargo_puede_aprobar').checked = false;
    document.getElementById('cargo_descripcion').value = '';
    document.getElementById('modalCargoBackdrop').style.display = 'flex';
}

function editarCargoModal(c) {
    document.getElementById('modalCargoTitle').innerHTML = '<i class="fas fa-edit" style="color:#d97706;"></i> Editar Cargo: ' + c.nombre;
    document.getElementById('formCargo').action = '<?= BASE_URL ?>/organizacion/editarCargo/' + c.id;
    document.getElementById('cargo_id').value = c.id;
    document.getElementById('cargo_nombre').value = c.nombre;
    document.getElementById('cargo_codigo').value = c.codigo;
    document.getElementById('cargo_area_id').value = c.area_id;
    document.getElementById('cargo_nivel_jerarquico').value = c.nivel_jerarquico;
    document.getElementById('cargo_puede_aprobar').checked = (c.puede_aprobar == 1);
    document.getElementById('cargo_descripcion').value = c.descripcion || '';
    document.getElementById('modalCargoBackdrop').style.display = 'flex';
}

function cerrarModalCargo() {
    document.getElementById('modalCargoBackdrop').style.display = 'none';
}

// Cierre al hacer click en el backdrop o tecla Escape
window.onclick = function(e) {
    if (e.target === document.getElementById('modalCargoBackdrop')) cerrarModalCargo();
    if (e.target === document.getElementById('modalAsignarBackdrop')) cerrarModalAsignar();
    if (e.target === document.getElementById('modalPermisosBackdrop')) cerrarModalPermisos();
    if (e.target === document.getElementById('modalSelectAreaBackdrop')) cerrarModalSelectArea();
};

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalPermisos();
        cerrarModalCargo();
        cerrarModalAsignar();
        cerrarModalSelectArea();
    }
});

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const openPermisosId = urlParams.get('open_permisos');
    if (openPermisosId) {
        abrirModalPermisos(openPermisosId);
    }

    // Inicializar filtros si vinieron por PHP o URL
    if (currentFilterState.areaId !== 'all' || currentFilterState.nivel !== 'all') {
        aplicarFiltrosCargos();
    }
});
</script>
