<?php
/**
 * Vista Principal: Estructura Organizacional & Organigrama Ejecutivo
 * Diseño Todo en una Pantalla (Zero Scroll Horizontal), Moderno y Altamente Animado
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$isAdmin = in_array($userRol, ['superadmin', 'admin']);

// Organizar áreas por ID para búsqueda rápida en la vista
$areasById = [];
foreach ($data['areasFlat'] as $a) {
    $areasById[$a['id']] = $a;
}

// 1. Organizar áreas por ID, parent_id y nivel
$areasById = [];
$areasByParent = [];
$areasByNivel = [];

foreach ($data['areasFlat'] as $a) {
    $areasById[$a['id']] = $a;
    $pid = ($a['parent_id'] !== null && $a['parent_id'] !== '') ? (int)$a['parent_id'] : 0;
    $areasByParent[$pid][] = $a;
    $areasByNivel[(int)$a['nivel']][] = $a;
}

// 2. Identificar CEO (Nivel 0)
$ceoArea = $areasByNivel[0][0] ?? null;
$ceoId = $ceoArea ? (int)$ceoArea['id'] : 1;

// 3. Identificar divisiones de Nivel 1 (hijas de CEO)
$gerenciaAdmin = null;
$gerenciaProyectos = null;
$areaTI = null;
$areaEmpresasPropias = null;

$divsNivel1 = $areasByParent[$ceoId] ?? [];
foreach ($divsNivel1 as $a) {
    $codigo = strtoupper(trim($a['codigo']));
    if ($codigo === 'GAF' || stripos($a['nombre'], 'Administraci') !== false) {
        $gerenciaAdmin = $a;
    } elseif ($codigo === 'GP' || stripos($a['nombre'], 'Proyectos') !== false) {
        $gerenciaProyectos = $a;
    } elseif ($codigo === 'TI' || stripos($a['nombre'], 'Tecnolog') !== false) {
        $areaTI = $a;
    } elseif ($codigo === 'EMP-PROP' || stripos($a['nombre'], 'Empresas') !== false) {
        $areaEmpresasPropias = $a;
    }
}

// 4. Sub-áreas de Administración y Finanzas (Nivel 2, parent = GAF)
$subAdmin = $gerenciaAdmin ? ($areasByParent[(int)$gerenciaAdmin['id']] ?? []) : [];

// 5. Sub-áreas de Gerencia de Proyectos (Nivel 2, parent = GP)
$subProyectos = [];
$consultoriaArea = null;
$ejecucionesArea = null;
$licitacionesArea = null;

if ($gerenciaProyectos) {
    $hijosGP = $areasByParent[(int)$gerenciaProyectos['id']] ?? [];
    foreach ($hijosGP as $a) {
        $cod = strtoupper(trim($a['codigo']));
        if ($cod === 'CST' || stripos($a['nombre'], 'Consultor') !== false) {
            $consultoriaArea = $a;
        } elseif ($cod === 'EJEC' || stripos($a['nombre'], 'Ejecuci') !== false) {
            $ejecucionesArea = $a;
        } elseif ($cod === 'LIC' || stripos($a['nombre'], 'Licitaci') !== false) {
            $licitacionesArea = $a;
        } else {
            $subProyectos[] = $a;
        }
    }
}

// 6. Sub-niveles de Consultoría (según modelo oficial ASCONSULT):
// 6.1 Líneas de Servicio Principales (Expedientes Técnicos y Supervisiones)
// 6.2 Especialidades Técnicas por Tipo de Proyecto (Edificaciones, Viales, Estudios)
$lineasConsultoria = [];
$especialidadesConsultoria = [];
$unidadesConsultoria = $consultoriaArea ? ($areasByParent[(int)$consultoriaArea['id']] ?? []) : [];

if ($consultoriaArea) {
    foreach ($unidadesConsultoria as $u) {
        $cod = strtoupper(trim($u['codigo']));
        if ($cod === 'EXPT' || $cod === 'SUPV' || stripos($u['nombre'], 'Expediente') !== false || stripos($u['nombre'], 'Supervisi') !== false) {
            $lineasConsultoria[] = $u;
        } else {
            $especialidadesConsultoria[] = $u;
        }
    }
}

// 7. Unidades de Ejecuciones (Nivel 3 si las hubiera)
$unidadesEjecucion = $ejecucionesArea ? ($areasByParent[(int)$ejecucionesArea['id']] ?? []) : [];

/**
 * Función recursiva para renderizar la vista de Árbol Jerárquico Completo
 */
