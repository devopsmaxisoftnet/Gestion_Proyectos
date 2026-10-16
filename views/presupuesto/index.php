<?php
/**
 * Vista: Control Presupuestal por actividad (Fase 2)
 * Disponibilidad presupuestal, compromisos de compra y bitácora de excesos autorizados.
 */
include __DIR__ . '/../dashboard/_fase_ui.php';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$migrado = (bool)($data['migrado'] ?? false);
$proyectos = $data['proyectos'] ?? [];
$proyectoId = (int)($data['proyectoId'] ?? 0);
$proyectoActual = $data['proyectoActual'] ?? null;
$est = $data['estado'] ?? null;
$excesos = $data['excesos'] ?? [];
$resExc = $data['resumenExcesos'] ?? ['n' => 0, 'total' => 0];
$puedeConfigurar = (bool)($data['puedeConfigurar'] ?? false);

$MODOS = [
    'informar' => ['Solo informar', 'fa-eye', '#64748b', 'Los tableros muestran el estado, pero no se avisa ni se registra nada al operar.'],
    'advertir' => ['Advertir y registrar', 'fa-bell', '#d97706', 'Al aprobar una OC o sacar material del almacén se avisa del exceso, la operación continúa y queda en la bitácora.'],
    'bloquear' => ['Bloquear con autorización', 'fa-lock', '#dc2626', 'Una operación que excede el presupuesto se detiene hasta que un usuario facultado la autorice con un motivo.'],
];

if ($est) {
    $cfg = $est['config']; $tot = $est['totales']; $filas = $est['filas']; $conteo = $est['conteo'];
    $usado = $tot['real'] + $tot['pendiente'];
    $pctUsado = $tot['presupuesto'] > 0 ? round($usado / $tot['presupuesto'] * 100, 1) : 0.0;
    $tonoUso = $pctUsado > 100 ? 'danger' : ($pctUsado >= $cfg['umbral'] ? 'warn' : 'ok');
    $modo = $MODOS[$cfg['modo']];

    // Actividades hoja que requieren atención (peor primero)
    $atencion = array_values(array_filter($filas, fn($f) => $f['es_hoja'] && in_array($f['estado'], ['excedido', 'sin_presupuesto', 'alerta'], true)));
    usort($atencion, fn($a, $b) => [$b['estado'] !== 'alerta', $b['pct'] ?? 999] <=> [$a['estado'] !== 'alerta', $a['pct'] ?? 999]);
    $atencion = array_slice($atencion, 0, 6);
    $nRiesgo = $conteo['excedido'] + $conteo['sin_presupuesto'];

    $graf = [
        'principales' => array_values(array_filter($filas, fn($f) => $f['nivel'] === 0)),
        'hojas' => array_values(array_filter($filas, fn($f) => $f['es_hoja'])),
    ];
    $mini = fn($arr) => array_map(fn($f) => [
        'codigo' => $f['codigo'], 'nombre' => $f['nombre'], 'presupuesto' => $f['presupuesto'], 'ejecutado' => $f['ejecutado'],
        'por_recibir' => $f['por_recibir'], 'pendiente' => $f['pendiente'], 'disponible' => $f['disponible'],
    ], $arr);
    $grafJson = ['principales' => $mini($graf['principales']), 'hojas' => $mini($graf['hojas'])];
}

/** Barra apilada de una actividad: ejecutado | por recibir | en aprobación, con marca del 100 % del presupuesto */
function cp_barra(array $f): string {
    if ($f['presupuesto'] <= 0) {
        return $f['pendiente'] + $f['real'] > 0
            ? '<div class="cp-stack sin"><span>Sin presupuesto asignado</span></div>'
            : '<div class="cp-stack vacio"></div>';
    }
    $usado = $f['pct_ejec'] + $f['pct_rec'] + $f['pct_pend'];
    $escala = max(100.0, $usado);                          // si se excede, la marca del 100 % se desplaza a la izquierda
    $w = fn($p) => round($p / $escala * 100, 2);
    return '<div class="cp-stack">'
         . '<i class="s1" data-w="' . $w($f['pct_ejec']) . '" title="Ejecutado"></i>'
         . '<i class="s2" data-w="' . $w($f['pct_rec']) . '" title="Por recibir"></i>'
         . '<i class="s3" data-w="' . $w($f['pct_pend']) . '" title="En aprobación"></i>'
         . '<b class="cp-tick" style="left:' . $w(100) . '%"></b></div>';
}
?>
<style>
.cp-sel { display:flex; align-items:center; gap:12px; flex-wrap:wrap; background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:12px 18px; margin-bottom:18px; }
.cp-sel select { padding:9px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; font-weight:600; color:#1e293b; background:#fff; outline:none; min-width:320px; max-width:100%; }
.cp-sel .cp-lbl { font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.5px; }
.cp-g21 { display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1fr); gap:16px; margin-bottom:22px; }
@media (max-width:1000px) { .cp-g21 { grid-template-columns:1fr; } }
.cp-aviso { background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:13px; }

