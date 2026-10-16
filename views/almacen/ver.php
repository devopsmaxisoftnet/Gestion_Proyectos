<?php
/**
 * Vista: Detalle de un movimiento de almacén (ingreso o salida) — imprimible.
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = fn($v) => 'S/ ' . number_format((float)$v, 2);

$mov = $data['mov'];
$detalles = $data['detalles'];
$permisos = $data['permisos'];
$esIngreso = $mov['tipo_movimiento'] === 'ingreso';
$anulado = $mov['estado'] === 'anulado';
$total = array_sum(array_map(fn($d) => (float)$d['subtotal'], $detalles));
$codigo = $mov['guia_remision'] ?: '#' . $mov['id'];
?>
<?php include __DIR__ . '/_estilos.php'; ?>
<style>@media print { .no-print, .sidebar, .top-header, nav, header { display:none !important; } .main-content { margin:0 !important; padding:0 !important; } .content-area { padding:0 !important; } .alm-print { border:none !important; box-shadow:none !important; } }</style>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <div class="no-print alm-card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-bottom:20px; padding:14px 20px; border-radius:16px;">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/almacen" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:7px 14px; border-radius:9px; font-size:12.5px; font-weight:700; text-decoration:none;"><i class="fas fa-arrow-left"></i> Almacén</a>
            <span style="font-size:13px; font-weight:700; color:#0f172a;"><?= $esIngreso ? 'Ingreso' : 'Vale de salida' ?>: <?= $h($codigo) ?></span>
            <span class="alm-badge <?= $anulado ? 'neutral' : 'ok' ?>"><?= $anulado ? 'Anulado' : 'Registrado' ?></span>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button onclick="window.print()" style="background:#fff; border:1.5px solid #cbd5e1; color:#334155; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer;"><i class="fas fa-print"></i> Imprimir</button>
            <?php if ($permisos['editar'] && !$anulado): ?>
            <button onclick="document.getElementById('modalAnular').style.display='flex'" style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer;"><i class="fas fa-ban"></i> Anular</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="alm-card alm-print" style="max-width:900px; margin:0 auto; border-radius:18px; padding:34px; box-shadow:0 6px 24px rgba(0,0,0,.04);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #e2e8f0; padding-bottom:18px; margin-bottom:20px; flex-wrap:wrap; gap:14px;">
            <div>
                <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;"><?= $esIngreso ? 'Nota de ingreso a almacén' : 'Vale de salida a obra' ?></div>
                <div style="font-size:24px; font-weight:900; color:<?= $esIngreso ? '#0d9488' : '#ea580c' ?>; font-family:monospace; margin-top:2px;"><?= $h($codigo) ?></div>
            </div>
            <div style="text-align:right; font-size:12.5px; color:#475569; line-height:1.6;">
                <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($mov['fecha'])) ?><br>
                <strong>Registrado por:</strong> <?= $h($mov['registrado_nombre'] ?: 'Sistema') ?><br>
                <strong>Proyecto:</strong> [<?= $h($mov['proyecto_codigo']) ?>] <?= $h($mov['proyecto_nombre']) ?>
                <?php if ($mov['oc_codigo']): ?><br><strong>Orden de compra:</strong> <a href="<?= BASE_URL ?>/compras/ver/<?= (int)$mov['orden_compra_id'] ?>" style="color:#ea580c; font-weight:700;"><?= $h($mov['oc_codigo']) ?></a><?php endif; ?>
            </div>
        </div>

        <?php if ($anulado): ?>
        <div style="background:#f1f5f9; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; margin-bottom:18px; font-size:12.5px; color:#475569;">
            <i class="fas fa-ban"></i> <strong>Movimiento anulado.</strong> Motivo: <?= $h($mov['motivo_anulacion'] ?: '—') ?>. Su efecto en stock y costos fue revertido.
        </div>
        <?php endif; ?>

        <table class="alm-tbl" style="margin-bottom:18px;">
            <thead><tr style="background:#0f172a; color:#fff;">
                <th style="width:36px; text-align:center; color:#fff;">#</th><th style="color:#fff;">Ítem</th>
                <?php if (!$esIngreso): ?><th style="color:#fff;">Actividad (WBS)</th><?php endif; ?>
                <th style="text-align:right; color:#fff;">Cantidad</th><th style="text-align:right; color:#fff;">Costo unit.</th><th style="text-align:right; color:#fff;">Importe</th>
            </tr></thead>
            <tbody>
            <?php foreach ($detalles as $n => $d):
                $esEq = !empty($d['equipo_id']);
                $nombre = $esEq ? $d['equipo_nombre'] : $d['material_nombre'];
                $cod = $esEq ? $d['equipo_codigo'] : $d['material_codigo'];
            ?>
                <tr>
                    <td style="text-align:center; color:#94a3b8; font-weight:600;"><?= $n + 1 ?></td>
                    <td><strong style="color:#1e293b;"><?= $h($nombre) ?></strong><div style="font-family:monospace; font-size:11px; color:#94a3b8;"><?= $h($cod) ?><?= $esEq ? ' · equipo (horas)' : ' · ' . $h($d['unidad_medida']) ?></div></td>
                    <?php if (!$esIngreso): ?>
                    <td><?php if ($d['tarea_id']): ?><a href="<?= BASE_URL ?>/tareas?proyecto_id=<?= (int)$mov['proyecto_id'] ?>" style="font-family:monospace; font-weight:700; font-size:11.5px; background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; padding:2px 7px; border-radius:6px; text-decoration:none;"><?= $h($d['tarea_codigo']) ?></a> <span style="font-size:12px; color:#475569;"><?= $h($d['tarea_nombre']) ?></span><?php endif; ?></td>
                    <?php endif; ?>
                    <td style="text-align:right; font-weight:700;"><?= number_format((float)$d['cantidad'], 2) ?></td>
                    <td style="text-align:right;"><?= number_format((float)$d['precio_unitario'], 2) ?></td>
                    <td style="text-align:right; font-weight:800;"><?= number_format((float)$d['subtotal'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px; flex-wrap:wrap;">
            <div style="flex:1; min-width:200px; font-size:12.5px; color:#475569;">
                <?php if (!empty($mov['observaciones'])): ?><strong style="color:#1e293b;">Observaciones:</strong><br><?= nl2br($h($mov['observaciones'])) ?><?php endif; ?>
            </div>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 20px; font-size:15px; font-weight:800; color:#0f172a;">
                Total <?= $esIngreso ? 'ingresado' : 'cargado al WBS' ?>: <span style="color:<?= $esIngreso ? '#0d9488' : '#ea580c' ?>;"><?= $money($total) ?></span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-top:40px; text-align:center; font-size:11.5px; color:#64748b;">
            <div style="padding-top:8px; border-top:1px dashed #cbd5e1;"><?= $esIngreso ? 'Almacenero' : 'Entregado por (Almacén)' ?></div>
            <div style="padding-top:8px; border-top:1px dashed #cbd5e1;"><?= $esIngreso ? 'Proveedor / Transportista' : 'Recibido por (Residente / Capataz)' ?></div>
        </div>
    </div>
</div>

<?php if ($permisos['editar'] && !$anulado): ?>
<div id="modalAnular" class="alm-overlay no-print">
    <div class="alm-modal">
        <div style="background:linear-gradient(135deg,#991b1b 0%,#dc2626 100%); padding:18px 24px; color:#fff; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:16px; font-weight:700;"><i class="fas fa-ban"></i> Anular <?= $h($codigo) ?></h3>
            <button type="button" onclick="document.getElementById('modalAnular').style.display='none'" style="background:transparent; border:none; color:rgba(255,255,255,.7); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/almacen/anular/<?= (int)$mov['id'] ?>" style="padding:22px 24px;">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
            <p style="font-size:12.5px; color:#64748b; margin:0 0 14px;">Se revertirá el stock y el costo promedio, y se recalculará el costo real de las actividades afectadas.</p>
            <div class="alm-field"><label>Motivo de la anulación *</label><textarea name="motivo" rows="3" required maxlength="255"></textarea></div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
                <button type="button" onclick="document.getElementById('modalAnular').style.display='none'" style="padding:9px 16px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:9px 20px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer;">Confirmar anulación</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
