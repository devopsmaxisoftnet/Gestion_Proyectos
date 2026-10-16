<?php
/**
 * Vista: Tareo / RRHH por actividad
 * Hoja diaria (un colaborador puede repartir sus horas entre varias actividades del WBS) y
 * productividad del proyecto (horas-hombre por avance, costo de mano de obra, tendencia).
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = fn($v) => 'S/ ' . number_format((float)$v, 2);
$jsonSeguro = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$proyectos = $data['proyectos'];
$proyectoId = (int)$data['proyectoId'];
$proyectoActual = $data['proyectoActual'];
$fecha = $data['fecha'];
$registros = $data['registros'];
$profesionales = $data['profesionales'];
$tareas = $data['tareas'];
$resumen = $data['resumen'];
$analitica = $data['analitica'];
$otrasHoras = $data['otrasHoras'];
$puedeGuardar = $data['puedeGuardar'];

$prevFecha = date('Y-m-d', strtotime($fecha . ' -1 day'));
$nextFecha = date('Y-m-d', strtotime($fecha . ' +1 day'));
$esHoy = $fecha === date('Y-m-d');

// Promedio de H-H por 1 % de avance entre las actividades con avance (referencia de productividad)
$hhVals = array_values(array_filter(array_map(fn($a) => $a['hh_por_avance'], $analitica['actividades'] ?? []), fn($v) => $v !== null));
$hhPromedio = $hhVals ? array_sum($hhVals) / count($hhVals) : 0;

$kpi = function (string $icono, string $bg, string $fg, string $valor, string $etiqueta, string $sub = '') {
    return '<div class="alm-card alm-kpi"><div class="alm-kpi-icon" style="background:' . $bg . '; color:' . $fg . ';"><i class="fas ' . $icono . '"></i></div>'
         . '<div><div class="alm-kpi-val" style="color:' . $fg . ';">' . $valor . '</div><div class="alm-kpi-lbl">' . $etiqueta . '</div>'
         . ($sub !== '' ? '<div class="alm-sub">' . $sub . '</div>' : '') . '</div></div>';
};
?>
<?php include __DIR__ . '/../almacen/_estilos.php'; ?>
<style>
.tar-chart { position:relative; height:280px; }
.tar-empty { position:absolute; inset:0; display:none; flex-direction:column; align-items:center; justify-content:center; text-align:center; color:#94a3b8; font-size:12.5px; gap:8px; padding:20px; }
.tar-empty i { font-size:30px; color:#cbd5e1; }
.tar-cost { font-weight:800; color:#0f172a; white-space:nowrap; }
.tar-warn { border-color:#f59e0b !important; background:#fffbeb !important; }
.tar-err { border-color:#dc2626 !important; background:#fef2f2 !important; }
.alm-tab.activo { color:#059669; border-bottom-color:#059669; }
.alm-tab.activo .cnt { background:#d1fae5; color:#047857; }
</style>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <!-- Cabecera: proyecto (centro de costo) y fecha -->
    <div class="alm-card" style="border-radius:16px; padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg,#10b981 0%,#059669 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; box-shadow:0 4px 12px rgba(16,185,129,.25);"><i class="fas fa-user-clock"></i></div>
            <div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span style="background:#ecfdf5; color:#047857; border:1px solid #d1fae5; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; text-transform:uppercase;">RRHH · Mano de obra por actividad</span>
                    <?php if ($proyectoActual): ?><span style="font-family:monospace; background:#f1f5f9; color:#475569; font-size:11.5px; font-weight:700; padding:2px 7px; border-radius:5px;">Centro de costo: <?= $h($proyectoActual['codigo']) ?></span><?php endif; ?>
                </div>
                <h2 style="font-size:19px; font-weight:800; color:#0f172a; margin:4px 0 0;"><?= $proyectoActual ? $h($proyectoActual['nombre']) : 'Selecciona un proyecto' ?></h2>
            </div>
        </div>
        <form method="GET" action="<?= BASE_URL ?>/asistencias" style="display:flex; align-items:center; gap:8px; margin:0; flex-wrap:wrap;">
            <select name="proyecto_id" onchange="this.form.submit()" style="padding:9px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; font-weight:600; color:#1e293b; background:#fff; max-width:340px;">
                <?php if (empty($proyectos)): ?><option value="">No hay proyectos registrados</option><?php endif; ?>
                <?php foreach ($proyectos as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === $proyectoId) ? 'selected' : '' ?>>[<?= $h($p['codigo']) ?>] <?= $h($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:inline-flex; align-items:center; background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:10px; overflow:hidden;">
                <a href="<?= BASE_URL ?>/asistencias?proyecto_id=<?= $proyectoId ?>&fecha=<?= $prevFecha ?>" style="padding:8px 12px; color:#475569; text-decoration:none; border-right:1px solid #cbd5e1;" title="Día anterior"><i class="fas fa-chevron-left"></i></a>
                <input type="date" name="fecha" value="<?= $h($fecha) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()" style="border:none; background:transparent; padding:7px 12px; font-size:13px; font-weight:700; color:#1e293b; outline:none;">
                <a href="<?= $esHoy ? '#' : BASE_URL . '/asistencias?proyecto_id=' . $proyectoId . '&fecha=' . $nextFecha ?>" style="padding:8px 12px; color:<?= $esHoy ? '#cbd5e1' : '#475569' ?>; text-decoration:none; border-left:1px solid #cbd5e1; <?= $esHoy ? 'pointer-events:none;' : '' ?>" title="Día siguiente"><i class="fas fa-chevron-right"></i></a>
            </div>
        </form>
    </div>

<?php if ($proyectoId <= 0): ?>
    <div style="background:#fff; border:1.5px dashed #cbd5e1; border-radius:16px; padding:60px 20px; text-align:center;">
        <i class="fas fa-users-cog" style="font-size:42px; color:#94a3b8; margin-bottom:14px; display:block;"></i>
        <h3 style="font-size:17px; font-weight:700; color:#1e293b; margin:0 0 6px;">Selecciona un proyecto</h3>
        <p style="font-size:13px; color:#64748b; margin:0;">Elige un proyecto para registrar las horas del personal en sus actividades.</p>
    </div>
<?php else: ?>

    <?php if ($resumen['sin_actividad'] > 0): ?>
    <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
        <i class="fas fa-exclamation-triangle"></i> Hay <strong><?= number_format($resumen['sin_actividad'], 1) ?> h</strong> de tareos anteriores <strong>sin actividad</strong>: no cuentan en el costo del WBS. Abre esa fecha, asígnales una actividad y guarda.
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(290px, 1fr)); gap:14px; margin-bottom:20px;">
        <?= $kpi('fa-user-check', '#eff6ff', '#2563eb', (string)$resumen['personas_dia'], $esHoy ? 'Personal con tareo hoy' : 'Personal con tareo ese día', $resumen['personas_acum'] . ' personas en el proyecto') ?>
        <?= $kpi('fa-clock', '#ecfdf5', '#059669', number_format($resumen['hn_dia'] + $resumen['he_dia'], 1) . ' h', 'Horas de la jornada', number_format($resumen['hn_dia'], 1) . ' normales · ' . number_format($resumen['he_dia'], 1) . ' extras') ?>
        <?= $kpi('fa-coins', '#fffbeb', '#d97706', $money($resumen['costo_dia']), 'Mano de obra del día', 'Imputada a las actividades') ?>
        <?= $kpi('fa-history', '#f5f3ff', '#7c3aed', number_format($resumen['horas_acum'], 1) . ' h', 'Horas acumuladas', $resumen['dias'] . ' días con tareo · ' . number_format($resumen['pct_extras'], 1) . '% extras') ?>
        <?= $kpi('fa-hand-holding-usd', '#fff7ed', '#ea580c', $money($resumen['costo_acum']), 'Mano de obra acumulada', 'Costo real del proyecto por tareo') ?>
        <?= $kpi('fa-tachometer-alt', '#f0fdfa', '#0d9488', $resumen['hh_por_avance'] !== null ? number_format($resumen['hh_por_avance'], 1) . ' h' : '—', 'H-H por 1 % de avance', $resumen['hh_por_avance'] !== null ? 'Avance físico del proyecto: ' . number_format($resumen['avance'], 1) . '%' : 'Requiere horas y avance registrados') ?>
    </div>

    <div class="alm-card" style="border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.03);">
        <div class="alm-tabs">
            <button type="button" class="alm-tab activo" data-tab="hoja"><i class="fas fa-clipboard-list"></i> Hoja de tareo <span class="cnt" id="cntFilas">0</span></button>
            <button type="button" class="alm-tab" data-tab="productividad"><i class="fas fa-chart-line"></i> Productividad <span class="cnt"><?= count($analitica['actividades']) ?></span></button>
        </div>

        <!-- ═════ Hoja de tareo ═════ -->
        <div class="alm-panel activo" id="panel-hoja">
        <form method="POST" action="<?= BASE_URL ?>/asistencias/guardar" id="formTareo">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
            <input type="hidden" name="proyecto_id" value="<?= $proyectoId ?>">
            <input type="hidden" name="fecha" value="<?= $h($fecha) ?>">

            <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span style="font-weight:700; color:#1e293b; font-size:13.5px;"><i class="far fa-calendar-check" style="color:#059669;"></i> Jornada del <?= date('d/m/Y', strtotime($fecha)) ?></span>
                    <?php if ($data['reintento']): ?><span class="alm-badge warn">Corrige los datos y vuelve a guardar</span><?php endif; ?>
                </div>
                <?php if ($puedeGuardar): ?>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <div class="alm-field" style="min-width:230px;">
                        <select id="selActDefault" title="Actividad que se propone en las filas nuevas"></select>
                    </div>
                    <label style="display:inline-flex; align-items:center; gap:6px; font-size:12px; color:#475569; font-weight:600; cursor:pointer;"><input type="checkbox" id="chkPadres" onchange="refrescarActividades()"> Incluir padres</label>
                    <button type="button" onclick="agregarFila()" style="background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; padding:7px 13px; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer;"><i class="fas fa-plus"></i> Fila</button>
                    <button type="button" onclick="cargarPersonal()" style="background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; padding:7px 13px; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer;" title="Agrega a toda la nómina con 8 h en la actividad elegida"><i class="fas fa-users"></i> Cargar todo el personal (8 h)</button>
                </div>
                <?php endif; ?>
            </div>

            <div style="overflow-x:auto;">
                <table class="alm-tbl" style="min-width:1200px;">
                    <thead><tr>
                        <th style="width:34px;">#</th><th style="min-width:230px;">Colaborador *</th><th style="min-width:260px;">Actividad (WBS) *</th>
                        <th style="width:100px; text-align:center;">H. normales</th><th style="width:100px; text-align:center;">H. extras</th>
                        <th style="width:140px;">Tipo de labor</th><th>Observaciones</th><th style="width:110px; text-align:right;">Costo</th><th style="width:44px;"></th>
                    </tr></thead>
                    <tbody id="tbodyTareo"></tbody>
                    <tfoot><tr style="background:#f8fafc;">
                        <td colspan="3" style="padding:12px 14px; font-weight:700; color:#475569; text-align:right;">Totales de la hoja</td>
                        <td style="text-align:center; font-weight:800; color:#059669;" id="totHN">0.0</td>
                        <td style="text-align:center; font-weight:800; color:#d97706;" id="totHE">0.0</td>
                        <td colspan="2"></td>
                        <td style="text-align:right; font-weight:800; color:#0f172a;" id="totCosto">S/ 0.00</td><td></td>
                    </tr></tfoot>
                </table>
            </div>

            <?php if ($puedeGuardar): ?>
            <div style="padding:16px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <span style="font-size:12px; color:#64748b; max-width:600px;">Un colaborador puede repartir su jornada entre varias actividades. El costo = H. normales × tarifa + H. extras × tarifa extra (tarifas en <a href="<?= BASE_URL ?>/profesionales" style="color:#059669; font-weight:700;">Profesionales</a>) y se suma al costo real de cada actividad.</span>
                <button type="submit" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%); color:#fff; border:none; padding:10px 24px; border-radius:10px; font-size:13.5px; font-weight:700; cursor:pointer; box-shadow:0 4px 12px rgba(16,185,129,.3);"><i class="fas fa-save"></i> Guardar tareo del día</button>
            </div>
            <?php endif; ?>
        </form>
        </div>

        <!-- ═════ Productividad ═════ -->
        <div class="alm-panel" id="panel-productividad" style="padding:20px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(380px, 1fr)); gap:16px; margin-bottom:20px;">
                <div class="alm-card" style="padding:18px 20px; min-width:0;">
                    <h3 style="margin:0 0 4px; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-layer-group" style="color:#059669;"></i> Horas-hombre por actividad</h3>
                    <p style="margin:0 0 10px; font-size:11.5px; color:#94a3b8;">Normales y extras · las 10 actividades con más horas</p>
                    <div class="tar-chart"><canvas id="cHoras"></canvas><div class="tar-empty"><i class="fas fa-user-clock"></i>Aún no hay horas registradas en este proyecto.</div></div>
                </div>
                <div class="alm-card" style="padding:18px 20px; min-width:0;">
                    <h3 style="margin:0 0 4px; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-tachometer-alt" style="color:#0d9488;"></i> Productividad: H-H por 1 % de avance</h3>
                    <p style="margin:0 0 10px; font-size:11.5px; color:#94a3b8;">Menos horas por punto de avance = más productiva · verde por debajo del promedio, rojo muy por encima</p>
                    <div class="tar-chart"><canvas id="cProd"></canvas><div class="tar-empty"><i class="fas fa-tachometer-alt"></i>Se calcula cuando una actividad tiene horas y avance registrado.</div></div>
                </div>
                <div class="alm-card" style="padding:18px 20px; min-width:0;">
                    <h3 style="margin:0 0 4px; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-chart-bar" style="color:#2563eb;"></i> Tendencia diaria (14 días)</h3>
                    <p style="margin:0 0 10px; font-size:11.5px; color:#94a3b8;">Horas registradas y costo de mano de obra por día</p>
                    <div class="tar-chart"><canvas id="cTend"></canvas><div class="tar-empty"><i class="fas fa-calendar-alt"></i>No hay tareo en los últimos 14 días.</div></div>
                </div>
                <div class="alm-card" style="padding:18px 20px; min-width:0;">
                    <h3 style="margin:0 0 4px; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-user-tie" style="color:#d97706;"></i> Costo de mano de obra por colaborador</h3>
                    <p style="margin:0 0 10px; font-size:11.5px; color:#94a3b8;">Los 8 con mayor costo acumulado en el proyecto</p>
                    <div class="tar-chart"><canvas id="cProf"></canvas><div class="tar-empty"><i class="fas fa-user-tie"></i>Sin tareo registrado todavía.</div></div>
                </div>
            </div>

            <div style="overflow-x:auto; border:1px solid #e2e8f0; border-radius:14px;">
                <table class="alm-tbl">
                    <thead><tr><th>Actividad</th><th style="text-align:center;">Personas</th><th style="text-align:right;">H. normales</th><th style="text-align:right;">H. extras</th><th style="text-align:right;">Total H-H</th><th style="text-align:right;">Costo M.O.</th><th style="min-width:130px;">Avance</th><th style="text-align:right;">H-H / 1 %</th><th style="text-align:center;">Productividad</th></tr></thead>
                    <tbody>
                    <?php if (empty($analitica['actividades'])): ?>
                        <tr><td colspan="9" style="padding:34px; text-align:center; color:#94a3b8;"><i class="fas fa-clipboard-list" style="font-size:28px; display:block; margin-bottom:8px; color:#cbd5e1;"></i>Aún no hay horas imputadas a actividades.</td></tr>
                    <?php else: foreach ($analitica['actividades'] as $a):
                        $hh = $a['hh_por_avance'];
                        $ratio = ($hh !== null && $hhPromedio > 0) ? $hh / $hhPromedio : null;
                        [$tono, $txt] = $ratio === null ? ['neutral', 'Sin avance'] : ($ratio <= 1 ? ['ok', 'Eficiente'] : ($ratio <= 1.5 ? ['warn', 'Regular'] : ['danger', 'Baja']));
                    ?>
                        <tr>
                            <td><span style="font-family:monospace; font-weight:700; background:#f1f5f9; padding:2px 7px; border-radius:6px; font-size:11.5px;"><?= $h($a['codigo']) ?></span> <strong style="color:#1e293b; font-size:12.5px;"><?= $h($a['nombre']) ?></strong></td>
                            <td style="text-align:center;"><?= (int)$a['personas'] ?></td>
                            <td style="text-align:right;"><?= number_format($a['hn'], 1) ?></td>
                            <td style="text-align:right; color:#d97706;"><?= number_format($a['he'], 1) ?></td>
                            <td style="text-align:right; font-weight:800;"><?= number_format($a['horas'], 1) ?></td>
                            <td style="text-align:right; font-weight:700;"><?= $money($a['costo']) ?></td>
                            <td><div style="display:flex; align-items:center; gap:8px;"><div class="alm-bar" style="flex:1;"><div data-ancho="<?= min(100, (float)$a['progreso']) ?>" style="background-color:#3b82f6;"></div></div><strong style="font-size:12px;"><?= number_format($a['progreso'], 0) ?>%</strong></div></td>
                            <td style="text-align:right; font-weight:700;"><?= $hh !== null ? number_format($hh, 2) : '—' ?></td>
                            <td style="text-align:center;"><span class="alm-badge <?= $tono ?>"><?= $txt ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<?php if ($proyectoId > 0): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const PROFES = <?= $jsonSeguro($profesionales) ?>;
const TAREAS = <?= $jsonSeguro($tareas) ?>;
const REGISTROS = <?= $jsonSeguro($registros) ?>;
const OTRAS = <?= $jsonSeguro((object)$otrasHoras) ?>;       // horas del mismo día en otros proyectos
const ANALITICA = <?= $jsonSeguro($analitica) ?>;
const HH_PROMEDIO = <?= json_encode(round($hhPromedio, 4)) ?>;
const PUEDE_GUARDAR = <?= $puedeGuardar ? 'true' : 'false' ?>;
const MAX_DIA = 24;

const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = (n, d = 2) => Number(n || 0).toLocaleString('es-PE', {minimumFractionDigits: d, maximumFractionDigits: d});
const profPorId = id => PROFES.find(p => String(p.id) === String(id));
const TIPOS = ['Operativo', 'Supervisión', 'Técnico', 'Mantenimiento', 'Apoyo'];
let idx = 0;

/* ── Pestañas ──────────────────────────────────────────────────────────── */
let graficosListos = false;
function activarTab(nombre) {
    if (!document.getElementById('panel-' + nombre)) nombre = 'hoja';
    document.querySelectorAll('.alm-tab').forEach(t => t.classList.toggle('activo', t.dataset.tab === nombre));
    document.querySelectorAll('.alm-panel').forEach(p => p.classList.toggle('activo', p.id === 'panel-' + nombre));
    if (nombre === 'productividad') {
        requestAnimationFrame(() => document.querySelectorAll('.alm-bar > div[data-ancho]').forEach(b => { b.style.width = b.dataset.ancho + '%'; }));
        if (!graficosListos) { graficosListos = true; initGraficos(); }   // los canvas ocultos no se pueden medir: se dibujan al mostrarlos
    }
}
document.querySelectorAll('.alm-tab').forEach(t => t.addEventListener('click', () => {
    history.replaceState(null, '', t.dataset.tab === 'hoja' ? location.pathname + location.search : '#' + t.dataset.tab);
    activarTab(t.dataset.tab);
}));

