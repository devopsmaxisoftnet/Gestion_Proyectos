<?php
/**
 * Dashboard Fase 2 · Planificación y proyectos
 * ¿Cómo van mis obras? Avance físico vs gasto, plazos, WBS y horas-hombre.
 */
include __DIR__ . '/_fase_ui.php';

$k = $data['kpis'];
$g = $data['graficos'];
$atencion = $data['atencion'];

$gap = $k['consumo_pct'] - $k['avance_ponderado'];            // gasto por encima del avance
$tonoGap = $gap > 15 ? 'danger' : ($gap > 5 ? 'warn' : 'ok');

$acciones = '';
if (PermisosHelper::tieneAcceso('proyectos')) { $acciones .= '<a href="' . BASE_URL . '/proyectos"><i class="fas fa-project-diagram"></i> Proyectos</a>'; }
if (PermisosHelper::tieneAcceso('tareas'))    { $acciones .= '<a href="' . BASE_URL . '/tareas"><i class="fas fa-sitemap"></i> WBS / Tareas</a>'; }
if (PermisosHelper::tieneAcceso('asistencias')) { $acciones .= '<a href="' . BASE_URL . '/asistencias"><i class="fas fa-user-clock"></i> Tareo / RRHH</a>'; }
?>
<div class="fd-wrap">

<?= fd_hero([
    'g1' => '#1d4ed8', 'g2' => '#0ea5e9', 'icono' => 'fa-drafting-compass', 'badge' => 'Fase 2 · Planificación y proyectos',
    'titulo' => '¿Cómo van mis obras?',
    'subtitulo' => 'Avance físico frente al gasto, cumplimiento de plazos y estado de la estructura de actividades de cada proyecto.',
    'acciones' => $acciones,
    'cifra_label' => 'Presupuesto en ejecución', 'cifra' => $k['presupuesto_ejecucion'],
    'cifra_sub' => $k['proyectos_ejecucion'] . ' proyectos en ejecución · ' . fd_money($k['ejecutado_ejecucion']) . ' gastado',
    'anillo' => $k['avance_ponderado'], 'anillo_label' => 'Avance físico',
]) ?>

<div class="fd-grid-kpi n6">
    <?= fd_kpi('fa-hard-hat', '#eff6ff', '#2563eb', 'Proyectos en ejecución', [
        'to' => $k['proyectos_ejecucion'], 'sub' => 'de ' . $k['proyectos_total'] . ' · ' . $k['proyectos_planificacion'] . ' en planificación'], 0) ?>
    <?= fd_kpi('fa-chart-line', '#ecfeff', '#0891b2', 'Avance físico ponderado', [
        'to' => $k['avance_ponderado'], 'dec' => 1, 'suffix' => '%', 'bar' => $k['avance_ponderado'], 'barClass' => 'info',
        'sub' => 'Ponderado por presupuesto'], 1) ?>
    <?= fd_kpi('fa-coins', $tonoGap === 'ok' ? '#f0fdf4' : ($tonoGap === 'warn' ? '#fffbeb' : '#fef2f2'), $tonoGap === 'ok' ? '#16a34a' : ($tonoGap === 'warn' ? '#d97706' : '#dc2626'),
        'Presupuesto consumido', [
        'to' => $k['consumo_pct'], 'dec' => 1, 'suffix' => '%', 'bar' => $k['consumo_pct'], 'barClass' => $tonoGap, 'tono' => $tonoGap === 'ok' ? '' : $tonoGap,
        'sub' => $gap > 0 ? 'Gasto ' . number_format($gap, 1) . ' pts por encima del avance' : 'Gasto alineado con el avance'], 2) ?>
    <?= fd_kpi('fa-calendar-times', $k['atrasados'] ? '#fef2f2' : '#f0fdf4', $k['atrasados'] ? '#dc2626' : '#16a34a', 'Con plazo vencido', [
        'to' => $k['atrasados'], 'tono' => $k['atrasados'] ? 'danger' : 'ok', 'sub' => $k['atrasados'] ? 'Fecha fin planificada superada' : 'Todos dentro de plazo'], 3) ?>
    <?= fd_kpi('fa-tasks', '#f5f3ff', '#7c3aed', 'Actividades WBS', [
        'to' => $k['tareas_completadas'], 'suffix' => ' / ' . $k['tareas_total'],
        'sub' => $k['tareas_bloqueadas'] . ' bloqueadas · ' . $k['tareas_vencidas'] . ' vencidas'], 4) ?>
    <?= fd_kpi('fa-user-clock', '#fff7ed', '#ea580c', 'Horas-hombre del mes', [
        'to' => $k['horas_mes'], 'dec' => 1, 'sub' => 'Mano de obra imputada: ' . fd_money($k['costo_mo_mes'])], 5) ?>
