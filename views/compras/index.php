<?php
/**
 * Vista: Compras y Catálogos (Fase 3: Logística)
 * KPIs, gráficos Chart.js, listado de órdenes y gestión rápida de catálogos
 * (materiales, equipos y proveedores) en pestañas.
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$isAdmin = in_array($userRol, ['superadmin', 'admin']);

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = fn($v, $s = 'S/') => $s . ' ' . number_format((float)$v, 2);
$jsonSeguro = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$ordenes = $data['ordenes'] ?? [];
$resumen = $data['resumen'] ?? [];
$proyectos = $data['proyectos'] ?? [];
$filtros = $data['filtros'] ?? [];
$graficos = $data['graficos'] ?? ['estados' => [], 'top' => []];
$catalogos = $data['catalogos'] ?? ['material' => [], 'equipo' => [], 'proveedor' => []];
$permisos = $data['permisos'] ?? ['crear' => false, 'editar' => false, 'eliminar' => false];

$estadoBadges = [
    'borrador'             => ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => 'Borrador',    'color' => '#94a3b8'],
    'pendiente_aprobacion' => ['bg' => '#fffbeb', 'text' => '#b45309', 'label' => 'Por Aprobar', 'color' => '#f59e0b'],
    'aprobada'             => ['bg' => '#ecfdf5', 'text' => '#047857', 'label' => 'Aprobada',    'color' => '#10b981'],
    'rechazada'            => ['bg' => '#fef2f2', 'text' => '#dc2626', 'label' => 'Rechazada',   'color' => '#ef4444'],
    'recibida'             => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'label' => 'Recibida',    'color' => '#3b82f6'],
    'anulada'              => ['bg' => '#f1f5f9', 'text' => '#64748b', 'label' => 'Anulada',     'color' => '#cbd5e1'],
];

// Dataset del gráfico "Compras por Estado"
$gEstados = ['labels' => [], 'cantidades' => [], 'montos' => [], 'colores' => []];
foreach ($estadoBadges as $k => $b) {
    if (!empty($graficos['estados'][$k])) {
        $gEstados['labels'][] = $b['label'];
        $gEstados['cantidades'][] = $graficos['estados'][$k]['cantidad'];
        $gEstados['montos'][] = $graficos['estados'][$k]['monto'];
        $gEstados['colores'][] = $b['color'];
    }
}

$mostrarProyecto = empty($filtros['proyecto_id']);
$gTop = ['labels' => [], 'nombres' => [], 'gasto' => [], 'presupuesto' => []];
foreach ($graficos['top'] as $t) {
    $gTop['labels'][] = ($mostrarProyecto ? $t['proyecto_codigo'] . ' · ' : '') . $t['codigo'];
    $gTop['nombres'][] = $t['nombre'];
    $gTop['gasto'][] = round($t['gasto'], 2);
    $gTop['presupuesto'][] = round($t['costo_estimado'], 2);
}

$sinAct = (float)($resumen['monto_sin_actividad'] ?? 0);

$kpiCard = function (string $icono, string $bg, string $fg, string $valor, string $etiqueta, string $extra = '') use ($h) {
    return '<div class="cmp-card cmp-kpi"><div class="cmp-kpi-icon" style="background:' . $bg . '; color:' . $fg . ';"><i class="fas ' . $icono . '"></i></div>'
         . '<div><div class="cmp-kpi-val" style="color:' . $fg . ';">' . $valor . '</div>'
         . '<div class="cmp-kpi-lbl">' . $etiqueta . '</div>' . $extra . '</div></div>';
};
?>

<style>
.cmp-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; }
.cmp-kpi { padding:14px 18px; display:flex; align-items:center; gap:14px; transition:transform .2s, box-shadow .2s; }
.cmp-kpi:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,.06); }
.cmp-kpi-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.cmp-kpi-val { font-size:19px; font-weight:800; line-height:1; }
.cmp-kpi-lbl { font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px; }
.cmp-tabs { display:flex; gap:4px; flex-wrap:wrap; padding:0 16px; background:#fafafa; border-bottom:1px solid #e2e8f0; }
.cmp-tab { padding:13px 16px; font-size:13px; font-weight:700; color:#64748b; border:none; background:none; cursor:pointer; border-bottom:2.5px solid transparent; display:inline-flex; align-items:center; gap:7px; }
.cmp-tab:hover { color:#c2410c; }
.cmp-tab.activo { color:#ea580c; border-bottom-color:#ea580c; }
.cmp-tab .cnt { background:#f1f5f9; color:#475569; font-size:10.5px; padding:1px 7px; border-radius:999px; }
.cmp-tab.activo .cnt { background:#ffedd5; color:#c2410c; }
.cmp-panel { display:none; }
.cmp-panel.activo { display:block; }
.cmp-tbl { width:100%; border-collapse:collapse; text-align:left; font-size:13px; }
.cmp-tbl thead tr { background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.cmp-tbl th { padding:12px 14px; }
.cmp-tbl td { padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.cmp-tbl tbody tr:hover { background:#fbfcfd; }
.cmp-tbl tr.inactivo td { opacity:.5; }
.cmp-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; }
.cmp-btn { width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-size:12px; cursor:pointer; border:1px solid #cbd5e1; background:#f1f5f9; color:#334155; }
.cmp-btn.naranja { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.cmp-btn.azul { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
.cmp-btn.rojo { background:#fef2f2; border-color:#fee2e2; color:#dc2626; }
.cmp-btn.verde { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
.cmp-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.cmp-modal { background:#fff; border-radius:18px; width:95%; max-width:560px; box-shadow:0 20px 40px rgba(0,0,0,.2); overflow:hidden; max-height:92vh; display:flex; flex-direction:column; animation:cmpIn .3s ease-out; }
@keyframes cmpIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:none; } }
.cmp-field label { display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px; }
.cmp-field input, .cmp-field textarea, .cmp-field select { width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box; background:#fff; }
.cmp-search { width:100%; padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; outline:none; box-sizing:border-box; }
</style>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <!-- Encabezado -->
    <div class="cmp-card" style="border-radius:16px; padding:18px 22px; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.02); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg, #f97316 0%, #ea580c 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:22px; box-shadow:0 4px 12px rgba(249,115,22,0.25);">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="background:#fff7ed; color:#c2410c; border:1px solid #ffedd5; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; text-transform:uppercase;">Fase 3 · Ejecución</span>
                    <span style="font-size:12px; color:#64748b;">Adquisiciones imputadas a actividades del WBS</span>
                </div>
                <h2 style="font-size:19px; font-weight:800; color:#0f172a; margin:4px 0 0 0;">Órdenes de Compra y Catálogos</h2>
            </div>
        </div>

        <?php if ($permisos['crear']): ?>
        <a href="<?= BASE_URL ?>/compras/crear<?= !empty($filtros['proyecto_id']) ? '?proyecto_id=' . (int)$filtros['proyecto_id'] : '' ?>" style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%); color:#ffffff; text-decoration:none; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:7px; box-shadow:0 4px 12px rgba(249,115,22,0.25);">
            <i class="fas fa-plus-circle"></i> Generar Orden de Compra
        </a>
        <?php endif; ?>
    </div>

    <!-- KPIs -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:20px;">
        <?= $kpiCard('fa-file-invoice', '#eff6ff', '#2563eb', (string)(int)($resumen['total_ordenes'] ?? 0), 'Total Órdenes') ?>
        <?= $kpiCard('fa-clock', '#fffbeb', '#d97706', (string)(int)($resumen['total_pendientes'] ?? 0), 'Pendientes Aprobación',
            '<div style="font-size:11px; color:#94a3b8; margin-top:3px;">' . $money($resumen['monto_pendiente'] ?? 0) . ' en espera</div>') ?>
        <?= $kpiCard('fa-check-double', '#f0fdf4', '#16a34a', (string)(int)($resumen['total_aprobadas'] ?? 0), 'Órdenes Aprobadas') ?>
        <?= $kpiCard('fa-money-bill-wave', '#fdf2f8', '#be185d', $money($resumen['monto_aprobado'] ?? 0), 'Monto Aprobado (con IGV)') ?>
        <?= $kpiCard($sinAct > 0 ? 'fa-unlink' : 'fa-link', $sinAct > 0 ? '#fef2f2' : '#f0fdf4', $sinAct > 0 ? '#dc2626' : '#16a34a',
            $money($sinAct), 'Compras sin actividad',
            '<div style="font-size:11px; color:#94a3b8; margin-top:3px;">' . ($sinAct > 0 ? (int)$resumen['lineas_sin_actividad'] . ' líneas no llegan al WBS' : 'Todo imputado al WBS') . '</div>') ?>
    </div>

    <!-- Gráficos -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:16px; margin-bottom:20px;">
        <div class="cmp-card" style="padding:18px 20px; min-width:0;">
            <h3 style="margin:0 0 10px; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-chart-pie" style="color:#ea580c;"></i> Compras por Estado</h3>
            <div style="position:relative; height:270px;">
                <canvas id="chartEstados"></canvas>
                <div id="vacioEstados" style="display:none; position:absolute; inset:0; align-items:center; justify-content:center; color:#94a3b8; font-size:12.5px; text-align:center;">Aún no hay órdenes de compra registradas.</div>
            </div>
        </div>
        <div class="cmp-card" style="padding:18px 20px; min-width:0;">
            <h3 style="margin:0 0 10px; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-chart-bar" style="color:#2563eb;"></i> Top Actividades con Mayor Gasto en Compras</h3>
            <div style="position:relative; height:270px;">
                <canvas id="chartTop"></canvas>
                <div id="vacioTop" style="display:none; position:absolute; inset:0; align-items:center; justify-content:center; color:#94a3b8; font-size:12.5px; text-align:center; padding:20px;">Ninguna compra aprobada está imputada a actividades todavía.</div>
            </div>
            <div style="font-size:10.5px; color:#94a3b8; margin-top:6px;">Gasto neto sin IGV · color según % del presupuesto de la actividad (verde &lt; 80 %, ámbar 80-100 %, rojo &gt; 100 %).</div>
        </div>
    </div>

    <!-- Pestañas: Órdenes / Catálogos -->
    <div class="cmp-card" style="border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
        <div class="cmp-tabs" id="cmpTabs">
            <button type="button" class="cmp-tab activo" data-tab="ordenes"><i class="fas fa-file-invoice"></i> Órdenes de Compra <span class="cnt"><?= count($ordenes) ?></span></button>
            <button type="button" class="cmp-tab" data-tab="materiales"><i class="fas fa-cubes"></i> Materiales <span class="cnt"><?= count($catalogos['material']) ?></span></button>
            <button type="button" class="cmp-tab" data-tab="equipos"><i class="fas fa-truck-monster"></i> Equipos <span class="cnt"><?= count($catalogos['equipo']) ?></span></button>
            <button type="button" class="cmp-tab" data-tab="proveedores"><i class="fas fa-handshake"></i> Proveedores <span class="cnt"><?= count($catalogos['proveedor']) ?></span></button>
        </div>

        <!-- ═════ Panel: Órdenes ═════ -->
        <div class="cmp-panel activo" id="panel-ordenes">
            <form method="GET" action="<?= BASE_URL ?>/compras" style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin:0;">
                <div style="position:relative; flex:1; max-width:340px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input class="cmp-search" type="text" name="busqueda" value="<?= $h($filtros['busqueda'] ?? '') ?>" placeholder="Código, proveedor o proyecto...">
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <select name="proyecto_id" onchange="this.form.submit()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; outline:none; background:#ffffff;">
                        <option value="">Todos los Proyectos</option>
                        <?php foreach ($proyectos as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (($filtros['proyecto_id'] ?? null) == $p['id']) ? 'selected' : '' ?>>[<?= $h($p['codigo']) ?>] <?= $h($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="estado" onchange="this.form.submit()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; outline:none; background:#ffffff;">
                        <option value="">Todos los Estados</option>
                        <?php foreach ($estadoBadges as $k => $b): ?>
                            <option value="<?= $k ?>" <?= (($filtros['estado'] ?? '') === $k) ? 'selected' : '' ?>><?= $h($b['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="background:#f1f5f9; border:1.5px solid #cbd5e1; padding:7px 14px; border-radius:9px; font-size:12.5px; font-weight:700; color:#334155; cursor:pointer;"><i class="fas fa-filter"></i> Filtrar</button>
                </div>
            </form>

            <div style="overflow-x:auto;">
                <table class="cmp-tbl">
                    <thead>
                        <tr>
                            <th style="width:130px;">Código O/C</th>
                            <th>Proyecto Destino</th>
                            <th>Proveedor</th>
                            <th style="width:110px;">Emisión</th>
                            <th style="width:140px; text-align:right;">Total</th>
                            <th style="width:130px; text-align:center;">Estado</th>
                            <th>Solicitante</th>
                            <th style="width:130px; text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes)): ?>
                        <tr><td colspan="8" style="padding:40px 20px; text-align:center; color:#94a3b8;">
                            <i class="fas fa-shopping-bag" style="font-size:32px; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                            No se encontraron órdenes de compra registradas.
                        </td></tr>
                        <?php else: foreach ($ordenes as $oc):
                            $badge = $estadoBadges[$oc['estado']] ?? ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => ucfirst($oc['estado'])];
                            $editable = $permisos['editar'] && in_array($oc['estado'], CompraModel::ESTADOS_EDITABLES, true);
                            $sinImputar = (int)$oc['items_sin_actividad'];
                        ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/compras/ver/<?= (int)$oc['id'] ?>" style="font-family:monospace; font-weight:800; color:#ea580c; text-decoration:none; font-size:12.5px;">
                                    <i class="fas fa-file-invoice"></i> <?= $h($oc['codigo']) ?>
                                </a>
                            </td>
                            <td>
                                <span style="font-weight:700; color:#1e293b; display:block;"><?= $h($oc['proyecto_nombre']) ?></span>
                                <span style="font-family:monospace; font-size:11px; color:#64748b;"><?= $h($oc['proyecto_codigo']) ?></span>
                            </td>
                            <td>
                                <strong style="color:#334155; font-size:12.5px; display:block;"><?= $h($oc['proveedor_nombre']) ?></strong>
                                <?php if (!empty($oc['proveedor_ruc'])): ?><span style="font-size:11px; color:#94a3b8;">RUC: <?= $h($oc['proveedor_ruc']) ?></span><?php endif; ?>
                            </td>
                            <td style="color:#475569; font-size:12.5px;"><?= date('d/m/Y', strtotime($oc['fecha_emision'])) ?></td>
                            <td style="text-align:right;">
                                <strong style="color:#0f172a; font-size:13.5px;"><?= $h($oc['moneda']) ?> <?= number_format((float)$oc['total'], 2) ?></strong>
                                <div style="font-size:10.5px; color:#94a3b8;"><?= (int)$oc['total_items'] ?> ítems</div>
                                <?php if ($sinImputar > 0 && $oc['estado'] !== 'anulada'): ?>
                                    <div style="font-size:10.5px; color:#d97706; font-weight:700;" title="Líneas sin actividad del WBS"><i class="fas fa-unlink"></i> <?= $sinImputar ?> sin actividad</div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <span class="cmp-badge" style="background:<?= $badge['bg'] ?>; color:<?= $badge['text'] ?>;">
                                    <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span><?= $h($badge['label']) ?>
                                </span>
                            </td>
                            <td style="color:#475569; font-size:12px;"><?= $h($oc['solicitante_nombre'] ?? 'Sistema') ?></td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; gap:6px;">
                                    <a class="cmp-btn naranja" href="<?= BASE_URL ?>/compras/ver/<?= (int)$oc['id'] ?>" title="Ver Comprobante"><i class="fas fa-eye"></i></a>
                                    <?php if ($editable): ?>
                                    <a class="cmp-btn azul" href="<?= BASE_URL ?>/compras/editar/<?= (int)$oc['id'] ?>" title="Editar Orden"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                    <?php if ($isAdmin || $oc['estado'] === 'borrador'): ?>
                                    <a class="cmp-btn rojo" href="<?= BASE_URL ?>/compras/eliminar/<?= (int)$oc['id'] ?>?csrf_token=<?= $h($csrfToken) ?>" onclick="return confirm('¿Confirmas eliminar esta Orden de Compra?')" title="Eliminar Orden"><i class="fas fa-trash-alt"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═════ Panel: Materiales ═════ -->
        <div class="cmp-panel" id="panel-materiales">
            <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="position:relative; flex:1; max-width:340px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input class="cmp-search" type="text" placeholder="Buscar material..." oninput="filtrarCatalogo('tabla-material', this.value)">
                </div>
                <?php if ($permisos['crear']): ?><button type="button" onclick="abrirCatalogo('material')" class="cmp-btn-nuevo" style="background:#ea580c; color:#fff; border:none; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer;"><i class="fas fa-plus"></i> Nuevo Material</button><?php endif; ?>
            </div>
            <div style="overflow-x:auto;">
                <table class="cmp-tbl" id="tabla-material">
                    <thead><tr><th style="width:110px;">Código</th><th>Nombre</th><th>Unidad</th><th>Categoría</th><th style="text-align:right;">P. Referencial</th><th style="text-align:right;">Stock Actual</th><th style="text-align:right;">Stock Mín.</th><th style="text-align:center;">Estado</th><th style="text-align:right; width:100px;">Acciones</th></tr></thead>
                    <tbody>
                    <?php if (empty($catalogos['material'])): ?>
                        <tr><td colspan="9" style="padding:34px; text-align:center; color:#94a3b8;">El catálogo de materiales está vacío.</td></tr>
                    <?php else: foreach ($catalogos['material'] as $m): ?>
                        <tr class="<?= $m['activo'] ? '' : 'inactivo' ?>">
                            <td><span style="font-family:monospace; font-weight:700; background:#f1f5f9; padding:3px 7px; border-radius:6px; font-size:12px;"><?= $h($m['codigo']) ?></span></td>
                            <td style="font-weight:700; color:#1e293b;"><?= $h($m['nombre']) ?></td>
                            <td style="font-family:monospace; color:#475569;"><?= $h($m['unidad_medida']) ?></td>
                            <td style="color:#475569;"><?= $h($m['categoria'] ?: '—') ?></td>
                            <td style="text-align:right; font-weight:700;"><?= number_format((float)$m['precio_referencial'], 2) ?></td>
                            <td style="text-align:right; font-weight:700; color:<?= (float)$m['stock_minimo'] > 0 && (float)$m['stock_actual'] <= (float)$m['stock_minimo'] ? '#dc2626' : ((float)$m['stock_actual'] > 0 ? '#15803d' : '#94a3b8') ?>;"><?= number_format((float)$m['stock_actual'], 2) ?><?php if ((float)$m['stock_minimo'] > 0 && (float)$m['stock_actual'] <= (float)$m['stock_minimo']): ?> <i class="fas fa-exclamation-triangle" title="Stock crítico"></i><?php endif; ?></td>
                            <td style="text-align:right; color:#64748b;"><?= number_format((float)$m['stock_minimo'], 2) ?></td>
                            <td style="text-align:center;"><span class="cmp-badge" style="background:<?= $m['activo'] ? '#dcfce7' : '#f1f5f9' ?>; color:<?= $m['activo'] ? '#15803d' : '#64748b' ?>;"><?= $m['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                            <td style="text-align:right;"><div style="display:inline-flex; gap:6px;">
                                <?php if ($permisos['editar']): ?>
                                <button type="button" class="cmp-btn azul" title="Editar" onclick="abrirCatalogo('material', <?= (int)$m['id'] ?>)"><i class="fas fa-pencil-alt"></i></button>
                                <button type="button" class="cmp-btn <?= $m['activo'] ? 'rojo' : 'verde' ?>" title="<?= $m['activo'] ? 'Desactivar' : 'Activar' ?>" onclick="cambiarEstadoCatalogo('material', <?= (int)$m['id'] ?>, <?= $m['activo'] ? 0 : 1 ?>)"><i class="fas <?= $m['activo'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i></button>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding:10px 20px; font-size:11.5px; color:#94a3b8;">El stock solo se mueve con ingresos y salidas en <a href="<?= BASE_URL ?>/almacen" style="color:#ea580c; font-weight:700;">Almacén</a>; aquí se define su valor inicial al crear el material.</div>
        </div>

        <!-- ═════ Panel: Equipos ═════ -->
        <div class="cmp-panel" id="panel-equipos">
            <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="position:relative; flex:1; max-width:340px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input class="cmp-search" type="text" placeholder="Buscar equipo..." oninput="filtrarCatalogo('tabla-equipo', this.value)">
                </div>
                <?php if ($permisos['crear']): ?><button type="button" onclick="abrirCatalogo('equipo')" style="background:#ea580c; color:#fff; border:none; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer;"><i class="fas fa-plus"></i> Nuevo Equipo</button><?php endif; ?>
            </div>
            <div style="overflow-x:auto;">
                <table class="cmp-tbl" id="tabla-equipo">
                    <thead><tr><th style="width:110px;">Código</th><th>Nombre</th><th>Tipo</th><th style="text-align:right;">Costo / Hora</th><th style="text-align:center;">Estado</th><th style="text-align:right; width:100px;">Acciones</th></tr></thead>
                    <tbody>
                    <?php if (empty($catalogos['equipo'])): ?>
                        <tr><td colspan="6" style="padding:34px; text-align:center; color:#94a3b8;">El catálogo de equipos está vacío.</td></tr>
                    <?php else: foreach ($catalogos['equipo'] as $e): ?>
                        <tr class="<?= $e['activo'] ? '' : 'inactivo' ?>">
                            <td><span style="font-family:monospace; font-weight:700; background:#f1f5f9; padding:3px 7px; border-radius:6px; font-size:12px;"><?= $h($e['codigo']) ?></span></td>
                            <td style="font-weight:700; color:#1e293b;"><?= $h($e['nombre']) ?></td>
                            <td style="color:#475569;"><?= $h($e['tipo'] ?: '—') ?></td>
                            <td style="text-align:right; font-weight:700;"><?= number_format((float)$e['costo_hora'], 2) ?></td>
                            <td style="text-align:center;"><span class="cmp-badge" style="background:<?= $e['activo'] ? '#dcfce7' : '#f1f5f9' ?>; color:<?= $e['activo'] ? '#15803d' : '#64748b' ?>;"><?= $e['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                            <td style="text-align:right;"><div style="display:inline-flex; gap:6px;">
                                <?php if ($permisos['editar']): ?>
                                <button type="button" class="cmp-btn azul" title="Editar" onclick="abrirCatalogo('equipo', <?= (int)$e['id'] ?>)"><i class="fas fa-pencil-alt"></i></button>
                                <button type="button" class="cmp-btn <?= $e['activo'] ? 'rojo' : 'verde' ?>" title="<?= $e['activo'] ? 'Desactivar' : 'Activar' ?>" onclick="cambiarEstadoCatalogo('equipo', <?= (int)$e['id'] ?>, <?= $e['activo'] ? 0 : 1 ?>)"><i class="fas <?= $e['activo'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i></button>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═════ Panel: Proveedores ═════ -->
        <div class="cmp-panel" id="panel-proveedores">
            <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="position:relative; flex:1; max-width:340px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input class="cmp-search" type="text" placeholder="Buscar proveedor o RUC..." oninput="filtrarCatalogo('tabla-proveedor', this.value)">
                </div>
                <?php if ($permisos['crear']): ?><button type="button" onclick="abrirCatalogo('proveedor')" style="background:#ea580c; color:#fff; border:none; padding:8px 16px; border-radius:9px; font-size:12.5px; font-weight:700; cursor:pointer;"><i class="fas fa-plus"></i> Nuevo Proveedor</button><?php endif; ?>
            </div>
            <div style="overflow-x:auto;">
                <table class="cmp-tbl" id="tabla-proveedor">
                    <thead><tr><th>Razón Social</th><th style="width:120px;">RUC</th><th>Contacto</th><th>Especialidad</th><th style="text-align:center;">Calif.</th><th style="text-align:center;">Estado</th><th style="text-align:right; width:100px;">Acciones</th></tr></thead>
                    <tbody>
                    <?php if (empty($catalogos['proveedor'])): ?>
                        <tr><td colspan="7" style="padding:34px; text-align:center; color:#94a3b8;">No hay proveedores registrados.</td></tr>
                    <?php else: foreach ($catalogos['proveedor'] as $pv): ?>
                        <tr class="<?= $pv['activo'] ? '' : 'inactivo' ?>">
                            <td style="font-weight:700; color:#1e293b;"><?= $h($pv['razon_social']) ?></td>
                            <td style="font-family:monospace; color:#475569;"><?= $h($pv['ruc'] ?: '—') ?></td>
                            <td>
                                <div style="color:#334155; font-weight:600; font-size:12.5px;"><?= $h($pv['contacto_nombre'] ?: '—') ?></div>
                                <div style="font-size:11px; color:#94a3b8;"><?= $h(trim(($pv['telefono'] ?? '') . ' ' . ($pv['email'] ?? ''))) ?></div>
                            </td>
                            <td style="color:#475569; font-size:12.5px; max-width:260px;"><?= $h($pv['especialidad'] ?: '—') ?></td>
                            <td style="text-align:center; font-weight:700; color:#b45309;"><i class="fas fa-star" style="font-size:11px;"></i> <?= number_format((float)$pv['calificacion'], 1) ?></td>
                            <td style="text-align:center;"><span class="cmp-badge" style="background:<?= $pv['activo'] ? '#dcfce7' : '#f1f5f9' ?>; color:<?= $pv['activo'] ? '#15803d' : '#64748b' ?>;"><?= $pv['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                            <td style="text-align:right;"><div style="display:inline-flex; gap:6px;">
                                <?php if ($permisos['editar']): ?>
                                <button type="button" class="cmp-btn azul" title="Editar" onclick="abrirCatalogo('proveedor', <?= (int)$pv['id'] ?>)"><i class="fas fa-pencil-alt"></i></button>
                                <button type="button" class="cmp-btn <?= $pv['activo'] ? 'rojo' : 'verde' ?>" title="<?= $pv['activo'] ? 'Desactivar' : 'Activar' ?>" onclick="cambiarEstadoCatalogo('proveedor', <?= (int)$pv['id'] ?>, <?= $pv['activo'] ? 0 : 1 ?>)"><i class="fas <?= $pv['activo'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i></button>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal genérico de catálogos -->
<div id="modalCatalogo" class="cmp-overlay">
    <div class="cmp-modal">
        <div style="background:linear-gradient(135deg, #c2410c 0%, #ea580c 100%); padding:18px 24px; color:#fff; display:flex; justify-content:space-between; align-items:center;">
            <h3 id="catTitulo" style="margin:0; font-size:16px; font-weight:700;">Catálogo</h3>
            <button type="button" onclick="cerrarCatalogo()" style="background:transparent; border:none; color:rgba(255,255,255,.7); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form id="formCatalogo" onsubmit="guardarCatalogo(event)" style="padding:22px 24px; overflow-y:auto;">
            <div id="catCampos" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;"></div>
            <div id="catError" style="display:none; margin-top:14px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; border-radius:9px; padding:9px 12px; font-size:12.5px;"></div>
            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:16px; margin-top:16px; border-top:1px solid #f1f5f9;">
                <button type="button" onclick="cerrarCatalogo()" style="padding:9px 16px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" id="catGuardar" style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%); color:#fff; border:none; padding:9px 20px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer;"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const BASE_URL_JS = <?= $jsonSeguro(BASE_URL) ?>;
const CSRF = <?= $jsonSeguro($csrfToken) ?>;
const G_ESTADOS = <?= $jsonSeguro($gEstados) ?>;
const G_TOP = <?= $jsonSeguro($gTop) ?>;
const CAT_DATA = <?= $jsonSeguro([
    'material'  => array_column($catalogos['material'], null, 'id'),
    'equipo'    => array_column($catalogos['equipo'], null, 'id'),
    'proveedor' => array_column($catalogos['proveedor'], null, 'id'),
]) ?>;

const fmt = n => Number(n || 0).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/* ── Pestañas (estado en el hash de la URL) ────────────────────────────── */
function activarTab(nombre) {
    if (!document.getElementById('panel-' + nombre)) nombre = 'ordenes';
    document.querySelectorAll('.cmp-tab').forEach(t => t.classList.toggle('activo', t.dataset.tab === nombre));
    document.querySelectorAll('.cmp-panel').forEach(p => p.classList.toggle('activo', p.id === 'panel-' + nombre));
}
document.querySelectorAll('.cmp-tab').forEach(t => t.addEventListener('click', () => {
    history.replaceState(null, '', t.dataset.tab === 'ordenes' ? location.pathname + location.search : '#' + t.dataset.tab);
    activarTab(t.dataset.tab);
}));
activarTab(location.hash.replace('#', '') || 'ordenes');

