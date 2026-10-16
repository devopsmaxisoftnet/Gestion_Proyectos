<?php
/**
 * Vista: Almacén (Fase 3) — KPIs, gráficos Chart.js, movimientos, inventario valorizado
 * y órdenes de compra por recepcionar.
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = fn($v) => 'S/ ' . number_format((float)$v, 2);
$num = fn($v) => number_format((float)$v, 2);
$jsonSeguro = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$kpis = $data['kpis'];
$graficos = $data['graficos'];
$movimientos = $data['movimientos'];
$inventario = $data['inventario'];
$porRecepcionar = $data['porRecepcionar'];
$proyectos = $data['proyectos'];
$filtros = $data['filtros'];
$permisos = $data['permisos'];

$kpi = function (string $icono, string $bg, string $fg, string $valor, string $etiqueta, string $sub = '') {
    return '<div class="alm-card alm-kpi"><div class="alm-kpi-icon" style="background:' . $bg . '; color:' . $fg . ';"><i class="fas ' . $icono . '"></i></div>'
         . '<div><div class="alm-kpi-val" style="color:' . $fg . ';">' . $valor . '</div><div class="alm-kpi-lbl">' . $etiqueta . '</div>'
         . ($sub !== '' ? '<div class="alm-sub">' . $sub . '</div>' : '') . '</div></div>';
};

$criticosData = array_map(fn($m) => [
    'etiqueta' => $m['codigo'],
    'nombre' => $m['nombre'],
    'unidad' => $m['unidad_medida'],
    'stock' => (float)$m['stock_actual'],
    'minimo' => (float)$m['stock_minimo'],
], $graficos['critico']);
?>
<?php include __DIR__ . '/_estilos.php'; ?>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <div class="alm-card" style="border-radius:16px; padding:18px 22px; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.02); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg,#14b8a6 0%,#0d9488 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; box-shadow:0 4px 12px rgba(13,148,136,0.25);">
                <i class="fas fa-warehouse"></i>
            </div>
            <div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="background:#f0fdfa; color:#0f766e; border:1px solid #ccfbf1; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; text-transform:uppercase;">Fase 3 · Ejecución</span>
                    <span style="font-size:12px; color:#64748b;">Stock valorizado a costo promedio · consumo imputado al WBS</span>
                </div>
                <h2 style="font-size:19px; font-weight:800; color:#0f172a; margin:4px 0 0;">Almacén e Inventario</h2>
            </div>
        </div>
        <?php if ($permisos['crear']): ?>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="alm-cta teal" href="<?= BASE_URL ?>/almacen/ingreso"><i class="fas fa-truck-loading"></i> Ingreso desde OC</a>
            <a class="alm-cta naranja" href="<?= BASE_URL ?>/almacen/salida"><i class="fas fa-dolly"></i> Vale de Salida a Obra</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($kpis['criticos'] > 0): ?>
    <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:13px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <i class="fas fa-exclamation-triangle"></i>
        <span><strong><?= (int)$kpis['criticos'] ?> material(es) en stock crítico</strong><?= $kpis['agotados'] ? ' · ' . (int)$kpis['agotados'] . ' agotado(s)' : '' ?>. Revisa el inventario y genera las órdenes de compra de reposición.</span>
        <a href="#inventario" onclick="activarTab('inventario'); filtrarSoloCriticos(true);" style="margin-left:auto; color:#b91c1c; font-weight:700;">Ver críticos →</a>
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; margin-bottom:20px;">
        <?= $kpi('fa-boxes', '#f0fdfa', '#0d9488', $money($kpis['valor_inventario']), 'Valor del Inventario', (int)$kpis['items_activos'] . ' materiales activos') ?>
        <?= $kpi('fa-exclamation-triangle', $kpis['criticos'] ? '#fef2f2' : '#f0fdf4', $kpis['criticos'] ? '#dc2626' : '#16a34a', (string)(int)$kpis['criticos'], 'Stock Crítico', $kpis['criticos'] ? (int)$kpis['agotados'] . ' agotado(s)' : 'Sin alertas') ?>
        <?= $kpi('fa-clipboard-check', '#fffbeb', '#d97706', (string)(int)$kpis['por_recepcionar'], 'OC por Recepcionar', 'Aprobadas sin ingresar') ?>
        <?= $kpi('fa-truck-loading', '#eff6ff', '#2563eb', $money($kpis['ingresos_mes']['monto']), 'Ingresos del Mes', (int)$kpis['ingresos_mes']['movs'] . ' recepción(es)') ?>
        <?= $kpi('fa-dolly', '#fff7ed', '#ea580c', $money($kpis['salidas_mes']['monto']), 'Salidas a Obra del Mes', (int)$kpis['salidas_mes']['movs'] . ' vale(s) cargados al WBS') ?>
    </div>

    <!-- Gráficos -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:16px; margin-bottom:20px;">
        <div class="alm-card" style="padding:18px 20px; min-width:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                <h3 style="margin:0; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-chart-bar" style="color:#0d9488;"></i> Movimiento de Almacén (6 meses)</h3>
                <div style="display:flex; gap:6px;">
                    <button type="button" class="alm-toggle activo" data-modo="monto" onclick="cambiarModoMovimientos('monto', this)">S/ Valor</button>
                    <button type="button" class="alm-toggle" data-modo="movs" onclick="cambiarModoMovimientos('movs', this)">N° Movimientos</button>
                </div>
            </div>
            <div style="position:relative; height:270px;"><canvas id="chartMovimientos"></canvas></div>
        </div>
        <div class="alm-card" style="padding:18px 20px; min-width:0;">
            <h3 style="margin:0 0 10px; font-size:14px; font-weight:800; color:#0f172a;"><i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i> Alertas de Stock Crítico</h3>
            <div style="position:relative; height:270px;">
                <canvas id="chartCritico"></canvas>
                <div id="vacioCritico" style="display:none; position:absolute; inset:0; flex-direction:column; align-items:center; justify-content:center; color:#16a34a; font-size:13px; font-weight:600; text-align:center; gap:8px;">
                    <i class="fas fa-check-circle" style="font-size:34px;"></i>
                    Ningún material está bajo su stock mínimo.<br><span style="color:#94a3b8; font-weight:500; font-size:11.5px;">Define el stock mínimo de cada material en Compras › Materiales.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas -->
    <div class="alm-card" style="border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
        <div class="alm-tabs">
            <button type="button" class="alm-tab activo" data-tab="movimientos"><i class="fas fa-exchange-alt"></i> Movimientos <span class="cnt"><?= count($movimientos) ?></span></button>
            <button type="button" class="alm-tab" data-tab="inventario"><i class="fas fa-boxes"></i> Inventario <span class="cnt"><?= count($inventario) ?></span></button>
            <button type="button" class="alm-tab" data-tab="recepcion"><i class="fas fa-clipboard-check"></i> Por recepcionar <span class="cnt"><?= count($porRecepcionar) ?></span></button>
        </div>

        <!-- Movimientos -->
        <div class="alm-panel activo" id="panel-movimientos">
            <form method="GET" action="<?= BASE_URL ?>/almacen" style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin:0;">
                <div style="position:relative; flex:1; max-width:320px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input class="alm-search" type="text" name="busqueda" value="<?= $h($filtros['busqueda']) ?>" placeholder="Vale, guía, OC o proyecto...">
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <select name="tipo" onchange="this.form.submit()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; background:#fff;">
                        <option value="">Ingresos y salidas</option>
                        <option value="ingreso" <?= $filtros['tipo'] === 'ingreso' ? 'selected' : '' ?>>Solo ingresos</option>
                        <option value="salida" <?= $filtros['tipo'] === 'salida' ? 'selected' : '' ?>>Solo salidas</option>
                    </select>
                    <select name="proyecto_id" onchange="this.form.submit()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; background:#fff;">
                        <option value="">Todos los proyectos</option>
                        <?php foreach ($proyectos as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ($filtros['proyecto_id'] == $p['id']) ? 'selected' : '' ?>>[<?= $h($p['codigo']) ?>] <?= $h($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="estado" onchange="this.form.submit()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; background:#fff;">
                        <option value="">Todos los estados</option>
                        <option value="registrado" <?= $filtros['estado'] === 'registrado' ? 'selected' : '' ?>>Registrados</option>
                        <option value="anulado" <?= $filtros['estado'] === 'anulado' ? 'selected' : '' ?>>Anulados</option>
                    </select>
                    <button type="submit" style="background:#f1f5f9; border:1.5px solid #cbd5e1; padding:7px 14px; border-radius:9px; font-size:12.5px; font-weight:700; color:#334155; cursor:pointer;"><i class="fas fa-filter"></i> Filtrar</button>
                </div>
            </form>
            <div style="overflow-x:auto;">
                <table class="alm-tbl">
                    <thead><tr><th style="width:90px;">Tipo</th><th>Vale / Guía</th><th style="width:105px;">Fecha</th><th>Proyecto</th><th>Origen</th><th style="text-align:center;">Líneas</th><th style="text-align:right;">Valor</th><th style="text-align:center;">Estado</th><th style="text-align:right; width:90px;">Acciones</th></tr></thead>
                    <tbody>
                    <?php if (empty($movimientos)): ?>
                        <tr><td colspan="9" style="padding:40px; text-align:center; color:#94a3b8;"><i class="fas fa-inbox" style="font-size:30px; display:block; margin-bottom:8px; color:#cbd5e1;"></i>No hay movimientos con estos filtros.</td></tr>
                    <?php else: foreach ($movimientos as $m): $esIng = $m['tipo_movimiento'] === 'ingreso'; ?>
                        <tr class="<?= $m['estado'] === 'anulado' ? 'anulado' : '' ?>">
                            <td><span class="alm-badge <?= $esIng ? 'info' : 'warn' ?>"><i class="fas <?= $esIng ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i> <?= $esIng ? 'Ingreso' : 'Salida' ?></span></td>
                            <td><a href="<?= BASE_URL ?>/almacen/ver/<?= (int)$m['id'] ?>" style="font-family:monospace; font-weight:800; color:#0d9488; text-decoration:none;"><?= $h($m['guia_remision'] ?: '#' . $m['id']) ?></a></td>
                            <td style="color:#475569;"><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                            <td><strong style="color:#1e293b; font-size:12.5px;"><?= $h($m['proyecto_nombre']) ?></strong><div style="font-family:monospace; font-size:11px; color:#94a3b8;"><?= $h($m['proyecto_codigo']) ?></div></td>
                            <td style="font-size:12.5px; color:#475569;"><?= $m['oc_codigo'] ? '<i class="fas fa-file-invoice" style="color:#ea580c;"></i> ' . $h($m['oc_codigo']) : '<span style="color:#94a3b8;">Consumo a obra</span>' ?></td>
                            <td style="text-align:center; font-weight:700;"><?= (int)$m['lineas'] ?></td>
                            <td style="text-align:right; font-weight:800; color:#0f172a;"><?= $money($m['monto']) ?></td>
                            <td style="text-align:center;"><span class="alm-badge <?= $m['estado'] === 'anulado' ? 'neutral' : 'ok' ?>"><?= $m['estado'] === 'anulado' ? 'Anulado' : 'Registrado' ?></span></td>
                            <td style="text-align:right;"><div style="display:inline-flex; gap:6px;">
                                <a class="alm-btn teal" href="<?= BASE_URL ?>/almacen/ver/<?= (int)$m['id'] ?>" title="Ver detalle"><i class="fas fa-eye"></i></a>
                                <?php if ($permisos['editar'] && $m['estado'] === 'registrado'): ?>
                                <button type="button" class="alm-btn rojo" title="Anular movimiento" onclick="abrirAnular(<?= (int)$m['id'] ?>, <?= $jsonSeguro($m['guia_remision'] ?: '#' . $m['id']) ?>)"><i class="fas fa-ban"></i></button>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventario -->
        <div class="alm-panel" id="panel-inventario">
            <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="position:relative; flex:1; max-width:320px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input class="alm-search" id="busquedaInv" type="text" placeholder="Buscar material..." oninput="filtrarInventario()">
                </div>
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:600; color:#475569; cursor:pointer;">
                    <input type="checkbox" id="soloCriticos" onchange="filtrarInventario()"> Solo stock crítico
                </label>
            </div>
            <div style="overflow-x:auto;">
                <table class="alm-tbl" id="tablaInventario">
                    <thead><tr><th style="width:110px;">Código</th><th>Material</th><th>Und.</th><th style="text-align:right;">Stock</th><th style="text-align:right;">Mínimo</th><th style="min-width:130px;">Cobertura</th><th style="text-align:right;">Costo Prom.</th><th style="text-align:right;">Valor</th><th style="text-align:center;">Estado</th><th style="text-align:right; width:60px;"></th></tr></thead>
                    <tbody>
                    <?php if (empty($inventario)): ?>
                        <tr><td colspan="10" style="padding:34px; text-align:center; color:#94a3b8;">No hay materiales en el catálogo.</td></tr>
                    <?php else: foreach ($inventario as $i):
                        $cob = (float)$i['stock_minimo'] > 0 ? min(100, ((float)$i['stock_actual'] / (float)$i['stock_minimo']) * 100) : 100;
                        $colorBarra = $i['alerta'] === 'ok' ? '#16a34a' : ($i['alerta'] === 'agotado' ? '#dc2626' : '#f59e0b');
                    ?>
                        <tr data-alerta="<?= $h($i['alerta']) ?>" class="<?= $i['activo'] ? '' : 'anulado' ?>">
                            <td><span style="font-family:monospace; font-weight:700; background:#f1f5f9; padding:3px 7px; border-radius:6px; font-size:12px;"><?= $h($i['codigo']) ?></span></td>
                            <td style="font-weight:700; color:#1e293b;"><?= $h($i['nombre']) ?></td>
                            <td style="font-family:monospace; color:#475569;"><?= $h($i['unidad_medida']) ?></td>
                            <td style="text-align:right; font-weight:800; color:<?= $i['alerta'] === 'ok' ? '#0f172a' : '#dc2626' ?>;"><?= $num($i['stock_actual']) ?></td>
                            <td style="text-align:right; color:#64748b;"><?= (float)$i['stock_minimo'] > 0 ? $num($i['stock_minimo']) : '—' ?></td>
                            <td><?php if ((float)$i['stock_minimo'] > 0): ?><div class="alm-bar"><div data-ancho="<?= round($cob) ?>" style="background-color:<?= $colorBarra ?>;"></div></div><?php else: ?><span style="font-size:11px; color:#94a3b8;">Sin mínimo definido</span><?php endif; ?></td>
                            <td style="text-align:right;"><?= $money($i['costo_promedio']) ?></td>
                            <td style="text-align:right; font-weight:800;"><?= $money($i['valor']) ?></td>
                            <td style="text-align:center;"><span class="alm-badge <?= ['ok' => 'ok', 'critico' => 'warn', 'agotado' => 'danger'][$i['alerta']] ?>"><?= ['ok' => 'Normal', 'critico' => 'Crítico', 'agotado' => 'Agotado'][$i['alerta']] ?></span></td>
                            <td style="text-align:right;"><button type="button" class="alm-btn azul" title="Kardex" onclick="verKardex(<?= (int)$i['id'] ?>, <?= $jsonSeguro($i['nombre']) ?>)"><i class="fas fa-history"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Por recepcionar -->
        <div class="alm-panel" id="panel-recepcion">
            <div style="overflow-x:auto;">
                <table class="alm-tbl">
                    <thead><tr><th>Orden de Compra</th><th>Proyecto</th><th>Proveedor</th><th style="width:120px;">Entrega prevista</th><th style="text-align:center;">Materiales pendientes</th><th style="text-align:right; width:110px;"></th></tr></thead>
                    <tbody>
                    <?php if (empty($porRecepcionar)): ?>
                        <tr><td colspan="6" style="padding:40px; text-align:center; color:#94a3b8;"><i class="fas fa-check-circle" style="font-size:30px; display:block; margin-bottom:8px; color:#86efac;"></i>No hay órdenes aprobadas pendientes de recepción.</td></tr>
                    <?php else: foreach ($porRecepcionar as $oc):
                        $atrasada = !empty($oc['fecha_entrega']) && $oc['fecha_entrega'] < date('Y-m-d'); ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/compras/ver/<?= (int)$oc['id'] ?>" style="font-family:monospace; font-weight:800; color:#ea580c; text-decoration:none;"><i class="fas fa-file-invoice"></i> <?= $h($oc['codigo']) ?></a></td>
                            <td><strong style="color:#1e293b; font-size:12.5px;"><?= $h($oc['proyecto_nombre']) ?></strong><div style="font-family:monospace; font-size:11px; color:#94a3b8;"><?= $h($oc['proyecto_codigo']) ?></div></td>
                            <td style="color:#475569; font-size:12.5px;"><?= $h($oc['proveedor']) ?></td>
                            <td style="font-size:12.5px; color:<?= $atrasada ? '#dc2626' : '#475569' ?>; font-weight:<?= $atrasada ? 700 : 500 ?>;"><?= $oc['fecha_entrega'] ? date('d/m/Y', strtotime($oc['fecha_entrega'])) : '—' ?><?= $atrasada ? ' <i class="fas fa-clock" title="Atrasada"></i>' : '' ?></td>
                            <td style="text-align:center;"><span class="alm-badge warn"><?= (int)$oc['items_pendientes'] ?> ítem(s) · <?= $num($oc['unidades_pendientes']) ?> und.</span></td>
                            <td style="text-align:right;"><?php if ($permisos['crear']): ?><a class="alm-cta teal" style="padding:6px 12px; font-size:12px;" href="<?= BASE_URL ?>/almacen/ingreso?oc_id=<?= (int)$oc['id'] ?>"><i class="fas fa-truck-loading"></i> Recepcionar</a><?php endif; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: anular movimiento -->
<div id="modalAnular" class="alm-overlay">
    <div class="alm-modal">
        <div style="background:linear-gradient(135deg,#991b1b 0%,#dc2626 100%); padding:18px 24px; color:#fff; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:16px; font-weight:700;"><i class="fas fa-ban"></i> Anular movimiento <span id="anularCodigo"></span></h3>
            <button type="button" onclick="cerrarModal('modalAnular')" style="background:transparent; border:none; color:rgba(255,255,255,.7); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form id="formAnular" method="POST" style="padding:22px 24px;">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
            <p style="font-size:12.5px; color:#64748b; margin:0 0 14px;">Se revertirá el stock y el costo promedio, y se recalculará el costo real de las actividades afectadas.</p>
            <div class="alm-field"><label>Motivo de la anulación *</label><textarea name="motivo" rows="3" required maxlength="255" placeholder="Ej: error de digitación, material devuelto..."></textarea></div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
                <button type="button" onclick="cerrarModal('modalAnular')" style="padding:9px 16px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:9px 20px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer;">Confirmar anulación</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: kardex -->
<div id="modalKardex" class="alm-overlay">
    <div class="alm-modal" style="max-width:820px;">
        <div style="background:linear-gradient(135deg,#0f766e 0%,#0d9488 100%); padding:18px 24px; color:#fff; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:16px; font-weight:700;"><i class="fas fa-history"></i> Kardex · <span id="kardexNombre"></span></h3>
            <button type="button" onclick="cerrarModal('modalKardex')" style="background:transparent; border:none; color:rgba(255,255,255,.7); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div id="kardexCuerpo" style="overflow-y:auto;"></div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const BASE_URL_JS = <?= $jsonSeguro(BASE_URL) ?>;
const G_MESES = <?= $jsonSeguro($graficos['meses']) ?>;
const G_CRITICO = <?= $jsonSeguro($criticosData) ?>;

const fmt = n => Number(n || 0).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/* ── Pestañas ──────────────────────────────────────────────────────────── */
function activarTab(nombre) {
    if (!document.getElementById('panel-' + nombre)) nombre = 'movimientos';
    document.querySelectorAll('.alm-tab').forEach(t => t.classList.toggle('activo', t.dataset.tab === nombre));
    document.querySelectorAll('.alm-panel').forEach(p => p.classList.toggle('activo', p.id === 'panel-' + nombre));
    animarBarras();
}
document.querySelectorAll('.alm-tab').forEach(t => t.addEventListener('click', () => {
    history.replaceState(null, '', t.dataset.tab === 'movimientos' ? location.pathname + location.search : '#' + t.dataset.tab);
    activarTab(t.dataset.tab);
}));