</div>

<div class="fd-grid-2">
    <div class="fd-card span2 fd-rise" style="--d:.1s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-balance-scale" style="color:#2563eb;"></i> Avance físico vs. presupuesto consumido</h3>
            <p class="fd-card-sub">Proyectos en ejecución de mayor presupuesto · la barra de gasto se pone ámbar o roja cuando supera al avance</p>
        </div></div>
        <div class="fd-chart alto"><canvas id="cAvance"></canvas><div class="fd-empty"><i class="fas fa-hard-hat"></i>No hay proyectos en ejecución con presupuesto.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.15s">
        <div class="fd-card-head"><h3 class="fd-card-title"><i class="fas fa-project-diagram" style="color:#0891b2;"></i> Proyectos por estado</h3></div>
        <div class="fd-chart"><canvas id="cEstados"></canvas><div class="fd-empty"><i class="fas fa-folder-open"></i>Sin proyectos registrados.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.2s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-sitemap" style="color:#7c3aed;"></i> Estado del WBS</h3>
            <p class="fd-card-sub">Actividades de todos los proyectos</p>
        </div></div>
        <div class="fd-chart"><canvas id="cTareas"></canvas><div class="fd-empty"><i class="fas fa-sitemap"></i>Aún no hay actividades registradas en el WBS.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.25s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-layer-group" style="color:#ea580c;"></i> Costo directo por categoría</h3>
            <p class="fd-card-sub">Compras, almacén, subcontratos y tareo imputados a actividades</p>
        </div></div>
        <div class="fd-chart"><canvas id="cCostos"></canvas><div class="fd-empty"><i class="fas fa-receipt"></i>Aún no hay gasto imputado a actividades.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.3s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-user-clock" style="color:#059669;"></i> Horas-hombre (6 meses)</h3>
            <p class="fd-card-sub">Normales y extras registradas en el tareo</p>
        </div></div>
        <div class="fd-chart"><canvas id="cHoras"></canvas><div class="fd-empty"><i class="fas fa-user-clock"></i>Aún no hay horas registradas en el tareo.</div></div>
    </div>
</div>

<div class="fd-section"><div><h3>Proyectos que requieren atención</h3><p>Plazo vencido, sobrecosto, gasto por encima del avance o actividades bloqueadas.</p></div></div>
<div class="fd-card fd-rise" style="--d:.1s; padding:8px 14px;">
<?php if (empty($atencion)): ?>
    <div class="fd-ok-box"><i class="fas fa-check-circle"></i>Ningún proyecto presenta alertas. ¡Todo bajo control!</div>
<?php else: ?>
    <div style="overflow-x:auto;">
    <table class="fd-table">
        <thead><tr><th>Proyecto</th><th>Estado</th><th style="min-width:150px;">Avance</th><th>Alertas</th><th class="num"></th></tr></thead>
        <tbody>
        <?php foreach ($atencion as $p): ?>
            <tr>
                <td><a class="fd-link" style="color:#1d4ed8;" href="<?= BASE_URL ?>/tareas?proyecto_id=<?= (int)$p['id'] ?>"><?= fd_h($p['codigo']) ?></a>
                    <div style="font-size:12px; color:#64748b; max-width:280px;"><?= fd_h($p['nombre']) ?></div></td>
                <td><?= fd_badge($p['estado'] === 'en_ejecucion' ? 'info' : 'neutral', $p['estado'] === 'en_ejecucion' ? 'En ejecución' : 'Planificación') ?></td>
                <td><div style="display:flex; align-items:center; gap:8px;"><div class="fd-bar" style="flex:1;"><div class="info" data-w="<?= min(100, (float)$p['avance']) ?>"></div></div>
                    <span style="font-weight:700; font-size:12px;"><?= number_format($p['avance'], 0) ?>%</span></div></td>
                <td><div style="display:flex; gap:6px; flex-wrap:wrap;"><?php foreach ($p['motivos'] as [$tono, $txt]) { echo fd_badge($tono, $txt); } ?></div></td>
                <td class="num"><a href="<?= BASE_URL ?>/tareas?proyecto_id=<?= (int)$p['id'] ?>" class="fd-link" style="color:#2563eb; font-size:12px;">Ver WBS <i class="fas fa-chevron-right"></i></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