function filtrarCatalogo(tablaId, q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('#' + tablaId + ' tbody tr').forEach(tr => { tr.style.display = !q || tr.textContent.toLowerCase().includes(q) ? '' : 'none'; });
}

/* ── Gráficos ──────────────────────────────────────────────────────────── */
(function initGraficos() {
    if (typeof Chart === 'undefined') return;

    const tot = G_ESTADOS.cantidades.reduce((a, b) => a + b, 0);
    if (tot === 0) {
        document.getElementById('chartEstados').style.display = 'none';
        document.getElementById('vacioEstados').style.display = 'flex';
    } else {
        new Chart(document.getElementById('chartEstados'), {
            type: 'doughnut',
            data: {labels: G_ESTADOS.labels, datasets: [{data: G_ESTADOS.cantidades, backgroundColor: G_ESTADOS.colores, borderWidth: 2, hoverOffset: 10}]},
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '60%',
                animation: {animateRotate: true, animateScale: true, duration: 1300},
                plugins: {
                    legend: {position: 'bottom'},
                    tooltip: {callbacks: {label: c => `${c.label}: ${c.parsed} orden(es) · S/ ${fmt(G_ESTADOS.montos[c.dataIndex])}`}}
                }
            }
        });
    }

    if (!G_TOP.labels.length) {
        document.getElementById('chartTop').style.display = 'none';
        document.getElementById('vacioTop').style.display = 'flex';
    } else {
        // Semáforo contra el presupuesto de la actividad
        const colores = G_TOP.gasto.map((g, i) => {
            const p = G_TOP.presupuesto[i];
            if (p <= 0) return '#3b82f6';
            const pct = g / p * 100;
            return pct > 100 ? '#dc2626' : (pct >= 80 ? '#f59e0b' : '#16a34a');
        });
        new Chart(document.getElementById('chartTop'), {
            type: 'bar',
            data: {labels: G_TOP.labels, datasets: [{label: 'Gasto en compras', data: G_TOP.gasto, backgroundColor: colores, borderRadius: 6}]},
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                animation: {duration: 1200, easing: 'easeOutQuart'},
                plugins: {
                    legend: {display: false},
                    tooltip: {callbacks: {
                        title: it => G_TOP.labels[it[0].dataIndex] + ' · ' + G_TOP.nombres[it[0].dataIndex],
                        label: c => 'Gasto: S/ ' + fmt(c.parsed.x),
                        afterLabel: c => {
                            const p = G_TOP.presupuesto[c.dataIndex];
                            return p > 0 ? ['Presupuesto: S/ ' + fmt(p), 'Consumo: ' + (c.parsed.x / p * 100).toFixed(1) + '%'] : 'Sin presupuesto asignado';
                        }
                    }}
                },
                scales: {x: {beginAtZero: true, ticks: {callback: v => 'S/ ' + Number(v).toLocaleString('es-PE')}}}
            }
        });
    }
})();

