<?php
/**
 * Dashboard Fase 3 · Ejecución y operación
 * ¿Estoy abasteciendo bien? Compras, proveedores, almacén y stock.
 */
include __DIR__ . '/_fase_ui.php';

$k = $data['kpis'];
$g = $data['graficos'];
$porAprobar = $data['por_aprobar'];
$porRecepcionar = $data['por_recepcionar'];

$acciones = '';
if (PermisosHelper::tieneAcceso('compras'))   { $acciones .= '<a href="' . BASE_URL . '/compras"><i class="fas fa-shopping-cart"></i> Órdenes de Compra</a>'; }
if (PermisosHelper::tieneAcceso('almacenes')) { $acciones .= '<a href="' . BASE_URL . '/almacen"><i class="fas fa-warehouse"></i> Almacén</a>'; }
if (PermisosHelper::puede('crear', 'compras')) { $acciones .= '<a href="' . BASE_URL . '/compras/crear"><i class="fas fa-plus-circle"></i> Nueva OC</a>'; }

$estadoTxt = ['borrador' => 'Borrador', 'pendiente_aprobacion' => 'Por aprobar', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'recibida' => 'Recibida', 'anulada' => 'Anulada'];
$estadoCol = ['borrador' => '#94a3b8', 'pendiente_aprobacion' => '#f59e0b', 'aprobada' => '#10b981', 'rechazada' => '#ef4444', 'recibida' => '#3b82f6', 'anulada' => '#cbd5e1'];
$estadosData = ['labels' => [], 'cant' => [], 'monto' => [], 'colores' => []];
foreach ($estadoTxt as $e => $txt) {
    if (!empty($g['estados'][$e])) {
        $estadosData['labels'][] = $txt; $estadosData['cant'][] = $g['estados'][$e]['cantidad'];
        $estadosData['monto'][] = $g['estados'][$e]['monto']; $estadosData['colores'][] = $estadoCol[$e];
    }
}
$topAct = ['labels' => [], 'nombres' => [], 'gasto' => [], 'pres' => []];
foreach ($g['top_actividades'] as $t) {
    $topAct['labels'][] = $t['proyecto_codigo'] . ' · ' . $t['codigo']; $topAct['nombres'][] = $t['nombre'];
    $topAct['gasto'][] = round($t['gasto'], 2); $topAct['pres'][] = round($t['costo_estimado'], 2);
}
$jsData = [
    'comprasMes' => $g['compras_mes'], 'estados' => $estadosData, 'almacen' => $g['almacen_meses'], 'topAct' => $topAct,
    'proveedores' => array_map(fn($p) => ['nombre' => $p['nombre'], 'monto' => (float)$p['monto'], 'n' => (int)$p['n']], $g['proveedores']),
];
?>
<div class="fd-wrap">

<?= fd_hero([
    'g1' => '#0f766e', 'g2' => '#10b981', 'icono' => 'fa-truck-loading', 'badge' => 'Fase 3 · Ejecución y operación',
    'titulo' => '¿Estoy abasteciendo bien?',
    'subtitulo' => 'Compras aprobadas, proveedores, recepción en almacén y alertas de stock: todo lo que mueve la obra, en una sola vista.',
    'acciones' => $acciones,
    'cifra_label' => 'Comprado (neto, OC aprobadas)', 'cifra' => $k['comprado'],
    'cifra_sub' => fd_money($k['comprado_mes']) . ' este mes · ' . $k['ordenes_mes'] . ' orden(es)',
    'anillo' => $k['cumplimiento'], 'anillo_label' => 'OC ya recibidas',
]) ?>