.cp-stack { position:relative; display:flex; height:12px; background:#e2e8f0; border-radius:999px; overflow:hidden; min-width:150px; }
.cp-stack i { display:block; height:100%; width:0; transition:width 1.3s cubic-bezier(.22,.8,.3,1);
    background-image:linear-gradient(45deg,rgba(255,255,255,.28) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.28) 50%,rgba(255,255,255,.28) 75%,transparent 75%,transparent);
    background-size:1rem 1rem; animation:fdStripes 1s linear infinite; }
.cp-stack i.s1 { background-color:#3b82f6; } .cp-stack i.s2 { background-color:#8b5cf6; } .cp-stack i.s3 { background-color:#f59e0b; }
.cp-tick { position:absolute; top:-2px; bottom:-2px; width:2px; background:#0f172a; opacity:.75; }
.cp-stack.sin { background:#fee2e2; color:#b91c1c; font-size:10.5px; font-weight:700; align-items:center; justify-content:center; height:20px; }
.cp-stack.vacio { opacity:.5; }
.cp-leyenda { display:flex; gap:16px; flex-wrap:wrap; font-size:11.5px; color:#64748b; font-weight:600; }
.cp-leyenda span::before { content:''; display:inline-block; width:10px; height:10px; border-radius:3px; margin-right:6px; vertical-align:-1px; background:var(--c); }

.cp-filtros { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.cp-chip { padding:6px 13px; border-radius:999px; border:1px solid #cbd5e1; background:#fff; font-size:12px; font-weight:700; color:#475569; cursor:pointer; transition:all .15s; }
.cp-chip:hover { border-color:#6366f1; color:#4338ca; }
.cp-chip.activo { background:#4338ca; border-color:#4338ca; color:#fff; }
.cp-chip small { opacity:.75; margin-left:3px; }
.cp-buscar { padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; outline:none; width:250px; }
.cp-row.es-padre td { background:#f8fafc; font-weight:700; }
.cp-cod { font-family:monospace; font-weight:700; background:#f1f5f9; color:#1e293b; padding:2px 7px; border-radius:6px; font-size:11.5px; border:1px solid #e2e8f0; white-space:nowrap; }
.cp-neg { color:#b91c1c; font-weight:800; }

.cp-atn { display:flex; flex-direction:column; gap:12px; }
.cp-atn-item { padding:12px 14px; border:1px solid #e2e8f0; border-radius:12px; transition:transform .2s, box-shadow .2s; }
.cp-atn-item:hover { transform:translateX(3px); box-shadow:0 6px 16px -8px rgba(15,23,42,.2); }
.cp-atn-item.danger { border-left:4px solid #dc2626; } .cp-atn-item.warn { border-left:4px solid #f59e0b; }

.cp-modo { display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0; }
.cp-modo-ico { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff; flex-shrink:0; }
.cp-capas { margin-top:14px; display:grid; gap:7px; font-size:12.5px; color:#475569; }
.cp-capas div { display:flex; align-items:center; gap:9px; }
.cp-capas i.dot { width:11px; height:11px; border-radius:4px; flex-shrink:0; }
.cp-formula { margin-top:12px; padding:10px 14px; border-radius:11px; background:#eef2ff; color:#3730a3; font-size:12.5px; font-weight:700; text-align:center; }

.cp-timeline { display:flex; flex-direction:column; }
.cp-tl { display:flex; gap:12px; padding:11px 0; border-bottom:1px solid #f1f5f9; }
.cp-tl:last-child { border-bottom:none; }
.cp-tl-dot { width:34px; height:34px; border-radius:10px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
.cp-motivo { font-size:12px; color:#475569; margin-top:3px; font-style:italic; }

.cp-ov { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.cp-md { background:#fff; border-radius:20px; width:95%; max-width:640px; box-shadow:0 24px 48px rgba(0,0,0,.25); overflow:hidden; max-height:92vh; display:flex; flex-direction:column; animation:cpIn .3s ease-out; }
@keyframes cpIn { from { opacity:0; transform:translateY(20px) scale(.98); } to { opacity:1; transform:none; } }
.cp-md-head { background:linear-gradient(120deg,#4338ca,#0ea5e9); color:#fff; padding:18px 24px; display:flex; justify-content:space-between; align-items:center; }
.cp-opt { display:flex; gap:14px; align-items:flex-start; padding:13px 15px; border:2px solid #e2e8f0; border-radius:14px; cursor:pointer; transition:all .15s; margin-bottom:10px; }
.cp-opt:hover { border-color:#a5b4fc; }
.cp-opt.sel { border-color:#4338ca; background:#eef2ff; box-shadow:0 6px 16px -8px #4338ca; }
.cp-opt input { margin-top:4px; accent-color:#4338ca; }
.cp-opt .ico { width:38px; height:38px; border-radius:11px; display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; }
.cp-fld label { display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px; }
.cp-fld input { width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box; }
.cp-btn { color:#fff; border:none; padding:9px 20px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; background:linear-gradient(135deg,#4f46e5,#4338ca); box-shadow:0 4px 12px rgba(67,56,202,.28); display:inline-flex; align-items:center; gap:7px; }
.cp-btn.sec { background:#fff; color:#475569; border:1px solid #cbd5e1; box-shadow:none; }
</style>

<div class="fd-wrap" style="padding:16px 24px 40px;">

<?php if (!$migrado): ?>
    <div class="cp-aviso"><i class="fas fa-exclamation-triangle"></i> <strong>Falta aplicar la migración del control presupuestal</strong> (tablas <code>presupuesto_control</code> y <code>presupuesto_excesos</code>). Ejecuta <code>database/upgrade.php</code> desde la consola. Mientras tanto se usa el modo «Advertir» sin bitácora.</div>
<?php endif; ?>

<form method="GET" action="<?= BASE_URL ?>/presupuesto" class="cp-sel fd-rise">
    <span class="cp-lbl"><i class="fas fa-project-diagram"></i> Proyecto</span>
    <select name="proyecto_id" onchange="this.form.submit()">
        <?php if (empty($proyectos)): ?><option value="">No hay proyectos registrados</option><?php endif; ?>
        <?php foreach ($proyectos as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $proyectoId ? 'selected' : '' ?>>[<?= fd_h($p['codigo']) ?>] <?= fd_h($p['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if (!$est): ?>
    <div class="fd-card" style="text-align:center; padding:60px 20px;">
        <i class="fas fa-balance-scale" style="font-size:42px; color:#cbd5e1; display:block; margin-bottom:12px;"></i>
        <h3 style="margin:0 0 6px; color:#1e293b;">Selecciona un proyecto</h3>
        <p style="margin:0; color:#64748b; font-size:13px;">Aquí verás el presupuesto de cada actividad frente a lo ejecutado y a lo ya comprometido en compras.</p>
    </div>
<?php else:
    $acciones = '';
    if (PermisosHelper::tieneAcceso('tareas')) { $acciones .= '<a href="' . BASE_URL . '/tareas?proyecto_id=' . $proyectoId . '"><i class="fas fa-sitemap"></i> WBS / Insumos</a>'; }
    if (PermisosHelper::tieneAcceso('compras')) { $acciones .= '<a href="' . BASE_URL . '/compras?proyecto_id=' . $proyectoId . '"><i class="fas fa-file-invoice-dollar"></i> Compras</a>'; }
    if (PermisosHelper::tieneAcceso('almacenes')) { $acciones .= '<a href="' . BASE_URL . '/almacen?proyecto_id=' . $proyectoId . '"><i class="fas fa-warehouse"></i> Almacén</a>'; }
    if ($puedeConfigurar && $migrado) { $acciones .= '<a href="#" onclick="abrirConfig(); return false;"><i class="fas fa-sliders-h"></i> Configurar control</a>'; }
?>

<?= fd_hero([
    'g1' => '#4338ca', 'g2' => '#0ea5e9', 'icono' => 'fa-balance-scale', 'badge' => 'Fase 2 · Control presupuestal',
    'titulo' => $proyectoActual['nombre'],
    'subtitulo' => 'Lo que ya se ejecutó, lo que está comprometido en órdenes de compra y lo que realmente queda disponible en cada actividad.',
    'acciones' => $acciones,
    'cifra_label' => $tot['disponible'] >= 0 ? 'Disponible del presupuesto' : 'Sobregiro del presupuesto', 'cifra' => $tot['disponible'],
    'cifra_sub' => 'Presupuesto ' . fd_money($tot['presupuesto']) . ' · usado ' . fd_money($usado),
    'anillo' => min(100, $pctUsado), 'anillo_label' => 'Presupuesto usado',
]) ?>

<div class="fd-grid-kpi n6">
    <?= fd_kpi('fa-wallet', '#eef2ff', '#4338ca', 'Presupuesto', ['to' => $tot['presupuesto'], 'prefix' => 'S/ ', 'sub' => count($graf['hojas']) . ' actividades hoja'], 0) ?>
    <?= fd_kpi('fa-check-double', '#eff6ff', '#2563eb', 'Ejecutado', [
        'to' => $tot['ejecutado'], 'prefix' => 'S/ ', 'bar' => $tot['presupuesto'] > 0 ? $tot['ejecutado'] / $tot['presupuesto'] * 100 : 0, 'barClass' => 'info',
        'sub' => 'Salidas de almacén, tareo y subcontratos'], 1) ?>
    <?= fd_kpi('fa-truck-loading', '#f5f3ff', '#7c3aed', 'Por recibir (OC aprobadas)', [
        'to' => $tot['por_recibir'], 'prefix' => 'S/ ', 'sub' => 'Compromiso firme · ya está en el costo real'], 2) ?>
    <?= fd_kpi('fa-hourglass-half', '#fffbeb', '#d97706', 'En aprobación (OC)', [
        'to' => $tot['pendiente'], 'prefix' => 'S/ ', 'tono' => $tot['pendiente'] > 0 ? 'warn' : '', 'sub' => 'Aún no suma al costo real'], 3) ?>
    <?= fd_kpi($tot['disponible'] >= 0 ? 'fa-piggy-bank' : 'fa-exclamation-circle', $tonoUso === 'danger' ? '#fef2f2' : '#f0fdf4', $tonoUso === 'danger' ? '#dc2626' : '#16a34a', $tot['disponible'] >= 0 ? 'Disponible' : 'Sobregiro', [
        'to' => abs($tot['disponible']), 'prefix' => ($tot['disponible'] < 0 ? '− ' : '') . 'S/ ', 'tono' => $tonoUso === 'ok' ? 'ok' : $tonoUso,
        'bar' => min(100, $pctUsado), 'barClass' => $tonoUso, 'sub' => $pctUsado . '% del presupuesto usado'], 4) ?>
    <?= fd_kpi('fa-user-shield', $resExc['n'] ? '#fef2f2' : '#f8fafc', $resExc['n'] ? '#dc2626' : '#94a3b8', 'Excesos autorizados', [
        'to' => $resExc['n'], 'tono' => $resExc['n'] ? 'danger' : '', 'sub' => $resExc['n'] ? fd_money($resExc['total']) . ' sobre presupuesto en total' : 'Ninguna operación excedió el presupuesto'], 5) ?>
</div>

<div class="cp-g21">
    <div class="fd-card fd-rise" style="--d:.1s">
        <div class="fd-card-head">
            <div>
                <h3 class="fd-card-title"><i class="fas fa-layer-group" style="color:#4338ca;"></i> Presupuesto vs. compromisos por actividad</h3>
                <p class="fd-card-sub">La marca negra es el presupuesto · si las barras la superan, la actividad está sobregirada</p>
            </div>
            <div style="display:flex; gap:6px;">
                <button type="button" class="cp-chip activo" data-vista="principales" onclick="cambiarVista('principales', this)">Principales</button>
                <button type="button" class="cp-chip" data-vista="hojas" onclick="cambiarVista('hojas', this)">Hojas</button>
            </div>
        </div>
        <div class="fd-chart alto"><canvas id="cCompromisos"></canvas><div class="fd-empty"><i class="fas fa-sitemap"></i>Este proyecto aún no tiene actividades en el WBS.</div></div>
    </div>

    <div class="fd-card fd-rise" style="--d:.15s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-chart-pie" style="color:#7c3aed;"></i> ¿En qué está el presupuesto?</h3>
            <p class="fd-card-sub">Ejecutado, comprometido y disponible</p>
        </div></div>
        <div class="fd-chart"><canvas id="cUso"></canvas><div class="fd-empty"><i class="fas fa-wallet"></i>Aún no hay presupuesto cargado en el WBS.</div></div>
    </div>
</div>

<div class="fd-grid-2">
    <div class="fd-card fd-rise" style="--d:.2s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i> Requieren atención
                <?= $nRiesgo ? fd_badge('danger', $nRiesgo . ' en riesgo') : '' ?></h3>
            <p class="fd-card-sub">Actividades hoja cerca o por encima de su presupuesto</p>
        </div></div>
        <?php if (empty($atencion)): ?>
            <div class="fd-ok-box"><i class="fas fa-check-circle"></i>Todas las actividades están dentro de su presupuesto.</div>
        <?php else: ?>
            <div class="cp-atn">
            <?php foreach ($atencion as $a): $c = $a['clase']; ?>
                <div class="cp-atn-item <?= $c ?>">
                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:7px;">
                        <div style="min-width:0;"><span class="cp-cod"><?= fd_h($a['codigo']) ?></span> <strong style="font-size:13px; color:#1e293b;"><?= fd_h($a['nombre']) ?></strong></div>
                        <?= fd_badge($c, $a['pct'] === null ? 'Sin presupuesto' : $a['pct'] . '%') ?>
                    </div>
                    <div class="fd-bar"><div class="<?= $c ?>" data-w="<?= min(100, (float)($a['pct'] ?? 100)) ?>"></div></div>
                    <div style="display:flex; justify-content:space-between; font-size:11.5px; color:#64748b; margin-top:5px;">
                        <span>Presup. <?= fd_money($a['presupuesto']) ?> · usado <?= fd_money($a['real'] + $a['pendiente']) ?></span>
                        <span class="<?= $a['disponible'] < 0 ? 'cp-neg' : '' ?>"><?= $a['disponible'] < 0 ? 'Sobregiro ' : 'Disponible ' ?><?= fd_money(abs($a['disponible'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="fd-card fd-rise" style="--d:.25s">
        <div class="fd-card-head"><div>
            <h3 class="fd-card-title"><i class="fas fa-shield-alt" style="color:#4338ca;"></i> Modo de control del proyecto</h3>
            <p class="fd-card-sub">Qué ocurre cuando una operación excede el presupuesto</p>
        </div>
        <?php if ($puedeConfigurar && $migrado): ?><button type="button" class="cp-chip" onclick="abrirConfig()"><i class="fas fa-sliders-h"></i> Cambiar</button><?php endif; ?>
        </div>
        <div class="cp-modo">
            <div class="cp-modo-ico" style="background:<?= $modo[2] ?>;"><i class="fas <?= $modo[1] ?>"></i></div>
            <div>
                <div style="font-size:15px; font-weight:800; color:#0f172a;"><?= fd_h($modo[0]) ?></div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;"><?= fd_h($modo[3]) ?></div>
                <div style="font-size:11.5px; color:#94a3b8; margin-top:5px;">Tolerancia <strong><?= rtrim(rtrim(number_format($cfg['tolerancia'], 2), '0'), '.') ?>%</strong> · alerta desde <strong><?= rtrim(rtrim(number_format($cfg['umbral'], 2), '0'), '.') ?>%</strong><?= $cfg['configurado'] ? '' : ' · valores por defecto' ?></div>
            </div>
        </div>
        <div class="cp-capas">
            <div><i class="dot" style="background:#3b82f6;"></i><span><strong>Ejecutado:</strong> consumo ya realizado (almacén, tareo, subcontratos)</span></div>
            <div><i class="dot" style="background:#8b5cf6;"></i><span><strong>Por recibir:</strong> OC aprobadas que aún no llegan a almacén</span></div>
            <div><i class="dot" style="background:#f59e0b;"></i><span><strong>En aprobación:</strong> OC pendientes de aprobar</span></div>
        </div>
        <div class="cp-formula">Disponible = Presupuesto − Costo real − OC en aprobación</div>
    </div>
</div>

<div class="fd-card fd-rise" style="--d:.3s; margin-bottom:22px;">
    <div class="fd-card-head">
        <div>
            <h3 class="fd-card-title"><i class="fas fa-list-ul" style="color:#4338ca;"></i> Control por actividad</h3>
            <p class="fd-card-sub">Cada barra suma ejecutado, por recibir y en aprobación frente al presupuesto</p>
        </div>
        <div class="cp-leyenda"><span style="--c:#3b82f6">Ejecutado</span><span style="--c:#8b5cf6">Por recibir</span><span style="--c:#f59e0b">En aprobación</span></div>
    </div>
    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:12px; align-items:center;">
        <div class="cp-filtros" id="cpFiltros">
            <button type="button" class="cp-chip activo" data-f="todas">Todas <small><?= count($filas) ?></small></button>
            <button type="button" class="cp-chip" data-f="alerta">En alerta <small><?= $conteo['alerta'] ?></small></button>
            <button type="button" class="cp-chip" data-f="exceso">Excedidas <small><?= $nRiesgo ?></small></button>
            <button type="button" class="cp-chip" data-f="comprometidas">Con compromisos <small><?= count(array_filter($filas, fn($f) => $f['por_recibir'] + $f['pendiente'] > 0)) ?></small></button>
            <button type="button" class="cp-chip" data-f="hojas">Solo hojas <small><?= count($graf['hojas']) ?></small></button>
        </div>
        <div style="position:relative;"><i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
            <input type="text" id="cpBuscar" class="cp-buscar" placeholder="Buscar actividad…"></div>
    </div>
    <div style="overflow-x:auto;">
        <table class="fd-table" id="cpTabla">
            <thead><tr>
                <th>Actividad</th><th class="num">Presupuesto</th><th class="num">Ejecutado</th><th class="num">Por recibir</th><th class="num">En aprobación</th>
                <th class="num">Disponible</th><th style="min-width:190px;">Uso del presupuesto</th><th>Estado</th>
            </tr></thead>
            <tbody>
            <?php if (empty($filas)): ?>
                <tr><td colspan="8"><div class="fd-ok-box vacio"><i class="fas fa-sitemap"></i>Este proyecto no tiene actividades en el WBS.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($filas as $f): ?>
                <tr class="cp-row <?= $f['es_hoja'] ? '' : 'es-padre' ?>"
                    data-estado="<?= fd_h($f['estado']) ?>" data-hoja="<?= $f['es_hoja'] ? 1 : 0 ?>" data-comp="<?= $f['por_recibir'] + $f['pendiente'] > 0 ? 1 : 0 ?>">
                    <td><div style="padding-left:<?= $f['nivel'] * 20 ?>px; display:flex; align-items:center; gap:8px;">
                        <span class="cp-cod"><?= fd_h($f['codigo']) ?></span><span><?= fd_h($f['nombre']) ?></span></div></td>
                    <td class="num fd-nowrap"><?= fd_money($f['presupuesto'], 2) ?></td>
                    <td class="num fd-nowrap"><?= fd_money($f['ejecutado'], 2) ?></td>
                    <td class="num fd-nowrap"><?= $f['por_recibir'] > 0 ? fd_money($f['por_recibir'], 2) : '<span style="color:#cbd5e1;">—</span>' ?></td>
                    <td class="num fd-nowrap"><?= $f['pendiente'] > 0 ? fd_money($f['pendiente'], 2) : '<span style="color:#cbd5e1;">—</span>' ?></td>
                    <td class="num fd-nowrap <?= $f['disponible'] < 0 ? 'cp-neg' : '' ?>" style="font-weight:700;"><?= ($f['disponible'] < 0 ? '− ' : '') . fd_money(abs($f['disponible']), 2) ?></td>
                    <td><?= cp_barra($f) ?><div style="font-size:11px; color:#94a3b8; margin-top:3px;"><?= $f['pct'] === null ? '' : $f['pct'] . '% del presupuesto' ?></div></td>
                    <td><?= fd_badge($f['clase'], $f['etiqueta']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div id="cpSinResultados" class="fd-ok-box vacio" style="display:none;"><i class="fas fa-filter"></i>Ninguna actividad coincide con el filtro.</div>
    </div>
</div>

<div class="fd-card fd-rise" style="--d:.35s;">
    <div class="fd-card-head"><div>
        <h3 class="fd-card-title"><i class="fas fa-user-shield" style="color:#dc2626;"></i> Bitácora de excesos autorizados</h3>
        <p class="fd-card-sub">Cada operación que superó el presupuesto de una actividad, con quién la autorizó y por qué</p>
    </div><?= $resExc['n'] ? fd_badge('danger', $resExc['n'] . ' registro(s) · ' . fd_money($resExc['total'], 2)) : '' ?></div>
    <?php if (empty($excesos)): ?>
        <div class="fd-ok-box"><i class="fas fa-check-circle"></i>Sin excesos registrados en este proyecto.</div>
    <?php else: ?>
        <div class="cp-timeline">
        <?php foreach ($excesos as $x):
            $esOC = $x['origen'] === 'oc';
            $enlace = $x['origen_id'] ? BASE_URL . ($esOC ? '/compras/ver/' : '/almacen/ver/') . (int)$x['origen_id'] : null;
        ?>
            <div class="cp-tl">
                <div class="cp-tl-dot"><i class="fas <?= $esOC ? 'fa-file-invoice-dollar' : 'fa-dolly' ?>"></i></div>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center;">
                        <div style="font-size:13px; color:#1e293b;">
                            <?= $esOC ? 'Orden de compra' : 'Vale de salida' ?>
                            <?= $enlace ? '<a class="fd-link" style="color:#4338ca;" href="' . fd_h($enlace) . '">' . fd_h($x['origen_ref']) . '</a>' : '<strong>' . fd_h($x['origen_ref']) . '</strong>' ?>
                            en <span class="cp-cod"><?= fd_h($x['tarea_codigo']) ?></span> <?= fd_h($x['tarea_nombre']) ?>
                        </div>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <?= fd_badge('danger', '+ ' . fd_money($x['exceso'], 2)) ?>
                            <?= fd_badge($x['modo'] === 'bloquear' ? 'warn' : 'neutral', $x['modo'] === 'bloquear' ? 'Autorizado' : 'Con advertencia') ?>
                        </div>
                    </div>
                    <div style="font-size:11.5px; color:#94a3b8; margin-top:2px;">
                        <?= date('d/m/Y H:i', strtotime($x['created_at'])) ?> · <?= fd_h($x['autorizado_por'] ?? 'Usuario eliminado') ?>
                        · presupuesto <?= fd_money($x['presupuesto'], 2) ?>, consumo previo <?= fd_money($x['usado_previo'], 2) ?>, operación <?= fd_money($x['monto'], 2) ?>
                    </div>
                    <?php if (!empty($x['motivo'])): ?><div class="cp-motivo">“<?= fd_h($x['motivo']) ?>”</div><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($puedeConfigurar && $migrado): ?>
<div id="cpConfig" class="cp-ov">
    <div class="cp-md">
        <div class="cp-md-head">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:11px; background:rgba(255,255,255,.18); display:flex; align-items:center; justify-content:center;"><i class="fas fa-sliders-h"></i></div>
                <div><div style="font-size:16px; font-weight:800;">Configurar control presupuestal</div><div style="font-size:12px; opacity:.85;"><?= fd_h($proyectoActual['codigo']) ?> · <?= fd_h($proyectoActual['nombre']) ?></div></div>
            </div>
            <button type="button" onclick="cerrarConfig()" style="background:transparent; border:none; color:rgba(255,255,255,.75); font-size:18px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/presupuesto/configurar/<?= $proyectoId ?>" style="padding:22px 24px; overflow-y:auto;">
            <input type="hidden" name="csrf_token" value="<?= fd_h($csrfToken) ?>">
            <div style="font-size:12px; font-weight:800; color:#334155; margin-bottom:10px; text-transform:uppercase; letter-spacing:.4px;">¿Qué hacer cuando una operación excede el presupuesto?</div>
            <?php foreach ($MODOS as $k => $m): ?>
            <label class="cp-opt <?= $cfg['modo'] === $k ? 'sel' : '' ?>">
                <input type="radio" name="modo" value="<?= $k ?>" <?= $cfg['modo'] === $k ? 'checked' : '' ?>>
                <span class="ico" style="background:<?= $m[2] ?>;"><i class="fas <?= $m[1] ?>"></i></span>
                <span><strong style="color:#0f172a; font-size:13.5px;"><?= fd_h($m[0]) ?></strong><br><span style="font-size:12px; color:#64748b;"><?= fd_h($m[3]) ?></span></span>
            </label>
            <?php endforeach; ?>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin:16px 0 6px;">
                <div class="cp-fld"><label>Tolerancia sobre el presupuesto (%)</label>
                    <input type="number" name="tolerancia" min="0" max="50" step="0.5" value="<?= fd_h($cfg['tolerancia']) ?>"></div>
                <div class="cp-fld"><label>Alertar desde (% del presupuesto)</label>
                    <input type="number" name="umbral" min="1" max="100" step="1" value="<?= fd_h($cfg['umbral']) ?>"></div>
            </div>
            <div style="font-size:11.5px; color:#94a3b8; margin-bottom:18px;">La tolerancia permite pasarse ese porcentaje sin que se considere exceso. Se aplica a todas las actividades del proyecto.</div>
            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" class="cp-btn sec" onclick="cerrarConfig()">Cancelar</button>
                <button type="submit" class="cp-btn"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const CP = <?= fd_json($grafJson) ?>;
const CP_TOT = <?= fd_json($tot) ?>;
const CP_PCT = <?= json_encode($pctUsado) ?>;
let cChart = null, cVista = 'principales';

/* Barras apiladas de la tabla y de las tarjetas: crecen desde 0 */
document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(() => document.querySelectorAll('.cp-stack i[data-w]').forEach(b => { b.style.width = b.dataset.w + '%'; }));
    initGraficos();
});

function datosVista(v) {
    const a = CP[v];
    return {
        labels: a.map(f => f.codigo),
        ejec: a.map(f => f.ejecutado), rec: a.map(f => f.por_recibir), pend: a.map(f => f.pendiente), pres: a.map(f => f.presupuesto),
        nombres: a.map(f => f.nombre), disp: a.map(f => f.disponible)
    };
}

function initGraficos() {
    if (typeof Chart === 'undefined') return;
    const d = datosVista('principales');
    const cv = document.getElementById('cCompromisos');
    if (!d.labels.length) { fdVacio('cCompromisos'); }
    else {
        cChart = new Chart(cv, {
            type: 'bar',
            data: {labels: d.labels, datasets: [
                {label: 'Ejecutado', data: d.ejec, backgroundColor: '#3b82f6', stack: 'a', borderRadius: 4},
                {label: 'Por recibir', data: d.rec, backgroundColor: '#8b5cf6', stack: 'a', borderRadius: 4},
                {label: 'En aprobación', data: d.pend, backgroundColor: '#f59e0b', stack: 'a', borderRadius: 4},
                {label: 'Presupuesto', data: d.pres, type: 'line', showLine: false, pointStyle: 'line', pointRadius: 17, pointHoverRadius: 19,
                 pointBorderWidth: 3, pointBorderColor: '#0f172a', borderColor: '#0f172a', order: -1}
            ]},
            options: {
                responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
                scales: {x: {stacked: true, grid: {display: false}}, y: {stacked: true, beginAtZero: true, ticks: {callback: fdCorto}}},
                plugins: {tooltip: {callbacks: {
                    title: it => { const v = datosVista(cVista); return v.labels[it[0].dataIndex] + ' · ' + fdCorto2(v.nombres[it[0].dataIndex]); },
                    label: c => c.dataset.label + ': ' + fdMoney(c.parsed.y, 2),
                    afterBody: it => { const v = datosVista(cVista), i = it[0].dataIndex; return [(v.disp[i] < 0 ? 'Sobregiro: ' : 'Disponible: ') + fdMoney(Math.abs(v.disp[i]), 2)]; }
                }}}
            }
        });
    }

    const t = CP_TOT, disp = Math.max(0, t.disponible);
    const vals = [t.ejecutado, t.por_recibir, t.pendiente, disp];
    if (fdSuma(vals) <= 0) { fdVacio('cUso'); return; }
    new Chart(document.getElementById('cUso'), {
        type: 'doughnut',
        data: {labels: ['Ejecutado', 'Por recibir', 'En aprobación', 'Disponible'], datasets: [{data: vals, backgroundColor: ['#3b82f6', '#8b5cf6', '#f59e0b', '#bbf7d0'], borderWidth: 3, borderColor: '#fff', hoverOffset: 10}]},
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '68%',
            animation: {animateRotate: true, animateScale: true, duration: 1600},
            plugins: {
                centerText: {text: CP_PCT.toFixed(1) + '%', sub: CP_PCT > 100 ? 'sobregirado' : 'usado'},
                tooltip: {callbacks: {label: c => c.label + ': ' + fdMoney(c.parsed, 2)}}
            }
        }
    });
}
const fdCorto2 = s => String(s || '').length > 34 ? String(s).slice(0, 33) + '…' : String(s || '');

function cambiarVista(v, btn) {
    if (!cChart || !CP[v]) return;
    cVista = v;
    document.querySelectorAll('[data-vista]').forEach(b => b.classList.toggle('activo', b === btn));
    const d = datosVista(v);
    cChart.data.labels = d.labels;
    cChart.data.datasets[0].data = d.ejec; cChart.data.datasets[1].data = d.rec; cChart.data.datasets[2].data = d.pend; cChart.data.datasets[3].data = d.pres;
    cChart.update();
}

/* Filtros y búsqueda de la tabla */
let filtroActual = 'todas';
function aplicarFiltros() {
    const q = (document.getElementById('cpBuscar').value || '').toLowerCase().trim();
    let visibles = 0;
    document.querySelectorAll('#cpTabla tbody tr.cp-row').forEach(tr => {
        const e = tr.dataset.estado;
        const okF = filtroActual === 'todas'
            || (filtroActual === 'alerta' && e === 'alerta')
            || (filtroActual === 'exceso' && (e === 'excedido' || e === 'sin_presupuesto'))
            || (filtroActual === 'comprometidas' && tr.dataset.comp === '1')
            || (filtroActual === 'hojas' && tr.dataset.hoja === '1');
        const ok = okF && (!q || tr.textContent.toLowerCase().includes(q));
        tr.style.display = ok ? '' : 'none';
        if (ok) visibles++;
    });
    document.getElementById('cpSinResultados').style.display = visibles ? 'none' : 'block';
}
document.getElementById('cpFiltros').addEventListener('click', e => {
    const b = e.target.closest('.cp-chip'); if (!b) return;
    filtroActual = b.dataset.f;
    document.querySelectorAll('#cpFiltros .cp-chip').forEach(x => x.classList.toggle('activo', x === b));
    aplicarFiltros();
});
document.getElementById('cpBuscar').addEventListener('input', aplicarFiltros);

/* Modal de configuración */
function abrirConfig() { const m = document.getElementById('cpConfig'); if (m) m.style.display = 'flex'; }
function cerrarConfig() { const m = document.getElementById('cpConfig'); if (m) m.style.display = 'none'; }
document.querySelectorAll('.cp-opt input').forEach(r => r.addEventListener('change', () => {
    document.querySelectorAll('.cp-opt').forEach(o => o.classList.toggle('sel', o.querySelector('input').checked));
}));
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarConfig(); });
</script>
<?php endif; ?>
</div>