/* ── CRUD rápido de catálogos ──────────────────────────────────────────── */
const UNIDADES = ['UND', 'BOLSA', 'KG', 'M', 'M2', 'M3', 'GLN', 'VAR', 'PZA', 'GLB', 'HR', 'DIA'];
const CATALOGOS = {
    material: {titulo: 'Material', campos: [
        {n: 'codigo', l: 'Código', ph: 'Automático (MAT-0001)'},
        {n: 'unidad_medida', l: 'Unidad de medida', list: UNIDADES, def: 'UND'},
        {n: 'nombre', l: 'Nombre *', req: true, full: true},
        {n: 'categoria', l: 'Categoría', full: true},
        {n: 'precio_referencial', l: 'Precio referencial (S/)', t: 'number', step: '0.01', def: '0.00'},
        {n: 'stock_actual', l: 'Stock inicial', t: 'number', step: '0.01', def: '0.00', soloCrear: true},
        {n: 'stock_minimo', l: 'Stock mínimo (alerta)', t: 'number', step: '0.01', def: '0.00'},
    ]},
    equipo: {titulo: 'Equipo', campos: [
        {n: 'codigo', l: 'Código', ph: 'Automático (EQ-0001)'},
        {n: 'tipo', l: 'Tipo', ph: 'Pesado, Liviano, Herramientas'},
        {n: 'nombre', l: 'Nombre *', req: true, full: true},
        {n: 'costo_hora', l: 'Costo por hora (S/)', t: 'number', step: '0.01', def: '0.00'},
    ]},
    proveedor: {titulo: 'Proveedor', campos: [
        {n: 'razon_social', l: 'Razón social *', req: true, full: true},
        {n: 'ruc', l: 'RUC (11 dígitos)', pattern: '\\d{11}', max: 11},
        {n: 'calificacion', l: 'Calificación (0-5)', t: 'number', step: '0.1', min: 0, max: 5, def: '5.0'},
        {n: 'contacto_nombre', l: 'Contacto'},
        {n: 'telefono', l: 'Teléfono'},
        {n: 'email', l: 'Correo electrónico', t: 'email', full: true},
        {n: 'direccion', l: 'Dirección', full: true},
        {n: 'especialidad', l: 'Especialidad', full: true},
    ]},
};
let catTipoActual = null, catIdActual = 0;