/* ── Actividades ───────────────────────────────────────────────────────── */
function opcionesActividad(sel) {
    const verPadres = document.getElementById('chkPadres').checked;
    let html = '<option value="">— Elige la actividad —</option>';
    TAREAS.forEach(t => {
        if (!t.es_hoja && !verPadres && String(t.id) !== String(sel)) return;
        html += `<option value="${t.id}" ${String(t.id) === String(sel) ? 'selected' : ''}>${'— '.repeat(t.nivel)}${esc(t.codigo)} · ${esc(t.nombre)}</option>`;
    });
    return html;
}
function refrescarActividades() {
    const def = document.getElementById('selActDefault');
    if (def) { const v = def.value; def.innerHTML = opcionesActividad(v).replace('— Elige la actividad —', '— Actividad por defecto —'); def.value = v; }
    document.querySelectorAll('#tbodyTareo .sel-act').forEach(s => { s.innerHTML = opcionesActividad(s.dataset.v || ''); });
}

/* ── Filas ─────────────────────────────────────────────────────────────── */
function opcionesProf(sel) {
    return '<option value="">— Colaborador —</option>' + PROFES.map(p =>
        `<option value="${p.id}" ${String(p.id) === String(sel) ? 'selected' : ''}>${esc(p.nombre)}${Number(p.activo) ? '' : ' (en proyecto)'}</option>`).join('');
}

