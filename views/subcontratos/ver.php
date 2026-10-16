<?php
/**
 * Vista: Ficha del Subcontrato (Fase 3) — contrato, valorizaciones y su cálculo.
 */
include __DIR__ . '/../dashboard/_fase_ui.php';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$s = $data['sub'];
$vals = $data['valorizaciones'];
$reactivar = $data['reactivar'];
$puedeAutorizar = (bool)$data['puedeAutorizar'];
$perm = $data['permisos'];
$sid = (int)$s['id'];

$estadoUI = ['activo' => ['ok', 'Activo'], 'finalizado' => ['info', 'Finalizado'], 'suspendido' => ['warn', 'Suspendido'], 'anulado' => ['neutral', 'Anulado']];
$valUI = ['borrador' => ['warn', 'Borrador'], 'aprobada' => ['info', 'Aprobada'], 'pagada' => ['ok', 'Pagada'], 'anulada' => ['neutral', 'Anulada']];
[$clEst, $etEst] = $estadoUI[$s['estado']] ?? ['neutral', $s['estado']];

// Contexto de la próxima valorización: lo ya aprobado o pagado
$vig = array_values(array_filter($vals, fn($v) => $v['estado'] !== 'anulada'));
$ultimaVigenteId = $vig ? $vig[count($vig) - 1]['id'] : 0;
$firmes = array_values(array_filter($vals, fn($v) => in_array($v['estado'], ['aprobada', 'pagada'], true)));
$prev = $firmes ? $firmes[count($firmes) - 1] : null;
$prevCtx = ['pct' => $prev ? $prev['avance_acumulado_pct'] : 0, 'brutoAcum' => $prev ? $prev['bruto_acumulado'] : 0,
            'amortAcum' => array_sum(array_column($firmes, 'amortizacion_adelanto')), 'hasta' => $prev['periodo_hasta'] ?? null];
$hayBorrador = (bool)array_filter($vals, fn($v) => $v['estado'] === 'borrador');
$editable = in_array($s['estado'], ['activo', 'suspendido'], true);
$conExcesoReact = $reactivar && $reactivar['hay_exceso'] && $reactivar['modo'] !== 'informar';
$bloqueaReact = $conExcesoReact && $reactivar['modo'] === 'bloquear';
$puedeAnularContrato = $perm['eliminar'] && in_array($s['estado'], ['activo', 'suspendido'], true) && $s['n_val'] === 0;

$tot = ['bruto' => 0, 'amort' => 0, 'ret' => 0, 'igv' => 0, 'neto' => 0];
foreach ($firmes as $v) { $tot['bruto'] += $v['monto_bruto']; $tot['amort'] += $v['amortizacion_adelanto']; $tot['ret'] += $v['retencion']; $tot['igv'] += $v['igv']; $tot['neto'] += $v['neto_pagar']; }
$pctAdel = $s['adelanto_monto'] > 0 ? round($s['amortizado'] / $s['adelanto_monto'] * 100, 1) : 0;
$grafVals = array_map(fn($v) => ['n' => '#' . $v['numero'], 'bruto' => $v['monto_bruto'], 'acum' => $v['avance_acumulado_pct'], 'estado' => $v['estado']], $vig);