function renderTreeBranch($nodes, $isAdmin) {
    if (empty($nodes)) return;
    echo '<ul class="org-tree-list">';
    foreach ($nodes as $node) {
        $nivel = (int)$node['nivel'];
        $nivelClass = 'tree-level-' . $nivel;
        $nivelBadge = 'NIVEL ' . $nivel;
        if ($nivel === 0) $nivelBadge = 'CEO · NIVEL 0';
        elseif ($nivel === 1) $nivelBadge = 'GERENCIA · NIVEL 1';
        elseif ($nivel === 2) $nivelBadge = 'DEPTO · NIVEL 2';
        elseif ($nivel === 3) $nivelBadge = 'UNIDAD · NIVEL 3';

        $hasChildren = !empty($node['hijos']);
        echo '<li class="org-tree-node ' . $nivelClass . '">';
        echo '  <div class="tree-node-card" onclick="location.href=\'' . BASE_URL . '/organizacion/cargos?area_id=' . $node['id'] . '\'">';
        echo '    <div class="tree-node-icon" style="background:' . htmlspecialchars($node['color'] ?: '#6366f1') . ';">';
        echo '      <i class="' . htmlspecialchars($node['icono'] ?: 'fas fa-sitemap') . '"></i>';
        echo '    </div>';
        echo '    <div class="tree-node-info">';
        echo '      <div class="tree-node-header">';
        echo '        <span class="tree-node-name">' . htmlspecialchars($node['nombre']) . '</span>';
        echo '        <span class="tree-node-code">' . htmlspecialchars($node['codigo']) . '</span>';
        echo '        <span class="tree-node-level-tag">' . $nivelBadge . '</span>';
        echo '      </div>';
        if (!empty($node['descripcion'])) {
            echo '      <div class="tree-node-desc">' . htmlspecialchars($node['descripcion']) . '</div>';
        }
        echo '    </div>';
        echo '    <div class="tree-node-meta">';
        echo '      <span><i class="fas fa-briefcase"></i> ' . (int)$node['total_cargos'] . ' cargos</span>';
        echo '      <span><i class="fas fa-users"></i> ' . (int)$node['total_usuarios'] . ' pers.</span>';
        if ($isAdmin) {
            echo '      <button class="action-icon-btn" onclick="event.stopPropagation(); editarAreaModal(' . htmlspecialchars(json_encode($node)) . ')"><i class="fas fa-edit"></i></button>';
        }
        echo '    </div>';
        echo '  </div>';

        if ($hasChildren) {
            renderTreeBranch($node['hijos'], $isAdmin);
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
/* ─── Variables de Color y Diseño ─── */
:root {
    --org-bg-canvas: #f8fafc;
    --org-card-bg: #ffffff;
    --org-border: #e2e8f0;
    --org-purple: #7c3aed;
    --org-blue: #0284c7;
    --org-orange: #ea580c;
    --org-emerald: #059669;
}

@keyframes floatGentle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}

@keyframes pulseBorder {
    0%, 100% { border-color: rgba(124, 58, 237, 0.4); box-shadow: 0 4px 20px rgba(124, 58, 237, 0.12); }
    50% { border-color: rgba(124, 58, 237, 0.8); box-shadow: 0 8px 30px rgba(124, 58, 237, 0.25); }
}

@keyframes slideInUpFade {
    0% { opacity: 0; transform: translateY(16px); }
    100% { opacity: 1; transform: translateY(0); }
}

.org-dashboard-wrapper {
    padding: 16px 24px 40px;
    max-width: 100%;
    margin: 0 auto;
    box-sizing: border-box;
    animation: slideInUpFade 0.4s ease-out;
}

/* ─── Top Header & Controls ─── */
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
    border: 1px solid var(--org-border);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
}

.org-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.org-header-icon {
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
}

.org-header-text h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
}

.org-header-text span {
    font-size: 12.5px;
    color: #64748b;
}

.org-top-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ─── KPI Pills Bar ─── */
.org-kpi-bar {
    display: flex;
    gap: 14px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.org-kpi-pill {
    flex: 1;
    min-width: 170px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: all 0.25s ease;
}

.org-kpi-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}

.org-kpi-pill-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.org-kpi-pill-val {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.org-kpi-pill-lbl {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    margin-top: 3px;
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
    padding: 8px 16px;
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
}

.view-mode-btn.active {
    background: #ffffff;
    color: #4f46e5;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* ─── Executive Board (Todo en 1 pantalla) ─── */
.org-executive-board {
    background: #ffffff;
    border: 1px solid var(--org-border);
    border-radius: 20px;
    padding: 24px 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    position: relative;
    overflow: hidden;
}

/* Conectores SVG sutiles */
.org-board-connectors {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

/* Nivel 0: Tarjeta CEO */
.ceo-row-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 24px;
    position: relative;
    z-index: 2;
}

.card-ceo {
    background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);
    border: 2px solid #a855f7;
    border-radius: 18px;
    padding: 16px 24px;
    width: 440px;
    max-width: 100%;
    box-shadow: 0 10px 25px rgba(168, 85, 247, 0.15);
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    animation: pulseBorder 3s infinite;
}

.card-ceo:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 16px 35px rgba(168, 85, 247, 0.25);
}

.ceo-avatar-badge {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
}

.ceo-info {
    flex: 1;
    min-width: 0;
}

.ceo-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 3px;
}

.ceo-title-row h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
}

.badge-ceo-pill {
    background: #ede9fe;
    color: #6d28d9;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 6px;
    letter-spacing: 0.5px;
}

.ceo-desc {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 8px;
}

.ceo-footer-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}

/* Línea conectora central */
.tree-stem-line {
    width: 2px;
    height: 24px;
    background: #cbd5e1;
    margin: -12px auto 14px;
}

/* ─── Grid de las 2 Divisiones Principales (1 pantalla) ─── */
.org-divisions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    position: relative;
    z-index: 2;
}

@media (max-width: 1024px) {
    .org-divisions-grid {
        grid-template-columns: 1fr;
    }
}

/* Columna de División */
.org-division-col {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 18px;
    padding: 18px;
    transition: all 0.25s;
}

.org-division-col:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

/* Encabezado de la División (Nivel 1) */
.division-header-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 14px 18px;
    border: 1.5px solid transparent;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    position: relative;
    transition: all 0.2s;
}

.division-header-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06);
}

.div-header-admin {
    border-left: 5px solid #0284c7;
}

.div-header-proy {
    border-left: 5px solid #ea580c;
}

.div-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #ffffff;
    flex-shrink: 0;
}

.div-header-info {
    flex: 1;
    min-width: 0;
}

.div-header-info h4 {
    margin: 0 0 2px;
    font-size: 15.5px;
    font-weight: 700;
    color: #0f172a;
}

.div-header-info span {
    font-size: 12px;
    color: #64748b;
}

/* Sub-departamentos (Grid compacto de 2 columnas dentro de cada división) */
.sub-departments-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.dept-mini-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 94px;
}

.dept-mini-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}

.dept-mini-top {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 6px;
}

.dept-mini-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #ffffff;
    flex-shrink: 0;
}