function agregarFila(r) {
    r = r || {};
    const i = idx++;
    const bloqueada = !PUEDE_GUARDAR;
    const def = document.getElementById('selActDefault');
    const tarea = r.tarea_id ?? (def ? def.value : '') ?? '';
    const tr = document.createElement('tr');
    tr.className = 'fila alm-form-cell';
    tr.innerHTML = `
        <td class="n" style="color:#94a3b8; font-weight:700; text-align:center;"></td>
        <td><select class="sel-prof" name="tareo[${i}][profesional_id]" ${bloqueada ? 'disabled' : ''} onchange="recalcular()">${opcionesProf(r.profesional_id)}</select>
            <div class="info-prof" style="font-size:11px; color:#94a3b8; margin-top:3px; min-height:14px;"></div></td>
        <td><select class="sel-act" name="tareo[${i}][tarea_id]" data-v="${esc(tarea)}" ${bloqueada ? 'disabled' : ''} onchange="this.dataset.v = this.value; this.classList.remove('tar-err'); recalcular()">${opcionesActividad(tarea)}</select>
            ${!r.tarea_id && r.guardado ? '<div style="font-size:10.5px; color:#d97706; margin-top:3px;"><i class="fas fa-exclamation-triangle"></i> Sin actividad</div>' : ''}</td>
        <td><input class="num hn" type="number" step="0.5" min="0" max="24" name="tareo[${i}][horas_normales]" value="${r.horas_normales ?? 8}" ${bloqueada ? 'disabled' : ''} oninput="recalcular()"></td>
        <td><input class="num he" type="number" step="0.5" min="0" max="24" name="tareo[${i}][horas_extras]" value="${r.horas_extras ?? 0}" ${bloqueada ? 'disabled' : ''} oninput="recalcular()" style="color:#d97706;"></td>
        <td><select name="tareo[${i}][tipo_labor]" ${bloqueada ? 'disabled' : ''}>${TIPOS.map(t => `<option value="${t}" ${(r.tipo_labor || 'Operativo') === t ? 'selected' : ''}>${t}</option>`).join('')}</select></td>
        <td><input type="text" name="tareo[${i}][observaciones]" maxlength="500" value="${esc(r.observaciones)}" ${bloqueada ? 'disabled' : ''} placeholder="Frente de trabajo, incidente o nota..."></td>
        <td class="tar-cost costo" style="text-align:right;">S/ 0.00</td>
        <td style="text-align:center;">${bloqueada ? '' : '<button type="button" onclick="this.closest(\'tr\').remove(); renumerar(); recalcular();" style="background:transparent; border:none; color:#cbd5e1; cursor:pointer;" title="Quitar fila"><i class="fas fa-trash-alt"></i></button>'}</td>`;
    document.getElementById('tbodyTareo').appendChild(tr);
    renumerar(); recalcular();
}