<div class="fd-grid-kpi n8">
    <?= fd_kpi('fa-calendar-alt', '#ecfdf5', '#059669', 'Compras del mes', [
        'to' => $k['comprado_mes'], 'prefix' => 'S/ ', 'sub' => $k['ordenes_mes'] . ' orden(es) aprobadas o recibidas'], 0) ?>
    <?= fd_kpi('fa-clock', '#fffbeb', '#d97706', 'OC por aprobar', [
        'to' => $k['pendientes_n'], 'tono' => $k['pendientes_n'] ? 'warn' : '', 'sub' => fd_money($k['pendientes_monto']) . ' en espera'], 0) ?>
    <?= fd_kpi('fa-clipboard-check', '#eff6ff', '#2563eb', 'Por recepcionar', [
        'to' => $k['por_recepcionar'], 'sub' => 'OC aprobadas sin ingresar a almacén'], 1) ?>
    <?= fd_kpi('fa-boxes', '#f0fdfa', '#0d9488', 'Valor del inventario', [
        'to' => $k['valor_inventario'], 'prefix' => 'S/ ', 'sub' => 'Costo promedio ponderado'], 2) ?>
    <?= fd_kpi('fa-exclamation-triangle', $k['criticos'] ? '#fef2f2' : '#f0fdf4', $k['criticos'] ? '#dc2626' : '#16a34a', 'Stock crítico', [
        'to' => $k['criticos'], 'tono' => $k['criticos'] ? 'danger' : 'ok', 'sub' => $k['criticos'] ? 'Materiales bajo su mínimo' : 'Sin alertas de reposición'], 3) ?>
    <?= fd_kpi('fa-dolly', '#fff7ed', '#ea580c', 'Consumo a obra (mes)', [
        'to' => $k['salidas_mes'], 'prefix' => 'S/ ', 'sub' => 'Vales de salida imputados al WBS'], 4) ?>
    <?= fd_kpi($k['sin_actividad'] > 0 ? 'fa-unlink' : 'fa-link', $k['sin_actividad'] > 0 ? '#fef2f2' : '#f0fdf4', $k['sin_actividad'] > 0 ? '#dc2626' : '#16a34a', 'Compras sin actividad', [
        'to' => $k['sin_actividad'], 'prefix' => 'S/ ', 'tono' => $k['sin_actividad'] > 0 ? 'danger' : 'ok',
        'sub' => $k['sin_actividad'] > 0 ? $k['lineas_sin_actividad'] . ' líneas no llegan al WBS' : 'Todo imputado al WBS'], 5) ?>
    <?= fd_kpi('fa-handshake', '#f5f3ff', '#7c3aed', 'Subcontratos activos', [
        'to' => $k['subcontratos_n'], 'sub' => fd_money($k['subcontratos_monto']) . ' contratados'], 6) ?>
</div>

<div class="fd-grid-2">
    <div class="fd-card span2 fd-rise" style="--d:.1s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-chart-bar" style="color:#0d9488;"></i> Compras por mes</h3>
            <p class="fd-card-sub">Monto neto de órdenes aprobadas y recibidas · la línea marca la cantidad de órdenes</p>
        </div></div>
        <div class="fd-chart alto"><canvas id="cComprasMes"></canvas><div class="fd-empty"><i class="fas fa-shopping-cart"></i>Aún no hay compras aprobadas en los últimos 6 meses.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.15s">
        <div class="fd-card-head"><h3 class="fd-card-title"><i class="fas fa-chart-pie" style="color:#ea580c;"></i> Órdenes por estado</h3></div>
        <div class="fd-chart"><canvas id="cEstados"></canvas><div class="fd-empty"><i class="fas fa-file-invoice"></i>No hay órdenes de compra registradas.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.2s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-truck" style="color:#2563eb;"></i> Principales proveedores</h3>
            <p class="fd-card-sub">Por monto neto comprado</p>
        </div></div>
        <div class="fd-chart"><canvas id="cProv"></canvas><div class="fd-empty"><i class="fas fa-truck"></i>Sin compras aprobadas todavía.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.25s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-exchange-alt" style="color:#0d9488;"></i> Movimiento de almacén</h3>
            <p class="fd-card-sub">Ingresos y salidas a obra (6 meses)</p>
        </div></div>
        <div class="fd-chart"><canvas id="cAlmacen"></canvas><div class="fd-empty"><i class="fas fa-warehouse"></i>Aún no hay movimientos de almacén.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.3s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-sitemap" style="color:#7c3aed;"></i> Actividades con más gasto en compras</h3>
            <p class="fd-card-sub">Color según % del presupuesto de la actividad</p>
        </div></div>
        <div class="fd-chart"><canvas id="cTopAct"></canvas><div class="fd-empty"><i class="fas fa-sitemap"></i>Ninguna compra aprobada está imputada a actividades.</div></div>
    </div>
</div>