function abrirCatalogo(tipo, id) {
    const cfg = CATALOGOS[tipo];
    const reg = id ? CAT_DATA[tipo][id] : null;
    catTipoActual = tipo; catIdActual = id || 0;
    document.getElementById('catTitulo').textContent = (id ? 'Editar ' : 'Nuevo ') + cfg.titulo;
    document.getElementById('catError').style.display = 'none';

    document.getElementById('catCampos').innerHTML = cfg.campos.map(c => {
        const v = reg ? (reg[c.n] ?? '') : (c.def ?? '');
        const attrs = `${c.soloCrear && id ? 'disabled' : ''} name="${c.n}" value="${esc(v)}" ${c.req ? 'required' : ''} ${c.step ? `step="${c.step}"` : ''} ${c.min !== undefined ? `min="${c.min}"` : (c.t === 'number' ? 'min="0"' : '')} ${c.max !== undefined ? (c.t === 'number' ? `max="${c.max}"` : `maxlength="${c.max}"`) : ''} ${c.pattern ? `pattern="${c.pattern}"` : ''} ${c.ph ? `placeholder="${esc(c.ph)}"` : ''} ${c.list ? `list="dl-${c.n}"` : ''}`;
        const dl = c.list ? `<datalist id="dl-${c.n}">${c.list.map(u => `<option value="${u}">`).join('')}</datalist>` : '';
        return `<div class="cmp-field" style="${c.full ? 'grid-column:1 / -1;' : ''}"><label>${esc(c.l)}</label><input type="${c.t || 'text'}" ${attrs}>${dl}</div>`;
    }).join('');

    document.getElementById('modalCatalogo').style.display = 'flex';
}

