<?php
/**
 * Vista: Ingreso a Almacén — recepción de materiales de una OC aprobada.
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$jsonSeguro = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$ordenes = $data['ordenes'];
$ocId = (int)$data['ocId'];
$viejo = $data['viejo'];
$post = $data['post'];
$enLista = $ocId > 0 && in_array($ocId, array_map('intval', array_column($ordenes, 'id')), true);
?>
<?php include __DIR__ . '/_estilos.php'; ?>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <div class="alm-card" style="border-radius:16px; padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#14b8a6 0%,#0d9488 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px;"><i class="fas fa-truck-loading"></i></div>
            <div>
                <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0 0 2px;">Ingreso a Almacén</h2>
                <span style="font-size:12.5px; color:#64748b;">Recepción de materiales de una orden de compra aprobada · incrementa el stock y recalcula el costo promedio</span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/almacen#recepcion" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:8px 16px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none;"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if ($ocId > 0 && !$enLista): ?>
    <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
        <i class="fas fa-exclamation-triangle"></i> La orden solicitada no está disponible para recepción (debe estar <strong>aprobada</strong> y tener materiales pendientes).
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/almacen/ingreso" id="formIngreso">
        <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">

        <div class="alm-card" style="padding:22px; margin-bottom:20px;">
            <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0 0 16px; padding-bottom:10px; border-bottom:1px solid #f1f5f9;"><i class="fas fa-file-invoice" style="color:#ea580c;"></i> Orden de compra a recepcionar</h3>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                <div class="alm-field" style="grid-column:span 2; min-width:240px;">
                    <label>Orden de compra aprobada *</label>
                    <select name="orden_compra_id" id="selOC" required onchange="cargarPendientes(this.value)">
                        <option value="">— Selecciona una orden —</option>
                        <?php foreach ($ordenes as $o): ?>
                            <option value="<?= (int)$o['id'] ?>" <?= $ocId === (int)$o['id'] ? 'selected' : '' ?>>
                                <?= $h($o['codigo']) ?> · <?= $h($o['proyecto_codigo']) ?> · <?= $h($o['proveedor']) ?> (<?= (int)$o['items_pendientes'] ?> pend.)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="alm-field">
                    <label>Fecha de recepción *</label>
                    <input type="date" name="fecha" required max="<?= date('Y-m-d') ?>" value="<?= $h($post['fecha'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="alm-field">
                    <label>Guía de remisión del proveedor</label>
                    <input type="text" name="guia_remision" maxlength="100" placeholder="Ej: T001-000458" value="<?= $h($post['guia_remision'] ?? '') ?>">
                </div>
            </div>
            <div id="infoOC" style="margin-top:14px; font-size:12.5px; color:#475569;"></div>
        </div>

        <div class="alm-card" style="padding:22px; margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #f1f5f9; flex-wrap:wrap; gap:10px;">
                <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-cubes" style="color:#0d9488;"></i> Materiales de la orden</h3>
                <button type="button" id="btnTodo" onclick="recibirTodo()" style="display:none; background:#f0fdfa; border:1px solid #99f6e4; color:#0f766e; padding:6px 14px; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer;"><i class="fas fa-check-double"></i> Recibir todo lo pendiente</button>
            </div>
            <div style="overflow-x:auto;">
                <table class="alm-tbl" id="tablaPend">
                    <thead><tr><th>Material</th><th>Und.</th><th style="text-align:right;">Ordenado</th><th style="text-align:right;">Ya recibido</th><th style="text-align:right;">Pendiente</th><th style="text-align:right; width:140px;">Recibir ahora</th><th style="text-align:right;">Costo unit.</th><th style="text-align:right;">Valor</th></tr></thead>
                    <tbody id="tbodyPend"><tr><td colspan="8" style="padding:34px; text-align:center; color:#94a3b8;">Selecciona una orden para ver sus materiales pendientes.</td></tr></tbody>
                </table>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:16px;">
                <div style="background:#f0fdfa; border:1px solid #ccfbf1; border-radius:12px; padding:12px 18px; font-size:14px; font-weight:800; color:#0f766e;">Valor a ingresar: S/ <span id="totalIngreso">0.00</span></div>
            </div>
        </div>

        <div class="alm-card" style="padding:22px;">
            <div class="alm-field" style="margin-bottom:16px;"><label>Observaciones (estado de la mercadería, faltantes, etc.)</label><textarea name="observaciones" rows="2"><?= $h($post['observaciones'] ?? '') ?></textarea></div>
            <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <a href="<?= BASE_URL ?>/almacen" style="padding:10px 20px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:13.5px; font-weight:600; text-decoration:none;">Cancelar</a>
                <button type="submit" id="btnGuardar" disabled style="background:linear-gradient(135deg,#14b8a6 0%,#0d9488 100%); color:#fff; border:none; padding:10px 26px; border-radius:10px; font-weight:700; font-size:13.5px; cursor:pointer; opacity:.5;"><i class="fas fa-check-circle"></i> Registrar Ingreso</button>
            </div>
        </div>
    </form>
</div>

<script>
const BASE_URL_JS = <?= $jsonSeguro(BASE_URL) ?>;
const VIEJO = <?= $jsonSeguro((object)$viejo) ?>;
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = n => Number(n || 0).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
let pendientes = [];

function cargarPendientes(ocId) {
    const tbody = document.getElementById('tbodyPend');
    const info = document.getElementById('infoOC');
    pendientes = []; info.innerHTML = '';
    document.getElementById('btnTodo').style.display = 'none';
    if (!ocId) { tbody.innerHTML = '<tr><td colspan="8" style="padding:34px; text-align:center; color:#94a3b8;">Selecciona una orden para ver sus materiales pendientes.</td></tr>'; recalcular(); return; }
    tbody.innerHTML = '<tr><td colspan="8" style="padding:34px; text-align:center; color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Cargando…</td></tr>';

    fetch(BASE_URL_JS + '/almacen/pendientesOC/' + ocId, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            if (!d.success) { tbody.innerHTML = '<tr><td colspan="8" style="padding:30px; color:#b91c1c;">' + esc(d.message || 'No se pudo cargar la orden.') + '</td></tr>'; return; }
            pendientes = d.items;
            info.innerHTML = `<i class="fas fa-project-diagram" style="color:#2563eb;"></i> <strong>${esc(d.orden.proyecto_codigo)}</strong> ${esc(d.orden.proyecto_nombre)} &nbsp;·&nbsp; <i class="fas fa-truck" style="color:#ea580c;"></i> ${esc(d.orden.proveedor)}` +
                (d.orden.moneda && d.orden.moneda !== 'PEN' ? ` &nbsp;<span class="alm-badge warn">Moneda ${esc(d.orden.moneda)}: el costo se registra sin conversión</span>` : '');
            tbody.innerHTML = d.items.map((it, i) => {
                const viejo = VIEJO[it.material_id];
                const sugerido = viejo !== undefined ? viejo : it.pendiente;
                const bloqueado = it.pendiente <= 0;
                return `<tr data-i="${i}" style="${bloqueado ? 'opacity:.55;' : ''}">
                    <td><input type="hidden" name="items[${i}][material_id]" value="${it.material_id}"><strong style="color:#1e293b;">${esc(it.nombre)}</strong><div style="font-family:monospace; font-size:11px; color:#94a3b8;">${esc(it.codigo)} · stock actual ${fmt(it.stock_actual)}</div></td>
                    <td style="font-family:monospace;">${esc(it.unidad_medida)}</td>
                    <td style="text-align:right;">${fmt(it.ordenado)}</td>
                    <td style="text-align:right; color:#64748b;">${fmt(it.recibido)}</td>
                    <td style="text-align:right; font-weight:800; color:${bloqueado ? '#16a34a' : '#d97706'};">${bloqueado ? '✓ completo' : fmt(it.pendiente)}</td>
                    <td class="alm-form-cell"><input class="num in-rec" type="number" step="0.01" min="0" max="${it.pendiente}" name="items[${i}][cantidad]" value="${bloqueado ? 0 : sugerido}" ${bloqueado ? 'disabled' : ''} oninput="recalcular()"></td>
                    <td style="text-align:right;">${fmt(it.precio_unitario)}</td>
                    <td style="text-align:right; font-weight:700;" class="celda-valor">0.00</td></tr>`;
            }).join('') || '<tr><td colspan="8" style="padding:30px; text-align:center; color:#94a3b8;">Esta orden no tiene materiales de catálogo para ingresar a almacén.</td></tr>';
            document.getElementById('btnTodo').style.display = d.items.some(x => x.pendiente > 0) ? 'inline-block' : 'none';
            recalcular();
        })
        .catch(() => { tbody.innerHTML = '<tr><td colspan="8" style="padding:30px; color:#b91c1c;">Error de red al cargar la orden.</td></tr>'; });
}

function recalcular() {
    let total = 0, hayCantidad = false;
    document.querySelectorAll('#tbodyPend tr[data-i]').forEach(tr => {
        const it = pendientes[tr.dataset.i];
        const inp = tr.querySelector('.in-rec');
        let q = parseFloat(inp.value) || 0;
        const invalido = q < 0 || q > it.pendiente + 0.004;
        inp.style.borderColor = invalido ? '#dc2626' : '#cbd5e1';
        if (invalido) q = 0;
        if (q > 0) hayCantidad = true;
        const val = q * it.precio_unitario;
        total += val;
        tr.querySelector('.celda-valor').textContent = fmt(val);
    });
    document.getElementById('totalIngreso').textContent = fmt(total);
    const btn = document.getElementById('btnGuardar');
    btn.disabled = !hayCantidad; btn.style.opacity = hayCantidad ? 1 : .5;
}

function recibirTodo() {
    document.querySelectorAll('#tbodyPend tr[data-i]').forEach(tr => {
        const it = pendientes[tr.dataset.i];
        if (it.pendiente > 0) tr.querySelector('.in-rec').value = it.pendiente;
    });
    recalcular();
}

document.addEventListener('DOMContentLoaded', () => {
    const v = document.getElementById('selOC').value;
    if (v) cargarPendientes(v);
});
</script>