<div class="fd-section"><div><h3>Para atender hoy</h3><p>Pendientes de aprobación, recepciones por hacer y reposiciones urgentes.</p></div></div>
<div class="fd-grid-2 w">
    <div class="fd-card fd-rise" style="--d:.1s; padding:16px 18px;">
        <h3 class="fd-card-title" style="margin-bottom:8px;"><i class="fas fa-hourglass-half" style="color:#d97706;"></i> OC por aprobar</h3>
        <?php if (empty($porAprobar)): ?><div class="fd-ok-box"><i class="fas fa-check-circle"></i>No hay órdenes esperando aprobación.</div>
        <?php else: ?><div style="overflow-x:auto;"><table class="fd-table"><thead><tr><th>Orden</th><th>Proveedor</th><th class="num">Total</th><th>Espera</th></tr></thead><tbody>
            <?php foreach ($porAprobar as $o): $d = (int)$o['dias']; ?>
            <tr><td><a class="fd-link fd-nowrap" style="color:#ea580c;" href="<?= BASE_URL ?>/compras/ver/<?= (int)$o['id'] ?>"><?= fd_h($o['codigo']) ?></a><div style="font-size:11px; color:#94a3b8;"><?= fd_h($o['proyecto']) ?></div></td>
                <td style="font-size:12.5px; color:#475569;"><?= fd_h($o['proveedor']) ?></td>
                <td class="num" style="font-weight:700;"><?= fd_h($o['moneda']) ?> <?= number_format((float)$o['total'], 2) ?></td>
                <td><?= fd_badge($d > 7 ? 'danger' : ($d > 3 ? 'warn' : 'neutral'), $d . ' d') ?></td></tr>
            <?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>

    <div class="fd-card fd-rise" style="--d:.15s; padding:16px 18px;">
        <h3 class="fd-card-title" style="margin-bottom:8px;"><i class="fas fa-clipboard-check" style="color:#2563eb;"></i> Por recepcionar en almacén</h3>
        <?php if (empty($porRecepcionar)): ?><div class="fd-ok-box"><i class="fas fa-check-circle"></i>No hay órdenes aprobadas pendientes de recepción.</div>
        <?php else: ?><div style="overflow-x:auto;"><table class="fd-table"><thead><tr><th>Orden</th><th>Proveedor</th><th>Entrega</th><th class="num"></th></tr></thead><tbody>
            <?php foreach ($porRecepcionar as $o): $atras = !empty($o['fecha_entrega']) && $o['fecha_entrega'] < date('Y-m-d'); ?>
            <tr><td><a class="fd-link fd-nowrap" style="color:#ea580c;" href="<?= BASE_URL ?>/compras/ver/<?= (int)$o['id'] ?>"><?= fd_h($o['codigo']) ?></a><div style="font-size:11px; color:#94a3b8;"><?= fd_h($o['proyecto_codigo']) ?></div></td>
                <td style="font-size:12.5px; color:#475569;"><?= fd_h($o['proveedor']) ?></td>
                <td><?= $o['fecha_entrega'] ? fd_badge($atras ? 'danger' : 'neutral', date('d/m/Y', strtotime($o['fecha_entrega'])) . ($atras ? ' · atrasada' : '')) : '—' ?></td>
                <td class="num"><a class="fd-link" style="color:#0d9488; font-size:12px;" href="<?= BASE_URL ?>/almacen/ingreso?oc_id=<?= (int)$o['id'] ?>">Recepcionar <i class="fas fa-chevron-right"></i></a></td></tr>
            <?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>

    <div class="fd-card fd-rise" style="--d:.2s; padding:16px 18px; grid-column:1 / -1;">
        <h3 class="fd-card-title" style="margin-bottom:10px;"><i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i> Reposición urgente (stock crítico)</h3>
        <?php if (empty($g['critico'])): ?><div class="fd-ok-box"><i class="fas fa-check-circle"></i>Ningún material está por debajo de su stock mínimo.</div>
        <?php else: ?><div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px;">
            <?php foreach ($g['critico'] as $m):
                $stock = (float)$m['stock_actual']; $min = (float)$m['stock_minimo']; $pct = $min > 0 ? min(100, $stock / $min * 100) : 0; ?>
            <div style="border:1px solid #f1f5f9; border-radius:12px; padding:12px 14px;">
                <div style="display:flex; justify-content:space-between; gap:8px; align-items:center;">
                    <strong style="font-size:13px; color:#1e293b;"><?= fd_h($m['nombre']) ?></strong>
                    <?= fd_badge($stock <= 0 ? 'danger' : 'warn', $stock <= 0 ? 'Agotado' : 'Crítico') ?>
                </div>
                <div class="fd-bar" style="margin:9px 0 6px;"><div class="<?= $stock <= 0 ? 'danger' : 'warn' ?>" data-w="<?= round($pct) ?>"></div></div>
                <div style="font-size:11.5px; color:#64748b;"><strong><?= number_format($stock, 2) ?></strong> de <?= number_format($min, 2) ?> <?= fd_h($m['unidad_medida']) ?> mínimos</div>
            </div>
            <?php endforeach; ?></div><?php endif; ?>
    </div>
</div>

</div>

<script>
const D = <?= fd_json($jsData) ?>;
const noChart = typeof Chart === 'undefined';

