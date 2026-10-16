<?php
/**
 * Vista: Comprobante y Detalle de Orden de Compra (Fase 3: Logística)
 * Con flujo de Aprobación Jerárquica (Logística / Gerencia) y Formato Imprimible
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$orden = $data['orden'] ?? [];
$detalles = $data['detalles'] ?? [];
$puedeAprobar = $data['puedeAprobar'] ?? false;

$estadoBadges = [
    'borrador' => ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => 'Borrador'],
    'pendiente_aprobacion' => ['bg' => '#fffbeb', 'text' => '#b45309', 'label' => 'Pendiente de Aprobación'],
    'aprobada' => ['bg' => '#ecfdf5', 'text' => '#047857', 'label' => 'Aprobada Oficialmente'],
    'rechazada' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'label' => 'Rechazada con Observaciones'],
    'recibida' => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'label' => 'Recibida en Almacén'],
    'anulada' => ['bg' => '#f1f5f9', 'text' => '#64748b', 'label' => 'Anulada']
];
$puedeEditar = $data['puedeEditar'] ?? false;
$puedeAnular = $data['puedeAnular'] ?? false;
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$badge = $estadoBadges[$orden['estado']] ?? ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => ucfirst($orden['estado'])];
$esPendiente = ($orden['estado'] === 'pendiente_aprobacion');
$simbolo = ($orden['moneda'] === 'USD') ? '$' : 'S/';

// Control presupuestal: impacto de aprobar esta orden
$control = $data['control'] ?? null;
$conExceso = $control && $control['hay_exceso'] && $control['modo'] !== 'informar';
$motivoObligatorio = $conExceso && $control['modo'] === 'bloquear';
?>

<style>
@media print {
    .sidebar, .top-header, .no-print, nav, header {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
    .content-area {
        padding: 0 !important;
    }
    .print-voucher-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <!-- Barra Superior de Acciones (No Imprimible) -->
    <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-bottom:20px; background:#ffffff; padding:14px 20px; border-radius:16px; border:1px solid #e2e8f0; box-shadow:0 2px 10px rgba(0,0,0,0.02);">
        <div style="display:flex; align-items:center; gap:12px;">
            <a href="<?= BASE_URL ?>/compras" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:7px 14px; border-radius:9px; font-size:12.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-arrow-left"></i> Listado
            </a>
            <span style="font-size:13px; font-weight:700; color:#0f172a;">Orden: <?= htmlspecialchars($orden['codigo']) ?></span>
            <span style="display:inline-block; padding:3px 9px; border-radius:6px; font-size:11px; font-weight:700; background:<?= $badge['bg'] ?>; color:<?= $badge['text'] ?>;">
                <?= $badge['label'] ?>
            </span>
        </div>

        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <button onclick="window.print()" style="background:#ffffff; border:1.5px solid #cbd5e1; color:#334155; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-print"></i> Imprimir Voucher
            </button>

            <?php if ($puedeEditar): ?>
                <a href="<?= BASE_URL ?>/compras/editar/<?= (int)$orden['id'] ?>" style="background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-edit"></i> Editar
                </a>
            <?php endif; ?>

            <?php if ($orden['estado'] === 'aprobada' && PermisosHelper::puede('crear', 'almacenes')): ?>
                <a href="<?= BASE_URL ?>/almacen/ingreso?oc_id=<?= (int)$orden['id'] ?>" style="background:linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color:#ffffff; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-truck-loading"></i> Recepcionar en almacén
                </a>
            <?php endif; ?>

            <?php if ($puedeAnular): ?>
                <form method="POST" action="<?= BASE_URL ?>/compras/anular/<?= (int)$orden['id'] ?>" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
                    <button type="submit" onclick="return confirm('¿Anular esta Orden de Compra? Dejará de computar en el costo de las actividades.')" style="background:#f8fafc; border:1px solid #cbd5e1; color:#475569; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fas fa-ban"></i> Anular
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($esPendiente && $puedeAprobar && $conExceso): ?>
                <button type="button" onclick="abrirModalExceso()" style="background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:#ffffff; border:none; padding:8px 18px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(217,119,6,0.3);">
                    <i class="fas fa-exclamation-triangle"></i> <?= $motivoObligatorio ? 'Autorizar exceso y aprobar' : 'Aprobar con advertencia' ?>
                </button>
                <button onclick="abrirModalRechazo()" style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-times-circle"></i> Rechazar
                </button>
            <?php elseif ($esPendiente && $puedeAprobar): ?>
                <form method="POST" action="<?= BASE_URL ?>/compras/aprobar/<?= $orden['id'] ?>" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button type="submit" onclick="return confirm('¿Aprobar formalmente esta Orden de Compra para despacho?')" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; border:none; padding:8px 18px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(16,185,129,0.25);">
                        <i class="fas fa-check-circle"></i> Aprobar Orden
                    </button>
                </form>

                <button onclick="abrirModalRechazo()" style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-times-circle"></i> Rechazar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($control && $esPendiente):
        $impEval = $control; $impTitulo = 'Control presupuestal de esta orden'; $impSub = 'Impacto de aprobarla en el presupuesto de cada actividad imputada';
        include __DIR__ . '/../presupuesto/_impacto.php';
    endif; ?>

    <!-- Voucher Oficial de Orden de Compra -->
    <div class="print-voucher-card" style="max-width:850px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; padding:36px; box-shadow:0 6px 24px rgba(0,0,0,0.04);">
        
        <!-- Cabecera del Voucher -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #e2e8f0; padding-bottom:20px; margin-bottom:22px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <div style="width:36px; height:36px; border-radius:8px; background:#ea580c; color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:800;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <h2 style="font-size:18px; font-weight:900; color:#0f172a; margin:0; letter-spacing:-0.3px;">ERP GESTIÓN DE PROYECTOS</h2>
                        <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Departamento de Logística y Abastecimiento</span>
                    </div>
                </div>
                <div style="font-size:12px; color:#475569; line-height:1.4;">
                    Av. Javier Prado Este 4200, Lima · RUC: 20456789123<br>
                    Email: logistica@gestionproyectos.com · Central: (01) 619-2000
                </div>
            </div>

            <div style="text-align:right;">
                <div style="background:#fff7ed; border:1.5px solid #fed7aa; border-radius:12px; padding:12px 18px; display:inline-block;">
                    <div style="font-size:11px; font-weight:800; color:#9a3412; text-transform:uppercase; letter-spacing:0.5px;">ORDEN DE COMPRA</div>
                    <div style="font-size:18px; font-weight:900; color:#ea580c; font-family:monospace; margin-top:2px;">
                        <?= htmlspecialchars($orden['codigo']) ?>
                    </div>
                </div>
                <div style="margin-top:8px;">
                    <span style="display:inline-block; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:800; background:<?= $badge['bg'] ?>; color:<?= $badge['text'] ?>;">
                        <?= $badge['label'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Bloques de Información: Proveedor y Destino -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:22px;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px;">
                <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-truck" style="color:#ea580c;"></i> Datos del Proveedor
                </div>
                <div style="font-size:13.5px; font-weight:800; color:#0f172a; margin-bottom:4px;">
                    <?= htmlspecialchars((string)($orden['proveedor_nombre'] ?? '')) ?>
                </div>
                <div style="font-size:12px; color:#475569; line-height:1.5;">
                    <?php if (!empty($orden['proveedor_ruc'])): ?>
                        <strong>RUC:</strong> <?= htmlspecialchars($orden['proveedor_ruc']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($orden['proveedor_direccion'])): ?>
                        <strong>Dirección:</strong> <?= htmlspecialchars($orden['proveedor_direccion']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($orden['proveedor_telefono'])): ?>
                        <strong>Teléfono:</strong> <?= htmlspecialchars($orden['proveedor_telefono']) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px;">
                <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-project-diagram" style="color:#2563eb;"></i> Proyecto Destino & Despacho
                </div>
                <div style="font-size:13.5px; font-weight:800; color:#0f172a; margin-bottom:4px;">
                    [<?= htmlspecialchars($orden['proyecto_codigo']) ?>] <?= htmlspecialchars($orden['proyecto_nombre']) ?>
                </div>
                <div style="font-size:12px; color:#475569; line-height:1.5;">
                    <strong>Fecha Emisión:</strong> <?= date('d/m/Y', strtotime($orden['fecha_emision'])) ?><br>
                    <strong>Entrega Requerida:</strong> <?= !empty($orden['fecha_entrega']) ? date('d/m/Y', strtotime($orden['fecha_entrega'])) : 'Inmediata' ?><br>
                    <strong>Condición de Pago:</strong> <?= htmlspecialchars($orden['condicion_pago']) ?><br>
                    <strong>Lugar:</strong> <?= htmlspecialchars($orden['lugar_entrega'] ?? 'En almacén de obra') ?>
                </div>
            </div>
        </div>

        <!-- Tabla de Ítems / Materiales -->
        <table style="width:100%; border-collapse:collapse; text-align:left; font-size:12.5px; margin-bottom:18px;">
            <thead>
                <tr style="background:#0f172a; color:#ffffff; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                    <th style="padding:10px 12px; width:40px; text-align:center;">#</th>
                    <th style="padding:10px 14px;">Descripción del Ítem / Material</th>
                    <th style="padding:10px 12px; width:150px;">Actividad (WBS)</th>
                    <th style="padding:10px 12px; width:90px; text-align:center;">U.M.</th>
                    <th style="padding:10px 12px; width:90px; text-align:center;">Cant.</th>
                    <th style="padding:10px 14px; width:120px; text-align:right;">P. Unit (<?= $simbolo ?>)</th>
                    <th style="padding:10px 14px; width:130px; text-align:right;">Total (<?= $simbolo ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detalles)): ?>
                <tr>
                    <td colspan="7" style="padding:30px; text-align:center; color:#94a3b8;">
                        No hay ítems registrados en esta orden.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($detalles as $idx => $d): ?>
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:10px 12px; text-align:center; color:#94a3b8; font-weight:600;"><?= $idx + 1 ?></td>
                        <td style="padding:10px 14px; color:#1e293b; font-weight:600;">
                            <?= $h($d['descripcion']) ?>
                            <?php if (!empty($d['material_codigo']) || !empty($d['equipo_codigo'])): ?>
                                <div style="font-size:10.5px; color:#94a3b8; font-family:monospace; font-weight:600;"><?= $h($d['material_codigo'] ?: $d['equipo_codigo']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px 12px;">
                            <?php if (!empty($d['tarea_id'])): ?>
                                <a href="<?= BASE_URL ?>/tareas?proyecto_id=<?= (int)$orden['proyecto_id'] ?>" title="<?= $h($d['tarea_nombre']) ?>" style="font-family:monospace; font-weight:700; font-size:11.5px; background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; padding:2px 7px; border-radius:6px; text-decoration:none;"><?= $h($d['tarea_codigo']) ?></a>
                            <?php else: ?>
                                <span style="font-size:11px; color:#d97706; font-style:italic;">Sin imputar</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px 12px; text-align:center; font-family:monospace; color:#475569;"><?= htmlspecialchars($d['unidad_medida']) ?></td>
                        <td style="padding:10px 12px; text-align:center; font-weight:700; color:#0f172a;"><?= number_format($d['cantidad'], 2) ?></td>
                        <td style="padding:10px 14px; text-align:right; font-weight:600; color:#334155;"><?= number_format($d['precio_unitario'], 2) ?></td>
                        <td style="padding:10px 14px; text-align:right; font-weight:800; color:#0f172a;"><?= number_format($d['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Resumen Financiero -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; gap:20px;">
            <div style="flex:1; font-size:12px; color:#475569;">
                <?php if (!empty($orden['observaciones'])): ?>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px;">
                        <strong style="color:#1e293b; display:block; margin-bottom:4px;">Observaciones y Términos:</strong>
                        <?= nl2br(htmlspecialchars($orden['observaciones'])) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($orden['motivo_rechazo'])): ?>
                    <div style="background:#fef2f2; border:1px solid #fee2e2; border-radius:10px; padding:12px; margin-top:10px; color:#991b1b;">
                        <strong style="display:block; margin-bottom:4px;"><i class="fas fa-exclamation-triangle"></i> Motivo de Rechazo:</strong>
                        <?= nl2br(htmlspecialchars($orden['motivo_rechazo'])) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="width:280px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:12.5px; color:#64748b;">
                    <span>Subtotal Neto:</span>
                    <strong style="color:#1e293b;"><?= $simbolo ?> <?= number_format($orden['subtotal'], 2) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:12.5px; color:#64748b;">
                    <span>I.G.V. (18%):</span>
                    <strong style="color:#1e293b;"><?= $simbolo ?> <?= number_format($orden['igv'], 2) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; padding-top:10px; border-top:1.5px solid #cbd5e1; font-size:15px; font-weight:800; color:#0f172a;">
                    <span>TOTAL:</span>
                    <span style="color:#ea580c;"><?= $simbolo ?> <?= number_format($orden['total'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Firmas y Trazabilidad de Aprobación -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px; padding-top:20px; border-top:1px solid #e2e8f0; text-align:center;">
            <div style="padding-top:35px; border-top:1px dashed #cbd5e1;">
                <div style="font-weight:700; color:#1e293b; font-size:12.5px;">
                    <?= htmlspecialchars($orden['solicitante_nombre'] ?? 'Personal de Obra') ?>
                </div>
                <div style="font-size:11px; color:#64748b;">Solicitado por (Responsable de Proyecto)</div>
            </div>

            <div style="padding-top:35px; border-top:1px dashed #cbd5e1;">
                <?php if ($orden['estado'] === 'aprobada'): ?>
                    <div style="font-weight:800; color:#059669; font-size:12.5px;">
                        <i class="fas fa-check-circle"></i> APROBADO: <?= htmlspecialchars($orden['aprobador_nombre'] ?? 'Gerencia / Logística') ?>
                    </div>
                    <div style="font-size:11px; color:#64748b;">
                        Fecha: <?= !empty($orden['fecha_aprobacion']) ? date('d/m/Y H:i', strtotime($orden['fecha_aprobacion'])) : '' ?>
                    </div>
                <?php elseif ($orden['estado'] === 'rechazada'): ?>
                    <div style="font-weight:800; color:#dc2626; font-size:12.5px;">
                        <i class="fas fa-ban"></i> RECHAZADO: <?= htmlspecialchars($orden['aprobador_nombre'] ?? 'Logística / Gerencia') ?>
                    </div>
                    <div style="font-size:11px; color:#64748b;">
                        Fecha: <?= !empty($orden['fecha_aprobacion']) ? date('d/m/Y H:i', strtotime($orden['fecha_aprobacion'])) : '' ?>
                    </div>
                <?php else: ?>
                    <div style="font-weight:700; color:#d97706; font-size:12.5px;">
                        <i class="fas fa-hourglass-half"></i> Pendiente de Aprobación
                    </div>
                    <div style="font-size:11px; color:#64748b;">Jefe de Logística / Gerencia de Área</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Rechazar Orden con Observaciones -->
<div id="modalRechazoOC" class="no-print" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#ffffff; border-radius:18px; width:95%; max-width:480px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden;">
        <div style="background:linear-gradient(135deg, #991b1b 0%, #dc2626 100%); padding:18px 24px; color:#ffffff; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-times-circle" style="font-size:20px;"></i>
                <h3 style="margin:0; font-size:16px; font-weight:700;">Rechazar Orden de Compra</h3>
            </div>
            <button onclick="cerrarModalRechazo()" style="background:transparent; border:none; color:rgba(255,255,255,0.7); font-size:18px; cursor:pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/compras/rechazar/<?= $orden['id'] ?>" style="padding:22px 24px;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Motivo u Observación de Rechazo *</label>
                <textarea name="motivo_rechazo" required rows="4" placeholder="Especifica la razón técnica o presupuestal del rechazo..." style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="cerrarModalRechazo()" style="padding:9px 16px; border-radius:9px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="background:#dc2626; color:#ffffff; border:none; padding:9px 20px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer;">
                    Confirmar Rechazo
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($esPendiente && $puedeAprobar && $conExceso): ?>
<div id="modalExcesoOC" class="no-print" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:20px; width:95%; max-width:560px; box-shadow:0 24px 48px rgba(0,0,0,.25); overflow:hidden; animation:impIn .3s ease-out;">
        <div style="background:linear-gradient(120deg,#d97706,#dc2626); color:#fff; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:11px; background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center;"><i class="fas fa-exclamation-triangle"></i></div>
                <div><div style="font-size:16px; font-weight:800;"><?= $motivoObligatorio ? 'Autorizar exceso de presupuesto' : 'Aprobar con advertencia' ?></div>
                <div style="font-size:12px; opacity:.9;">Orden <?= $h($orden['codigo']) ?> · <?= count(array_filter($control['items'], fn($i) => $i['nivel'] === 'exceso')) ?> actividad(es) sobregirada(s)</div></div>
            </div>
            <button type="button" onclick="cerrarModalExceso()" style="background:transparent; border:none; color:rgba(255,255,255,.8); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/compras/aprobar/<?= (int)$orden['id'] ?>" style="padding:22px 24px;">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 14px; font-size:12.5px; color:#7f1d1d; margin-bottom:14px; line-height:1.55;">
                <?= $h(ControlPresupuestalModel::resumenExcesos($control, 6)) ?>.<br>
                Exceso total: <strong>S/ <?= number_format($control['exceso_total'], 2) ?></strong>.
                <?= $motivoObligatorio ? 'El proyecto está en modo <strong>bloquear</strong>: la aprobación queda registrada a tu nombre con el motivo que indiques.' : 'El proyecto está en modo <strong>advertir</strong>: la orden se aprueba y el exceso queda registrado.' ?>
            </div>
            <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Motivo de la autorización <?= $motivoObligatorio ? '*' : '(opcional)' ?></label>
            <textarea name="motivo_exceso" id="motivoExceso" rows="3" maxlength="500" <?= $motivoObligatorio ? 'required minlength="' . ControlPresupuestalModel::MOTIVO_MIN . '"' : '' ?> placeholder="Ej.: Urgencia de obra por lluvias, se compensará con el adicional N.° 2…" style="width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; box-sizing:border-box; font-family:inherit; resize:vertical;"></textarea>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" onclick="cerrarModalExceso()" style="padding:9px 16px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border:none; padding:9px 20px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer;"><i class="fas fa-check-circle"></i> Aprobar la orden</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function abrirModalExceso() { const m = document.getElementById('modalExcesoOC'); if (m) { m.style.display = 'flex'; const t = document.getElementById('motivoExceso'); if (t) t.focus(); } }
function cerrarModalExceso() { const m = document.getElementById('modalExcesoOC'); if (m) m.style.display = 'none'; }
function abrirModalRechazo() {
    document.getElementById('modalRechazoOC').style.display = 'flex';
}
function cerrarModalRechazo() {
    document.getElementById('modalRechazoOC').style.display = 'none';
}
</script>