.dept-mini-title h5 {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dept-mini-code {
    font-size: 10.5px;
    font-weight: 700;
    color: #64748b;
}

.dept-mini-desc {
    font-size: 11.5px;
    color: #64748b;
    line-height: 1.35;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.dept-mini-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: #475569;
    border-top: 1px dashed #f1f5f9;
    padding-top: 6px;
}

.dept-mini-footer span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* ─── Tarjeta Destacada: Consultoría (Jerarquía Oficial ASCONSULT: Líneas + Especialidades) ─── */
.dept-card-featured-cst {
    grid-column: 1 / -1;
    background: #ffffff;
    border: 1.5px solid #fed7aa;
    border-radius: 14px;
    padding: 14px 16px;
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.05);
    transition: all 0.2s;
}

.dept-card-featured-cst:hover {
    border-color: #ea580c;
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.1);
}

.cst-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.cst-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cst-title {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    cursor: pointer;
}

.cst-title:hover {
    color: #ea580c;
}

.cst-subtitle {
    font-size: 11.5px;
    color: #64748b;
}

.badge-pill-lvl2 {
    font-size: 10px;
    font-weight: 800;
    background: #ffedd5;
    color: #c2410c;
    padding: 1px 6px;
    border-radius: 4px;
}

.cst-badge-meta {
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
}

/* 1. Líneas de Servicio Operativas (Expedientes Técnicos y Supervisiones) */
.cst-lines-section {
    margin-bottom: 6px;
}

.cst-lines-label {
    font-size: 11px;
    font-weight: 700;
    color: #7c2d12;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cst-lines-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.cst-line-card {
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
    border: 1.5px solid #fdba74;
    border-radius: 11px;
    padding: 10px 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 82px;
}

.cst-line-card:hover {
    transform: translateY(-2px);
    border-color: #ea580c;
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.15);
}

.cst-line-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}

.cst-line-icon {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 12px;
}

.cst-line-name {
    font-size: 12.5px;
    font-weight: 800;
    color: #9a3412;
}

.cst-code-pill {
    font-size: 10px;
    font-weight: 800;
    color: #c2410c;
    background: #ffffff;
    padding: 1px 6px;
    border-radius: 4px;
    border: 1px solid #fed7aa;
}

.cst-line-desc {
    font-size: 10.5px;
    color: #7c2d12;
    opacity: 0.9;
    line-height: 1.25;
    margin-bottom: 6px;
}

.cst-line-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    font-weight: 700;
    color: #c2410c;
    border-top: 1px dashed rgba(234, 88, 12, 0.25);
    padding-top: 4px;
}

/* Conector Visual Jerárquico hacia Especialidades */
.cst-tree-stem-connector {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 8px 0 6px;
}

.cst-stem-line {
    flex: 1;
    height: 1px;
    background: #fed7aa;
}