</div>

</div>

<script>
const D = <?= fd_json($g) ?>;

/* Avance vs consumo */
(function () {
    const a = D.avance_vs_costo;
    if (!a.labels.length || typeof Chart === 'undefined') return fdVacio('cAvance');
    const colorConsumo = a.consumo.map((c, i) => c <= a.avance[i] + 5 ? '#16a34a' : (c <= a.avance[i] + 15 ? '#f59e0b' : '#dc2626'));
    new Chart(document.getElementById('cAvance'), {
        type: 'bar',
        data: {labels: a.labels, datasets: [
            {label: 'Avance físico %', data: a.avance, backgroundColor: '#3b82f6', borderRadius: 8},
            {label: 'Presupuesto consumido %', data: a.consumo, backgroundColor: colorConsumo, borderRadius: 8},
        ]},
        options: {
            responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            plugins: {tooltip: {callbacks: {
                title: it => a.labels[it[0].dataIndex] + ' · ' + a.nombres[it[0].dataIndex],
                label: c => c.dataset.label + ': ' + fdFmt(c.parsed.y, 1) + '%'
            }}},
            scales: {y: {beginAtZero: true, suggestedMax: 100, ticks: {callback: v => v + '%'}}, x: {grid: {display: false}}}
        }
    });
})();

/* Dona genérica */
function dona(id, etiquetas, valores, colores, textoCentro, subCentro) {
    if (typeof Chart === 'undefined' || fdSuma(valores) <= 0) return fdVacio(id);
    new Chart(document.getElementById(id), {
        type: 'doughnut',
        data: {labels: etiquetas, datasets: [{data: valores, backgroundColor: colores, borderWidth: 3, borderColor: '#fff', hoverOffset: 12}]},
        options: {responsive: true, maintainAspectRatio: false, cutout: '66%',
            animation: {animateRotate: true, animateScale: true},
            plugins: {centerText: {text: textoCentro, sub: subCentro},
                tooltip: {callbacks: {label: c => ' ' + c.label + ': ' + (id === 'cCostos' ? fdMoney(c.parsed) : c.parsed)}}}}
    });
}

const ES = {planificacion: 'Planificación', en_ejecucion: 'En ejecución', suspendido: 'Suspendido', finalizado: 'Finalizado', liquidado: 'Liquidado'};
const ESC = {planificacion: '#94a3b8', en_ejecucion: '#3b82f6', suspendido: '#f59e0b', finalizado: '#16a34a', liquidado: '#8b5cf6'};
const ek = Object.keys(D.estados_proyecto);
dona('cEstados', ek.map(k => ES[k] || k), ek.map(k => D.estados_proyecto[k]), ek.map(k => ESC[k] || '#cbd5e1'), String(fdSuma(Object.values(D.estados_proyecto))), 'proyectos');

const tk = Object.keys(D.estados_tareas);
dona('cTareas', tk, Object.values(D.estados_tareas), ['#16a34a', '#3b82f6', '#cbd5e1', '#dc2626'], String(fdSuma(Object.values(D.estados_tareas))), 'actividades');

const c = D.costos, cv = [c.materiales, c.equipos, c.mano_obra, c.subcontratos];
dona('cCostos', ['Materiales', 'Equipos', 'Mano de obra', 'Subcontratos'], cv, ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b'], fdMoney(fdSuma(cv)), 'costo directo');

/* Horas-hombre */
(function () {
    const h = D.horas;
    if (typeof Chart === 'undefined' || fdSuma(h.normales) + fdSuma(h.extras) <= 0) return fdVacio('cHoras');
    new Chart(document.getElementById('cHoras'), {
        type: 'bar',
        data: {labels: h.labels, datasets: [
            {label: 'Normales', data: h.normales, backgroundColor: '#10b981', borderRadius: 6},
            {label: 'Extras', data: h.extras, backgroundColor: '#f59e0b', borderRadius: 6},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {x: {stacked: true, grid: {display: false}}, y: {stacked: true, beginAtZero: true, ticks: {callback: v => v + ' h'}}},
            plugins: {tooltip: {callbacks: {label: c => c.dataset.label + ': ' + fdFmt(c.parsed.y, 1) + ' h'}}}}
    });
})();
</script>