function animarBarras() {
    requestAnimationFrame(() => document.querySelectorAll('.alm-bar > div[data-ancho]').forEach(b => { b.style.width = b.dataset.ancho + '%'; }));
}

/* ── Inventario ────────────────────────────────────────────────────────── */
function filtrarInventario() {
    const q = document.getElementById('busquedaInv').value.toLowerCase().trim();
    const soloCrit = document.getElementById('soloCriticos').checked;
    document.querySelectorAll('#tablaInventario tbody tr[data-alerta]').forEach(tr => {
        const okQ = !q || tr.textContent.toLowerCase().includes(q);
        const okC = !soloCrit || tr.dataset.alerta !== 'ok';
        tr.style.display = okQ && okC ? '' : 'none';
    });
}
function filtrarSoloCriticos(v) { document.getElementById('soloCriticos').checked = v; filtrarInventario(); }

/* ── Modales ───────────────────────────────────────────────────────────── */
function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }
document.addEventListener('keydown', e => { if (e.key === 'Escape') { cerrarModal('modalAnular'); cerrarModal('modalKardex'); } });

function abrirAnular(id, codigo) {
    document.getElementById('formAnular').action = BASE_URL_JS + '/almacen/anular/' + id;
    document.getElementById('anularCodigo').textContent = codigo;
    document.getElementById('modalAnular').style.display = 'flex';
}