.cst-stem-badge {
    font-size: 9.5px;
    font-weight: 800;
    color: #c2410c;
    background: #ffedd5;
    padding: 2px 8px;
    border-radius: 12px;
    border: 1px solid #fdba74;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* 2. Especialidades por Tipo de Proyecto (Edificaciones, Viales, Estudios) */
.cst-specialties-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

@media (max-width: 640px) {
    .cst-specialties-grid {
        grid-template-columns: 1fr;
    }
}

.cst-spec-chip {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-left: 3px solid #f97316;
    border-radius: 8px;
    padding: 7px 9px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.cst-spec-chip:hover {
    border-color: #f97316;
    background: #fff7ed;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

.cst-spec-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: #fff7ed;
    color: #ea580c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    flex-shrink: 0;
}

.cst-spec-info {
    flex: 1;
    min-width: 0;
}

.cst-spec-name {
    font-size: 11.5px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cst-spec-sub {
    font-size: 9.5px;
    color: #64748b;
    font-weight: 600;
}

/* Roles Operativos en Ejecuciones */
.dept-roles-badges {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px dashed #f1f5f9;
}

.dept-role-pill {
    font-size: 10px;
    font-weight: 700;
    color: #9a3412;
    background: #fff7ed;
    padding: 2px 6px;
    border-radius: 4px;
    border: 1px solid #fed7aa;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* ─── Direcciones Estratégicas Nivel 1 ─── */
.strategic-direct-block {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed #e2e8f0;
}

.strategic-direct-title {
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.strategic-direct-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.strategic-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 84px;
}

.strategic-card:hover {
    transform: translateY(-2px);
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* ─── View Switcher Toolbar & Legends ─── */
.board-view-switch-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
    background: #ffffff;
    padding: 10px 18px;
    border-radius: 14px;
    border: 1px solid var(--org-border);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.view-switch-pill-group {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
}

.view-switch-btn {
    border: none;
    background: transparent;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s;
}

.view-switch-btn:hover {
    color: #0f172a;
}

.view-switch-btn.active {
    background: #ffffff;
    color: #4f46e5;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

.view-switch-legend {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.legend-dot-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
}

.legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.dot-l0 { background: #7c3aed; }
.dot-l1 { background: #ea580c; }
.dot-l2 { background: #0284c7; }
.dot-l3 { background: #059669; }

/* ─── Árbol Jerárquico Completo (Nivel 0 al 3) ─── */
.org-tree-wrapper {
    background: #ffffff;
    border: 1px solid var(--org-border);
    border-radius: 20px;
    padding: 24px 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    animation: slideInUpFade 0.3s ease-out;
}

.org-tree-list {
    list-style: none;
    padding-left: 28px;
    margin: 0;
    position: relative;
}

.org-tree-list::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 0;
    bottom: 14px;
    width: 2px;
    background: #e2e8f0;
}

.org-tree-node {
    position: relative;
    margin: 10px 0;
}

.org-tree-node::before {
    content: '';
    position: absolute;
    left: -16px;
    top: 22px;
    width: 14px;
    height: 2px;
    background: #cbd5e1;
}

.tree-node-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.2s;
    cursor: pointer;
}

.tree-node-card:hover {
    transform: translateX(4px);
    border-color: #6366f1;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.1);
}

.tree-node-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 15px;
    flex-shrink: 0;
}

.tree-node-info {
    flex: 1;
    min-width: 0;
}

.tree-node-header {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.tree-node-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
}

.tree-node-code {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    background: #f1f5f9;
    padding: 1px 6px;
    border-radius: 4px;
}

.tree-node-level-tag {
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
}

.tree-level-0 .tree-node-level-tag { background: #ede9fe; color: #6d28d9; }
.tree-level-1 .tree-node-level-tag { background: #ffedd5; color: #c2410c; }
.tree-level-2 .tree-node-level-tag { background: #e0f2fe; color: #0369a1; }
.tree-level-3 .tree-node-level-tag { background: #dcfce7; color: #15803d; }

.tree-node-desc {
    font-size: 11.5px;
    color: #64748b;
    margin-top: 2px;
}

.tree-node-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
}

/* Acciones en hover */
.action-icon-btn {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
}

.action-icon-btn:hover {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
}

/* Botones principales de la vista */
.btn-org-brand {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: #ffffff;
    border: none;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    transition: all 0.2s;
}

.btn-org-brand:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
}

.btn-org-light {
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-org-light:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}
</style>

<div class="org-dashboard-wrapper">
    <!-- Top Unified Navigation Bar -->
    <?php
    $activeTab = 'tablero';
    $orgNavTitle = 'Organigrama & Estructura Corporativa';
    $orgNavSubtitle = 'Flujo de integración ERP · Áreas, cargos y control de permisos jerárquicos';
    include __DIR__ . '/navbar_organizacion.php';
    ?>

    <!-- KPI Compact Pills Bar -->
    <div class="org-kpi-bar">
        <div class="org-kpi-pill">
            <div class="org-kpi-pill-icon" style="background:#ede9fe; color:#7c3aed;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <div class="org-kpi-pill-val"><?= $data['totalAreas'] ?></div>
                <div class="org-kpi-pill-lbl">Áreas Corporativas</div>
            </div>
        </div>

        <div class="org-kpi-pill">
            <div class="org-kpi-pill-icon" style="background:#fef3c7; color:#d97706;">
                <i class="fas fa-briefcase"></i>
            </div>
            <div>
                <div class="org-kpi-pill-val"><?= $data['totalCargos'] ?></div>
                <div class="org-kpi-pill-lbl">Cargos & Puestos</div>
            </div>
        </div>

        <div class="org-kpi-pill">
            <div class="org-kpi-pill-icon" style="background:#dcfce7; color:#15803d;">
                <i class="fas fa-user-check"></i>
            </div>
            <div>
                <div class="org-kpi-pill-val"><?= $data['totalUsuarios'] ?></div>
                <div class="org-kpi-pill-lbl">Colaboradores Activos</div>
            </div>
        </div>

        <div class="org-kpi-pill">
            <div class="org-kpi-pill-icon" style="background:#cffafe; color:#0891b2;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <div class="org-kpi-pill-val" style="font-size:15px; color:#0891b2;">Anti-Tampering</div>
                <div class="org-kpi-pill-lbl">Control Estricto por Área</div>
            </div>
        </div>
    </div>

    <!-- View Switcher & Hierarchy Legend Bar -->
    <div class="board-view-switch-bar">
        <div class="view-switch-pill-group">
            <button type="button" class="view-switch-btn active" id="btnViewBoard" onclick="switchOrganigramaView('board')">
                <i class="fas fa-th-large"></i> Tablero Ejecutivo (1 Pantalla)
            </button>
            <button type="button" class="view-switch-btn" id="btnViewTree" onclick="switchOrganigramaView('tree')">
                <i class="fas fa-sitemap"></i> Vista de Árbol Jerárquico Completo (Nivel 0 al 3)
            </button>
        </div>
        <div class="view-switch-legend">
            <span class="legend-dot-pill"><span class="legend-dot dot-l0"></span> Nivel 0: CEO</span>
            <span class="legend-dot-pill"><span class="legend-dot dot-l1"></span> Nivel 1: Gerencias</span>
            <span class="legend-dot-pill"><span class="legend-dot dot-l2"></span> Nivel 2: Departamentos</span>
            <span class="legend-dot-pill"><span class="legend-dot dot-l3"></span> Nivel 3: Unidades Especializadas</span>
        </div>
    </div>

    <!-- Executive Board: Organigrama Completo en 1 Sola Pantalla -->
    <div class="org-executive-board" id="orgExecutiveBoard">
        <!-- NIVEL 0: GERENCIA GENERAL (CEO) -->
        <?php if ($ceoArea): ?>
        <div class="ceo-row-wrap">
            <div class="card-ceo" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $ceoArea['id'] ?>'" style="cursor:pointer;">
                <div class="ceo-avatar-badge">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="ceo-info">
                    <div class="ceo-title-row">
                        <h3><?= htmlspecialchars($ceoArea['nombre']) ?></h3>
                        <span class="badge-ceo-pill">CEO · NIVEL 0</span>
                    </div>
                    <div class="ceo-desc"><?= htmlspecialchars($ceoArea['descripcion'] ?: 'Dirección ejecutiva corporativa') ?></div>
                    <div class="ceo-footer-meta">
                        <span><i class="fas fa-user-tie" style="color:#7c3aed;"></i> <?= (int)$ceoArea['total_usuarios'] ?> directivos</span>
                        <span><i class="fas fa-id-badge" style="color:#7c3aed;"></i> <?= (int)$ceoArea['total_cargos'] ?> puestos</span>
                        <?php if ($isAdmin): ?>
                        <div style="margin-left:auto; display:flex; gap:6px;">
                            <button class="action-icon-btn" title="Editar CEO" onclick="event.stopPropagation(); editarAreaModal(<?= htmlspecialchars(json_encode($ceoArea)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="tree-stem-line"></div>
        <?php endif; ?>

        <!-- DIVISIONES Y DEPARTAMENTOS EN GRID BALANCEADO (TODO EN 1 PANTALLA) -->
        <div class="org-divisions-grid">
            <!-- ══════ COLUMNA 1: GERENCIA DE PROYECTOS & OPERACIONES ══════ -->
            <div class="org-division-col">
                <?php if ($gerenciaProyectos): ?>
                <div class="division-header-card div-header-proy">
                    <div class="div-header-icon" style="background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);">
                        <i class="fas fa-hard-hat"></i>
                    </div>
                    <div class="div-header-info">
                        <h4><?= htmlspecialchars($gerenciaProyectos['nombre']) ?></h4>
                        <span><?= htmlspecialchars($gerenciaProyectos['codigo']) ?> · Gerencia de Operaciones y Producción (NIVEL 1)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="background:#ffedd5; color:#c2410c; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px;">
                            3 Departamentos · <?= count($unidadesConsultoria) ?> Unidades
                        </span>
                        <?php if ($isAdmin): ?>
                        <button class="action-icon-btn" title="Editar Gerencia" onclick="editarAreaModal(<?= htmlspecialchars(json_encode($gerenciaProyectos)) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sub-departamentos de Proyectos (Nivel 2) -->
                <div class="sub-departments-grid">
                    <?php if ($licitacionesArea): ?>
                    <div class="dept-mini-card" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $licitacionesArea['id'] ?>'">
                        <div>
                            <div class="dept-mini-top">
                                <div class="dept-mini-icon" style="background: <?= htmlspecialchars($licitacionesArea['color'] ?: '#ea580c') ?>;">
                                    <i class="<?= htmlspecialchars($licitacionesArea['icono'] ?: 'fas fa-file-signature') ?>"></i>
                                </div>
                                <div class="dept-mini-title">
                                    <h5><?= htmlspecialchars($licitacionesArea['nombre']) ?></h5>
                                    <span class="dept-mini-code"><?= htmlspecialchars($licitacionesArea['codigo']) ?> · NIVEL 2</span>
                                </div>
                            </div>
                            <div class="dept-mini-desc">
                                <?= htmlspecialchars($licitacionesArea['descripcion'] ?: 'Estudios de mercado y propuestas de licitación') ?>
                            </div>
                        </div>
                        <div class="dept-mini-footer">
                            <span><i class="fas fa-user-friends"></i> <?= (int)$licitacionesArea['total_usuarios'] ?> pers.</span>
                            <span><i class="fas fa-briefcase"></i> <?= (int)$licitacionesArea['total_cargos'] ?> cargos</span>
                            <?php if ($isAdmin): ?>
                            <span onclick="event.stopPropagation(); editarAreaModal(<?= htmlspecialchars(json_encode($licitacionesArea)) ?>)" style="cursor:pointer; color:#6366f1;">
                                <i class="fas fa-pen"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($ejecucionesArea): ?>
                    <div class="dept-mini-card" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $ejecucionesArea['id'] ?>'">
                        <div>
                            <div class="dept-mini-top">
                                <div class="dept-mini-icon" style="background: <?= htmlspecialchars($ejecucionesArea['color'] ?: '#d97706') ?>;">
                                    <i class="<?= htmlspecialchars($ejecucionesArea['icono'] ?: 'fas fa-hard-hat') ?>"></i>
                                </div>
                                <div class="dept-mini-title">
                                    <h5><?= htmlspecialchars($ejecucionesArea['nombre']) ?></h5>
                                    <span class="dept-mini-code"><?= htmlspecialchars($ejecucionesArea['codigo']) ?> · NIVEL 2</span>
                                </div>
                            </div>
                            <div class="dept-mini-desc">
                                <?= htmlspecialchars($ejecucionesArea['descripcion'] ?: 'Ejecución de infraestructura y obras civiles') ?>
                            </div>
                            <!-- Roles Operativos según Modelo ASCONSULT -->
                            <div class="dept-roles-badges">
                                <span class="dept-role-pill" title="Dirección en campo"><i class="fas fa-user-hard-hat"></i> Residente</span>
                                <span class="dept-role-pill" title="Gestión operativa de obra"><i class="fas fa-user-cog"></i> Administración</span>
                            </div>
                        </div>
                        <div class="dept-mini-footer">
                            <span><i class="fas fa-user-friends"></i> <?= (int)$ejecucionesArea['total_usuarios'] ?> pers.</span>
                            <span><i class="fas fa-briefcase"></i> <?= (int)$ejecucionesArea['total_cargos'] ?> cargos</span>
                            <?php if ($isAdmin): ?>
                            <span onclick="event.stopPropagation(); editarAreaModal(<?= htmlspecialchars(json_encode($ejecucionesArea)) ?>)" style="cursor:pointer; color:#6366f1;">
                                <i class="fas fa-pen"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- TARJETA DESTACADA: CONSULTORÍA (JERARQUÍA SEGÚN MODELO ASCONSULT) -->
                    <?php if ($consultoriaArea): ?>
                    <div class="dept-card-featured-cst">
                        <div class="cst-header-row">
                            <div class="cst-header-left">
                                <div class="dept-mini-icon" style="background: <?= htmlspecialchars($consultoriaArea['color'] ?: '#b45309') ?>; width:34px; height:34px;">
                                    <i class="<?= htmlspecialchars($consultoriaArea['icono'] ?: 'fas fa-drafting-compass') ?>"></i>
                                </div>
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <h5 class="cst-title" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $consultoriaArea['id'] ?>'">
                                            <?= htmlspecialchars($consultoriaArea['nombre']) ?> (<?= htmlspecialchars($consultoriaArea['codigo']) ?>)
                                        </h5>
                                        <span class="badge-pill-lvl2">NIVEL 2</span>
                                    </div>
                                    <span class="cst-subtitle">Proyectos de servicios profesionales y consultoría técnica</span>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="cst-badge-meta">
                                    <i class="fas fa-layer-group" style="color:#ea580c;"></i> <?= count($lineasConsultoria) ?> Líneas · <?= count($especialidadesConsultoria) ?> Especialidades
                                </span>
                                <?php if ($isAdmin): ?>
                                <button class="action-icon-btn" title="Editar Consultoría" onclick="editarAreaModal(<?= htmlspecialchars(json_encode($consultoriaArea)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 1. Líneas de Servicio Operativas: Expedientes Técnicos y Supervisiones -->
                        <div class="cst-lines-section">
                            <div class="cst-lines-label">
                                <i class="fas fa-project-diagram"></i> Líneas de Servicio Operativas
                            </div>
                            <div class="cst-lines-grid">
                                <?php foreach ($lineasConsultoria as $linea): ?>
                                <div class="cst-line-card" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $linea['id'] ?>'">
                                    <div class="cst-line-top">
                                        <div style="display:flex; align-items:center; gap:7px;">
                                            <div class="cst-line-icon" style="background:<?= htmlspecialchars($linea['color'] ?: '#ea580c') ?>;">
                                                <i class="<?= htmlspecialchars($linea['icono'] ?: 'fas fa-folder-open') ?>"></i>
                                            </div>
                                            <span class="cst-line-name"><?= htmlspecialchars($linea['nombre']) ?></span>
                                        </div>
                                        <span class="cst-code-pill"><?= htmlspecialchars($linea['codigo']) ?></span>
                                    </div>
                                    <div class="cst-line-desc">
                                        <?= htmlspecialchars($linea['descripcion'] ?: ($linea['codigo'] === 'EXPT' ? 'Elaboración de expedientes técnicos y proyectos' : 'Control técnico y seguimiento en obra')) ?>
                                    </div>
                                    <div class="cst-line-footer">
                                        <span><i class="fas fa-briefcase"></i> <?= (int)$linea['total_cargos'] ?> cargo</span>
                                        <span><i class="fas fa-user-friends"></i> <?= (int)$linea['total_usuarios'] ?> pers.</span>
                                        <?php if ($isAdmin): ?>
                                        <span onclick="event.stopPropagation(); editarAreaModal(<?= htmlspecialchars(json_encode($linea)) ?>)" style="cursor:pointer; color:#6366f1; margin-left:auto;">
                                            <i class="fas fa-pen"></i>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Conector visual jerárquico hacia Especialidades -->
                        <div class="cst-tree-stem-connector">
                            <div class="cst-stem-line"></div>
                            <div class="cst-stem-badge"><i class="fas fa-sitemap"></i> Especialidades por tipo de proyecto</div>
                            <div class="cst-stem-line"></div>
                        </div>

                        <!-- 2. Especialidades Técnicas: Edificaciones, Viales, Estudios -->
                        <div class="cst-specialties-grid">
                            <?php foreach ($especialidadesConsultoria as $esp): ?>
                            <div class="cst-spec-chip" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $esp['id'] ?>'" title="<?= htmlspecialchars($esp['descripcion'] ?: $esp['nombre']) ?>">
                                <div class="cst-spec-icon">
                                    <i class="<?= htmlspecialchars($esp['icono'] ?: 'fas fa-cube') ?>"></i>
                                </div>
                                <div class="cst-spec-info">
                                    <div class="cst-spec-name"><?= htmlspecialchars($esp['nombre']) ?></div>
                                    <div class="cst-spec-sub">
                                        <span class="cst-spec-code"><?= htmlspecialchars($esp['codigo']) ?></span>
                                        <span>· Especialidad</span>
                                    </div>
                                </div>
                                <?php if ($isAdmin): ?>
                                <span onclick="event.stopPropagation(); editarAreaModal(<?= htmlspecialchars(json_encode($esp)) ?>)" style="cursor:pointer; color:#94a3b8; font-size:10px;">
                                    <i class="fas fa-pen"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ══════ COLUMNA 2: ADMINISTRACIÓN Y FINANZAS + DIRECCIONES ESTRATÉGICAS ══════ -->
            <div class="org-division-col">
                <?php if ($gerenciaAdmin): ?>
                <div class="division-header-card div-header-admin">
                    <div class="div-header-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="div-header-info">
                        <h4><?= htmlspecialchars($gerenciaAdmin['nombre']) ?></h4>
                        <span><?= htmlspecialchars($gerenciaAdmin['codigo']) ?> · Gerencia de Soporte y Control Financiero (NIVEL 1)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px;">
                            <?= count($subAdmin) ?> Departamentos
                        </span>
                        <?php if ($isAdmin): ?>
                        <button class="action-icon-btn" title="Editar Gerencia" onclick="editarAreaModal(<?= htmlspecialchars(json_encode($gerenciaAdmin)) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sub-departamentos de Administración (2x2 Grid - Nivel 2) -->
                <div class="sub-departments-grid">
                    <?php foreach ($subAdmin as $dept): ?>
                    <div class="dept-mini-card" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $dept['id'] ?>'">
                        <div>
                            <div class="dept-mini-top">
                                <div class="dept-mini-icon" style="background: <?= htmlspecialchars($dept['color'] ?: '#0284c7') ?>;">
                                    <i class="<?= htmlspecialchars($dept['icono'] ?: 'fas fa-building') ?>"></i>
                                </div>
                                <div class="dept-mini-title">
                                    <h5><?= htmlspecialchars($dept['nombre']) ?></h5>
                                    <span class="dept-mini-code"><?= htmlspecialchars($dept['codigo']) ?> · NIVEL 2</span>
                                </div>
                            </div>
                            <div class="dept-mini-desc">
                                <?= htmlspecialchars($dept['descripcion'] ?: 'Departamento corporativo') ?>
                            </div>
                        </div>
                        <div class="dept-mini-footer">
                            <span><i class="fas fa-user-friends"></i> <?= (int)$dept['total_usuarios'] ?> pers.</span>
                            <span><i class="fas fa-briefcase"></i> <?= (int)$dept['total_cargos'] ?> cargos</span>
                            <?php if ($isAdmin): ?>
                            <span onclick="event.stopPropagation(); editarAreaModal(<?= htmlspecialchars(json_encode($dept)) ?>)" style="cursor:pointer; color:#6366f1;">
                                <i class="fas fa-pen"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- DIRECCIONES Y GERENCIAS ESTRATÉGICAS (NIVEL 1 DIRECTAS A CEO) -->
                <div class="strategic-direct-block">
                    <div class="strategic-direct-title">
                        <i class="fas fa-network-wired" style="color:#6366f1;"></i> Direcciones y Gerencias Estratégicas (NIVEL 1)
                    </div>
                    <div class="strategic-direct-grid">
                        <!-- TI (Nivel 1) -->
                        <?php if ($areaTI): ?>
                        <div class="strategic-card" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $areaTI['id'] ?>'">
                            <div>
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:5px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="dept-mini-icon" style="background: <?= htmlspecialchars($areaTI['color'] ?: '#4f46e5') ?>; width:28px; height:28px; font-size:12px;">
                                            <i class="<?= htmlspecialchars($areaTI['icono'] ?: 'fas fa-desktop') ?>"></i>
                                        </div>
                                        <div>
                                            <h5 style="margin:0; font-size:12.5px; font-weight:700; color:#0f172a;"><?= htmlspecialchars($areaTI['nombre']) ?></h5>
                                            <span style="font-size:10px; font-weight:700; color:#6366f1;"><?= htmlspecialchars($areaTI['codigo']) ?></span>
                                        </div>
                                    </div>
                                    <span style="font-size:9.5px; font-weight:800; background:#ede9fe; color:#6d28d9; padding:1px 5px; border-radius:4px;">NIVEL 1</span>
                                </div>
                                <div style="font-size:11px; color:#64748b; line-height:1.3; margin-bottom:6px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    <?= htmlspecialchars($areaTI['descripcion'] ?: 'Infraestructura, soporte e integraciones de sistemas ERP') ?>
                                </div>
                            </div>
                            <div class="dept-mini-footer">
                                <span><i class="fas fa-user-friends"></i> <?= (int)$areaTI['total_usuarios'] ?> pers.</span>
                                <span><i class="fas fa-briefcase"></i> <?= (int)$areaTI['total_cargos'] ?> cargos</span>
                                <?php if ($isAdmin): ?>
                                <span onclick="event.stopPropagation(); editarAreaModal(<?= htmlspecialchars(json_encode($areaTI)) ?>)" style="cursor:pointer; color:#6366f1;">
                                    <i class="fas fa-pen"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- EMPRESAS PROPIAS (Nivel 1) -->
                        <?php if ($areaEmpresasPropias): ?>
                        <div class="strategic-card" onclick="location.href='<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $areaEmpresasPropias['id'] ?>'">
                            <div>
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:5px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="dept-mini-icon" style="background: <?= htmlspecialchars($areaEmpresasPropias['color'] ?: '#059669') ?>; width:28px; height:28px; font-size:12px;">
                                            <i class="<?= htmlspecialchars($areaEmpresasPropias['icono'] ?: 'fas fa-building') ?>"></i>
                                        </div>
                                        <div>
                                            <h5 style="margin:0; font-size:12.5px; font-weight:700; color:#0f172a;"><?= htmlspecialchars($areaEmpresasPropias['nombre']) ?></h5>
                                            <span style="font-size:10px; font-weight:700; color:#059669;"><?= htmlspecialchars($areaEmpresasPropias['codigo']) ?></span>
                                        </div>
                                    </div>
                                    <span style="font-size:9.5px; font-weight:800; background:#dcfce7; color:#15803d; padding:1px 5px; border-radius:4px;">NIVEL 1</span>
                                </div>
                                <div style="font-size:11px; color:#64748b; line-height:1.3; margin-bottom:6px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    <?= htmlspecialchars($areaEmpresasPropias['descripcion'] ?: 'Consorcios y unidades operativas de negocio') ?>
                                </div>
                            </div>
                            <div class="dept-mini-footer">
                                <span><i class="fas fa-user-friends"></i> <?= (int)$areaEmpresasPropias['total_usuarios'] ?> pers.</span>
                                <span><i class="fas fa-briefcase"></i> <?= (int)$areaEmpresasPropias['total_cargos'] ?> cargos</span>
                                <?php if ($isAdmin): ?>
                                <span onclick="event.stopPropagation(); editarAreaModal(<?= htmlspecialchars(json_encode($areaEmpresasPropias)) ?>)" style="cursor:pointer; color:#6366f1;">
                                    <i class="fas fa-pen"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Árbol Jerárquico Completo (Nivel 0 al 3) -->
    <div class="org-tree-wrapper" id="orgTreeViewWrapper" style="display:none;">
        <div style="margin-bottom: 18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h4 style="margin:0; font-size:16px; font-weight:800; color:#0f172a;"><i class="fas fa-sitemap" style="color:#6366f1;"></i> Vista de Árbol Jerárquico Completo</h4>
                <span style="font-size:12px; color:#64748b;">Estructura corporativa vertical con trazabilidad de dependencia del Nivel 0 al Nivel 3</span>
            </div>
            <button class="btn-org-light" onclick="switchOrganigramaView('board')">
                <i class="fas fa-th-large"></i> Volver a Tablero
            </button>
        </div>
        <?php renderTreeBranch($data['areasTree'], $isAdmin); ?>
    </div>
</div>

<!-- ─── MODAL CREAR / EDITAR ÁREA ─── -->
<div class="org-modal-backdrop" id="modalAreaBackdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#ffffff; border-radius:20px; width:100%; max-width:540px; box-shadow:0 25px 50px rgba(0,0,0,0.25); overflow:hidden; border:1px solid #e2e8f0; animation:slideInUpFade 0.25s;">
        <div style="padding:18px 24px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <h3 id="modalAreaTitle" style="margin:0; font-size:17px; font-weight:700; color:#0f172a;"><i class="fas fa-sitemap" style="color:#6366f1;"></i> Nueva Área</h3>
            <button onclick="cerrarModalArea()" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>
        <form id="formArea" method="POST" action="<?= BASE_URL ?>/organizacion/crearArea" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="area_id" id="area_id" value="">
            <div style="padding:22px; max-height:75vh; overflow-y:auto;">
                <div style="display:grid; grid-template-columns:2fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:5px;">Nombre del Área *</label>
                        <input type="text" name="nombre" id="area_nombre" required placeholder="Ej: Tesorería" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13.5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:5px;">Código *</label>
                        <input type="text" name="codigo" id="area_codigo" required placeholder="Ej: TES" style="text-transform:uppercase; width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13.5px; box-sizing:border-box;">
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:5px;">Área Superior (Padre)</label>
                    <select name="parent_id" id="area_parent_id" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13.5px; box-sizing:border-box;">
                        <option value="">-- Ninguna (Área Raíz / Gerencia General) --</option>
                        <?php foreach ($data['areasFlat'] as $af): ?>
                            <option value="<?= $af['id'] ?>"><?= str_repeat('— ', (int)$af['nivel']) . htmlspecialchars($af['nombre']) ?> (<?= $af['codigo'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:5px;">Icono FontAwesome</label>
                        <input type="text" name="icono" id="area_icono" value="fas fa-sitemap" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13.5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:5px;">Color</label>
                        <input type="color" name="color" id="area_color" value="#6366f1" style="width:100%; height:40px; padding:2px; border:1.5px solid #cbd5e1; border-radius:10px; box-sizing:border-box; cursor:pointer;">
                    </div>
                </div>

                <div style="margin-bottom:6px;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:5px;">Descripción</label>
                    <textarea name="descripcion" id="area_descripcion" rows="3" placeholder="Responsabilidades..." style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13.5px; box-sizing:border-box;"></textarea>
                </div>
            </div>
            <div style="padding:14px 22px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="cerrarModalArea()" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:8px 16px; border-radius:9px; font-weight:600; font-size:13.5px; cursor:pointer;">Cancelar</button>
                <button type="submit" style="background:linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color:#ffffff; border:none; padding:8px 18px; border-radius:9px; font-weight:600; font-size:13.5px; cursor:pointer; box-shadow:0 4px 10px rgba(99,102,241,0.3);">
                    <i class="fas fa-save"></i> Guardar Área
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ─── MODAL ASIGNAR PUESTO UNIFICADO ─── -->
<?php include __DIR__ . '/modal_asignar_puesto.php'; ?>

<script>
function abrirModalArea() {
    document.getElementById('modalAreaTitle').innerHTML = '<i class="fas fa-sitemap" style="color:#6366f1;"></i> Nueva Área Organizacional';
    document.getElementById('formArea').action = '<?= BASE_URL ?>/organizacion/crearArea';
    document.getElementById('area_id').value = '';
    document.getElementById('area_nombre').value = '';
    document.getElementById('area_codigo').value = '';
    document.getElementById('area_parent_id').value = '';
    document.getElementById('area_icono').value = 'fas fa-sitemap';
    document.getElementById('area_color').value = '#6366f1';
    document.getElementById('area_descripcion').value = '';
    document.getElementById('modalAreaBackdrop').style.display = 'flex';
}

function editarAreaModal(node) {
    document.getElementById('modalAreaTitle').innerHTML = '<i class="fas fa-edit" style="color:#6366f1;"></i> Editar Área: ' + node.nombre;
    document.getElementById('formArea').action = '<?= BASE_URL ?>/organizacion/editarArea/' + node.id;
    document.getElementById('area_id').value = node.id;
    document.getElementById('area_nombre').value = node.nombre;
    document.getElementById('area_codigo').value = node.codigo;
    document.getElementById('area_parent_id').value = node.parent_id || '';
    document.getElementById('area_icono').value = node.icono || 'fas fa-sitemap';
    document.getElementById('area_color').value = node.color || '#6366f1';
    document.getElementById('area_descripcion').value = node.descripcion || '';
    document.getElementById('modalAreaBackdrop').style.display = 'flex';
}

function cerrarModalArea() {
    document.getElementById('modalAreaBackdrop').style.display = 'none';
}

function switchOrganigramaView(mode) {
    const boardEl = document.getElementById('orgExecutiveBoard');
    const treeEl = document.getElementById('orgTreeViewWrapper');
    const btnBoard = document.getElementById('btnViewBoard');
    const btnTree = document.getElementById('btnViewTree');
    
    if (mode === 'tree') {
        if (boardEl) boardEl.style.display = 'none';
        if (treeEl) treeEl.style.display = 'block';
        if (btnBoard) btnBoard.classList.remove('active');
        if (btnTree) btnTree.classList.add('active');
    } else {
        if (boardEl) boardEl.style.display = 'block';
        if (treeEl) treeEl.style.display = 'none';
        if (btnTree) btnTree.classList.remove('active');
        if (btnBoard) btnBoard.classList.add('active');
    }
}

window.onclick = function(e) {
    if (e.target === document.getElementById('modalAreaBackdrop')) {
        cerrarModalArea();
    }
};
</script>