function renumerar() {
    const filas = document.querySelectorAll('#tbodyTareo tr.fila');
    filas.forEach((tr, i) => tr.querySelector('.n').textContent = i + 1);
    document.getElementById('cntFilas').textContent = filas.length;
    const v = document.getElementById('filaVacia');
    if (!filas.length && !v) {
        document.getElementById('tbodyTareo').insertAdjacentHTML('beforeend', '<tr id="filaVacia"><td colspan="9" style="padding:40px; text-align:center; color:#94a3b8;"><i class="fas fa-clipboard-list" style="font-size:30px; display:block; margin-bottom:8px; color:#cbd5e1;"></i>No hay tareo registrado este día. Usa <strong>Fila</strong> o <strong>Cargar todo el personal</strong>.</td></tr>');
    } else if (filas.length && v) { v.remove(); }
}

function cargarPersonal() {
    const def = document.getElementById('selActDefault').value;
    if (!def) { alert('Primero elige la "actividad por defecto" a la que se cargarán las horas.'); document.getElementById('selActDefault').focus(); return; }
    const ya = new Set([...document.querySelectorAll('#tbodyTareo .sel-prof')].map(s => s.value));
    PROFES.filter(p => !ya.has(String(p.id))).forEach(p => agregarFila({profesional_id: p.id, tarea_id: def, horas_normales: 8, horas_extras: 0}));
}