function verKardex(id, nombre) {
    const cuerpo = document.getElementById('kardexCuerpo');
    document.getElementById('kardexNombre').textContent = nombre;
    cuerpo.innerHTML = '<div style="padding:40px; text-align:center; color:#64748b;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    document.getElementById('modalKardex').style.display = 'flex';

    fetch(BASE_URL_JS + '/almacen/kardex/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            if (!d.success || !d.movimientos.length) { cuerpo.innerHTML = '<div style="padding:34px; text-align:center; color:#94a3b8;">Este material aún no tiene movimientos.</div>'; return; }
            cuerpo.innerHTML = `<table class="alm-tbl"><thead><tr><th>Fecha</th><th>Tipo</th><th>Documento</th><th>Origen / Actividad</th><th style="text-align:right;">Cantidad</th><th style="text-align:right;">Costo unit.</th></tr></thead><tbody>` +
                d.movimientos.map(m => {
                    const ing = m.tipo_movimiento === 'ingreso';
                    const anul = m.estado === 'anulado';
                    return `<tr style="${anul ? 'opacity:.5; text-decoration:line-through;' : ''}"><td>${esc(m.fecha)}</td>
                        <td><span class="alm-badge ${ing ? 'info' : 'warn'}">${ing ? 'Ingreso' : 'Salida'}</span></td>
                        <td><a href="${BASE_URL_JS}/almacen/ver/${m.movimiento_id}" style="font-family:monospace; font-weight:700; color:#0d9488;">${esc(m.guia_remision || '#' + m.movimiento_id)}</a></td>
                        <td>${ing ? esc(m.oc_codigo || '') : esc(m.proyecto_codigo) + ' · ' + esc(m.tarea_codigo || '—')}</td>
                        <td style="text-align:right; font-weight:700; color:${ing ? '#15803d' : '#c2410c'};">${ing ? '+' : '−'}${fmt(m.cantidad)}</td>
                        <td style="text-align:right;">${fmt(m.precio_unitario)}</td></tr>`;
                }).join('') + '</tbody></table>';
        })
        .catch(() => { cuerpo.innerHTML = '<div style="padding:30px; color:#b91c1c;">Error de red al cargar el kardex.</div>'; });
}

