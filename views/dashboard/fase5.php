<?php
/**
 * Dashboard Fase 5 · Análisis y rentabilidad
 * ¿Qué rindió cada proyecto? Margen proyectado, índices CPI/SPI y cartera por línea de negocio.
 */
include __DIR__ . '/_fase_ui.php';

$k = $data['kpis'];
$g = $data['graficos'];
$ranking = $data['ranking'];

$tonoIdx = fn($v) => $v === null ? 'neutral' : ($v >= 1 ? 'ok' : ($v >= 0.9 ? 'warn' : 'danger'));
$barIdx = fn($v) => $v === null ? 0 : min(100, $v / 1.5 * 100);   // 1.5 = tope visual de la barra
$margenOk = $k['margen'] >= 0;
?>
<div class="fd-wrap">

<?= fd_hero([
    'g1' => '#6d28d9', 'g2' => '#c026d3', 'icono' => 'fa-chart-line', 'badge' => 'Fase 5 · Análisis y rentabilidad',
    'titulo' => '¿Qué rindió cada proyecto?',
    'subtitulo' => 'Margen proyectado al cierre y eficiencia de costo y plazo (CPI y SPI) de los proyectos en ejecución, frente a lo contratado.',
    'acciones' => '<a href="' . BASE_URL . '/dashboard?fase=4"><i class="fas fa-hand-holding-usd"></i> Control económico</a><a href="' . BASE_URL . '/dashboard?fase=2"><i class="fas fa-drafting-compass"></i> Planificación</a>',
    'cifra_label' => 'Margen proyectado al cierre', 'cifra' => abs($k['margen']), 'cifra_prefix' => ($margenOk ? '' : '− ') . 'S/ ',
    'cifra_sub' => $k['proyectos'] . ' proyectos en ejecución · ingreso ' . fd_money($k['ingreso']),
    'anillo' => max(0, $k['margen_pct']), 'anillo_label' => 'Margen s/ ingreso',
]) ?>

<div class="fd-grid-kpi n8">
    <?= fd_kpi('fa-file-signature', '#f5f3ff', '#7c3aed', 'Ingreso contratado', [
        'to' => $k['ingreso'], 'prefix' => 'S/ ', 'sub' => 'Proyectos en ejecución'], 0) ?>
    <?= fd_kpi('fa-bullseye', '#fff7ed', '#ea580c', 'Costo proyectado al cierre', [
        'to' => $k['eac'], 'prefix' => 'S/ ', 'sub' => 'Costo real ÷ avance físico (EAC)'], 1) ?>
    <?= fd_kpi('fa-percentage', $margenOk ? '#f0fdf4' : '#fef2f2', $margenOk ? '#16a34a' : '#dc2626', 'Margen proyectado', [
        'to' => $k['margen_pct'], 'dec' => 1, 'suffix' => '%', 'tono' => $margenOk ? 'ok' : 'danger', 'bar' => max(0, min(100, $k['margen_pct'] * 3)), 'barClass' => $margenOk ? 'ok' : 'danger',
        'sub' => 'Ingreso − costo proyectado'], 2) ?>
    <?= fd_kpi('fa-coins', '#eff6ff', '#2563eb', 'CPI · eficiencia de costo', [
        'to' => $k['cpi'] ?? 0, 'dec' => 2, 'tono' => $k['cpi'] === null ? '' : ($tonoIdx($k['cpi']) === 'neutral' ? '' : $tonoIdx($k['cpi'])),
        'bar' => $barIdx($k['cpi']), 'barClass' => $tonoIdx($k['cpi']) === 'neutral' ? 'info' : $tonoIdx($k['cpi']),
        'sub' => $k['cpi'] === null ? 'Sin datos' : ($k['cpi'] >= 1 ? 'Se gasta menos de lo que se avanza' : 'Se gasta más de lo que se avanza')], 3) ?>
    <?= fd_kpi('fa-calendar-check', '#ecfeff', '#0891b2', 'SPI · cumplimiento de plazo', [
        'to' => $k['spi'] ?? 0, 'dec' => 2, 'tono' => $k['spi'] === null ? '' : ($tonoIdx($k['spi']) === 'neutral' ? '' : $tonoIdx($k['spi'])),
        'bar' => $barIdx($k['spi']), 'barClass' => $tonoIdx($k['spi']) === 'neutral' ? 'info' : $tonoIdx($k['spi']),
        'sub' => $k['spi'] === null ? 'Sin datos' : ($k['spi'] >= 1 ? 'Avance igual o mayor al cronograma' : 'Avance por debajo del cronograma')], 4) ?>
    <?= fd_kpi('fa-exclamation-triangle', $k['en_riesgo'] ? '#fef2f2' : '#f0fdf4', $k['en_riesgo'] ? '#dc2626' : '#16a34a', 'Proyectos en riesgo', [
        'to' => $k['en_riesgo'], 'suffix' => ' / ' . $k['proyectos'], 'tono' => $k['en_riesgo'] ? 'danger' : 'ok', 'sub' => 'Margen negativo o CPI < 0.90'], 5) ?>
    <?= fd_kpi('fa-gem', '#fdf4ff', '#a21caf', 'Utilidad en contratos', [
        'to' => $k['utilidad_contratos'], 'prefix' => 'S/ ', 'sub' => 'Utilidad declarada en todos los contratos'], 6) ?>
    <?= fd_kpi('fa-briefcase', '#f0f9ff', '#0284c7', 'Cartera contratada', [
        'to' => $k['cartera_contratos'], 'prefix' => 'S/ ', 'sub' => 'Suma de todos los contratos'], 7) ?>