/* ── Totales y validaciones en vivo ────────────────────────────────────── */
function recalcular() {
    let hn = 0, he = 0, costo = 0;
    const porPersona = {}, vistos = {};
    const filas = [...document.querySelectorAll('#tbodyTareo tr.fila')];

    filas.forEach(tr => {
        const pid = tr.querySelector('.sel-prof').value;
        const n = parseFloat(tr.querySelector('.hn').value) || 0;
        const e = parseFloat(tr.querySelector('.he').value) || 0;
        const p = profPorId(pid);
        const c = p ? n * Number(p.costo_hora) + e * Number(p.costo_hora_extra) : 0;
        tr.querySelector('.costo').textContent = 'S/ ' + fmt(c);
        tr.querySelector('.info-prof').textContent = p ? `${p.tipo_profesional || ''} · S/ ${fmt(p.costo_hora)}/h · extra S/ ${fmt(p.costo_hora_extra)}/h` : '';
        hn += n; he += e; costo += c;
        if (pid) porPersona[pid] = (porPersona[pid] || 0) + n + e;
        if (pid && tr.querySelector('.sel-act').value) {
            const k = pid + ':' + tr.querySelector('.sel-act').value;
            vistos[k] = (vistos[k] || 0) + 1;
        }
    });

    filas.forEach(tr => {
        const pid = tr.querySelector('.sel-prof').value;
        const total = (porPersona[pid] || 0) + (Number(OTRAS[pid]) || 0);
        const exceso = pid && total > MAX_DIA + 0.001;
        const dup = pid && tr.querySelector('.sel-act').value && vistos[pid + ':' + tr.querySelector('.sel-act').value] > 1;
        tr.querySelector('.hn').classList.toggle('tar-err', !!exceso);
        tr.querySelector('.he').classList.toggle('tar-err', !!exceso);
        tr.querySelector('.sel-prof').classList.toggle('tar-warn', !!dup);
        tr.querySelector('.hn').title = exceso ? `${total} h en el día (máx. ${MAX_DIA})${OTRAS[pid] ? ' · incluye ' + OTRAS[pid] + ' h en otros proyectos' : ''}` : '';
        tr.querySelector('.sel-prof').title = dup ? 'Mismo colaborador y actividad repetidos: se sumarán las horas' : '';
    });

    document.getElementById('totHN').textContent = fmt(hn, 1);
    document.getElementById('totHE').textContent = fmt(he, 1);
    document.getElementById('totCosto').textContent = 'S/ ' + fmt(costo);
}