function cerrarCatalogo() { document.getElementById('modalCatalogo').style.display = 'none'; }

function mostrarErrorCatalogo(msg) {
    const e = document.getElementById('catError'); e.textContent = msg; e.style.display = 'block';
}

function guardarCatalogo(ev) {
    ev.preventDefault();
    const btn = document.getElementById('catGuardar');
    const fd = new FormData(document.getElementById('formCatalogo'));
    fd.append('tipo', catTipoActual); fd.append('id', catIdActual); fd.append('csrf_token', CSRF);
    btn.disabled = true;

    fetch(BASE_URL_JS + '/catalogos/guardar', {method: 'POST', body: fd, headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            if (!d.success) { mostrarErrorCatalogo(d.message || 'No se pudo guardar.'); btn.disabled = false; return; }
            const hash = {material: 'materiales', equipo: 'equipos', proveedor: 'proveedores'}[catTipoActual];
            location.hash = hash; location.reload();
        })
        .catch(() => { mostrarErrorCatalogo('Error de red al guardar.'); btn.disabled = false; });
}

function cambiarEstadoCatalogo(tipo, id, activo) {
    if (!activo && !confirm('¿Desactivar este registro? Dejará de aparecer en los selectores, pero se conserva en las órdenes existentes.')) return;
    const fd = new FormData();
    fd.append('tipo', tipo); fd.append('id', id); fd.append('activo', activo); fd.append('csrf_token', CSRF);
    fetch(BASE_URL_JS + '/catalogos/estado', {method: 'POST', body: fd, headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert(d.message || 'No se pudo cambiar el estado.'); })
        .catch(() => alert('Error de red.'));
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarCatalogo(); });
</script>
