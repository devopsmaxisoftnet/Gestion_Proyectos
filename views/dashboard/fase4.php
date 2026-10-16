<?php
/**
 * Dashboard Fase 4 · Control económico y financiero
 * ¿Estoy cobrando lo que valorizo? Valorizaciones, cuentas por cobrar y tesorería.
 */
include __DIR__ . '/_fase_ui.php';

$k = $data['kpis'];
$g = $data['graficos'];
$pendientes = $data['pendientes'];

$flujoOk = $k['flujo_neto'] >= 0;
$antig = $k['dias_promedio'];
$tonoAntig = $antig > 90 ? 'danger' : ($antig > 30 ? 'warn' : 'ok');
?>
<div class="fd-wrap">

<?= fd_hero([
    'g1' => '#c2410c', 'g2' => '#f59e0b', 'icono' => 'fa-hand-holding-usd', 'badge' => 'Fase 4 · Control económico y financiero',
    'titulo' => '¿Estoy cobrando lo que valorizo?',
    'subtitulo' => 'Valorizaciones presentadas, cobradas y por cobrar, su antigüedad y el resultado frente al costo ejecutado de cada proyecto.',
    'acciones' => '<a href="' . BASE_URL . '/proyectos"><i class="fas fa-project-diagram"></i> Proyectos</a><a href="' . BASE_URL . '/contratos?fase=2"><i class="fas fa-file-contract"></i> Contratos</a>',
    'cifra_label' => 'Valorizado acumulado', 'cifra' => $k['valorizado'],
    'cifra_sub' => $k['n_valorizaciones'] . ' valorizaciones registradas',
    'anillo' => $k['pct_cobrado'], 'anillo_label' => 'Cobrado',
]) ?>

<div class="fd-grid-kpi n6">
    <?= fd_kpi('fa-check-circle', '#f0fdf4', '#16a34a', 'Cobrado', [
        'to' => $k['cobrado'], 'prefix' => 'S/ ', 'tono' => 'ok', 'bar' => $k['pct_cobrado'], 'barClass' => 'ok', 'sub' => number_format($k['pct_cobrado'], 1) . '% de lo valorizado'], 0) ?>
    <?= fd_kpi('fa-hourglass-half', $k['por_cobrar'] > 0 ? '#fffbeb' : '#f0fdf4', $k['por_cobrar'] > 0 ? '#d97706' : '#16a34a', 'Por cobrar', [
        'to' => $k['por_cobrar'], 'prefix' => 'S/ ', 'tono' => $k['por_cobrar'] > 0 ? 'warn' : 'ok', 'sub' => $k['n_pendientes'] . ' valorización(es) pendiente(s)'], 1) ?>
    <?= fd_kpi('fa-stopwatch', '#eff6ff', '#2563eb', 'Antigüedad de lo pendiente', [
        'to' => $antig, 'suffix' => ' días', 'tono' => $k['n_pendientes'] ? $tonoAntig : '', 'sub' => 'Promedio ponderado por monto'], 2) ?>
    <?= fd_kpi('fa-file-invoice-dollar', '#f5f3ff', '#7c3aed', 'Avance de facturación', [
        'to' => $k['pct_facturado'], 'dec' => 1, 'suffix' => '%', 'bar' => $k['pct_facturado'], 'barClass' => 'info', 'sub' => 'Valorizado ÷ ' . fd_money($k['contratado']) . ' contratado'], 3) ?>
    <?= fd_kpi('fa-tools', '#fff7ed', '#ea580c', 'Costo ejecutado', [
        'to' => $k['costo_ejecutado'], 'prefix' => 'S/ ', 'sub' => 'En los proyectos que valorizan'], 4) ?>
    <?= fd_kpi($flujoOk ? 'fa-piggy-bank' : 'fa-exclamation-circle', $flujoOk ? '#f0fdf4' : '#fef2f2', $flujoOk ? '#16a34a' : '#dc2626', 'Flujo neto estimado', [
        'to' => abs($k['flujo_neto']), 'prefix' => ($flujoOk ? '' : '− ') . 'S/ ', 'tono' => $flujoOk ? 'ok' : 'danger', 'sub' => 'Cobrado − costo ejecutado'], 5) ?>