/* ── Gráficos de productividad ─────────────────────────────────────────── */
function vacio(id) { const c = document.getElementById(id); c.style.display = 'none'; c.parentElement.querySelector('.tar-empty').style.display = 'flex'; }
const suma = a => a.reduce((x, y) => x + (Number(y) || 0), 0);

function initGraficos() {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.animation.duration = 1300;
    Chart.defaults.animation.easing = 'easeOutQuart';
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;

    const acts = ANALITICA.actividades;

    // 1) Horas por actividad
    const top = acts.slice(0, 10);
    if (!top.length) vacio('cHoras'); else new Chart(document.getElementById('cHoras'), {
        type: 'bar',
        data: {labels: top.map(a => a.codigo), datasets: [
            {label: 'Normales', data: top.map(a => a.hn), backgroundColor: '#10b981', borderRadius: 6},
            {label: 'Extras', data: top.map(a => a.he), backgroundColor: '#f59e0b', borderRadius: 6},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {x: {stacked: true, grid: {display: false}}, y: {stacked: true, beginAtZero: true, ticks: {callback: v => v + ' h'}}},
            plugins: {tooltip: {callbacks: {title: it => top[it[0].dataIndex].codigo + ' · ' + top[it[0].dataIndex].nombre,
                label: c => c.dataset.label + ': ' + fmt(c.parsed.y, 1) + ' h', footer: it => 'Costo M.O.: S/ ' + fmt(top[it[0].dataIndex].costo)}}}}
    });

    // 2) Productividad: H-H por 1 % de avance
    const conAvance = acts.filter(a => a.hh_por_avance !== null);
    if (!conAvance.length) vacio('cProd'); else new Chart(document.getElementById('cProd'), {
        data: {labels: conAvance.map(a => a.codigo), datasets: [
            {type: 'bar', label: 'H-H por 1 % de avance', data: conAvance.map(a => a.hh_por_avance), borderRadius: 6,
             backgroundColor: conAvance.map(a => { const r = HH_PROMEDIO > 0 ? a.hh_por_avance / HH_PROMEDIO : 1; return r <= 1 ? '#16a34a' : (r <= 1.5 ? '#f59e0b' : '#dc2626'); })},
            {type: 'line', label: 'Promedio', data: conAvance.map(() => HH_PROMEDIO), borderColor: '#94a3b8', borderDash: [6, 5], borderWidth: 2, pointRadius: 0},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {x: {grid: {display: false}}, y: {beginAtZero: true, ticks: {callback: v => v + ' h'}}},
            plugins: {tooltip: {callbacks: {title: it => conAvance[it[0].dataIndex].codigo + ' · ' + conAvance[it[0].dataIndex].nombre,
                label: c => c.datasetIndex === 1 ? 'Promedio: ' + fmt(c.parsed.y, 2) + ' h' : fmt(c.parsed.y, 2) + ' h por punto de avance',
                afterBody: it => ['Avance: ' + fmt(conAvance[it[0].dataIndex].progreso, 0) + '% · ' + fmt(conAvance[it[0].dataIndex].horas, 1) + ' h en total']}}}}
    });

    // 3) Tendencia diaria
    const d = ANALITICA.diario;
    if (suma(d.map(x => x.horas)) <= 0) vacio('cTend'); else new Chart(document.getElementById('cTend'), {
        data: {labels: d.map(x => x.label), datasets: [
            {type: 'bar', label: 'Horas', data: d.map(x => x.horas), backgroundColor: '#3b82f6', borderRadius: 6, yAxisID: 'y'},
            {type: 'line', label: 'Costo M.O. (S/)', data: d.map(x => x.costo), borderColor: '#f97316', backgroundColor: '#f97316', tension: .35, pointRadius: 4, yAxisID: 'y1'},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {y: {beginAtZero: true, ticks: {callback: v => v + ' h'}}, y1: {position: 'right', beginAtZero: true, grid: {display: false}, ticks: {callback: v => 'S/ ' + v}}, x: {grid: {display: false}}},
            plugins: {tooltip: {callbacks: {label: c => c.datasetIndex === 0 ? 'Horas: ' + fmt(c.parsed.y, 1) : 'Costo: S/ ' + fmt(c.parsed.y)}}}}
    });

    // 4) Costo por colaborador
    const pr = ANALITICA.profesionales;
    if (!pr.length) vacio('cProf'); else new Chart(document.getElementById('cProf'), {
        type: 'bar',
        data: {labels: pr.map(p => p.nombre.length > 18 ? p.nombre.slice(0, 17) + '…' : p.nombre), datasets: [{label: 'Costo M.O.', data: pr.map(p => p.costo), backgroundColor: '#f59e0b', borderRadius: 8}]},
        options: {indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}, tooltip: {callbacks: {
            title: it => pr[it[0].dataIndex].nombre, label: c => 'Costo: S/ ' + fmt(c.parsed.x),
            afterLabel: c => [fmt(pr[c.dataIndex].horas, 1) + ' h (' + fmt(pr[c.dataIndex].extras, 1) + ' extras)', pr[c.dataIndex].dias + ' día(s) con tareo']}}},
            scales: {x: {beginAtZero: true, ticks: {callback: v => 'S/ ' + Number(v).toLocaleString('es-PE')}}, y: {grid: {display: false}}}}
    });
}

/* ── Inicio ────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    refrescarActividades();
    REGISTROS.forEach(r => agregarFila(r));
    renumerar();

    document.getElementById('formTareo').addEventListener('submit', e => {
        let faltan = 0;
        document.querySelectorAll('#tbodyTareo tr.fila').forEach(tr => {
            const pid = tr.querySelector('.sel-prof').value;
            const h = (parseFloat(tr.querySelector('.hn').value) || 0) + (parseFloat(tr.querySelector('.he').value) || 0);
            const act = tr.querySelector('.sel-act');
            if (pid && h > 0 && !act.value) { act.classList.add('tar-err'); faltan++; }
        });
        if (faltan) { e.preventDefault(); alert('Cada fila con horas debe cargarse a una actividad del WBS (' + faltan + ' sin actividad).'); }
    });

    activarTab(location.hash.replace('#', '') || 'hoja');
});
</script>
<?php endif; ?>