/* Compras por mes */
(function () {
    const c = D.comprasMes;
    if (noChart || fdSuma(c.monto) <= 0) return fdVacio('cComprasMes');
    new Chart(document.getElementById('cComprasMes'), {
        data: {labels: c.labels, datasets: [
            {type: 'bar', label: 'Monto neto', data: c.monto, backgroundColor: '#14b8a6', borderRadius: 8, yAxisID: 'y'},
            {type: 'line', label: 'N° de órdenes', data: c.ordenes, borderColor: '#f97316', backgroundColor: '#f97316', tension: .35, pointRadius: 5, pointHoverRadius: 7, yAxisID: 'y1'},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {y: {beginAtZero: true, ticks: {callback: fdCorto}},
                     y1: {position: 'right', beginAtZero: true, grid: {display: false}, ticks: {precision: 0}}, x: {grid: {display: false}}},
            plugins: {tooltip: {callbacks: {label: x => x.dataset.label + ': ' + (x.datasetIndex === 0 ? fdMoney(x.parsed.y, 2) : x.parsed.y)}}}}
    });
})();

/* Órdenes por estado */
(function () {
    const e = D.estados;
    if (noChart || !e.labels.length) return fdVacio('cEstados');
    new Chart(document.getElementById('cEstados'), {
        type: 'doughnut',
        data: {labels: e.labels, datasets: [{data: e.cant, backgroundColor: e.colores, borderWidth: 3, borderColor: '#fff', hoverOffset: 12}]},
        options: {responsive: true, maintainAspectRatio: false, cutout: '64%', animation: {animateRotate: true, animateScale: true},
            plugins: {centerText: {text: String(fdSuma(e.cant)), sub: 'órdenes'},
                tooltip: {callbacks: {label: c => ` ${c.label}: ${c.parsed} orden(es) · ${fdMoney(e.monto[c.dataIndex])}`}}}}
    });
})();

/* Proveedores */
(function () {
    const p = D.proveedores;
    if (noChart || !p.length) return fdVacio('cProv');
    new Chart(document.getElementById('cProv'), {
        type: 'bar',
        data: {labels: p.map(x => x.nombre.length > 18 ? x.nombre.slice(0, 17) + '…' : x.nombre),
               datasets: [{label: 'Monto neto', data: p.map(x => x.monto), backgroundColor: ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#dbeafe'], borderRadius: 8}]},
        options: {indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false},
            tooltip: {callbacks: {title: it => p[it[0].dataIndex].nombre, label: c => fdMoney(c.parsed.x, 2) + ' · ' + p[c.dataIndex].n + ' orden(es)'}}},
            scales: {x: {beginAtZero: true, ticks: {callback: fdCorto}}, y: {grid: {display: false}}}}
    });
})();

/* Almacén */
(function () {
    const a = D.almacen;
    if (noChart || fdSuma(a.ingreso_monto) + fdSuma(a.salida_monto) <= 0) return fdVacio('cAlmacen');
    new Chart(document.getElementById('cAlmacen'), {
        type: 'bar',
        data: {labels: a.labels, datasets: [
            {label: 'Ingresos', data: a.ingreso_monto, backgroundColor: '#14b8a6', borderRadius: 6},
            {label: 'Salidas a obra', data: a.salida_monto, backgroundColor: '#f97316', borderRadius: 6},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {y: {beginAtZero: true, ticks: {callback: fdCorto}}, x: {grid: {display: false}}},
            plugins: {tooltip: {callbacks: {label: c => c.dataset.label + ': ' + fdMoney(c.parsed.y, 2)}}}}
    });
})();

/* Top actividades */
(function () {
    const t = D.topAct;
    if (noChart || !t.labels.length) return fdVacio('cTopAct');
    const col = t.gasto.map((g, i) => { const p = t.pres[i]; if (p <= 0) return '#3b82f6'; const r = g / p * 100; return r > 100 ? '#dc2626' : (r >= 80 ? '#f59e0b' : '#16a34a'); });
    new Chart(document.getElementById('cTopAct'), {
        type: 'bar',
        data: {labels: t.labels, datasets: [{label: 'Gasto en compras', data: t.gasto, backgroundColor: col, borderRadius: 8}]},
        options: {indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}, tooltip: {callbacks: {
            title: it => t.labels[it[0].dataIndex] + ' · ' + t.nombres[it[0].dataIndex],
            label: c => 'Gasto: ' + fdMoney(c.parsed.x, 2),
            afterLabel: c => t.pres[c.dataIndex] > 0 ? ['Presupuesto: ' + fdMoney(t.pres[c.dataIndex], 2), 'Consumo: ' + fdFmt(c.parsed.x / t.pres[c.dataIndex] * 100, 1) + '%'] : 'Sin presupuesto asignado'}}},
            scales: {x: {beginAtZero: true, ticks: {callback: fdCorto}}, y: {grid: {display: false}}}}
    });
})();
</script>