/* ── Gráficos ──────────────────────────────────────────────────────────── */
let chartMov = null;
function cambiarModoMovimientos(modo, btn) {
    if (!chartMov) return;
    document.querySelectorAll('.alm-toggle[data-modo]').forEach(b => b.classList.toggle('activo', b === btn));
    chartMov.data.datasets[0].data = G_MESES['ingreso_' + modo];
    chartMov.data.datasets[1].data = G_MESES['salida_' + modo];
    chartMov.options.scales.y.ticks.callback = v => modo === 'monto' ? 'S/ ' + Number(v).toLocaleString('es-PE') : v;
    chartMov.options.plugins.tooltip.callbacks.label = c => c.dataset.label + ': ' + (modo === 'monto' ? 'S/ ' + fmt(c.parsed.y) : c.parsed.y + ' movimiento(s)');
    chartMov.update();
}

document.addEventListener('DOMContentLoaded', () => {
    activarTab(location.hash.replace('#', '') || 'movimientos');
    if (typeof Chart === 'undefined') return;

    chartMov = new Chart(document.getElementById('chartMovimientos'), {
        type: 'bar',
        data: {labels: G_MESES.labels, datasets: [
            {label: 'Ingresos', data: G_MESES.ingreso_monto, backgroundColor: '#14b8a6', borderRadius: 6},
            {label: 'Salidas a obra', data: G_MESES.salida_monto, backgroundColor: '#f97316', borderRadius: 6},
        ]},
        options: {
            responsive: true, maintainAspectRatio: false,
            animation: {duration: 1200, easing: 'easeOutQuart'},
            interaction: {mode: 'index', intersect: false},
            plugins: {legend: {position: 'bottom'}, tooltip: {callbacks: {label: c => c.dataset.label + ': S/ ' + fmt(c.parsed.y)}}},
            scales: {y: {beginAtZero: true, ticks: {callback: v => 'S/ ' + Number(v).toLocaleString('es-PE')}}}
        }
    });

    if (!G_CRITICO.length) {
        document.getElementById('chartCritico').style.display = 'none';
        document.getElementById('vacioCritico').style.display = 'flex';
    } else {
        new Chart(document.getElementById('chartCritico'), {
            type: 'bar',
            data: {labels: G_CRITICO.map(c => c.etiqueta), datasets: [
                {label: 'Stock actual', data: G_CRITICO.map(c => c.stock), backgroundColor: G_CRITICO.map(c => c.stock <= 0 ? '#dc2626' : '#f59e0b'), borderRadius: 6},
                {label: 'Stock mínimo', data: G_CRITICO.map(c => c.minimo), backgroundColor: '#cbd5e1', borderRadius: 6},
            ]},
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                animation: {duration: 1200, easing: 'easeOutQuart'},
                plugins: {legend: {position: 'bottom'}, tooltip: {callbacks: {
                    title: it => G_CRITICO[it[0].dataIndex].etiqueta + ' · ' + G_CRITICO[it[0].dataIndex].nombre,
                    label: c => c.dataset.label + ': ' + fmt(c.parsed.x) + ' ' + G_CRITICO[c.dataIndex].unidad
                }}},
                scales: {x: {beginAtZero: true}}
            }
        });
    }
});
</script>