function sc_fecha($f) { return $f ? date('d/m/Y', strtotime($f)) : '—'; }
?>
<style>
.sv-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:18px 22px; margin-bottom:20px; }
.sv-btn { padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; border:1px solid #cbd5e1; background:#fff; color:#334155; }
.sv-btn.teal { background:linear-gradient(135deg,#14b8a6,#0f766e); border-color:transparent; color:#fff; box-shadow:0 4px 12px rgba(15,118,110,.25); }
.sv-btn.azul { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
.sv-btn.rojo { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
.sv-btn.ambar { background:#fffbeb; border-color:#fde68a; color:#b45309; }
.sv-btn.verde { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.sv-btn.mini { padding:5px 10px; font-size:11.5px; }
.sv-g21 { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.3fr); gap:16px; margin-bottom:20px; }
@media (max-width:1000px) { .sv-g21 { grid-template-columns:1fr; } }
.sv-dl { display:grid; grid-template-columns:130px 1fr; gap:9px 12px; font-size:13px; margin:0; }
.sv-dl dt { color:#64748b; font-weight:600; } .sv-dl dd { margin:0; color:#1e293b; font-weight:600; }
.sv-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.sv-tbl th { text-align:left; padding:9px 10px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#64748b; background:#f8fafc; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.sv-tbl td { padding:11px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.sv-tbl .num { text-align:right; white-space:nowrap; }
.sv-tbl tr.anulada td { opacity:.5; text-decoration:line-through; }
.sv-tbl tr.anulada td:last-child, .sv-tbl tr.anulada td:nth-last-child(2) { text-decoration:none; }
.sv-tbl tfoot td { font-weight:800; background:#f0fdfa; border-top:2px solid #99f6e4; }
.sv-ov { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.sv-md { background:#fff; border-radius:20px; width:95%; max-width:560px; box-shadow:0 24px 48px rgba(0,0,0,.25); overflow:hidden; max-height:92vh; display:flex; flex-direction:column; animation:svIn .3s ease-out; }
@keyframes svIn { from { opacity:0; transform:translateY(20px) scale(.98); } to { opacity:1; transform:none; } }
.sv-md-h { background:linear-gradient(120deg,#0f766e,#10b981); color:#fff; padding:16px 22px; display:flex; justify-content:space-between; align-items:center; }
.sv-md-h.rojo { background:linear-gradient(120deg,#d97706,#dc2626); }
.sv-md-b { padding:20px 22px; overflow-y:auto; }
.sv-f label { display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px; }
.sv-f input, .sv-f textarea { width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box; font-family:inherit; }
.sv-calc { margin-top:14px; border:1px solid #99f6e4; background:#f0fdfa; border-radius:12px; padding:12px 14px; font-size:12.5px; }
.sv-calc div { display:flex; justify-content:space-between; padding:3px 0; color:#334155; }
.sv-calc div.t { border-top:1px dashed #5eead4; margin-top:5px; padding-top:7px; font-size:14px; font-weight:800; color:#0f766e; }
.sv-calc div.n { color:#b91c1c; }
</style>

<div class="fd-wrap" style="padding:16px 24px 40px;">

<div class="sv-card fd-rise" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
    <div style="display:flex; align-items:center; gap:14px; min-width:0;">
        <a href="<?= BASE_URL ?>/subcontratos<?= '?proyecto_id=' . (int)$s['proyecto_id'] ?>" class="sv-btn"><i class="fas fa-arrow-left"></i> Listado</a>
        <div style="min-width:0;">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span style="font-family:monospace; font-weight:900; font-size:20px; color:#0f766e;"><?= fd_h($s['codigo']) ?></span> <?= fd_badge($clEst, $etEst) ?>
            </div>
            <div style="font-size:13px; color:#475569; margin-top:2px;"><?= fd_h($s['servicio']) ?></div>
        </div>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <?php if ($perm['editar'] && $editable): ?><a class="sv-btn azul" href="<?= BASE_URL ?>/subcontratos/editar/<?= $sid ?>"><i class="fas fa-edit"></i> Editar</a><?php endif; ?>
        <?php if ($perm['editar'] && $s['estado'] === 'activo'): ?>
            <form method="POST" action="<?= BASE_URL ?>/subcontratos/estado/<?= $sid ?>" style="margin:0;" onsubmit="return confirm('¿Suspender el subcontrato? Su saldo por valorizar dejará de comprometer presupuesto.')">
                <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>"><input type="hidden" name="nuevo_estado" value="suspendido">
                <button class="sv-btn ambar" type="submit"><i class="fas fa-pause-circle"></i> Suspender</button></form>
            <form method="POST" action="<?= BASE_URL ?>/subcontratos/estado/<?= $sid ?>" style="margin:0;" onsubmit="return confirm('¿Finalizar el subcontrato? Lo que no se valorizó se libera del presupuesto y ya no podrás valorizar.')">
                <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>"><input type="hidden" name="nuevo_estado" value="finalizado">
                <button class="sv-btn verde" type="submit"><i class="fas fa-flag-checkered"></i> Finalizar</button></form>
        <?php endif; ?>
        <?php if ($perm['editar'] && in_array($s['estado'], ['suspendido', 'finalizado'], true)): ?>
            <?php if ($conExcesoReact): ?>
                <button type="button" class="sv-btn teal" onclick="abrirModal('mdReactivar')"><i class="fas fa-play-circle"></i> <?= $s['estado'] === 'finalizado' ? 'Reabrir' : 'Reactivar' ?> con autorización</button>
            <?php else: ?>
                <form method="POST" action="<?= BASE_URL ?>/subcontratos/estado/<?= $sid ?>" style="margin:0;" onsubmit="return confirm('¿Volver a activar el subcontrato? Su saldo por valorizar volverá a comprometer presupuesto.')">
                    <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>"><input type="hidden" name="nuevo_estado" value="activo">
                    <button class="sv-btn teal" type="submit"><i class="fas fa-play-circle"></i> <?= $s['estado'] === 'finalizado' ? 'Reabrir' : 'Reactivar' ?></button></form>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($puedeAnularContrato): ?>
            <form method="POST" action="<?= BASE_URL ?>/subcontratos/estado/<?= $sid ?>" style="margin:0;" onsubmit="return confirm('¿Anular este subcontrato? No tiene valorizaciones y dejará de comprometer presupuesto.')">
                <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>"><input type="hidden" name="nuevo_estado" value="anulado">
                <button class="sv-btn rojo" type="submit"><i class="fas fa-ban"></i> Anular</button></form>
        <?php endif; ?>
    </div>
</div>

<?php if ($reactivar && $conExcesoReact):
    $impEval = $reactivar; $impTitulo = 'Control presupuestal al ' . ($s['estado'] === 'finalizado' ? 'reabrir' : 'reactivar') . ' este contrato'; $impSub = 'El saldo por valorizar vuelve a comprometer presupuesto';
    include __DIR__ . '/../presupuesto/_impacto.php';
endif; ?>

<div class="fd-grid-kpi n6">
    <?= fd_kpi('fa-handshake', '#f0fdfa', '#0f766e', 'Contratado', ['to' => $s['monto'], 'prefix' => 'S/ ', 'sub' => 'Sin IGV · retención ' . rtrim(rtrim(number_format($s['retencion_pct'], 2), '0'), '.') . '%'], 0) ?>
    <?= fd_kpi('fa-check-double', '#eff6ff', '#2563eb', 'Valorizado (costo real)', ['to' => $s['valorizado'], 'prefix' => 'S/ ', 'bar' => $s['avance_pct'], 'barClass' => $s['avance_pct'] >= 100 ? 'ok' : 'info', 'sub' => $s['avance_pct'] . '% del contrato'], 1) ?>
    <?= fd_kpi('fa-hourglass-half', '#fffbeb', '#d97706', 'Por valorizar', ['to' => $s['por_valorizar'], 'prefix' => 'S/ ', 'sub' => $s['estado'] === 'activo' ? 'Compromiso vigente en el presupuesto' : 'No compromete presupuesto (' . $etEst . ')'], 2) ?>
    <?= fd_kpi('fa-coins', '#fef2f2', '#dc2626', 'Adelanto', ['to' => $s['adelanto_monto'], 'prefix' => 'S/ ', 'bar' => $pctAdel, 'barClass' => 'warn', 'sub' => 'Amortizado ' . fd_money($s['amortizado'], 2) . ' (' . $pctAdel . '%)'], 3) ?>
    <?= fd_kpi('fa-lock', '#f5f3ff', '#7c3aed', 'Fondo de garantía', ['to' => $s['retenido'], 'prefix' => 'S/ ', 'sub' => 'Retenido en valorizaciones aprobadas'], 4) ?>
    <?= fd_kpi('fa-file-invoice-dollar', '#fff7ed', '#ea580c', 'Por pagar', ['to' => $s['por_pagar'], 'prefix' => 'S/ ', 'tono' => $s['por_pagar'] > 0 ? 'warn' : '', 'sub' => 'Pagado: ' . fd_money($s['pagado'], 2)], 5) ?>
</div>

<div class="sv-g21">
    <div class="sv-card fd-rise" style="--d:.1s; margin:0;">
        <h3 class="fd-card-title" style="margin-bottom:14px;"><i class="fas fa-file-contract" style="color:#0f766e;"></i> Datos del contrato</h3>
        <dl class="sv-dl">
            <dt>Subcontratista</dt><dd><?= fd_h($s['proveedor']) ?> <span style="color:#94a3b8; font-weight:500;">· RUC <?= fd_h($s['proveedor_ruc']) ?></span></dd>
            <dt>Proyecto</dt><dd><?= fd_h($s['proyecto_codigo'] . ' · ' . $s['proyecto_nombre']) ?></dd>
            <dt>Actividad WBS</dt><dd><?= $s['tarea_codigo'] ? '<a class="fd-link" style="color:#0f766e;" href="' . BASE_URL . '/tareas?proyecto_id=' . (int)$s['proyecto_id'] . '">' . fd_h($s['tarea_codigo'] . ' · ' . $s['tarea_nombre']) . '</a>' : '<span style="color:#dc2626;">Sin actividad</span>' ?></dd>
            <dt>Vigencia</dt><dd><?= sc_fecha($s['fecha_inicio']) ?> → <?= sc_fecha($s['fecha_fin']) ?></dd>
            <dt>Condiciones</dt><dd>Adelanto <?= fd_money($s['adelanto_monto'], 2) ?> · retención <?= rtrim(rtrim(number_format($s['retencion_pct'], 2), '0'), '.') ?>%</dd>
            <?php if (!empty($s['observaciones'])): ?><dt>Observaciones</dt><dd style="font-weight:500; color:#475569;"><?= nl2br(fd_h($s['observaciones'])) ?></dd><?php endif; ?>
        </dl>
        <div class="fd-nota" style="margin-top:16px;">
            <strong>Cómo se calcula cada valorización</strong><br>
            Valorizado = monto × avance acumulado − lo ya valorizado · Amortización = adelanto × valorizado ÷ monto ·
            Facturable = valorizado − amortización · IGV 18 % sobre lo facturable · Retención = % × valorizado ·
            <strong>Neto = facturable + IGV − retención</strong>.
        </div>
    </div>
    <div class="fd-card fd-rise" style="--d:.15s;">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-chart-line" style="color:#2563eb;"></i> Avance por valorización</h3>
            <p class="fd-card-sub">Monto valorizado de cada periodo y avance acumulado</p>
        </div></div>
        <div class="fd-chart"><canvas id="cVals"></canvas><div class="fd-empty"><i class="fas fa-file-invoice-dollar"></i>Aún no hay valorizaciones.</div></div>
    </div>
</div>

<div class="sv-card fd-rise" style="--d:.2s; padding:0; overflow:hidden;">
    <div style="padding:16px 22px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; border-bottom:1px solid #f1f5f9;">
        <div><h3 class="fd-card-title"><i class="fas fa-file-invoice-dollar" style="color:#0f766e;"></i> Valorizaciones</h3>
            <p class="fd-card-sub">Solo las aprobadas y pagadas suman al costo real de la actividad</p></div>
        <?php if ($perm['crear'] && $s['estado'] === 'activo' && !$hayBorrador && $s['avance_pct'] < 100): ?>
            <button type="button" class="sv-btn teal" onclick="abrirValorizacion(null)"><i class="fas fa-plus"></i> Nueva valorización</button>
        <?php elseif ($hayBorrador): ?>
            <?= fd_badge('warn', 'Hay una valorización en borrador') ?>
        <?php endif; ?>
    </div>
    <div style="overflow-x:auto;">
        <table class="sv-tbl">
            <thead><tr><th>N°</th><th>Periodo</th><th class="num">Avance acum.</th><th class="num">Valorizado</th><th class="num">Amortiz.</th><th class="num">Facturable</th><th class="num">IGV</th><th class="num">Retención</th><th class="num">Neto a pagar</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($vals)): ?>
                <tr><td colspan="11"><div class="fd-ok-box vacio"><i class="fas fa-file-invoice-dollar"></i>Aún no hay valorizaciones.<?= $s['estado'] === 'activo' && $perm['crear'] ? ' Registra la primera con «Nueva valorización».' : '' ?></div></td></tr>
            <?php endif; ?>
            <?php foreach ($vals as $v): [$cv, $ev] = $valUI[$v['estado']]; $esUlt = $v['id'] === $ultimaVigenteId; ?>
                <tr class="<?= $v['estado'] === 'anulada' ? 'anulada' : '' ?>">
                    <td><strong>#<?= $v['numero'] ?></strong></td>
                    <td class="fd-nowrap"><?= sc_fecha($v['periodo_desde']) ?> – <?= sc_fecha($v['periodo_hasta']) ?>
                        <?php if (!empty($v['nro_factura'])): ?><div style="font-size:11px; color:#94a3b8;">Fact. <?= fd_h($v['nro_factura']) ?> · pagada <?= sc_fecha($v['fecha_pago']) ?></div><?php endif; ?></td>
                    <td class="num"><?= $v['avance_acumulado_pct'] ?>%</td>
                    <td class="num" style="font-weight:700;"><?= fd_money($v['monto_bruto'], 2) ?></td>
                    <td class="num"><?= $v['amortizacion_adelanto'] > 0 ? '− ' . fd_money($v['amortizacion_adelanto'], 2) : '—' ?></td>
                    <td class="num"><?= fd_money($v['facturable'], 2) ?></td>
                    <td class="num"><?= fd_money($v['igv'], 2) ?></td>
                    <td class="num"><?= $v['retencion'] > 0 ? '− ' . fd_money($v['retencion'], 2) : '—' ?></td>
                    <td class="num" style="font-weight:800; color:#0f766e;"><?= fd_money($v['neto_pagar'], 2) ?></td>
                    <td><?= fd_badge($cv, $ev) ?><?php if ($v['aprobado_nombre'] && $v['estado'] !== 'borrador'): ?><div style="font-size:10.5px; color:#94a3b8; margin-top:2px;"><?= fd_h($v['aprobado_nombre']) ?></div><?php endif; ?></td>
                    <td class="fd-nowrap" style="text-align:right;">
                        <?php if ($v['estado'] === 'borrador'): ?>
                            <?php if ($perm['crear']): ?><button type="button" class="sv-btn mini azul" onclick='abrirValorizacion(<?= fd_json(['id' => $v['id'], 'pct' => $v['avance_acumulado_pct'], 'desde' => $v['periodo_desde'], 'hasta' => $v['periodo_hasta'], 'obs' => $v['observaciones']]) ?>)'><i class="fas fa-pen"></i></button><?php endif; ?>
                            <?php if ($perm['aprobar']): ?>
                            <form method="POST" action="<?= BASE_URL ?>/subcontratos/aprobarVal/<?= $v['id'] ?>" style="display:inline;" onsubmit="return confirm('¿Aprobar la valorización #<?= $v['numero'] ?>? Sumará <?= fd_money($v['monto_bruto'], 2) ?> al costo real de la actividad.')">
                                <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>"><button class="sv-btn mini verde" type="submit"><i class="fas fa-check"></i> Aprobar</button></form>
                            <?php endif; ?>
                        <?php elseif ($v['estado'] === 'aprobada' && $perm['editar']): ?>
                            <button type="button" class="sv-btn mini teal" onclick="abrirPago(<?= $v['id'] ?>, <?= $v['numero'] ?>, <?= json_encode($v['neto_pagar']) ?>)"><i class="fas fa-money-check-alt"></i> Pagar</button>
                        <?php endif; ?>
                        <?php if ($esUlt && $perm['eliminar'] && in_array($v['estado'], ['borrador', 'aprobada'], true)): ?>
                            <form method="POST" action="<?= BASE_URL ?>/subcontratos/anularVal/<?= $v['id'] ?>" style="display:inline;" onsubmit="return confirm('¿Anular la valorización #<?= $v['numero'] ?>?<?= $v['estado'] === 'aprobada' ? ' Se restará del costo real de la actividad.' : '' ?>')">
                                <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>"><button class="sv-btn mini rojo" type="submit" title="Anular"><i class="fas fa-ban"></i></button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($firmes): ?>
            <tfoot><tr><td colspan="3">Total aprobado y pagado</td><td class="num"><?= fd_money($tot['bruto'], 2) ?></td><td class="num">− <?= fd_money($tot['amort'], 2) ?></td>
                <td class="num"><?= fd_money($tot['bruto'] - $tot['amort'], 2) ?></td><td class="num"><?= fd_money($tot['igv'], 2) ?></td><td class="num">− <?= fd_money($tot['ret'], 2) ?></td>
                <td class="num"><?= fd_money($tot['neto'], 2) ?></td><td colspan="2"></td></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php if ($perm['crear'] && $s['estado'] === 'activo'): ?>
<div id="mdValorizacion" class="sv-ov">
    <div class="sv-md">
        <div class="sv-md-h"><div><div style="font-size:16px; font-weight:800;" id="mdValTitulo">Nueva valorización</div><div style="font-size:12px; opacity:.85;"><?= fd_h($s['codigo']) ?> · <?= fd_h($s['proveedor']) ?></div></div>
            <button type="button" onclick="cerrarModal('mdValorizacion')" style="background:transparent; border:none; color:rgba(255,255,255,.8); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button></div>
        <form method="POST" action="<?= BASE_URL ?>/subcontratos/valorizar/<?= $sid ?>" class="sv-md-b">
            <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>">
            <input type="hidden" name="valorizacion_id" id="valId" value="">
            <div class="sv-f" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div style="grid-column:1 / -1;"><label>Avance físico acumulado a la fecha (%) *</label>
                    <div style="display:flex; gap:8px;"><input type="number" name="avance_acumulado_pct" id="valPct" step="0.01" min="0.01" max="100" required oninput="calcVal()" style="font-weight:800; text-align:right;">
                    <button type="button" class="sv-btn mini" onclick="document.getElementById('valPct').value = 100; calcVal();">100 %</button></div>
                    <div style="font-size:11.5px; color:#94a3b8; margin-top:4px;">Avance anterior aprobado: <strong><?= $prevCtx['pct'] ?>%</strong> · el periodo valorizado es la diferencia</div></div>
                <div><label>Periodo desde *</label><input type="date" name="periodo_desde" id="valDesde" required></div>
                <div><label>Periodo hasta *</label><input type="date" name="periodo_hasta" id="valHasta" required></div>
                <div style="grid-column:1 / -1;"><label>Observaciones</label><textarea name="observaciones" id="valObs" rows="2" maxlength="500"></textarea></div>
            </div>
            <div class="sv-calc" id="valCalc"></div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="sv-btn" onclick="cerrarModal('mdValorizacion')">Cancelar</button>
                <button type="submit" class="sv-btn teal"><i class="fas fa-save"></i> Guardar borrador</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($perm['editar']): ?>
<div id="mdPago" class="sv-ov">
    <div class="sv-md" style="max-width:460px;">
        <div class="sv-md-h"><div><div style="font-size:16px; font-weight:800;">Registrar pago</div><div style="font-size:12px; opacity:.85;" id="mdPagoSub"></div></div>
            <button type="button" onclick="cerrarModal('mdPago')" style="background:transparent; border:none; color:rgba(255,255,255,.8); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button></div>
        <form method="POST" id="formPago" action="" class="sv-md-b">
            <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>">
            <div class="sv-f" style="display:grid; gap:12px;">
                <div><label>Fecha de pago *</label><input type="date" name="fecha_pago" required max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>"></div>
                <div><label>N° de factura del subcontratista</label><input type="text" name="nro_factura" maxlength="50" placeholder="F001-00012"></div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="sv-btn" onclick="cerrarModal('mdPago')">Cancelar</button>
                <button type="submit" class="sv-btn teal"><i class="fas fa-check"></i> Marcar como pagada</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($conExcesoReact && $perm['editar']): ?>
<div id="mdReactivar" class="sv-ov">
    <div class="sv-md">
        <div class="sv-md-h rojo"><div><div style="font-size:16px; font-weight:800;"><?= $bloqueaReact ? 'Autorizar exceso de presupuesto' : 'Continuar con advertencia' ?></div>
            <div style="font-size:12px; opacity:.9;"><?= fd_h($s['codigo']) ?></div></div>
            <button type="button" onclick="cerrarModal('mdReactivar')" style="background:transparent; border:none; color:rgba(255,255,255,.8); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button></div>
        <form method="POST" action="<?= BASE_URL ?>/subcontratos/estado/<?= $sid ?>" class="sv-md-b">
            <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>"><input type="hidden" name="nuevo_estado" value="activo">
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 14px; font-size:12.5px; color:#7f1d1d; margin-bottom:14px; line-height:1.55;">
                <?= fd_h(ControlPresupuestalModel::resumenExcesos($reactivar, 4)) ?>.<br>
                <?php if ($bloqueaReact && !$puedeAutorizar): ?>El proyecto está en modo <strong>bloquear</strong> y tu usuario no puede autorizar excesos.
                <?php elseif ($bloqueaReact): ?>Modo <strong>bloquear</strong>: queda registrado a tu nombre con el motivo que indiques.
                <?php else: ?>Modo <strong>advertir</strong>: se reactiva y el exceso queda registrado.<?php endif; ?>
            </div>
            <?php if (!($bloqueaReact && !$puedeAutorizar)): ?>
            <div class="sv-f">
                <?php if ($bloqueaReact): ?><label style="display:flex; align-items:center; gap:8px; margin-bottom:10px; cursor:pointer; color:#7f1d1d;"><input type="checkbox" name="autorizar_exceso" value="1" required style="width:auto;"> Autorizo el exceso de presupuesto de la actividad</label><?php endif; ?>
                <label>Motivo <?= $bloqueaReact ? '*' : '(opcional)' ?></label>
                <textarea name="motivo_exceso" rows="3" maxlength="500" <?= $bloqueaReact ? 'required minlength="10"' : '' ?> placeholder="Ej.: Se retoma el servicio por decisión de la gerencia…"></textarea>
            </div>
            <?php endif; ?>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="sv-btn" onclick="cerrarModal('mdReactivar')">Cancelar</button>
                <?php if (!($bloqueaReact && !$puedeAutorizar)): ?><button type="submit" class="sv-btn teal"><i class="fas fa-play-circle"></i> Activar</button><?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const SUB = {monto: <?= json_encode($s['monto']) ?>, adelanto: <?= json_encode($s['adelanto_monto']) ?>, ret: <?= json_encode($s['retencion_pct']) ?>};
const PREV = <?= fd_json($prevCtx) ?>;
const IGV = <?= json_encode(SubcontratoModel::IGV) ?>;
const GRAF = <?= fd_json($grafVals) ?>;
const BASE_URL_JS = <?= fd_json(BASE_URL) ?>;
const r2 = n => Math.round((n + Number.EPSILON) * 100) / 100;

function abrirModal(id) { const m = document.getElementById(id); if (m) m.style.display = 'flex'; }
function cerrarModal(id) { const m = document.getElementById(id); if (m) m.style.display = 'none'; }
document.addEventListener('keydown', e => { if (e.key === 'Escape') ['mdValorizacion', 'mdPago', 'mdReactivar'].forEach(cerrarModal); });

/* Cálculo idéntico al del servidor (SubcontratoModel::calcular) */
function calcVal() {
    const pct = parseFloat(document.getElementById('valPct').value) || 0;
    const box = document.getElementById('valCalc');
    if (pct <= PREV.pct) { box.innerHTML = `<div class="n">Ingresa un avance mayor al anterior (${PREV.pct} %).</div>`; return; }
    if (pct > 100) { box.innerHTML = '<div class="n">El avance no puede superar el 100 %.</div>'; return; }
    const brutoAcum = r2(SUB.monto * pct / 100), bruto = r2(brutoAcum - PREV.brutoAcum);
    const restante = Math.max(0, r2(SUB.adelanto - PREV.amortAcum));
    const amort = pct >= 99.995 ? restante : (SUB.monto > 0 ? Math.min(restante, r2(SUB.adelanto * bruto / SUB.monto)) : 0);
    const ret = r2(bruto * SUB.ret / 100), fact = r2(bruto - amort), igv = r2(fact * IGV), neto = r2(fact + igv - ret);
    const m = n => 'S/ ' + Number(n).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    box.innerHTML = `<div><span>Valorizado del periodo (${(pct - PREV.pct).toFixed(2)} % del contrato)</span><strong>${m(bruto)}</strong></div>
        <div><span>Amortización de adelanto</span><strong>− ${m(amort)}</strong></div>
        <div><span>Facturable</span><strong>${m(fact)}</strong></div>
        <div><span>IGV (${(IGV * 100).toFixed(0)} %)</span><strong>+ ${m(igv)}</strong></div>
        <div><span>Retención de garantía (${SUB.ret} %)</span><strong>− ${m(ret)}</strong></div>
        <div class="t"><span>Neto a pagar</span><strong>${m(neto)}</strong></div>`;
}

function abrirValorizacion(v) {
    const sumarDia = f => { if (!f) return ''; const d = new Date(f + 'T00:00:00'); d.setDate(d.getDate() + 1); return d.toISOString().slice(0, 10); };
    document.getElementById('mdValTitulo').textContent = v ? 'Editar valorización (borrador)' : 'Nueva valorización';
    document.getElementById('valId').value = v ? v.id : '';
    document.getElementById('valPct').value = v ? v.pct : '';
    document.getElementById('valDesde').value = v ? v.desde : sumarDia(PREV.hasta);
    document.getElementById('valHasta').value = v ? v.hasta : '';
    document.getElementById('valObs').value = v && v.obs ? v.obs : '';
    document.getElementById('valDesde').min = PREV.hasta ? sumarDia(PREV.hasta) : '';
    calcVal(); abrirModal('mdValorizacion');
}
function abrirPago(id, n, neto) {
    document.getElementById('formPago').action = BASE_URL_JS + '/subcontratos/pagarVal/' + id;
    document.getElementById('mdPagoSub').textContent = 'Valorización #' + n + ' · neto S/ ' + Number(neto).toLocaleString('es-PE', {minimumFractionDigits: 2});
    abrirModal('mdPago');
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    if (!GRAF.length) { fdVacio('cVals'); return; }
    const col = {borrador: '#fcd34d', aprobada: '#3b82f6', pagada: '#16a34a'};
    new Chart(document.getElementById('cVals'), {
        data: {labels: GRAF.map(g => g.n), datasets: [
            {type: 'bar', label: 'Valorizado del periodo', data: GRAF.map(g => g.bruto), backgroundColor: GRAF.map(g => col[g.estado] || '#94a3b8'), borderRadius: 6, yAxisID: 'y'},
            {type: 'line', label: 'Avance acumulado (%)', data: GRAF.map(g => g.acum), borderColor: '#0f766e', backgroundColor: '#0f766e', tension: .3, pointRadius: 5, yAxisID: 'y1'}]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            scales: {y: {beginAtZero: true, ticks: {callback: fdCorto}}, y1: {position: 'right', min: 0, max: 100, grid: {drawOnChartArea: false}, ticks: {callback: v => v + '%'}}},
            plugins: {tooltip: {callbacks: {label: c => c.dataset.type === 'line' ? c.dataset.label + ': ' + c.parsed.y + '%' : c.dataset.label + ': ' + fdMoney(c.parsed.y, 2)}}}}
    });
});
</script>
</div>