</div>

<div class="fd-grid-2">
    <div class="fd-card span2 fd-rise" style="--d:.1s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-chart-bar" style="color:#ea580c;"></i> Valorizaciones por mes</h3>
            <p class="fd-card-sub">Últimos 12 meses, apiladas por estado de cobranza</p>
        </div></div>
        <div class="fd-chart alto"><canvas id="cMensual"></canvas><div class="fd-empty"><i class="fas fa-file-invoice-dollar"></i>No hay valorizaciones en los últimos 12 meses.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.15s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-funnel-dollar" style="color:#f59e0b;"></i> Pipeline de cobranza</h3>
            <p class="fd-card-sub">Monto por estado de la valorización</p>
        </div></div>
        <div class="fd-chart"><canvas id="cPipeline"></canvas><div class="fd-empty"><i class="fas fa-file-invoice-dollar"></i>Sin valorizaciones registradas.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.2s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-balance-scale-right" style="color:#7c3aed;"></i> Valorizado, cobrado y costo por proyecto</h3>
            <p class="fd-card-sub">Los 8 proyectos con mayor valorización</p>
        </div></div>
        <div class="fd-chart"><canvas id="cProyectos"></canvas><div class="fd-empty"><i class="fas fa-project-diagram"></i>Ningún proyecto tiene valorizaciones todavía.</div></div>
    </div>

    <div class="fd-card span2 fd-rise" style="--d:.25s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-university" style="color:#0d9488;"></i> Tesorería: ingresos y egresos</h3>
            <p class="fd-card-sub">Movimientos de caja registrados en los últimos 6 meses<?= $k['tesoreria_movs'] ? ' · neto acumulado ' . fd_money($k['tesoreria_neto']) : '' ?></p>
        </div></div>
        <div class="fd-chart"><canvas id="cTesoreria"></canvas><div class="fd-empty"><i class="fas fa-university"></i>Aún no hay movimientos de tesorería registrados.</div></div>
    </div>
</div>

<div class="fd-section"><div><h3>Cuentas por cobrar</h3><p>Valorizaciones presentadas o aprobadas que aún no se cobran, de la más antigua a la más reciente.</p></div></div>
<div class="fd-card fd-rise" style="--d:.1s; padding:8px 14px;">
<?php if (empty($pendientes)): ?>
    <div class="fd-ok-box"><i class="fas fa-check-circle"></i>No hay valorizaciones pendientes de cobro.</div>
