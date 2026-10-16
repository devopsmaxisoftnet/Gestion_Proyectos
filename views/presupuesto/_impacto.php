<?php
/**
 * Panel reutilizable: impacto de una operación (p. ej. aprobar una OC) en el presupuesto de cada actividad.
 * Espera $impEval (resultado de ControlPresupuestalModel::evaluar*), $impTitulo y $impSub.
 */
$impH = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$impM = fn($v) => 'S/ ' . number_format((float)$v, 2);
$impModos = ['informar' => 'Solo informar', 'advertir' => 'Advertir y registrar', 'bloquear' => 'Bloquear con autorización'];
$impNivel = $impEval['hay_exceso'] ? 'exceso' : ($impEval['hay_alerta'] ? 'alerta' : 'ok');
$impCfg = [
    'exceso' => ['#fef2f2', '#fecaca', '#b91c1c', 'fa-exclamation-triangle', 'Excede el presupuesto'],
    'alerta' => ['#fffbeb', '#fde68a', '#b45309', 'fa-exclamation-circle', 'Cerca del límite'],
    'ok'     => ['#f0fdf4', '#bbf7d0', '#15803d', 'fa-check-circle', 'Dentro del presupuesto'],
][$impNivel];
?>
<style>
.imp-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; margin-bottom:20px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.02); animation:impIn .5s ease-out; }
@keyframes impIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
.imp-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:14px 20px; border-bottom:1px solid #f1f5f9; }
.imp-pill { display:inline-flex; align-items:center; gap:7px; padding:6px 13px; border-radius:999px; font-size:12px; font-weight:800; border:1px solid; }
.imp-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.imp-tbl th { text-align:left; padding:9px 12px; font-size:10.5px; text-transform:uppercase; letter-spacing:.4px; color:#64748b; background:#f8fafc; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.imp-tbl td { padding:11px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.imp-tbl .num { text-align:right; white-space:nowrap; }
.imp-bar { position:relative; display:flex; height:11px; background:#e2e8f0; border-radius:999px; overflow:hidden; min-width:170px; }
.imp-bar i { display:block; height:100%; width:0; transition:width 1.2s cubic-bezier(.22,.8,.3,1);
    background-image:linear-gradient(45deg,rgba(255,255,255,.28) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.28) 50%,rgba(255,255,255,.28) 75%,transparent 75%,transparent);
    background-size:1rem 1rem; animation:impStripes 1s linear infinite; }
.imp-bar i.antes { background-color:#3b82f6; } .imp-bar i.op { background-color:#f59e0b; } .imp-bar i.op.rojo { background-color:#dc2626; }
.imp-bar b { position:absolute; top:-2px; bottom:-2px; width:2px; background:#0f172a; opacity:.75; }
@keyframes impStripes { from { background-position:1rem 0; } to { background-position:0 0; } }
.imp-b { display:inline-flex; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
.imp-b.ok { background:#dcfce7; color:#15803d; } .imp-b.alerta { background:#fef3c7; color:#b45309; } .imp-b.exceso { background:#fee2e2; color:#b91c1c; }
.imp-cod { font-family:monospace; font-weight:700; background:#f1f5f9; padding:2px 7px; border-radius:6px; font-size:11.5px; border:1px solid #e2e8f0; }
</style>
<div class="imp-card no-print">
    <div class="imp-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:12px; background:<?= $impCfg[0] ?>; color:<?= $impCfg[2] ?>; display:flex; align-items:center; justify-content:center; font-size:17px;"><i class="fas fa-balance-scale"></i></div>
            <div>
                <div style="font-size:14px; font-weight:800; color:#0f172a;"><?= $impH($impTitulo ?? 'Control presupuestal') ?></div>
                <div style="font-size:12px; color:#64748b;"><?= $impH($impSub ?? '') ?></div>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <span style="font-size:11.5px; color:#64748b; font-weight:600;"><i class="fas fa-shield-alt"></i> <?= $impH($impModos[$impEval['modo']] ?? '') ?></span>
            <span class="imp-pill" style="background:<?= $impCfg[0] ?>; border-color:<?= $impCfg[1] ?>; color:<?= $impCfg[2] ?>;"><i class="fas <?= $impCfg[3] ?>"></i> <?= $impH($impCfg[4]) ?></span>
        </div>
    </div>
    <?php if (empty($impEval['items'])): ?>
        <div style="padding:22px; text-align:center; color:#94a3b8; font-size:12.5px;">Las líneas de esta orden no están imputadas a actividades del WBS, por lo que no afectan ningún presupuesto.</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="imp-tbl">
            <thead><tr><th>Actividad</th><th class="num">Presupuesto</th><th class="num">Usado antes</th><th class="num">Esta operación</th><th class="num">Quedaría</th><th style="min-width:190px;">Uso del presupuesto</th><th>Resultado</th></tr></thead>
            <tbody>
            <?php foreach ($impEval['items'] as $i):
                $tiene = $i['presupuesto'] > 0;
                $escala = $tiene ? max(100.0, (float)$i['pct_despues']) : 100.0;
                $wAntes = $tiene ? round(min((float)$i['pct_antes'], $escala) / $escala * 100, 2) : 0;
                $wOp = $tiene ? round(((float)$i['pct_despues'] - (float)$i['pct_antes']) / $escala * 100, 2) : 100;
            ?>
                <tr>
                    <td><span class="imp-cod"><?= $impH($i['codigo']) ?></span> <strong style="color:#1e293b;"><?= $impH($i['nombre']) ?></strong></td>
                    <td class="num"><?= $tiene ? $impM($i['presupuesto']) : '<span style="color:#b91c1c; font-weight:700;">Sin presupuesto</span>' ?></td>
                    <td class="num"><?= $impM($i['usado_previo']) ?></td>
                    <td class="num" style="font-weight:800; color:#b45309;">+ <?= $impM($i['monto']) ?></td>
                    <td class="num" style="font-weight:800; color:<?= $i['disponible_despues'] < 0 ? '#b91c1c' : '#15803d' ?>;"><?= $i['disponible_despues'] < 0 ? '− ' : '' ?><?= $impM(abs($i['disponible_despues'])) ?><div style="font-size:10.5px; font-weight:600; color:#94a3b8;"><?= $i['disponible_despues'] < 0 ? 'sobregiro' : 'disponible' ?></div></td>
                    <td>
                        <div class="imp-bar"><i class="antes" data-w="<?= $wAntes ?>"></i><i class="op <?= $i['nivel'] === 'exceso' ? 'rojo' : '' ?>" data-w="<?= max(0, $wOp) ?>"></i><?= $tiene ? '<b style="left:' . round(100 / $escala * 100, 2) . '%"></b>' : '' ?></div>
                        <div style="font-size:10.5px; color:#94a3b8; margin-top:3px;"><?= $tiene ? $i['pct_antes'] . '% → ' . $i['pct_despues'] . '%' : '' ?></div>
                    </td>
                    <td><span class="imp-b <?= $i['nivel'] ?>"><?= $i['nivel'] === 'exceso' ? ($i['sin_presupuesto'] ? 'Sin presupuesto' : 'Excede +' . $impM($i['exceso'])) : ($i['nivel'] === 'alerta' ? 'En alerta' : 'OK') ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<script>
requestAnimationFrame(() => document.querySelectorAll('.imp-bar i[data-w]').forEach(b => { b.style.width = b.dataset.w + '%'; }));
</script>