</div>

<div class="fd-grid-2">
    <div class="fd-card span2 fd-rise" style="--d:.1s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-chart-bar" style="color:#7c3aed;"></i> Ingreso contratado vs. costo proyectado</h3>
            <p class="fd-card-sub">Los 8 proyectos de mayor valor · la diferencia entre barras es el margen esperado</p>
        </div></div>
        <div class="fd-chart alto"><canvas id="cIngreso"></canvas><div class="fd-empty"><i class="fas fa-hard-hat"></i>No hay proyectos en ejecución para analizar.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.15s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-tachometer-alt" style="color:#2563eb;"></i> CPI y SPI por proyecto</h3>
            <p class="fd-card-sub">La línea punteada (1.0) es el punto de equilibrio</p>
        </div></div>
        <div class="fd-chart"><canvas id="cIndices"></canvas><div class="fd-empty"><i class="fas fa-tachometer-alt"></i>Sin proyectos con avance para calcular índices.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.2s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-chart-pie" style="color:#c026d3;"></i> Cartera contratada por línea de negocio</h3>
            <p class="fd-card-sub">Monto de todos los contratos</p>
        </div></div>
        <div class="fd-chart"><canvas id="cLineas"></canvas><div class="fd-empty"><i class="fas fa-file-signature"></i>No hay contratos registrados.</div></div>
    </div>

    <div class="fd-card span2 fd-rise" style="--d:.25s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-layer-group" style="color:#7c3aed;"></i> Rentabilidad declarada por línea</h3>
            <p class="fd-card-sub">Utilidad registrada en los contratos de cada línea de negocio</p>
        </div></div>
        <?php if (empty($g['lineas'])): ?><div class="fd-ok-box vacio"><i class="fas fa-file-signature"></i>No hay contratos registrados.</div>
        <?php else: ?><div style="overflow-x:auto;"><table class="fd-table"><thead><tr><th>Línea</th><th class="num">Contratos</th><th class="num">Monto</th><th style="min-width:120px;">Utilidad s/ monto</th></tr></thead><tbody>
            <?php foreach ($g['lineas'] as $l): $pu = $l['monto'] > 0 ? $l['utilidad'] / $l['monto'] * 100 : 0; ?>
            <tr><td><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:<?= fd_h($l['color']) ?>; margin-right:7px;"></span><strong style="color:#1e293b;"><?= fd_h($l['nombre']) ?></strong></td>
                <td class="num"><?= (int)$l['n'] ?></td><td class="num fd-nowrap"><?= fd_money($l['monto']) ?></td>
                <td><div style="display:flex; align-items:center; gap:8px;"><div class="fd-bar" style="flex:1;"><div class="<?= $pu >= 10 ? 'ok' : ($pu > 0 ? 'warn' : 'danger') ?>" data-w="<?= min(100, $pu * 4) ?>"></div></div><strong style="font-size:12px; min-width:40px; text-align:right;"><?= number_format($pu, 1) ?>%</strong></div>
                    <div style="font-size:11px; color:#94a3b8;"><?= fd_money($l['utilidad']) ?></div></td></tr>
            <?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>