<?php else: ?>
    <div style="overflow-x:auto;"><table class="fd-table">
        <thead><tr><th>Proyecto</th><th>Valorización</th><th>Estado</th><th>Fecha</th><th>Antigüedad</th><th class="num">Monto</th></tr></thead>
        <tbody>
        <?php foreach ($pendientes as $p): $d = (int)$p['dias']; ?>
            <tr>
                <td><strong style="color:#1e293b;"><?= fd_h($p['proyecto']) ?></strong><div style="font-size:12px; color:#64748b; max-width:260px;"><?= fd_h($p['proyecto_nombre']) ?></div></td>
                <td>N° <?= (int)$p['numero'] ?><div style="font-size:11.5px; color:#94a3b8;"><?= fd_h($p['periodo']) ?></div></td>
                <td><?= fd_badge($p['estado'] === 'aprobada' ? 'ok' : 'info', ucfirst($p['estado'])) ?></td>
                <td style="color:#475569;"><?= date('d/m/Y', strtotime($p['fecha'])) ?></td>
                <td><?= fd_badge($d > 90 ? 'danger' : ($d > 30 ? 'warn' : 'ok'), $d . ' días') ?></td>
                <td class="num" style="font-weight:800;"><?= fd_money($p['monto'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
</div>

</div>

<script>
const D = <?= fd_json($g) ?>;
const noChart = typeof Chart === 'undefined';
const ejeMoney = {beginAtZero: true, ticks: {callback: fdCorto}};

/* Valorizaciones por mes (apiladas) */
(function () {
    const m = D.mensual;
    if (noChart || fdSuma(m.presentada) + fdSuma(m.aprobada) + fdSuma(m.pagada) <= 0) return fdVacio('cMensual');
    new Chart(document.getElementById('cMensual'), {
        type: 'bar',
        data: {labels: m.labels, datasets: [
            {label: 'Pagada', data: m.pagada, backgroundColor: '#16a34a', borderRadius: 4},
            {label: 'Aprobada', data: m.aprobada, backgroundColor: '#3b82f6', borderRadius: 4},
            {label: 'Presentada', data: m.presentada, backgroundColor: '#f59e0b', borderRadius: 4},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {x: {stacked: true, grid: {display: false}}, y: Object.assign({stacked: true}, ejeMoney)},
            plugins: {tooltip: {callbacks: {label: c => c.dataset.label + ': ' + fdMoney(c.parsed.y, 2),
                footer: it => 'Total: ' + fdMoney(it.reduce((a, b) => a + b.parsed.y, 0), 2)}}}}
    });
})();

/* Pipeline */
(function () {
    const p = D.pipeline, et = Object.keys(p), v = Object.values(p);
    if (noChart || fdSuma(v) <= 0) return fdVacio('cPipeline');
    new Chart(document.getElementById('cPipeline'), {
        type: 'doughnut',
        data: {labels: et, datasets: [{data: v, backgroundColor: ['#cbd5e1', '#f59e0b', '#3b82f6', '#16a34a'], borderWidth: 3, borderColor: '#fff', hoverOffset: 12}]},
        options: {responsive: true, maintainAspectRatio: false, cutout: '66%', animation: {animateRotate: true, animateScale: true},
            plugins: {centerText: {text: fdMoney(fdSuma(v) - (p.Borrador || 0)), sub: 'valorizado'},
                tooltip: {callbacks: {label: c => ` ${c.label}: ${fdMoney(c.parsed, 2)}`}}}}
    });
})();

/* Por proyecto */
(function () {
    const p = D.por_proyecto;
    if (noChart || !p.labels.length) return fdVacio('cProyectos');
    new Chart(document.getElementById('cProyectos'), {
        type: 'bar',
        data: {labels: p.labels, datasets: [
            {label: 'Valorizado', data: p.valorizado, backgroundColor: '#8b5cf6', borderRadius: 6},
            {label: 'Cobrado', data: p.cobrado, backgroundColor: '#16a34a', borderRadius: 6},
            {label: 'Costo ejecutado', data: p.costo, backgroundColor: '#fb923c', borderRadius: 6},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {x: {grid: {display: false}}, y: ejeMoney},
            plugins: {tooltip: {callbacks: {title: it => p.labels[it[0].dataIndex] + ' · ' + p.nombres[it[0].dataIndex], label: c => c.dataset.label + ': ' + fdMoney(c.parsed.y, 2)}}}}
    });
})();

/* Tesorería */
(function () {
    const t = D.tesoreria;
    if (noChart || fdSuma(t.ingresos) + fdSuma(t.egresos) <= 0) return fdVacio('cTesoreria');
    new Chart(document.getElementById('cTesoreria'), {
        type: 'bar',
        data: {labels: t.labels, datasets: [
            {label: 'Ingresos', data: t.ingresos, backgroundColor: '#14b8a6', borderRadius: 6},
            {label: 'Egresos', data: t.egresos, backgroundColor: '#f87171', borderRadius: 6},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {x: {grid: {display: false}}, y: ejeMoney},
            plugins: {tooltip: {callbacks: {label: c => c.dataset.label + ': ' + fdMoney(c.parsed.y, 2)}}}}
    });
})();
</script>
