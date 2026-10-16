<?php
/**
 * Vista: Planificación WBS / EDT + Costeo Basado en Actividades (Fase 2)
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
require_once __DIR__ . '/../../helpers/PermisosHelper.php';
$csrfToken = CsrfHelper::getToken();
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$isAdmin = in_array($userRol, ['superadmin', 'admin']);
$puedeCrear = PermisosHelper::puede('crear', 'tareas');
$puedeEditar = PermisosHelper::puede('editar', 'tareas');
$puedeEliminar = PermisosHelper::puede('eliminar', 'tareas');

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = fn($v) => 'S/ ' . number_format((float)$v, 2);
// JSON seguro para incrustar dentro de <script>
$jsonSeguro = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$proyectos = $data['proyectos'] ?? [];
$proyectoId = (int)($data['proyectoId'] ?? 0);
$proyectoActual = $data['proyectoActual'] ?? null;
$arbol = $data['arbol'] ?? [];
$resumen = $data['resumen'] ?? [];
$costos = $data['costos'] ?? ['totales' => []];
$graficos = $data['graficos'] ?? [];
$usuarios = $data['usuarios'] ?? [];
$esquemaFaltante = $data['esquemaFaltante'] ?? [];
$insumosPorTarea = $data['insumosPorTarea'] ?? [];
$presInsumos = $data['presupuestoInsumos'] ?? null;
$catalogosInsumos = $data['catalogosInsumos'] ?? null;
$insumosActivo = $presInsumos !== null;   // false si falta la migración (tabla tarea_insumos)

$salud = $resumen['salud'] ?? TareaModel::calcularSalud(0, 0);
$totalEst = (float)($resumen['total_estimado'] ?? 0);
$totalReal = (float)($resumen['total_real'] ?? 0);
$saldo = $totalEst - $totalReal;
$totCat = $costos['totales'] ?? [];

// Avance calculado de actividades padre = promedio de sus hojas descendientes
$avanceCalc = [];
foreach ($arbol as $i => $t) {
    if (!$t['_tiene_hijos']) { continue; }
    $suma = 0; $n = 0;
    for ($j = $i + 1; $j < count($arbol) && $arbol[$j]['_nivel'] > $t['_nivel']; $j++) {
        if (!$arbol[$j]['_tiene_hijos']) { $suma += (float)$arbol[$j]['progreso']; $n++; }
    }
    $avanceCalc[(int)$t['id']] = $n ? round($suma / $n, 1) : (float)$t['progreso'];
}

// Mapa id → datos mínimos para los modales (evita volcar JSON dentro de atributos HTML)
$mapaTareas = [];
foreach ($arbol as $t) {
    $mapaTareas[(int)$t['id']] = [
        'id' => (int)$t['id'], 'codigo' => $t['codigo'], 'nombre' => $t['nombre'],
        'descripcion' => $t['descripcion'], 'fecha_inicio' => $t['fecha_inicio'], 'fecha_fin' => $t['fecha_fin'],
        'progreso' => $t['progreso'], 'estado' => $t['estado'], 'prioridad' => $t['prioridad'],
        'responsable_id' => $t['responsable_id'], 'costo_estimado' => $t['costo_estimado'],
        'costo_real' => $t['costo_real'], 'parent_id' => $t['parent_id'] ?? null,
        'tiene_hijos' => (bool)$t['_tiene_hijos'],
        'insumos_n' => (int)($insumosPorTarea[(int)$t['id']]['n'] ?? 0),
    ];
}

$estadoColors = [
    'pendiente'  => ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => 'Pendiente'],
    'en_proceso' => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'label' => 'En Proceso'],
    'completada' => ['bg' => '#f0fdf4', 'text' => '#15803d', 'label' => 'Completada'],
    'bloqueada'  => ['bg' => '#fef2f2', 'text' => '#dc2626', 'label' => 'Bloqueada']
];
$prioColors = [
    'urgente' => ['bg' => '#fef2f2', 'text' => '#dc2626'],
    'alta'    => ['bg' => '#fff7ed', 'text' => '#c2410c'],
    'media'   => ['bg' => '#fef3c7', 'text' => '#b45309'],
    'baja'    => ['bg' => '#f1f5f9', 'text' => '#475569']
];
?>

<style>
/* ── Módulo WBS / Costeo ABC ─────────────────────────────────────────── */
.wbs-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; }
.wbs-kpi { padding:14px 18px; display:flex; align-items:center; gap:14px; transition:transform .2s, box-shadow .2s; }
.wbs-kpi:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,.06); }
.wbs-kpi-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.wbs-kpi-val { font-size:19px; font-weight:800; color:#0f172a; line-height:1; }
.wbs-kpi-lbl { font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px; }

.wbs-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:10.5px; font-weight:700; }
.wbs-badge.ok { background:#dcfce7; color:#15803d; }
.wbs-badge.warn { background:#fef3c7; color:#b45309; }
.wbs-badge.danger { background:#fee2e2; color:#b91c1c; }
.wbs-badge.neutral { background:#f1f5f9; color:#64748b; }

/* Barra de progreso rayada y animada (equivalente a progress-bar-striped progress-bar-animated) */
.wbs-progress { width:100%; height:10px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
.wbs-progress.lg { height:14px; }
.wbs-progress-bar {
    height:100%; width:0; border-radius:999px; transition:width 1s cubic-bezier(.22,.8,.3,1);
    background-image:linear-gradient(45deg, rgba(255,255,255,.28) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.28) 50%, rgba(255,255,255,.28) 75%, transparent 75%, transparent);
    background-size:1rem 1rem; animation:wbsStripes 1s linear infinite;
}
.wbs-progress-bar.ok { background-color:#16a34a; }
.wbs-progress-bar.warn { background-color:#f59e0b; }
.wbs-progress-bar.danger { background-color:#dc2626; }
.wbs-progress-bar.neutral { background-color:#94a3b8; animation:none; background-image:none; }
@keyframes wbsStripes { from { background-position:1rem 0; } to { background-position:0 0; } }

.wbs-tree-row.es-padre { background:#f8fafc; }
.wbs-tree-row:hover { background:#fbfcfd; }
.wbs-caret { width:22px; height:22px; border:none; background:#e2e8f0; color:#475569; border-radius:6px; cursor:pointer; font-size:10px; flex-shrink:0; transition:transform .2s; }
.wbs-caret.cerrado { transform:rotate(-90deg); }
.wbs-caret-spacer { width:22px; flex-shrink:0; display:inline-block; }
.wbs-icon-btn { width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; border:1px solid #cbd5e1; background:#f1f5f9; color:#334155; text-decoration:none; }
.wbs-icon-btn.azul { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
.wbs-icon-btn.verde { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.wbs-icon-btn.ambar { background:#fffbeb; border-color:#fde68a; color:#b45309; }
.wbs-icon-btn.rojo { background:#fef2f2; border-color:#fee2e2; color:#dc2626; }

.wbs-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.wbs-modal { background:#fff; border-radius:18px; width:95%; box-shadow:0 20px 40px rgba(0,0,0,.2); overflow:hidden; animation:wbsSlideIn .3s ease-out; max-height:92vh; display:flex; flex-direction:column; }
@keyframes wbsSlideIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.wbs-modal-head { background:linear-gradient(135deg,#1e3a8a 0%,#1d4ed8 100%); padding:18px 24px; color:#fff; display:flex; justify-content:space-between; align-items:center; }
.wbs-field label { display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px; }
.wbs-field input, .wbs-field select, .wbs-field textarea { width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box; background:#fff; }
.wbs-tabs { display:flex; gap:6px; flex-wrap:wrap; border-bottom:1px solid #e2e8f0; padding:0 20px; background:#fafafa; }
.wbs-tab { padding:10px 14px; font-size:12.5px; font-weight:700; color:#64748b; border:none; background:none; cursor:pointer; border-bottom:2px solid transparent; }
.wbs-tab.activo { color:#1d4ed8; border-bottom-color:#1d4ed8; }
.wbs-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.wbs-tbl th { text-align:left; padding:8px 10px; background:#f8fafc; color:#475569; font-size:11px; text-transform:uppercase; border-bottom:1px solid #e2e8f0; }
.wbs-tbl td { padding:8px 10px; border-bottom:1px solid #f1f5f9; color:#334155; }
.wbs-tbl td.num, .wbs-tbl th.num { text-align:right; white-space:nowrap; }
.wbs-toggle-btn { padding:5px 12px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:11.5px; font-weight:700; color:#475569; cursor:pointer; }
.wbs-toggle-btn.activo { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }

/* ── Presupuesto por insumos ─────────────────────────────────────────── */
.ins-cat { padding:14px 16px; border-radius:12px; border:1px solid #e2e8f0; background:#fff; transition:transform .2s, box-shadow .2s; }
.ins-cat:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,.06); }
.ins-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.ins-tbl th { text-align:left; padding:7px 8px; background:#f8fafc; color:#475569; font-size:10.5px; text-transform:uppercase; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.ins-tbl td { padding:5px 6px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.ins-tbl input, .ins-tbl select { width:100%; padding:7px 8px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:12.5px; box-sizing:border-box; background:#fff; }
.ins-tbl input:focus, .ins-tbl select:focus { border-color:#3b82f6; outline:none; }
.ins-tbl input.num { text-align:right; }
.ins-tbl input:disabled, .ins-tbl select:disabled { background:#f8fafc; color:#475569; }
.ins-chip { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.ins-add { padding:6px 12px; border-radius:9px; border:1px dashed #94a3b8; background:#fff; font-size:12px; font-weight:700; color:#334155; cursor:pointer; }
.ins-add:hover { background:#f1f5f9; border-color:#475569; }
.ins-row-np td { background:#fef2f2; }
.ins-row-cat td { background:#f8fafc; font-weight:800; color:#0f172a; }
.ins-vinc { font-size:10px; color:#16a34a; margin-top:2px; }
.ins-vinc.libre { color:#94a3b8; }
.ins-err { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; border-radius:9px; padding:9px 12px; font-size:12.5px; margin:10px 20px 0; }
</style>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <?php if (!empty($esquemaFaltante)): ?>
    <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Migración FASE 0 incompleta.</strong> Faltan columnas/tablas: <code><?= $h(implode(', ', $esquemaFaltante)) ?></code>.
        El costeo por actividad ignorará los orígenes que no existan hasta que se apliquen.
    </div>
    <?php endif; ?>

    <!-- Barra de Selección de Proyecto y Encabezado WBS -->
    <div class="wbs-card" style="padding:18px 22px; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.02); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; border-radius:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:22px; box-shadow:0 4px 12px rgba(37,99,235,0.25);">
                <i class="fas fa-sitemap"></i>
            </div>
            <div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; text-transform:uppercase;">Fase 2 · Planificación y Costeo ABC</span>
                    <?php if ($proyectoActual): ?>
                        <span style="font-family:monospace; background:#f1f5f9; color:#475569; font-size:11.5px; font-weight:700; padding:2px 7px; border-radius:5px;">
                            <?= $h($proyectoActual['codigo']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h2 style="font-size:19px; font-weight:800; color:#0f172a; margin:4px 0 0 0;">
                    <?= $proyectoActual ? $h($proyectoActual['nombre']) : 'Selecciona un Proyecto' ?>
                </h2>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <form method="GET" action="<?= BASE_URL ?>/tareas" style="margin:0;">
                <select name="proyecto_id" onchange="this.form.submit()" style="padding:9px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; font-weight:600; color:#1e293b; background:#ffffff; outline:none; cursor:pointer;">
                    <?php if (empty($proyectos)): ?>
                        <option value="">No hay proyectos registrados</option>
                    <?php else: ?>
                        <?php foreach ($proyectos as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === $proyectoId) ? 'selected' : '' ?>>
                                [<?= $h($p['codigo']) ?>] <?= $h($p['nombre']) ?> (<?= (float)$p['avance_porcentaje'] ?>%)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>

            <?php if ($proyectoId > 0 && $puedeEditar): ?>
            <form method="POST" action="<?= BASE_URL ?>/tareas/recalcular/<?= $proyectoId ?>" style="margin:0;"
                  onsubmit="return confirm('Se recalculará el costo real de TODAS las actividades desde compras, almacén y tareo. Los costos reales ingresados manualmente serán reemplazados. ¿Continuar?')">
                <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                <button type="submit" title="Recalcular costo real desde compras, almacén y tareo" style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; padding:9px 16px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:7px;">
                    <i class="fas fa-sync-alt"></i> Recalcular costos
                </button>
            </form>
            <?php endif; ?>

            <?php if ($proyectoId > 0 && $puedeCrear): ?>
            <button onclick="abrirModalCrearTarea(0)" style="background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color:#ffffff; border:none; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:7px; box-shadow:0 4px 12px rgba(37,99,235,0.25);">
                <i class="fas fa-plus"></i> Nueva Actividad
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($proyectoId <= 0): ?>
        <div style="background:#ffffff; border:1.5px dashed #cbd5e1; border-radius:16px; padding:60px 20px; text-align:center;">
            <i class="fas fa-project-diagram" style="font-size:42px; color:#94a3b8; margin-bottom:14px; display:block;"></i>
            <h3 style="font-size:17px; font-weight:700; color:#1e293b; margin:0 0 6px;">No hay ningún proyecto seleccionado</h3>
            <p style="font-size:13px; color:#64748b; margin:0;">Crea un proyecto primero o selecciona uno de la lista para gestionar su estructura WBS.</p>
        </div>
    <?php else: ?>

        <!-- KPIs -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; margin-bottom:20px;">
            <div class="wbs-card wbs-kpi">
                <div class="wbs-kpi-icon" style="background:#eff6ff; color:#2563eb;"><i class="fas fa-tasks"></i></div>
                <div>
                    <div class="wbs-kpi-val"><?= (int)($resumen['total_tareas'] ?? 0) ?>
                        <span style="font-size:12px; font-weight:600; color:#64748b;">· <?= (int)($resumen['completadas'] ?? 0) ?> completadas</span></div>
                    <div class="wbs-kpi-lbl">Actividades WBS</div>
                </div>
            </div>

            <div class="wbs-card wbs-kpi">
                <div class="wbs-kpi-icon" style="background:#f0fdf4; color:#16a34a;"><i class="fas fa-chart-line"></i></div>
                <div style="flex:1;">
                    <div class="wbs-kpi-val" style="color:#16a34a;"><span id="kpiAvance"><?= (float)($resumen['promedio_avance'] ?? 0) ?></span>%</div>
                    <div class="wbs-kpi-lbl">Avance Físico</div>
                    <div class="wbs-progress" style="height:5px; margin-top:6px;">
                        <div class="wbs-progress-bar ok" data-width="<?= min(100, (float)($resumen['promedio_avance'] ?? 0)) ?>"></div>
                    </div>
                </div>
            </div>

            <div class="wbs-card wbs-kpi">
                <div class="wbs-kpi-icon" style="background:#e0e7ff; color:#4338ca;"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="wbs-kpi-val" style="font-size:17px;"><?= $money($totalEst) ?></div>
                    <div class="wbs-kpi-lbl">Presupuesto Base</div>
                </div>
            </div>

            <div class="wbs-card wbs-kpi">
                <div class="wbs-kpi-icon" style="background:#fef3c7; color:#d97706;"><i class="fas fa-coins"></i></div>
                <div style="flex:1;">
                    <div class="wbs-kpi-val" style="font-size:17px;"><?= $money($totalReal) ?></div>
                    <div class="wbs-kpi-lbl">Costo Real
                        <span class="wbs-badge <?= $h($salud['clase']) ?>" style="margin-left:4px;"><?= $h($salud['label']) ?> · <?= $salud['pct'] ?>%</span>
                    </div>
                    <div class="wbs-progress" style="height:5px; margin-top:6px;">
                        <div class="wbs-progress-bar <?= $h($salud['clase']) ?>" data-width="<?= min(100, $salud['pct']) ?>"></div>
                    </div>
                </div>
            </div>

            <div class="wbs-card wbs-kpi">
                <div class="wbs-kpi-icon" style="background:<?= $saldo >= 0 ? '#dcfce7' : '#fee2e2' ?>; color:<?= $saldo >= 0 ? '#15803d' : '#b91c1c' ?>;">
                    <i class="fas <?= $saldo >= 0 ? 'fa-piggy-bank' : 'fa-exclamation-circle' ?>"></i>
                </div>
                <div>
                    <div class="wbs-kpi-val" style="font-size:17px; color:<?= $saldo >= 0 ? '#15803d' : '#b91c1c' ?>;"><?= ($saldo < 0 ? '- ' : '') . $money(abs($saldo)) ?></div>
                    <div class="wbs-kpi-lbl"><?= $saldo >= 0 ? 'Saldo Disponible' : 'Sobrecosto' ?></div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:16px; margin-bottom:20px;">
            <div class="wbs-card" style="padding:18px 20px; grid-column:span 2; min-width:0;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                    <h3 style="margin:0; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-chart-bar" style="color:#2563eb;"></i> Presupuesto vs. Costo Real por Actividad</h3>
                    <div style="display:flex; gap:6px;">
                        <button type="button" class="wbs-toggle-btn activo" data-vista="principales" onclick="cambiarVistaBarras('principales', this)">Principales</button>
                        <button type="button" class="wbs-toggle-btn" data-vista="detalle" onclick="cambiarVistaBarras('detalle', this)">Detalle (hojas)</button>
                    </div>
                </div>
                <div style="position:relative; height:290px;">
                    <canvas id="chartBarras"></canvas>
                </div>
            </div>

            <div class="wbs-card" style="padding:18px 20px; min-width:0;">
                <h3 style="margin:0 0 10px; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-chart-pie" style="color:#8b5cf6;"></i> Distribución del Costo Directo</h3>
                <div style="position:relative; height:290px;">
                    <canvas id="chartDona"></canvas>
                    <div id="donaVacia" style="display:none; position:absolute; inset:0; align-items:center; justify-content:center; text-align:center; color:#94a3b8; font-size:12.5px; padding:20px;">
                        Aún no hay compras, salidas de almacén, subcontratos ni tareo imputados a actividades.
                    </div>
                </div>
            </div>
        </div>

        <?php if ($insumosActivo):
            $catMeta = [
                'material'    => ['fa-cubes', '#2563eb', '#eff6ff'],
                'mano_obra'   => ['fa-hard-hat', '#059669', '#ecfdf5'],
                'equipo'      => ['fa-truck-monster', '#7c3aed', '#f5f3ff'],
                'subcontrato' => ['fa-handshake', '#d97706', '#fffbeb'],
            ];
            $cobIns = $presInsumos['cobertura'];
            $hayInsumos = $cobIns['hojas_con_insumos'] > 0;
        ?>
        <!-- Presupuesto por insumos vs. consumo real -->
        <div class="wbs-card" style="padding:18px 20px; margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
                <div>
                    <h3 style="margin:0; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-balance-scale" style="color:#0ea5e9;"></i> Presupuesto por insumos vs. consumo real</h3>
                    <div style="font-size:12px; color:#64748b; margin-top:3px;">Compara lo presupuestado en cada actividad (materiales, mano de obra, equipos y subcontratos) con lo que ya consumieron compras, almacén y tareo.</div>
                </div>
                <div style="min-width:240px;">
                    <div style="display:flex; justify-content:space-between; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">
                        <span>Presupuesto detallado</span><span><?= $cobIns['pct'] ?>%</span>
                    </div>
                    <div class="wbs-progress"><div class="wbs-progress-bar <?= $cobIns['pct'] >= 80 ? 'ok' : ($cobIns['pct'] >= 40 ? 'warn' : 'neutral') ?>" data-width="<?= min(100, (float)$cobIns['pct']) ?>"></div></div>
                    <div style="font-size:11px; color:#94a3b8; margin-top:3px;"><?= (int)$cobIns['hojas_con_insumos'] ?> de <?= (int)$cobIns['hojas'] ?> actividades hoja con insumos<?= $cobIns['sin_desglosar'] > 0 ? ' · ' . $money($cobIns['sin_desglosar']) . ' sin desglosar' : '' ?></div>
                </div>
            </div>

            <?php if (!$hayInsumos): ?>
                <div style="border:1.5px dashed #cbd5e1; border-radius:12px; padding:28px 20px; text-align:center; color:#64748b; font-size:13px;">
                    <i class="fas fa-list-ol" style="font-size:28px; color:#94a3b8; display:block; margin-bottom:8px;"></i>
                    Aún no hay presupuesto por insumos. Abre una actividad hoja con el botón <strong><i class="fas fa-list-ol"></i></strong> de la tabla para presupuestar sus materiales, mano de obra, equipos y subcontratos.
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:16px;">
                    <?php foreach ($presInsumos['categorias'] as $k => $c): [$ico, $col, $bg] = $catMeta[$k]; ?>
                    <div class="ins-cat">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                            <div class="wbs-kpi-icon" style="width:34px; height:34px; font-size:14px; background:<?= $bg ?>; color:<?= $col ?>;"><i class="fas <?= $ico ?>"></i></div>
                            <div style="flex:1; min-width:0;">
                                <div class="wbs-kpi-lbl" style="margin:0;"><?= $h($c['label']) ?></div>
                                <div style="font-size:15px; font-weight:800; color:#0f172a;"><?= $money($c['real']) ?></div>
                            </div>
                            <span class="wbs-badge <?= $h($c['clase']) ?>"><?= $c['pct'] === null ? 'Sin ppto.' : $c['pct'] . '%' ?></span>
                        </div>
                        <div class="wbs-progress"><div class="wbs-progress-bar <?= $h($c['clase']) ?>" data-width="<?= min(100, (float)($c['pct'] ?? 0)) ?>"></div></div>
                        <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Presupuesto: <?= $money($c['plan']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:16px;">
                    <div style="min-width:0;">
                        <div style="position:relative; height:260px;"><canvas id="chartInsumos"></canvas></div>
                        <?php if ($presInsumos['real_sin_detalle'] > 0): ?>
                        <div style="font-size:11px; color:#94a3b8; margin-top:6px;"><i class="fas fa-info-circle"></i> Comparación solo de actividades con insumos. Hay <?= $money($presInsumos['real_sin_detalle']) ?> de costo real en actividades sin presupuesto por insumos.</div>
                        <?php endif; ?>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-size:12.5px; font-weight:800; color:#0f172a; margin-bottom:8px;">
                            <i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i> Mayores desviaciones
                            <?php if ($presInsumos['n_excedidos'] > 0): ?><span class="wbs-badge danger" style="margin-left:4px;"><?= (int)$presInsumos['n_excedidos'] ?> insumo(s)</span><?php endif; ?>
                        </div>
                        <?php if (empty($presInsumos['desviaciones'])): ?>
                            <div style="border:1px solid #bbf7d0; background:#f0fdf4; color:#15803d; border-radius:10px; padding:14px; font-size:12.5px;"><i class="fas fa-check-circle"></i> Ningún insumo supera su presupuesto.</div>
                        <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="wbs-tbl">
                                <thead><tr><th>Insumo</th><th class="num">Presup.</th><th class="num">Real</th><th class="num">Exceso</th></tr></thead>
                                <tbody>
                                <?php foreach ($presInsumos['desviaciones'] as $dv): ?>
                                    <tr>
                                        <td><strong><?= $h($dv['insumo']) ?></strong><?= !$dv['presupuestado'] ? ' <span class="wbs-badge danger">No presup.</span>' : '' ?><br><small style="color:#94a3b8;"><?= $h($dv['actividad']) ?></small></td>
                                        <td class="num"><?= $money($dv['plan']) ?></td>
                                        <td class="num"><?= $money($dv['real']) ?></td>
                                        <td class="num" style="color:#b91c1c; font-weight:800;">+ <?= $money($dv['exceso']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Árbol WBS -->
        <div class="wbs-card" style="border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.03);">

            <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:10px; flex:1; max-width:380px;">
                    <div style="position:relative; width:100%;">
                        <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                        <input type="text" id="filtroTextoTarea" onkeyup="aplicarVisibilidad()" placeholder="Buscar por código, actividad o descripción..." style="width:100%; padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <button type="button" class="wbs-toggle-btn" onclick="expandirTodo(true)"><i class="fas fa-angle-double-down"></i> Expandir</button>
                    <button type="button" class="wbs-toggle-btn" onclick="expandirTodo(false)"><i class="fas fa-angle-double-up"></i> Contraer</button>
                    <select id="filtroEstadoTarea" onchange="aplicarVisibilidad()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; outline:none; background:#ffffff;">
                        <option value="todos">Todos los Estados</option>
                        <option value="pendiente">Pendientes</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="completada">Completadas</option>
                        <option value="bloqueada">Bloqueadas</option>
                    </select>
                    <select id="filtroPrioridadTarea" onchange="aplicarVisibilidad()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; outline:none; background:#ffffff;">
                        <option value="todos">Todas las Prioridades</option>
                        <option value="urgente">Urgente</option>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table id="tablaWBS" style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                            <th style="padding:12px 16px; min-width:300px;">Actividad WBS</th>
                            <th style="padding:12px 14px; width:150px;">Cronograma</th>
                            <th style="padding:12px 14px; width:160px;">Avance físico</th>
                            <th style="padding:12px 14px; width:230px;">Presupuesto vs. Real</th>
                            <th style="padding:12px 12px; width:100px; text-align:center;">Estado</th>
                            <th style="padding:12px 14px; width:140px;">Responsable</th>
                            <th style="padding:12px 16px; text-align:right; width:150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($arbol)): ?>
                        <tr>
                            <td colspan="7" style="padding:40px 20px; text-align:center; color:#94a3b8;">
                                <i class="fas fa-sitemap" style="font-size:32px; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                                No se han registrado actividades en la EDT de este proyecto.<br>
                                <?php if ($puedeCrear): ?>
                                <button onclick="abrirModalCrearTarea(0)" style="margin-top:12px; background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; padding:6px 14px; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer;">
                                    <i class="fas fa-plus"></i> Crear primera actividad
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($arbol as $t):
                                $tid = (int)$t['id'];
                                $st = $estadoColors[$t['estado']] ?? $estadoColors['pendiente'];
                                $esPadre = (bool)$t['_tiene_hijos'];
                                $pct = $esPadre ? ($avanceCalc[$tid] ?? (float)$t['progreso']) : (float)$t['progreso'];
                                $sal = TareaModel::calcularSalud((float)$t['costo_real'], (float)$t['costo_estimado']);
                            ?>
                            <tr class="fila-tarea wbs-tree-row <?= $esPadre ? 'es-padre' : '' ?>" data-id="<?= $tid ?>"
                                data-ancestros="<?= $h($t['_ancestros']) ?>"
                                data-estado="<?= $h($t['estado']) ?>" data-prioridad="<?= $h($t['prioridad']) ?>"
                                style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:12px 16px;">
                                    <div style="display:flex; align-items:center; gap:8px; padding-left:<?= (int)$t['_nivel'] * 24 ?>px;">
                                        <?php if ($esPadre): ?>
                                            <button type="button" class="wbs-caret" id="caret-<?= $tid ?>" onclick="toggleRama(<?= $tid ?>)" title="Expandir / contraer"><i class="fas fa-chevron-down"></i></button>
                                        <?php else: ?>
                                            <span class="wbs-caret-spacer"></span>
                                        <?php endif; ?>
                                        <span style="font-family:monospace; font-weight:700; background:#f1f5f9; color:#1e293b; padding:3px 7px; border-radius:6px; font-size:11.5px; border:1px solid #e2e8f0; white-space:nowrap;">
                                            <?= $h($t['codigo']) ?>
                                        </span>
                                        <div style="min-width:0;">
                                            <div style="font-weight:<?= $esPadre ? 800 : 700 ?>; color:#1e293b; font-size:13.5px;"><?= $h($t['nombre']) ?></div>
                                            <?php if (!empty($t['descripcion'])): ?>
                                                <div style="font-size:11.5px; color:#64748b; margin-top:2px; max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= $h($t['descripcion']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:12px 14px;">
                                    <div style="font-size:11.5px; color:#334155; font-weight:600;">
                                        <i class="far fa-calendar-alt" style="color:#94a3b8;"></i> <?= date('d/m/Y', strtotime($t['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($t['fecha_fin'])) ?>
                                    </div>
                                    <div style="font-size:11px; color:#94a3b8; margin-top:2px;"><?= (int)$t['duracion_dias'] ?> días de plazo</div>
                                </td>
                                <td style="padding:12px 14px;">
                                    <?php if ($esPadre || !$puedeEditar): ?>
                                        <div style="font-weight:700; font-size:12px; color:#1e293b;" title="<?= $esPadre ? 'Promedio de sus sub-actividades' : '' ?>"><?= $pct ?>%</div>
                                    <?php else: ?>
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <input type="range" min="0" max="100" step="5" value="<?= $pct ?>" id="slider-<?= $tid ?>" onchange="actualizarAvanceRapido(<?= $tid ?>, this.value)" style="width:90px; cursor:pointer;">
                                            <span id="badge-pct-<?= $tid ?>" style="font-weight:700; font-size:12px; min-width:35px; color:#1e293b;"><?= $pct ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="wbs-progress" style="height:5px; margin-top:5px;">
                                        <div id="bar-pct-<?= $tid ?>" class="wbs-progress-bar <?= $pct >= 100 ? 'ok' : 'neutral' ?>" style="background-color:<?= $pct >= 100 ? '#10b981' : ($pct > 50 ? '#3b82f6' : '#f59e0b') ?>;" data-width="<?= min(100, $pct) ?>"></div>
                                    </div>
                                </td>
                                <td style="padding:12px 14px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                        <span style="font-weight:700; color:#1e293b; font-size:12.5px;"><?= $money($t['costo_real']) ?></span>
                                        <span class="wbs-badge <?= $h($sal['clase']) ?>"><?= $sal['clase'] === 'neutral' && (float)$t['costo_real'] == 0.0 ? 'Sin gasto' : $sal['pct'] . '%' ?></span>
                                    </div>
                                    <div class="wbs-progress" title="<?= $h($sal['label']) ?>">
                                        <div class="wbs-progress-bar <?= $h($sal['clase']) ?>" data-width="<?= min(100, $sal['pct']) ?>"></div>
                                    </div>
                                    <div style="font-size:11px; color:#94a3b8; margin-top:3px;">Presup.: <?= $money($t['costo_estimado']) ?><?= $esPadre ? ' (acumulado)' : '' ?>
                                        <?php if (!empty($insumosPorTarea[$tid]['n'])): ?><span class="wbs-badge neutral" style="margin-left:4px;" title="Presupuesto detallado por insumos"><i class="fas fa-list-ol"></i> <?= (int)$insumosPorTarea[$tid]['n'] ?> insumos</span><?php endif; ?></div>
                                </td>
                                <td style="padding:12px 12px; text-align:center;">
                                    <span id="badge-estado-<?= $tid ?>" style="display:inline-block; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:<?= $st['bg'] ?>; color:<?= $st['text'] ?>;"><?= $st['label'] ?></span>
                                    <div style="margin-top:4px;">
                                        <span style="display:inline-block; padding:1px 7px; border-radius:6px; font-size:9.5px; font-weight:700; text-transform:uppercase; background:<?= ($prioColors[$t['prioridad']] ?? $prioColors['media'])['bg'] ?>; color:<?= ($prioColors[$t['prioridad']] ?? $prioColors['media'])['text'] ?>;"><?= $h($t['prioridad']) ?></span>
                                    </div>
                                </td>
                                <td style="padding:12px 14px;">
                                    <?php if (!empty($t['resp_nombre'])): ?>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <div style="width:24px; height:24px; border-radius:6px; background:#e0e7ff; color:#4338ca; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center;"><?= $h(strtoupper(mb_substr($t['resp_nombre'], 0, 2))) ?></div>
                                            <span style="font-size:12px; color:#334155; font-weight:600;"><?= $h($t['resp_nombre']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:11.5px; font-style:italic;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 16px; text-align:right;">
                                    <div style="display:inline-flex; gap:5px;">
                                        <button class="wbs-icon-btn verde" onclick="verDesglose(<?= $tid ?>)" title="Ver desglose de gastos"><i class="fas fa-receipt"></i></button>
                                        <?php if ($insumosActivo && !$esPadre): ?>
                                        <button class="wbs-icon-btn ambar" onclick="abrirInsumos(<?= $tid ?>)" title="Presupuesto por insumos"><i class="fas fa-list-ol"></i></button>
                                        <?php endif; ?>
                                        <?php if ($puedeCrear): ?>
                                        <button class="wbs-icon-btn azul" onclick="abrirModalCrearTarea(<?= $tid ?>)" title="Agregar sub-actividad"><i class="fas fa-level-down-alt"></i></button>
                                        <?php endif; ?>
                                        <?php if ($puedeEditar): ?>
                                        <button class="wbs-icon-btn" onclick="editarTarea(<?= $tid ?>)" title="Editar actividad"><i class="fas fa-pencil-alt"></i></button>
                                        <?php endif; ?>
                                        <?php if ($puedeEliminar): ?>
                                        <a class="wbs-icon-btn rojo" href="<?= BASE_URL ?>/tareas/eliminar/<?= $tid ?>?csrf_token=<?= $h($csrfToken) ?>"
                                           onclick="return confirm('<?= $esPadre ? '¿Eliminar esta actividad y TODAS sus sub-actividades?' : '¿Eliminar esta actividad del WBS?' ?>')" title="Eliminar actividad"><i class="fas fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Modal: Crear / Editar Actividad WBS -->
<div id="modalTarea" class="wbs-overlay">
    <div class="wbs-modal" style="max-width:660px;">
        <div class="wbs-modal-head">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-sitemap" id="modalTareaIcon"></i>
                </div>
                <h3 id="modalTareaTitulo" style="margin:0; font-size:16px; font-weight:700;">Nueva Actividad</h3>
            </div>
            <button type="button" onclick="cerrarModalTarea()" style="background:transparent; border:none; color:rgba(255,255,255,0.7); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>

        <form id="formTarea" method="POST" action="<?= BASE_URL ?>/tareas/crear" style="padding:22px 24px; overflow-y:auto;">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
            <input type="hidden" name="proyecto_id" value="<?= $proyectoId ?>">

            <div class="wbs-field" style="margin-bottom:14px;">
                <label>Actividad padre (nivel superior del WBS)</label>
                <select name="parent_id" id="tarParentId">
                    <option value="" data-ancestros="">— Actividad principal (raíz) —</option>
                    <?php foreach ($arbol as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" data-ancestros="<?= $h($t['_ancestros']) ?>">
                            <?= str_repeat('— ', (int)$t['_nivel']) . $h($t['codigo'] . ' · ' . $t['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 2fr; gap:14px; margin-bottom:14px;">
                <div class="wbs-field">
                    <label>Código EDT *</label>
                    <input type="text" name="codigo" id="tarCodigo" required maxlength="50" placeholder="Ej: 1.01">
                </div>
                <div class="wbs-field">
                    <label>Nombre de la Actividad *</label>
                    <input type="text" name="nombre" id="tarNombre" required maxlength="255" placeholder="Ej: Obras preliminares y trazo">
                </div>
            </div>

            <div class="wbs-field" style="margin-bottom:14px;">
                <label>Descripción del Alcance</label>
                <textarea name="descripcion" id="tarDescripcion" rows="2" placeholder="Detalles de ejecución..."></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div class="wbs-field">
                    <label>Fecha Inicio *</label>
                    <input type="date" name="fecha_inicio" id="tarFechaInicio" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="wbs-field">
                    <label>Fecha Fin *</label>
                    <input type="date" name="fecha_fin" id="tarFechaFin" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px;">
                <div class="wbs-field">
                    <label>Avance (%)</label>
                    <input type="number" name="progreso" id="tarProgreso" min="0" max="100" step="1" value="0">
                </div>
                <div class="wbs-field">
                    <label>Estado</label>
                    <select name="estado" id="tarEstado">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="completada">Completada</option>
                        <option value="bloqueada">Bloqueada</option>
                    </select>
                </div>
                <div class="wbs-field">
                    <label>Prioridad</label>
                    <select name="prioridad" id="tarPrioridad">
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:8px;">
                <div class="wbs-field">
                    <label>Responsable</label>
                    <select name="responsable_id" id="tarResponsableId">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= $h($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wbs-field">
                    <label>Presupuesto Base / Costo Estimado (S/)</label>
                    <input type="number" step="0.01" min="0" name="costo_estimado" id="tarCostoEstimado" value="0.00">
                </div>
            </div>
            <div id="avisoCostos" style="background:#eff6ff; border:1px solid #dbeafe; color:#1e40af; border-radius:9px; padding:9px 12px; font-size:11.5px; margin-bottom:16px;">
                <i class="fas fa-info-circle"></i> El <strong>costo real</strong> se calcula automáticamente desde compras, subcontratos, salidas de almacén y tareo.
                <span id="avisoPadre" style="display:none;"> Esta actividad tiene sub-actividades: su presupuesto es la suma de ellas.</span>
                <span id="avisoInsumos" style="display:none;"> Esta actividad tiene presupuesto por insumos: el presupuesto es la suma de sus insumos (edítalo con el botón <i class="fas fa-list-ol"></i>).</span>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" onclick="cerrarModalTarea()" style="padding:9px 16px; border-radius:9px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color:#ffffff; border:none; padding:9px 20px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer;">
                    <i class="fas fa-save"></i> Guardar Actividad
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Desglose de gastos de la actividad -->
<div id="modalDesglose" class="wbs-overlay">
    <div class="wbs-modal" style="max-width:900px;">
        <div class="wbs-modal-head">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center;"><i class="fas fa-receipt"></i></div>
                <h3 id="desgloseTitulo" style="margin:0; font-size:16px; font-weight:700;">Desglose de gastos</h3>
            </div>
            <button type="button" onclick="cerrarDesglose()" style="background:transparent; border:none; color:rgba(255,255,255,0.7); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div id="desgloseCuerpo" style="overflow-y:auto; padding:0;">
            <div style="padding:40px; text-align:center; color:#64748b;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>
    </div>
</div>

<?php if ($insumosActivo): ?>
<!-- Modal: Presupuesto por insumos de una actividad -->
<div id="modalInsumos" class="wbs-overlay">
    <div class="wbs-modal" style="max-width:1080px;">
        <div class="wbs-modal-head">
            <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                <div style="width:34px; height:34px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas fa-list-ol"></i></div>
                <div style="min-width:0;">
                    <h3 id="insTitulo" style="margin:0; font-size:16px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Presupuesto por insumos</h3>
                    <div style="font-size:11.5px; opacity:.8;">Materiales, mano de obra, equipos y subcontratos de la actividad</div>
                </div>
            </div>
            <button type="button" onclick="cerrarInsumos()" style="background:transparent; border:none; color:rgba(255,255,255,0.7); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>

        <div class="wbs-tabs" id="insTabs">
            <button type="button" class="wbs-tab activo" data-tab="editor"><i class="fas fa-pen"></i> Presupuesto</button>
            <button type="button" class="wbs-tab" data-tab="comp"><i class="fas fa-balance-scale"></i> Presupuesto vs. Real</button>
        </div>

        <div id="insCuerpo" style="overflow-y:auto; flex:1; background:#fff;">
            <div style="padding:40px; text-align:center; color:#64748b;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>

        <div id="insPie" style="display:none; padding:12px 20px; border-top:1px solid #f1f5f9; background:#fafafa; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div id="insTotales" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;"></div>
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="cerrarInsumos()" style="padding:9px 16px; border-radius:9px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cerrar</button>
                <?php if ($puedeEditar): ?>
                <button type="button" id="insGuardar" onclick="guardarInsumos()" style="background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color:#ffffff; border:none; padding:9px 20px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer;">
                    <i class="fas fa-save"></i> Guardar presupuesto
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<datalist id="dlMateriales"></datalist>
<datalist id="dlEquipos"></datalist>
<?php endif; ?>

<?php if ($proyectoId > 0): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<?php endif; ?>
<script>
const BASE_URL_JS = <?= $jsonSeguro(BASE_URL) ?>;
const CSRF = <?= $jsonSeguro($csrfToken) ?>;
const TAREAS = <?= $jsonSeguro($mapaTareas) ?>;
const GRAFICOS = <?= $jsonSeguro($graficos) ?>;
const INS_ACTIVO = <?= $insumosActivo ? 'true' : 'false' ?>;
const PUEDE_EDITAR = <?= $puedeEditar ? 'true' : 'false' ?>;
const CAT = <?= $jsonSeguro($catalogosInsumos ?? ['materiales' => [], 'equipos' => [], 'labores' => []]) ?>;
const INS_RESUMEN = <?= $jsonSeguro((object)$insumosPorTarea) ?>;
const PRES_INS = <?= $jsonSeguro($presInsumos) ?>;

const fmt = n => 'S/ ' + Number(n || 0).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/* ── Barras de progreso animadas: crecen desde 0 al cargar ─────────────── */
document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(() => {
        document.querySelectorAll('.wbs-progress-bar[data-width]').forEach(b => { b.style.width = b.dataset.width + '%'; });
    });
    initGraficos();
});

/* ── Árbol: expandir / contraer + filtros ──────────────────────────────── */
const colapsados = new Set();

function toggleRama(id) {
    const k = String(id);
    colapsados.has(k) ? colapsados.delete(k) : colapsados.add(k);
    const c = document.getElementById('caret-' + id);
    if (c) c.classList.toggle('cerrado', colapsados.has(k));
    aplicarVisibilidad();
}

function expandirTodo(expandir) {
    colapsados.clear();
    document.querySelectorAll('.wbs-caret').forEach(c => {
        const id = c.id.replace('caret-', '');
        if (!expandir) colapsados.add(id);
        c.classList.toggle('cerrado', !expandir);
    });
    aplicarVisibilidad();
}

function aplicarVisibilidad() {
    const q = (document.getElementById('filtroTextoTarea').value || '').toLowerCase().trim();
    const est = document.getElementById('filtroEstadoTarea').value;
    const prio = document.getElementById('filtroPrioridadTarea').value;
    const filtrando = q !== '' || est !== 'todos' || prio !== 'todos';

    document.querySelectorAll('#tablaWBS tbody tr.fila-tarea').forEach(fila => {
        const ancestros = fila.dataset.ancestros ? fila.dataset.ancestros.split(',') : [];
        const ocultoPorRama = !filtrando && ancestros.some(a => colapsados.has(a));
        const okTxt = !q || fila.textContent.toLowerCase().includes(q);
        const okEst = est === 'todos' || fila.dataset.estado === est;
        const okPrio = prio === 'todos' || fila.dataset.prioridad === prio;
        fila.style.display = (!ocultoPorRama && okTxt && okEst && okPrio) ? '' : 'none';
    });
}

/* ── Modal crear / editar ──────────────────────────────────────────────── */
function filtrarPadresPosibles(tareaId) {
    // Un padre no puede ser la propia tarea ni uno de sus descendientes
    document.querySelectorAll('#tarParentId option').forEach(opt => {
        if (!opt.value || !tareaId) { opt.disabled = false; opt.hidden = false; return; }
        const anc = (opt.dataset.ancestros || '').split(',');
        const invalido = String(opt.value) === String(tareaId) || anc.includes(String(tareaId));
        opt.disabled = invalido; opt.hidden = invalido;
    });
}

function abrirModalCrearTarea(parentId) {
    parentId = parentId || 0;
    const f = document.getElementById('formTarea');
    document.getElementById('modalTareaTitulo').textContent = parentId ? 'Nueva Sub-actividad' : 'Nueva Actividad';
    document.getElementById('modalTareaIcon').className = 'fas fa-plus';
    f.action = BASE_URL_JS + '/tareas/crear';
    filtrarPadresPosibles(null);
    document.getElementById('tarParentId').value = parentId ? String(parentId) : '';
    document.getElementById('tarCodigo').value = parentId && TAREAS[parentId] ? TAREAS[parentId].codigo + '.' : '';
    document.getElementById('tarNombre').value = '';
    document.getElementById('tarDescripcion').value = '';
    document.getElementById('tarProgreso').value = '0';
    document.getElementById('tarEstado').value = 'pendiente';
    document.getElementById('tarPrioridad').value = 'media';
    document.getElementById('tarResponsableId').value = '';
    document.getElementById('tarCostoEstimado').value = '0.00';
    document.getElementById('tarCostoEstimado').readOnly = false;
    document.getElementById('avisoPadre').style.display = 'none';
    document.getElementById('avisoInsumos').style.display = 'none';
    document.getElementById('modalTarea').style.display = 'flex';
}

function editarTarea(id) {
    const t = TAREAS[id];
    if (!t) return;
    document.getElementById('modalTareaTitulo').textContent = 'Editar Actividad: ' + t.codigo;
    document.getElementById('modalTareaIcon').className = 'fas fa-edit';
    document.getElementById('formTarea').action = BASE_URL_JS + '/tareas/editar/' + t.id;
    filtrarPadresPosibles(t.id);
    document.getElementById('tarParentId').value = t.parent_id ? String(t.parent_id) : '';
    document.getElementById('tarCodigo').value = t.codigo || '';
    document.getElementById('tarNombre').value = t.nombre || '';
    document.getElementById('tarDescripcion').value = t.descripcion || '';
    document.getElementById('tarFechaInicio').value = t.fecha_inicio || '';
    document.getElementById('tarFechaFin').value = t.fecha_fin || '';
    document.getElementById('tarProgreso').value = t.progreso || '0';
    document.getElementById('tarEstado').value = t.estado || 'pendiente';
    document.getElementById('tarPrioridad').value = t.prioridad || 'media';
    document.getElementById('tarResponsableId').value = t.responsable_id || '';
    document.getElementById('tarCostoEstimado').value = t.costo_estimado || '0.00';
    // En actividades con hijas el presupuesto es la suma de las hijas (se sincroniza en el servidor)
    document.getElementById('tarCostoEstimado').readOnly = !!t.tiene_hijos || t.insumos_n > 0;
    document.getElementById('avisoPadre').style.display = t.tiene_hijos ? 'inline' : 'none';
    document.getElementById('avisoInsumos').style.display = t.insumos_n > 0 ? 'inline' : 'none';
    document.getElementById('modalTarea').style.display = 'flex';
}

function cerrarModalTarea() { document.getElementById('modalTarea').style.display = 'none'; }

/* ── Avance rápido (AJAX) ──────────────────────────────────────────────── */
function actualizarAvanceRapido(id, valor) {
    const badge = document.getElementById('badge-pct-' + id);
    const bar = document.getElementById('bar-pct-' + id);
    if (badge) badge.textContent = valor + '%';
    if (bar) bar.style.width = valor + '%';

    const body = new URLSearchParams({csrf_token: CSRF, progreso: valor});
    fetch(BASE_URL_JS + '/tareas/actualizarProgreso/' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: body.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const labels = {pendiente:'Pendiente', en_proceso:'En Proceso', completada:'Completada', bloqueada:'Bloqueada'};
        const be = document.getElementById('badge-estado-' + id);
        if (be && data.tarea) be.textContent = labels[data.tarea.estado] || data.tarea.estado;
        const kpi = document.getElementById('kpiAvance');
        if (kpi && data.resumen) kpi.textContent = data.resumen.promedio_avance;
    })
    .catch(err => console.error('Error al actualizar avance:', err));
}

/* ── Gráficos Chart.js ─────────────────────────────────────────────────── */
let chartBarras = null, chartDona = null;

function initGraficos() {
    if (typeof Chart === 'undefined' || !GRAFICOS || !GRAFICOS.principales) return;

    const vista = GRAFICOS.principales;
    const ctxB = document.getElementById('chartBarras');
    if (ctxB) {
        chartBarras = new Chart(ctxB, {
            type: 'bar',
            data: {
                labels: vista.labels,
                datasets: [
                    {label: 'Presupuesto', data: vista.estimado, backgroundColor: '#93c5fd', borderRadius: 6},
                    {label: 'Costo Real', data: vista.real, backgroundColor: vista.colores, borderRadius: 6}
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: {duration: 1200, easing: 'easeOutQuart'},
                interaction: {mode: 'index', intersect: false},
                plugins: {
                    legend: {position: 'bottom'},
                    tooltip: {callbacks: {
                        title: items => { const i = items[0].dataIndex; return vista_actual().labels[i] + ' · ' + vista_actual().nombres[i]; },
                        label: c => c.dataset.label + ': ' + fmt(c.parsed.y),
                        afterBody: items => {
                            const i = items[0].dataIndex, v = vista_actual();
                            const est = v.estimado[i], real = v.real[i];
                            return est > 0 ? ['Ejecución: ' + (real / est * 100).toFixed(1) + '%'] : [];
                        }
                    }}
                },
                scales: {y: {beginAtZero: true, ticks: {callback: v => 'S/ ' + Number(v).toLocaleString('es-PE')}}}
            }
        });
    }

    const d = GRAFICOS.dona;
    const total = d.valores.reduce((a, b) => a + b, 0);
    const ctxD = document.getElementById('chartDona');
    if (ctxD) {
        if (total <= 0) {
            ctxD.style.display = 'none';
            document.getElementById('donaVacia').style.display = 'flex';
        } else {
            chartDona = new Chart(ctxD, {
                type: 'doughnut',
                data: {labels: d.labels, datasets: [{data: d.valores, backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b'], borderWidth: 2, hoverOffset: 10}]},
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '62%',
                    animation: {animateRotate: true, animateScale: true, duration: 1300},
                    plugins: {
                        legend: {position: 'bottom'},
                        tooltip: {callbacks: {label: c => c.label + ': ' + fmt(c.parsed) + ' (' + (c.parsed / total * 100).toFixed(1) + '%)'}}
                    }
                }
            });
        }
    }
}

let vistaBarras = 'principales';
function vista_actual() { return GRAFICOS[vistaBarras]; }

function cambiarVistaBarras(vista, btn) {
    if (!chartBarras || !GRAFICOS[vista]) return;
    vistaBarras = vista;
    document.querySelectorAll('.wbs-toggle-btn[data-vista]').forEach(b => b.classList.toggle('activo', b === btn));
    const v = GRAFICOS[vista];
    chartBarras.data.labels = v.labels;
    chartBarras.data.datasets[0].data = v.estimado;
    chartBarras.data.datasets[1].data = v.real;
    chartBarras.data.datasets[1].backgroundColor = v.colores;
    chartBarras.update();
}

/* ── Modal de desglose de gastos ───────────────────────────────────────── */
function cerrarDesglose() { document.getElementById('modalDesglose').style.display = 'none'; }

function verDesglose(id) {
    const cuerpo = document.getElementById('desgloseCuerpo');
    document.getElementById('desgloseTitulo').textContent = 'Desglose de gastos';
    cuerpo.innerHTML = '<div style="padding:40px; text-align:center; color:#64748b;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    document.getElementById('modalDesglose').style.display = 'flex';

    fetch(BASE_URL_JS + '/tareas/desglose/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            if (!d.success) { cuerpo.innerHTML = '<div style="padding:30px; color:#b91c1c;">' + esc(d.message || 'No se pudo cargar el desglose.') + '</div>'; return; }
            renderDesglose(d);
        })
        .catch(() => { cuerpo.innerHTML = '<div style="padding:30px; color:#b91c1c;">Error de red al cargar el desglose.</div>'; });
}

function renderDesglose(d) {
    const t = d.tarea, dir = d.directos, det = d.detalle, s = d.salud;
    document.getElementById('desgloseTitulo').textContent = t.codigo + ' · ' + t.nombre;

    const tarjeta = (icono, color, bg, etiqueta, valor) =>
        `<div class="wbs-card" style="padding:12px 14px; display:flex; gap:10px; align-items:center;">
            <div class="wbs-kpi-icon" style="width:36px; height:36px; font-size:15px; background:${bg}; color:${color};"><i class="fas ${icono}"></i></div>
            <div><div style="font-size:15px; font-weight:800; color:#0f172a;">${fmt(valor)}</div>
            <div class="wbs-kpi-lbl" style="margin-top:2px;">${etiqueta}</div></div></div>`;

    const vacio = txt => `<div style="padding:30px; text-align:center; color:#94a3b8; font-size:12.5px;">${txt}</div>`;
    const tabla = (cols, filas) => `<div style="overflow-x:auto;"><table class="wbs-tbl"><thead><tr>${cols.map(c => `<th class="${c.num ? 'num' : ''}">${c.t}</th>`).join('')}</tr></thead><tbody>${filas.join('')}</tbody></table></div>`;
    const flag = (computa, extra) => computa == 1 ? '<span class="wbs-badge ok">Computa</span>' : `<span class="wbs-badge neutral">${extra}</span>`;

    const pCompras = det.compras.length ? tabla(
        [{t:'OC'},{t:'Proveedor'},{t:'Descripción'},{t:'Cant.',num:1},{t:'Total',num:1},{t:'Computa',num:1},{t:'Estado'}],
        det.compras.map(r => {
            const pct = Number(r.pct_almacen || 0);
            const nota = !['aprobada','recibida'].includes(r.estado) ? esc(r.estado)
                       : (pct >= 100 ? 'Vía almacén' : (pct > 0 ? pct + ' % vía almacén' : ''));
            return `<tr><td><strong>${esc(r.oc_codigo)}</strong><br><small>${esc(r.fecha_emision)}</small></td><td>${esc(r.proveedor)}</td><td>${esc(r.descripcion)}</td>
            <td class="num">${esc(r.cantidad)} ${esc(r.unidad_medida)}</td><td class="num">${fmt(r.total)}</td><td class="num"><strong>${fmt(r.monto_computa)}</strong></td>
            <td>${r.computa == 1 && !nota ? '<span class="wbs-badge ok">Computa</span>' : (r.computa == 1 ? '<span class="wbs-badge warn">' + nota + '</span>' : '<span class="wbs-badge neutral">' + nota + '</span>')}</td></tr>`;
        })
    ) : vacio('Sin líneas de órdenes de compra imputadas a esta actividad.');

    const pSub = det.subcontratos.length ? tabla(
        [{t:'Código'},{t:'Servicio'},{t:'Proveedor'},{t:'Monto',num:1},{t:'Estado'}],
        det.subcontratos.map(r => `<tr><td><strong>${esc(r.codigo)}</strong></td><td>${esc(r.servicio)}</td><td>${esc(r.proveedor)}</td>
            <td class="num">${fmt(r.monto)}</td><td>${flag(r.computa, esc(r.estado))}</td></tr>`)
    ) : vacio('Sin subcontratos imputados a esta actividad.');

    const pAlm = det.almacen.length ? tabla(
        [{t:'Fecha'},{t:'Vale / Guía'},{t:'Ítem'},{t:'Cant.',num:1},{t:'Costo',num:1}],
        det.almacen.map(r => `<tr><td>${esc(r.fecha)}</td><td>${esc(r.guia_remision || '—')}</td><td>${esc(r.item || 'Ítem de almacén')}</td>
            <td class="num">${esc(r.cantidad ?? '')}</td><td class="num">${fmt(r.costo)}</td></tr>`)
    ) : vacio('Sin salidas de almacén imputadas a esta actividad.');

    const pTareo = det.tareo.length ? tabla(
        [{t:'Fecha'},{t:'Personal'},{t:'H. normales',num:1},{t:'H. extras',num:1},{t:'Costo',num:1}],
        det.tareo.map(r => `<tr><td>${esc(r.fecha)}</td><td>${esc(r.profesional)}</td><td class="num">${esc(r.horas_normales)}</td>
            <td class="num">${esc(r.horas_extras)}</td><td class="num">${fmt(r.costo)}</td></tr>`)
    ) : vacio('Sin horas-hombre imputadas a esta actividad.');

    const pHijas = det.hijas.length ? tabla(
        [{t:'Código'},{t:'Sub-actividad'},{t:'Presupuesto',num:1},{t:'Costo real',num:1}],
        det.hijas.map(r => `<tr><td><strong>${esc(r.codigo)}</strong></td><td>${esc(r.nombre)}</td><td class="num">${fmt(r.costo_estimado)}</td><td class="num">${fmt(r.costo_real)}</td></tr>`)
    ) : vacio('Esta actividad no tiene sub-actividades.');

    const paneles = {compras: pCompras, sub: pSub, alm: pAlm, tareo: pTareo, hijas: pHijas};
    const tabs = [['compras','Compras', det.compras.length],['sub','Subcontratos', det.subcontratos.length],['alm','Almacén', det.almacen.length],['tareo','Mano de obra', det.tareo.length],['hijas','Sub-actividades', det.hijas.length]];

    const pct = Math.min(100, s.pct);
    document.getElementById('desgloseCuerpo').innerHTML = `
        <div style="padding:18px 20px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:6px;">
                <div style="font-size:13px; color:#334155;"><strong>${fmt(t.costo_real)}</strong> ejecutado de <strong>${fmt(t.costo_estimado)}</strong> presupuestado</div>
                <span class="wbs-badge ${esc(s.clase)}">${esc(s.label)} · ${esc(s.pct)}%</span>
            </div>
            <div class="wbs-progress lg"><div class="wbs-progress-bar ${esc(s.clase)}" style="width:${pct}%"></div></div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:10px; margin-top:16px;">
                ${tarjeta('fa-cubes', '#2563eb', '#eff6ff', 'Materiales', dir.materiales)}
                ${tarjeta('fa-truck-monster', '#7c3aed', '#f5f3ff', 'Equipos', dir.equipos)}
                ${tarjeta('fa-hard-hat', '#059669', '#ecfdf5', 'Mano de obra (' + Number(dir.horas).toFixed(1) + ' h)', dir.mano_obra)}
                ${tarjeta('fa-handshake', '#d97706', '#fffbeb', 'Subcontratos', dir.subcontratos)}
            </div>
            <div style="font-size:11px; color:#94a3b8; margin-top:8px;">Los montos de las tarjetas son costos directos de esta actividad; el costo real total incluye además sus sub-actividades.</div>
        </div>
        <div class="wbs-tabs" id="desgloseTabs">
            ${tabs.map((x, i) => `<button type="button" class="wbs-tab ${i === 0 ? 'activo' : ''}" data-panel="${x[0]}">${x[1]} <span class="wbs-badge neutral">${x[2]}</span></button>`).join('')}
        </div>
        <div id="desglosePanel" style="background:#fff;">${paneles.compras}</div>`;

    document.querySelectorAll('#desgloseTabs .wbs-tab').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#desgloseTabs .wbs-tab').forEach(x => x.classList.toggle('activo', x === b));
        document.getElementById('desglosePanel').innerHTML = paneles[b.dataset.panel];
    }));
}

/* ── Presupuesto por insumos ───────────────────────────────────────────── */
const TIPOS_INS = {
    material:    {label: 'Material',     plural: 'Materiales',   icono: 'fa-cubes',         color: '#2563eb', bg: '#eff6ff'},
    mano_obra:   {label: 'Mano de obra', plural: 'Mano de obra', icono: 'fa-hard-hat',      color: '#059669', bg: '#ecfdf5'},
    equipo:      {label: 'Equipo',       plural: 'Equipos',      icono: 'fa-truck-monster', color: '#7c3aed', bg: '#f5f3ff'},
    subcontrato: {label: 'Subcontrato',  plural: 'Subcontratos', icono: 'fa-handshake',     color: '#d97706', bg: '#fffbeb'}
};
const UNIDAD_FIJA = {mano_obra: 'h', equipo: 'h'};
const num = v => { const n = parseFloat(String(v ?? '').replace(/,/g, '')); return isFinite(n) ? n : 0; };
const r2 = n => Math.round((n + Number.EPSILON) * 100) / 100;

const MAT_ETQ = new Map(), EQ_ETQ = new Map();
let insId = null, insLineas = [], insComp = null, insTab = 'editor';

function initCatalogosIns() {
    if (typeof INS_ACTIVO === 'undefined' || !INS_ACTIVO) return;
    const et = c => c.codigo + ' · ' + c.nombre;
    const dlM = document.getElementById('dlMateriales'), dlE = document.getElementById('dlEquipos');
    dlM.innerHTML = CAT.materiales.map(m => { MAT_ETQ.set(et(m), m); return `<option value="${esc(et(m))}">`; }).join('');
    dlE.innerHTML = CAT.equipos.map(e => { EQ_ETQ.set(et(e), e); return `<option value="${esc(et(e))}">`; }).join('');
}
const etiquetaCat = c => c.codigo + ' · ' + c.nombre;

function insLineaNueva(tipo) {
    return {tipo, material_id: null, equipo_id: null, tipo_labor: tipo === 'mano_obra' ? CAT.labores[0] : null,
            descripcion: '', unidad: UNIDAD_FIJA[tipo] || (tipo === 'subcontrato' ? 'glb' : 'und'),
            cantidad: 0, precio_unitario: 0, _auto: false};
}

function abrirInsumos(id) {
    insId = id; insLineas = []; insComp = null;
    const t = TAREAS[id];
    document.getElementById('insTitulo').textContent = t ? t.codigo + ' · ' + t.nombre : 'Presupuesto por insumos';
    document.getElementById('insPie').style.display = 'none';
    document.getElementById('insCuerpo').innerHTML = '<div style="padding:40px; text-align:center; color:#64748b;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    document.getElementById('modalInsumos').style.display = 'flex';

    fetch(BASE_URL_JS + '/tareas/insumos/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            if (!d.success) { document.getElementById('insCuerpo').innerHTML = '<div style="padding:30px; color:#b91c1c;">' + esc(d.message || 'No se pudo cargar.') + '</div>'; return; }
            insLineas = d.insumos.map(l => ({...l, _auto: false}));
            insComp = d.comparativo;
            cambiarTabIns(PUEDE_EDITAR ? 'editor' : 'comp');
        })
        .catch(() => { document.getElementById('insCuerpo').innerHTML = '<div style="padding:30px; color:#b91c1c;">Error de red al cargar el presupuesto.</div>'; });
}

function cerrarInsumos() { document.getElementById('modalInsumos').style.display = 'none'; }

function cambiarTabIns(tab) {
    insTab = tab;
    document.querySelectorAll('#insTabs .wbs-tab').forEach(b => b.classList.toggle('activo', b.dataset.tab === tab));
    if (tab === 'editor') { renderEditorIns(); } else { renderComparativoIns(); }
}

/* ── Editor ────────────────────────────────────────────────────────────── */
function celdaInsumo(l, i) {
    const dis = PUEDE_EDITAR ? '' : 'disabled';
    if (l.tipo === 'mano_obra') {
        return `<select data-f="labor" ${dis}>${CAT.labores.map(x => `<option ${x === l.tipo_labor ? 'selected' : ''}>${esc(x)}</option>`).join('')}</select>
                <div class="ins-vinc">✔ Se compara con las horas del tareo de este tipo de labor</div>`;
    }
    if (l.tipo === 'material' || l.tipo === 'equipo') {
        const lista = l.tipo === 'material' ? 'dlMateriales' : 'dlEquipos';
        const arr = l.tipo === 'material' ? CAT.materiales : CAT.equipos;
        const id = l.tipo === 'material' ? l.material_id : l.equipo_id;
        const c = id ? arr.find(x => x.id === id) : null;
        const valor = c ? etiquetaCat(c) : (l.descripcion || '');
        return `<input type="text" data-f="desc" list="${lista}" value="${esc(valor)}" maxlength="255" placeholder="Busca en el catálogo (código o nombre)…" ${dis}>
                <div class="ins-vinc ${id ? '' : 'libre'}">${id ? '✔ Catálogo: se compara con salidas y compras de este ítem' : 'Texto libre: solo se compara a nivel de categoría'}</div>`;
    }
    return `<input type="text" data-f="desc" value="${esc(l.descripcion)}" maxlength="255" placeholder="Ej: Topografía y replanteo" ${dis}>
            <div class="ins-vinc libre">Se compara a nivel de la categoría Subcontratos</div>`;
}

function filaIns(l, i) {
    const dis = PUEDE_EDITAR ? '' : 'disabled';
    const t = TIPOS_INS[l.tipo];
    return `<tr data-i="${i}">
        <td style="width:150px;"><select data-f="tipo" ${dis}>${Object.keys(TIPOS_INS).map(k => `<option value="${k}" ${k === l.tipo ? 'selected' : ''}>${TIPOS_INS[k].label}</option>`).join('')}</select></td>
        <td style="min-width:290px;">${celdaInsumo(l, i)}</td>
        <td style="width:80px;"><input type="text" data-f="unidad" value="${esc(l.unidad)}" maxlength="20" ${UNIDAD_FIJA[l.tipo] || !PUEDE_EDITAR ? 'disabled' : ''}></td>
        <td style="width:110px;"><input type="number" class="num" data-f="cantidad" min="0" step="any" value="${l.cantidad || ''}" placeholder="0" ${dis}></td>
        <td style="width:120px;"><input type="number" class="num" data-f="precio_unitario" min="0" step="any" value="${l.precio_unitario || ''}" placeholder="0.00" ${dis}></td>
        <td style="width:120px; text-align:right; font-weight:800; color:#0f172a;" id="insPar-${i}">${fmt(r2(num(l.cantidad) * num(l.precio_unitario)))}</td>
        <td style="width:40px; text-align:center;">${PUEDE_EDITAR ? `<button type="button" class="wbs-icon-btn rojo" data-del="${i}" title="Quitar insumo"><i class="fas fa-times"></i></button>` : ''}</td>
    </tr>`;
}

function renderEditorIns() {
    const copiables = Object.keys(INS_RESUMEN).map(Number).filter(id => id !== insId && TAREAS[id]);
    const t = TAREAS[insId] || {};
    const acciones = PUEDE_EDITAR ? `
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-top:12px;">
            ${Object.keys(TIPOS_INS).map(k => `<button type="button" class="ins-add" data-add="${k}"><i class="fas ${TIPOS_INS[k].icono}" style="color:${TIPOS_INS[k].color};"></i> + ${TIPOS_INS[k].label}</button>`).join('')}
            ${copiables.length ? `<span style="flex:1;"></span>
                <select id="insCopiarDe" style="padding:6px 10px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:12px; max-width:260px;">
                    <option value="">Copiar insumos de otra actividad…</option>
                    ${copiables.map(id => `<option value="${id}">${esc(TAREAS[id].codigo + ' · ' + TAREAS[id].nombre)} (${INS_RESUMEN[id].n})</option>`).join('')}
                </select>
                <button type="button" class="ins-add" id="insCopiarBtn"><i class="fas fa-copy"></i> Copiar</button>` : ''}
        </div>` : '';

    document.getElementById('insCuerpo').innerHTML = `
        <div id="insError" class="ins-err" style="display:none;"></div>
        <div style="padding:16px 20px 20px;">
            ${PUEDE_EDITAR ? `<div style="background:#eff6ff; border:1px solid #dbeafe; color:#1e40af; border-radius:9px; padding:9px 12px; font-size:11.5px; margin-bottom:12px;">
                <i class="fas fa-info-circle"></i> La suma de los insumos será el <strong>presupuesto de la actividad</strong>. Los materiales y equipos del catálogo y la mano de obra por tipo de labor se comparan automáticamente con lo consumido; el resto se compara por categoría.
                ${t.insumos_n === 0 && num(t.costo_estimado) > 0 ? `<br><i class="fas fa-exclamation-triangle" style="color:#b45309;"></i> Esta actividad tiene hoy un presupuesto manual de <strong>${fmt(t.costo_estimado)}</strong> que será reemplazado por la suma de los insumos.` : ''}
            </div>` : ''}
            <div style="overflow-x:auto;">
                <table class="ins-tbl" id="insTabla">
                    <thead><tr><th>Tipo</th><th>Insumo</th><th>Unidad</th><th class="num" style="text-align:right;">Cantidad</th><th style="text-align:right;">P. unitario (S/)</th><th style="text-align:right;">Parcial</th><th></th></tr></thead>
                    <tbody id="insFilas"></tbody>
                </table>
            </div>
            <div id="insVacio" style="display:none; padding:26px; text-align:center; color:#94a3b8; font-size:12.5px; border:1.5px dashed #e2e8f0; border-radius:10px; margin-top:8px;">
                Esta actividad aún no tiene insumos. ${PUEDE_EDITAR ? 'Agrega el primero con los botones de abajo.' : ''}
            </div>
            ${acciones}
        </div>`;

    const tbody = document.getElementById('insFilas');
    tbody.innerHTML = insLineas.map(filaIns).join('');
    document.getElementById('insVacio').style.display = insLineas.length ? 'none' : 'block';
    document.getElementById('insPie').style.display = 'flex';
    if (PUEDE_EDITAR) { document.getElementById('insGuardar').style.display = ''; }
    totalesIns();

    tbody.addEventListener('input', onInsInput);
    tbody.addEventListener('change', onInsInput);
    tbody.addEventListener('click', e => {
        const b = e.target.closest('[data-del]');
        if (b) { insLineas.splice(+b.dataset.del, 1); renderEditorIns(); }
    });
    document.querySelectorAll('#insCuerpo [data-add]').forEach(b => b.addEventListener('click', () => {
        insLineas.push(insLineaNueva(b.dataset.add));
        renderEditorIns();
        const filas = document.querySelectorAll('#insFilas tr');
        const ult = filas[filas.length - 1];
        if (ult) { const f = ult.querySelector('input[data-f="desc"], select[data-f="labor"]'); if (f) f.focus(); }
    }));
    const cp = document.getElementById('insCopiarBtn');
    if (cp) cp.addEventListener('click', copiarInsumosDe);
}

function onInsInput(e) {
    const tr = e.target.closest('tr[data-i]');
    if (!tr) return;
    const i = +tr.dataset.i, l = insLineas[i], f = e.target.dataset.f;
    if (!l || !f) return;

    if (f === 'tipo') {
        if (e.type !== 'change') return;
        const nuevo = insLineaNueva(e.target.value);
        nuevo.cantidad = l.cantidad; nuevo.precio_unitario = l.precio_unitario;
        insLineas[i] = nuevo; renderEditorIns(); return;
    }
    if (f === 'labor') { l.tipo_labor = e.target.value; l.descripcion = e.target.value; }
    else if (f === 'unidad') { l.unidad = e.target.value; }
    else if (f === 'cantidad') { l.cantidad = e.target.value; }
    else if (f === 'precio_unitario') { l.precio_unitario = e.target.value; l._auto = false; }
    else if (f === 'desc') {
        l.descripcion = e.target.value;
        const mapa = l.tipo === 'material' ? MAT_ETQ : (l.tipo === 'equipo' ? EQ_ETQ : null);
        const c = mapa ? mapa.get(e.target.value) : null;
        const idKey = l.tipo === 'material' ? 'material_id' : 'equipo_id';
        const antes = l[idKey];
        l[idKey] = c ? c.id : null;
        if (c) {
            l.descripcion = c.nombre;
            if (l.tipo === 'material') l.unidad = c.unidad || l.unidad;
            if (!num(l.precio_unitario) || l._auto) { l.precio_unitario = c.precio; l._auto = true; tr.querySelector('[data-f="precio_unitario"]').value = c.precio || ''; }
            tr.querySelector('[data-f="unidad"]').value = l.unidad;
        }
        if ((antes === null) !== (l[idKey] === null)) {   // cambió el estado de vinculación con el catálogo
            const v = tr.querySelector('.ins-vinc');
            if (v) {
                v.className = 'ins-vinc ' + (c ? '' : 'libre');
                v.textContent = c ? '✔ Catálogo: se compara con salidas y compras de este ítem' : 'Texto libre: solo se compara a nivel de categoría';
            }
        }
    }
    const par = document.getElementById('insPar-' + i);
    if (par) par.textContent = fmt(r2(num(l.cantidad) * num(l.precio_unitario)));
    totalesIns();
}

function totalesIns() {
    const por = {}; let total = 0;
    insLineas.forEach(l => { const p = r2(num(l.cantidad) * num(l.precio_unitario)); por[l.tipo] = (por[l.tipo] || 0) + p; total += p; });
    document.getElementById('insTotales').innerHTML =
        Object.keys(TIPOS_INS).filter(k => por[k]).map(k => `<span class="ins-chip" style="background:${TIPOS_INS[k].bg}; color:${TIPOS_INS[k].color};"><i class="fas ${TIPOS_INS[k].icono}"></i> ${TIPOS_INS[k].plural}: ${fmt(por[k])}</span>`).join('')
        + `<span class="ins-chip" style="background:#0f172a; color:#fff; font-size:12.5px;">Total presupuesto: ${fmt(total)}</span>`;
}

const claveIns = l => l.material_id ? 'm:' + l.material_id : (l.equipo_id ? 'e:' + l.equipo_id : (l.tipo === 'mano_obra' ? 'l:' + l.tipo_labor : null));

function copiarInsumosDe() {
    const id = document.getElementById('insCopiarDe').value;
    if (!id) return;
    fetch(BASE_URL_JS + '/tareas/insumos/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            const existentes = new Set(insLineas.map(claveIns).filter(Boolean));
            let omitidos = 0;
            d.insumos.forEach(l => {
                const k = claveIns(l);
                if (k && existentes.has(k)) { omitidos++; return; }
                const {id: _id, parcial, ...resto} = l;
                insLineas.push({...resto, _auto: false});
                if (k) existentes.add(k);
            });
            renderEditorIns();
            if (omitidos) mostrarErrorIns(omitidos + ' insumo(s) ya estaban en la lista y no se duplicaron.');
        });
}

function mostrarErrorIns(msg) {
    const b = document.getElementById('insError');
    if (!b) return;
    b.textContent = msg; b.style.display = msg ? 'block' : 'none';
}

function guardarInsumos() {
    mostrarErrorIns('');
    // Se descartan las filas totalmente vacías
    const lineas = insLineas.filter(l => !(String(l.descripcion || '').trim() === '' && l.tipo !== 'mano_obra' && !num(l.cantidad) && !num(l.precio_unitario)))
        .map(l => ({tipo: l.tipo, material_id: l.material_id, equipo_id: l.equipo_id, tipo_labor: l.tipo_labor,
                    descripcion: l.descripcion, unidad: l.unidad, cantidad: num(l.cantidad), precio_unitario: num(l.precio_unitario)}));
    const btn = document.getElementById('insGuardar');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

    const body = new URLSearchParams({csrf_token: CSRF, lineas: JSON.stringify(lineas)});
    fetch(BASE_URL_JS + '/tareas/guardarInsumos/' + insId, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: body.toString()
    })
    .then(r => r.json().then(d => ({ok: r.ok, d})))
    .then(({ok, d}) => {
        if (ok && d.success) { location.reload(); return; }
        mostrarErrorIns(d.message || 'No se pudo guardar el presupuesto.');
        document.getElementById('insCuerpo').scrollTop = 0;
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar presupuesto';
    })
    .catch(() => {
        mostrarErrorIns('Error de red al guardar. Intenta de nuevo.');
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar presupuesto';
    });
}

/* ── Comparativo presupuesto vs real de la actividad ───────────────────── */
function barraEjec(sem) {
    if (!sem || sem.pct === null) return '';
    return `<div class="wbs-progress" style="height:6px; margin-top:3px;"><div class="wbs-progress-bar ${esc(sem.clase)}" style="width:${Math.min(100, sem.pct)}%"></div></div>`;
}

function renderComparativoIns() {
    const c = insComp;
    document.getElementById('insPie').style.display = 'flex';
    if (PUEDE_EDITAR) { document.getElementById('insGuardar').style.display = 'none'; }
    document.getElementById('insTotales').innerHTML = '';
    if (!c || (!c.lineas.length)) {
        document.getElementById('insCuerpo').innerHTML = '<div style="padding:40px; text-align:center; color:#94a3b8; font-size:13px;">Esta actividad no tiene presupuesto por insumos ni consumos imputados todavía.</div>';
        return;
    }
    const cards = Object.keys(TIPOS_INS).map(k => {
        const x = c.categorias[k], t = TIPOS_INS[k];
        if (!x.plan && !x.real) return '';
        return `<div class="ins-cat"><div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
            <div class="wbs-kpi-icon" style="width:32px; height:32px; font-size:13px; background:${t.bg}; color:${t.color};"><i class="fas ${t.icono}"></i></div>
            <div style="flex:1;"><div class="wbs-kpi-lbl" style="margin:0;">${t.plural}</div><div style="font-size:14px; font-weight:800; color:#0f172a;">${fmt(x.real)}</div></div>
            <span class="wbs-badge ${esc(x.clase)}">${x.pct === null ? 'Sin ppto.' : x.pct + '%'}</span></div>
            <div class="wbs-progress"><div class="wbs-progress-bar ${esc(x.clase)}" style="width:${Math.min(100, x.pct || 0)}%"></div></div>
            <div style="font-size:11px; color:#94a3b8; margin-top:4px;">Presupuesto: ${fmt(x.plan)}</div></div>`;
    }).join('');

    const filas = Object.keys(TIPOS_INS).map(k => {
        const ls = c.lineas.filter(l => l.tipo === k);
        if (!ls.length) return '';
        const x = c.categorias[k], t = TIPOS_INS[k];
        const cab = `<tr class="ins-row-cat"><td colspan="3"><i class="fas ${t.icono}" style="color:${t.color};"></i> ${t.plural}</td>
            <td class="num" style="text-align:right;">${fmt(x.plan)}</td><td class="num" style="text-align:right;">${fmt(x.real)}</td>
            <td><span class="wbs-badge ${esc(x.clase)}">${x.pct === null ? 'Sin ppto.' : x.pct + '%'}</span>${barraEjec(x)}</td></tr>`;
        const cuerpo = ls.map(l => {
            const sem = l.sem;
            const realCel = l.real === null ? '<span style="color:#94a3b8;">—</span>' : fmt(l.real);
            const cantReal = l.real_qty === null ? '<span style="color:#94a3b8;">—</span>' : Number(l.real_qty).toLocaleString('es-PE', {maximumFractionDigits: 3});
            const nota = l.real === null ? '<br><small style="color:#94a3b8;">Se compara en el total de la categoría</small>' : '';
            const badge = sem ? `<span class="wbs-badge ${esc(sem.clase)}">${esc(sem.pct === null ? sem.label : sem.pct + '%')}</span>${barraEjec(sem)}` : '<span style="color:#94a3b8; font-size:11px;">Por categoría</span>';
            return `<tr class="${l.no_presupuestado ? 'ins-row-np' : ''}">
                <td style="padding-left:24px;"><strong>${esc(l.descripcion)}</strong>${l.no_presupuestado ? ' <span class="wbs-badge danger">No presupuestado</span>' : ''}${nota}</td>
                <td>${esc(l.unidad)}</td>
                <td class="num" style="text-align:right;">${l.plan_qty ? Number(l.plan_qty).toLocaleString('es-PE', {maximumFractionDigits: 3}) : '—'} <span style="color:#94a3b8;">/</span> ${cantReal}</td>
                <td class="num" style="text-align:right;">${l.plan ? fmt(l.plan) : '—'}</td>
                <td class="num" style="text-align:right; font-weight:700;">${realCel}</td>
                <td style="min-width:130px;">${badge}</td></tr>`;
        }).join('');
        return cab + cuerpo;
    }).join('');

    const g = (c.plan > 0) ? Math.round(c.real / c.plan * 1000) / 10 : null;
    document.getElementById('insCuerpo').innerHTML = `
        <div style="padding:16px 20px 20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                <div style="font-size:13px; color:#334155;"><strong>${fmt(c.real)}</strong> consumido de <strong>${fmt(c.plan)}</strong> presupuestado por insumos
                    ${g !== null ? `<span class="wbs-badge ${g > 100 ? 'danger' : (g >= 80 ? 'warn' : 'ok')}" style="margin-left:6px;">${g}%</span>` : ''}</div>
                <div style="font-size:11px; color:#94a3b8;">Cantidades: presupuestada / consumida. Solo costos directos de esta actividad.</div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:16px;">${cards}</div>
            <div style="overflow-x:auto;"><table class="ins-tbl">
                <thead><tr><th>Insumo</th><th>Und.</th><th style="text-align:right;">Cant. ppto. / real</th><th style="text-align:right;">Presupuesto</th><th style="text-align:right;">Real</th><th>Ejecución</th></tr></thead>
                <tbody>${filas}</tbody></table></div>
        </div>`;
}

document.addEventListener('click', e => {
    const tab = e.target.closest('#insTabs .wbs-tab');
    if (tab) cambiarTabIns(tab.dataset.tab);
});

/* ── Gráfico de categorías (panel del proyecto) ───────────────────────── */
function initChartInsumos() {
    const cv = document.getElementById('chartInsumos');
    if (!cv || typeof Chart === 'undefined' || typeof PRES_INS === 'undefined' || !PRES_INS) return;
    const ks = Object.keys(TIPOS_INS), cs = PRES_INS.categorias;
    const color = {ok: '#16a34a', warn: '#f59e0b', danger: '#dc2626', neutral: '#94a3b8'};
    new Chart(cv, {
        type: 'bar',
        data: {
            labels: ks.map(k => TIPOS_INS[k].plural),
            datasets: [
                {label: 'Presupuesto', data: ks.map(k => cs[k].plan), backgroundColor: '#93c5fd', borderRadius: 6},
                {label: 'Consumo real', data: ks.map(k => cs[k].real), backgroundColor: ks.map(k => color[cs[k].clase]), borderRadius: 6}
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            animation: {duration: 1200, easing: 'easeOutQuart'},
            interaction: {mode: 'index', intersect: false},
            plugins: {
                legend: {position: 'bottom'},
                tooltip: {callbacks: {
                    label: c => c.dataset.label + ': ' + fmt(c.parsed.y),
                    afterBody: items => { const x = cs[ks[items[0].dataIndex]]; return x.pct === null ? [] : ['Ejecución: ' + x.pct + '%']; }
                }}
            },
            scales: {y: {beginAtZero: true, ticks: {callback: v => 'S/ ' + Number(v).toLocaleString('es-PE')}}}
        }
    });
}
document.addEventListener('DOMContentLoaded', () => { initCatalogosIns(); initChartInsumos(); });

document.addEventListener('keydown', e => { if (e.key === 'Escape') { cerrarModalTarea(); cerrarDesglose(); if (INS_ACTIVO) cerrarInsumos(); } });
</script>
