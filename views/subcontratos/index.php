<?php
/**
 * Vista: Subcontratos (Fase 3) — contratado, valorizado, por valorizar, por pagar y fondo de garantía.
 */
include __DIR__ . '/../dashboard/_fase_ui.php';

$migrado = (bool)($data['migrado'] ?? false);
$filtros = $data['filtros'];
$lista = $data['subcontratos'];
$res = $data['resumen'];
$proyectos = $data['proyectos'];
$perm = $data['permisos'];
$k = $res['kpis'] ?? null;

$estadoUI = [
    'activo' => ['ok', 'Activo'], 'finalizado' => ['info', 'Finalizado'], 'suspendido' => ['warn', 'Suspendido'], 'anulado' => ['neutral', 'Anulado'],
];
$pctVal = ($k && $k['contratado'] > 0) ? round($k['valorizado'] / $k['contratado'] * 100, 1) : 0.0;
$acciones = '';
if (PermisosHelper::tieneAcceso('tareas')) { $acciones .= '<a href="' . BASE_URL . '/presupuesto' . ($filtros['proyecto_id'] ? '?proyecto_id=' . (int)$filtros['proyecto_id'] : '') . '"><i class="fas fa-balance-scale"></i> Control presupuestal</a>'; }
if (PermisosHelper::tieneAcceso('compras')) { $acciones .= '<a href="' . BASE_URL . '/compras"><i class="fas fa-file-invoice-dollar"></i> Órdenes de compra</a>'; }
?>
<style>
.sc-fil { display:flex; gap:12px; flex-wrap:wrap; align-items:center; background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:12px 16px; margin-bottom:18px; }
.sc-fil select, .sc-fil input[type=text] { padding:9px 13px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; color:#1e293b; background:#fff; outline:none; }
.sc-fil input[type=text] { min-width:230px; }
.sc-btn { color:#fff; border:none; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; background:linear-gradient(135deg,#14b8a6,#0f766e); box-shadow:0 4px 12px rgba(15,118,110,.28); text-decoration:none; display:inline-flex; align-items:center; gap:7px; }
.sc-btn.sec { background:#fff; color:#475569; border:1px solid #cbd5e1; box-shadow:none; }
.sc-g21 { display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1fr); gap:16px; margin-bottom:22px; }
@media (max-width:1000px) { .sc-g21 { grid-template-columns:1fr; } }
.sc-cod { font-family:monospace; font-weight:800; color:#0f766e; text-decoration:none; }
.sc-cod:hover { text-decoration:underline; }
.sc-act { width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-size:12px; border:1px solid #cbd5e1; background:#f1f5f9; color:#334155; }
.sc-act.teal { background:#f0fdfa; border-color:#99f6e4; color:#0f766e; }
.sc-prog { min-width:150px; }
.sc-aviso { background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:13px; }
</style>

<div class="fd-wrap" style="padding:16px 24px 40px;">

<?php if (!$migrado): ?>
    <div class="sc-aviso"><i class="fas fa-exclamation-triangle"></i> <strong>Falta aplicar la migración de subcontratos y valorizaciones.</strong> Ejecuta <code>database/upgrade.php</code> desde la consola y recarga esta página.</div>
<?php else: ?>

<?= fd_hero([
    'g1' => '#0f766e', 'g2' => '#10b981', 'icono' => 'fa-handshake', 'badge' => 'Fase 3 · Subcontratos',
    'titulo' => 'Subcontratos y valorizaciones',
    'subtitulo' => 'Contratos con terceros por actividad del WBS: se valorizan por avance, amortizan el adelanto, retienen la garantía y suman al costo real solo al aprobarse.',
    'acciones' => $acciones,
    'cifra_label' => 'Contratado vigente', 'cifra' => $k['contratado'],
    'cifra_sub' => $k['activos'] . ' activos · ' . fd_money($k['por_valorizar']) . ' por valorizar',
    'anillo' => $pctVal, 'anillo_label' => 'Valorizado',
]) ?>

<div class="fd-grid-kpi n8">
    <?= fd_kpi('fa-file-signature', '#ecfdf5', '#059669', 'Contratos activos', ['to' => $k['activos'], 'sub' => $k['contratos'] . ' contratos en total' . ($k['borradores'] ? ' · ' . $k['borradores'] . ' valorización(es) en borrador' : '')], 0) ?>
    <?= fd_kpi('fa-handshake', '#f0fdfa', '#0f766e', 'Contratado', ['to' => $k['contratado'], 'prefix' => 'S/ ', 'sub' => 'Sin IGV · excluye anulados'], 1) ?>
    <?= fd_kpi('fa-check-double', '#eff6ff', '#2563eb', 'Valorizado (costo real)', ['to' => $k['valorizado'], 'prefix' => 'S/ ', 'bar' => $pctVal, 'barClass' => 'info', 'sub' => $pctVal . '% del contratado'], 2) ?>
    <?= fd_kpi('fa-hourglass-half', '#fffbeb', '#d97706', 'Por valorizar', ['to' => $k['por_valorizar'], 'prefix' => 'S/ ', 'sub' => 'Compromiso en el control presupuestal'], 3) ?>
    <?= fd_kpi('fa-file-invoice-dollar', '#fff7ed', '#ea580c', 'Por pagar', ['to' => $k['por_pagar'], 'prefix' => 'S/ ', 'tono' => $k['por_pagar'] > 0 ? 'warn' : '', 'sub' => 'Valorizaciones aprobadas sin pagar (neto)'], 4) ?>
    <?= fd_kpi('fa-money-check-alt', '#f0fdf4', '#16a34a', 'Pagado', ['to' => $k['pagado'], 'prefix' => 'S/ ', 'sub' => 'Neto pagado a subcontratistas'], 5) ?>
    <?= fd_kpi('fa-lock', '#f5f3ff', '#7c3aed', 'Fondo de garantía', ['to' => $k['retenido'], 'prefix' => 'S/ ', 'sub' => 'Retenido en valorizaciones aprobadas'], 6) ?>
    <?= fd_kpi('fa-coins', '#fef2f2', '#dc2626', 'Adelantos por amortizar', ['to' => $k['adelanto_pendiente'], 'prefix' => 'S/ ', 'sub' => 'Entregados y aún no descontados'], 7) ?>
</div>

<div class="sc-g21">
    <div class="fd-card fd-rise" style="--d:.1s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-chart-bar" style="color:#0f766e;"></i> Contratado vs. valorizado por subcontratista</h3>
            <p class="fd-card-sub">Los ocho de mayor monto contratado</p>
        </div></div>
        <div class="fd-chart"><canvas id="cProv"></canvas><div class="fd-empty"><i class="fas fa-handshake"></i>Aún no hay subcontratos registrados.</div></div>
    </div>
    <div class="fd-card fd-rise" style="--d:.15s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-chart-pie" style="color:#7c3aed;"></i> Avance económico</h3>
            <p class="fd-card-sub">Valorizado y saldo por valorizar</p>
        </div></div>
        <div class="fd-chart"><canvas id="cAvance"></canvas><div class="fd-empty"><i class="fas fa-chart-pie"></i>Sin montos contratados.</div></div>
    </div>
</div>

<form method="GET" action="<?= BASE_URL ?>/subcontratos" class="sc-fil fd-rise" style="--d:.2s">
    <select name="proyecto_id" onchange="this.form.submit()">
        <option value="">Todos los proyectos</option>
        <?php foreach ($proyectos as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= (int)$filtros['proyecto_id'] === (int)$p['id'] ? 'selected' : '' ?>>[<?= fd_h($p['codigo']) ?>] <?= fd_h($p['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="estado" onchange="this.form.submit()">
        <option value="">Todos los estados</option>
        <?php foreach ($estadoUI as $e => $u): ?><option value="<?= $e ?>" <?= $filtros['estado'] === $e ? 'selected' : '' ?>><?= $u[1] ?></option><?php endforeach; ?>
    </select>
    <input type="text" name="q" value="<?= fd_h($filtros['q']) ?>" placeholder="Buscar código, servicio o subcontratista…">
    <button type="submit" class="sc-btn sec"><i class="fas fa-search"></i> Buscar</button>
    <span style="flex:1;"></span>
    <?php if ($perm['crear']): ?><a class="sc-btn" href="<?= BASE_URL ?>/subcontratos/crear<?= $filtros['proyecto_id'] ? '?proyecto_id=' . (int)$filtros['proyecto_id'] : '' ?>"><i class="fas fa-plus"></i> Nuevo subcontrato</a><?php endif; ?>
</form>

<div class="fd-card fd-rise" style="--d:.25s; padding:6px 8px;">
    <div style="overflow-x:auto;">
        <table class="fd-table">
            <thead><tr><th>Código</th><th>Subcontratista</th><th>Servicio · actividad</th><th class="num">Contratado</th><th class="sc-prog">Valorizado</th><th class="num">Por pagar</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($lista)): ?>
                <tr><td colspan="8"><div class="fd-ok-box vacio"><i class="fas fa-handshake"></i>No hay subcontratos con esos filtros.<?= $perm['crear'] ? ' Registra el primero con «Nuevo subcontrato».' : '' ?></div></td></tr>
            <?php endif; ?>
            <?php foreach ($lista as $s): [$cl, $et] = $estadoUI[$s['estado']] ?? ['neutral', $s['estado']]; ?>
                <tr>
                    <td><a class="sc-cod" href="<?= BASE_URL ?>/subcontratos/ver/<?= (int)$s['id'] ?>"><?= fd_h($s['codigo']) ?></a>
                        <div style="font-size:11px; color:#94a3b8;"><?= fd_h($s['proyecto_codigo']) ?></div></td>
                    <td><strong style="color:#1e293b;"><?= fd_h($s['proveedor']) ?></strong><div style="font-size:11px; color:#94a3b8;">RUC <?= fd_h($s['proveedor_ruc']) ?></div></td>
                    <td><div style="font-weight:600; color:#334155;"><?= fd_h($s['servicio']) ?></div>
                        <div style="font-size:11.5px; color:#64748b;"><?= $s['tarea_codigo'] ? fd_h($s['tarea_codigo'] . ' · ' . $s['tarea_nombre']) : '<span style="color:#dc2626;">Sin actividad</span>' ?></div></td>
                    <td class="num fd-nowrap"><?= fd_money($s['monto'], 2) ?></td>
                    <td class="sc-prog">
                        <div style="display:flex; justify-content:space-between; font-size:11.5px; font-weight:700; color:#334155; margin-bottom:3px;"><span><?= fd_money($s['valorizado'], 2) ?></span><span><?= $s['avance_pct'] ?>%</span></div>
                        <div class="fd-bar"><div class="<?= $s['avance_pct'] >= 100 ? 'ok' : 'info' ?>" data-w="<?= min(100, $s['avance_pct']) ?>"></div></div>
                    </td>
                    <td class="num fd-nowrap"><?= $s['por_pagar'] > 0 ? '<strong style="color:#c2410c;">' . fd_money($s['por_pagar'], 2) . '</strong>' : '<span style="color:#cbd5e1;">—</span>' ?></td>
                    <td><?= fd_badge($cl, $et) ?><?= $s['borradores'] ? '<div style="margin-top:3px;">' . fd_badge('warn', 'Borrador') . '</div>' : '' ?></td>
                    <td class="fd-nowrap" style="text-align:right;">
                        <a class="sc-act teal" href="<?= BASE_URL ?>/subcontratos/ver/<?= (int)$s['id'] ?>" title="Ver y valorizar"><i class="fas fa-eye"></i></a>
                        <?php if ($perm['editar'] && in_array($s['estado'], ['activo', 'suspendido'], true)): ?><a class="sc-act" href="<?= BASE_URL ?>/subcontratos/editar/<?= (int)$s['id'] ?>" title="Editar"><i class="fas fa-pencil-alt"></i></a><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const SC_PROV = <?= fd_json($res['por_proveedor']) ?>;
const SC_K = <?= fd_json($k) ?>;
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    const cut = s => String(s).length > 22 ? String(s).slice(0, 21) + '…' : String(s);
    if (!SC_PROV.length) { fdVacio('cProv'); }
    else {
        new Chart(document.getElementById('cProv'), {
            type: 'bar',
            data: {labels: SC_PROV.map(p => cut(p.proveedor)), datasets: [
                {label: 'Contratado', data: SC_PROV.map(p => p.contratado), backgroundColor: '#99f6e4', borderRadius: 6},
                {label: 'Valorizado', data: SC_PROV.map(p => p.valorizado), backgroundColor: '#0f766e', borderRadius: 6}]},
            options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
                scales: {x: {grid: {display: false}}, y: {beginAtZero: true, ticks: {callback: fdCorto}}},
                plugins: {tooltip: {callbacks: {title: it => SC_PROV[it[0].dataIndex].proveedor, label: c => c.dataset.label + ': ' + fdMoney(c.parsed.y, 2)}}}}
        });
    }
    const vals = [SC_K.valorizado, SC_K.por_valorizar];
    if (fdSuma(vals) <= 0) { fdVacio('cAvance'); return; }
    const pct = SC_K.contratado > 0 ? (SC_K.valorizado / SC_K.contratado * 100) : 0;
    new Chart(document.getElementById('cAvance'), {
        type: 'doughnut',
        data: {labels: ['Valorizado', 'Por valorizar'], datasets: [{data: vals, backgroundColor: ['#0f766e', '#fcd34d'], borderWidth: 3, borderColor: '#fff', hoverOffset: 10}]},
        options: {responsive: true, maintainAspectRatio: false, cutout: '68%', animation: {animateRotate: true, animateScale: true, duration: 1600},
            plugins: {centerText: {text: pct.toFixed(1) + '%', sub: 'valorizado'}, tooltip: {callbacks: {label: c => c.label + ': ' + fdMoney(c.parsed, 2)}}}}
    });
});
</script>
<?php endif; ?>
</div>