</div>

<div class="fd-section"><div><h3>Ranking de rentabilidad</h3><p>Proyectos en ejecución, del menor al mayor margen proyectado: arriba, los que más necesitan tu atención.</p></div></div>
<div class="fd-card fd-rise" style="--d:.1s; padding:8px 14px;">
<?php if (empty($ranking)): ?>
    <div class="fd-ok-box vacio"><i class="fas fa-hard-hat"></i>No hay proyectos en ejecución con presupuesto.</div>
<?php else: ?>
    <div style="overflow-x:auto;"><table class="fd-table">
        <thead><tr><th>Proyecto</th><th class="num">Ingreso</th><th class="num">Costo proyectado</th><th style="min-width:170px;">Margen</th><th>CPI</th><th>SPI</th></tr></thead>
        <tbody>
        <?php foreach ($ranking as $p): $mOk = $p['margen'] >= 0; ?>
            <tr>
                <td><a class="fd-link" style="color:#6d28d9;" href="<?= BASE_URL ?>/tareas?proyecto_id=<?= (int)$p['id'] ?>"><?= fd_h($p['codigo']) ?></a>
                    <div style="font-size:12px; color:#64748b; max-width:260px;"><?= fd_h($p['nombre']) ?></div></td>
                <td class="num"><?= fd_money($p['ingreso']) ?></td>
                <td class="num"><?= fd_money($p['eac']) ?></td>
                <td><div style="display:flex; align-items:center; gap:9px;">
                    <div class="fd-bar" style="flex:1;"><div class="<?= $mOk ? ($p['margen_pct'] >= 10 ? 'ok' : 'warn') : 'danger' ?>" data-w="<?= max(0, min(100, $p['margen_pct'] * 3)) ?>"></div></div>
                    <strong style="font-size:12.5px; color:<?= $mOk ? '#15803d' : '#b91c1c' ?>; min-width:48px; text-align:right;"><?= number_format($p['margen_pct'], 1) ?>%</strong></div>
                    <div style="font-size:11px; color:#94a3b8;"><?= ($mOk ? '' : '− ') . fd_money(abs($p['margen'])) ?></div></td>
                <td><?= $p['cpi'] === null ? fd_badge('neutral', '—') : fd_badge($tonoIdx($p['cpi']), number_format($p['cpi'], 2)) ?></td>
                <td><?= $p['spi'] === null ? fd_badge('neutral', '—') : fd_badge($tonoIdx($p['spi']), number_format($p['spi'], 2)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
</div>

<div class="fd-nota fd-rise" style="--d:.2s; margin-top:18px;">
    <strong><i class="fas fa-info-circle" style="color:#7c3aed;"></i> Cómo leer estos indicadores</strong><br>
    <strong>CPI</strong> (índice de desempeño de costo) = valor ganado ÷ costo real. Mayor o igual a 1.0 significa que cada sol gastado produce al menos un sol de avance.
    <strong>SPI</strong> (índice de desempeño de cronograma) = avance real ÷ avance planificado a la fecha.
    <strong>Costo proyectado al cierre</strong> = costo real ÷ avance físico (con menos de 10 % de avance se usa el presupuesto, porque la estimación aún no es estable).
    <strong>Margen proyectado</strong> = ingreso contratado − costo proyectado.
</div>

</div>

<script>
const D = <?= fd_json($g) ?>;
const noChart = typeof Chart === 'undefined';

/* Ingreso vs costo proyectado */
(function () {
    const s = D.ingreso_vs_costo;
    if (noChart || !s.labels.length) return fdVacio('cIngreso');
    new Chart(document.getElementById('cIngreso'), {
        type: 'bar',
        data: {labels: s.labels, datasets: [
            {label: 'Ingreso contratado', data: s.ingreso, backgroundColor: '#8b5cf6', borderRadius: 8},
            {label: 'Costo proyectado al cierre', data: s.eac, backgroundColor: s.eac.map((c, i) => c > s.ingreso[i] ? '#dc2626' : '#f97316'), borderRadius: 8},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {x: {grid: {display: false}}, y: {beginAtZero: true, ticks: {callback: fdCorto}}},
            plugins: {tooltip: {callbacks: {
                title: it => s.labels[it[0].dataIndex] + ' · ' + s.nombres[it[0].dataIndex],
                label: c => c.dataset.label + ': ' + fdMoney(c.parsed.y),
                footer: it => { const i = it[0].dataIndex, m = s.ingreso[i] - s.eac[i]; return 'Margen: ' + (m < 0 ? '− ' : '') + fdMoney(Math.abs(m)) + ' (' + fdFmt(m / s.ingreso[i] * 100, 1) + '%)'; }
            }}}}
    });
})();

/* CPI y SPI */
(function () {
    const x = D.indices;
    if (noChart || !x.labels.length || (fdSuma(x.cpi) + fdSuma(x.spi)) <= 0) return fdVacio('cIndices');
    new Chart(document.getElementById('cIndices'), {
        data: {labels: x.labels, datasets: [
            {type: 'bar', label: 'CPI (costo)', data: x.cpi, backgroundColor: '#3b82f6', borderRadius: 6},
            {type: 'bar', label: 'SPI (plazo)', data: x.spi, backgroundColor: '#06b6d4', borderRadius: 6},
            {type: 'line', label: 'Equilibrio 1.0', data: x.labels.map(() => 1), borderColor: '#94a3b8', borderDash: [6, 5], borderWidth: 2, pointRadius: 0, fill: false},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {x: {grid: {display: false}}, y: {beginAtZero: true, ticks: {callback: v => fdFmt(v, 1)}}},
            plugins: {tooltip: {callbacks: {
                title: it => x.labels[it[0].dataIndex] + ' · ' + x.nombres[it[0].dataIndex],
                label: c => c.datasetIndex === 2 ? null : c.dataset.label + ': ' + (c.parsed.y === null ? '—' : fdFmt(c.parsed.y, 2))
            }}}}
    });
})();

/* Cartera por línea */
(function () {
    const l = D.lineas, total = fdSuma(l.map(x => x.monto));
    if (noChart || total <= 0) return fdVacio('cLineas');
    new Chart(document.getElementById('cLineas'), {
        type: 'doughnut',
        data: {labels: l.map(x => x.nombre), datasets: [{data: l.map(x => x.monto), backgroundColor: l.map(x => x.color), borderWidth: 3, borderColor: '#fff', hoverOffset: 12}]},
        options: {responsive: true, maintainAspectRatio: false, cutout: '66%', animation: {animateRotate: true, animateScale: true},
            plugins: {centerText: {text: fdMoney(total / 1e6, 1) + ' M', sub: 'cartera'},
                tooltip: {callbacks: {label: c => ` ${c.label}: ${fdMoney(c.parsed)} (${fdFmt(c.parsed / total * 100, 1)}%)`,
                    afterLabel: c => l[c.dataIndex].n + ' contrato(s) · utilidad declarada ' + fdMoney(l[c.dataIndex].utilidad)}}}}
    });
})();
</script>
