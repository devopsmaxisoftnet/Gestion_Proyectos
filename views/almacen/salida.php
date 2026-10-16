<?php
/**
 * Vista: Vale de Salida a Obra — cada línea exige una actividad del WBS.
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$jsonSeguro = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$proyectos = $data['proyectos'];
$catalogo = $data['catalogo'];
$post = $data['post'];
$items = $data['items'];
?>
<?php include __DIR__ . '/_estilos.php'; ?>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <div class="alm-card" style="border-radius:16px; padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,#f97316 0%,#ea580c 100%); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px;"><i class="fas fa-dolly"></i></div>
            <div>
                <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0 0 2px;">Vale de Salida a Obra</h2>
                <span style="font-size:12.5px; color:#64748b;">Cada ítem se carga a una actividad del WBS · descuenta stock y aumenta su costo real</span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/almacen" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:8px 16px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none;"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/almacen/salida" id="formSalida">
        <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">

        <div class="alm-card" style="padding:22px; margin-bottom:20px;">
            <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0 0 16px; padding-bottom:10px; border-bottom:1px solid #f1f5f9;"><i class="fas fa-info-circle" style="color:#ea580c;"></i> Datos del vale</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                <div class="alm-field" style="grid-column:span 2; min-width:240px;">
                    <label>Proyecto / obra *</label>
                    <select name="proyecto_id" id="selProyecto" required>
                        <option value="">— Selecciona el proyecto —</option>
                        <?php foreach ($proyectos as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)($post['proyecto_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>[<?= $h($p['codigo']) ?>] <?= $h($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="estadoActividades" style="font-size:11.5px; color:#64748b; margin-top:5px; min-height:16px;"></div>
                </div>
                <div class="alm-field">
                    <label>Fecha *</label>
                    <input type="date" name="fecha" required max="<?= date('Y-m-d') ?>" value="<?= $h($post['fecha'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="alm-field">
                    <label>N° de vale</label>
                    <input type="text" name="guia_remision" maxlength="100" placeholder="Automático (VS-<?= date('Y') ?>-0001)" value="<?= $h($post['guia_remision'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="alm-card" style="padding:22px; margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #f1f5f9; flex-wrap:wrap; gap:10px;">
                <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0;"><i class="fas fa-boxes" style="color:#0d9488;"></i> Materiales y equipos entregados</h3>
                <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <label style="display:inline-flex; align-items:center; gap:6px; font-size:12px; color:#475569; font-weight:600; cursor:pointer;"><input type="checkbox" id="chkPadres" onchange="refrescarActividades()"> Incluir actividades padre</label>
                    <button type="button" onclick="agregarFila()" style="background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; padding:6px 14px; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer;"><i class="fas fa-plus"></i> Agregar fila</button>
                </div>
            </div>
            <div id="ctlLiveVale"></div>
            <div style="overflow-x:auto;">
                <table class="alm-tbl" style="min-width:940px;">
                    <thead><tr><th style="width:34px;">#</th><th style="min-width:210px;">Actividad (WBS) *</th><th style="min-width:230px;">Material / equipo *</th><th style="width:120px; text-align:right;">Disponible</th><th style="width:120px; text-align:right;">Cantidad *</th><th style="width:110px; text-align:right;">Costo unit.</th><th style="width:110px; text-align:right;">Importe</th><th style="width:36px;"></th></tr></thead>
                    <tbody id="tbodySalida"></tbody>
                </table>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; flex-wrap:wrap; gap:12px;">
                <div style="font-size:11.5px; color:#94a3b8; max-width:520px;">Los materiales se valorizan al costo promedio vigente. Para equipos, la cantidad son <strong>horas</strong> y el costo es la tarifa por hora del catálogo.</div>
                <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:12px 18px; font-size:14px; font-weight:800; color:#c2410c;">Total a cargar al WBS: S/ <span id="totalVale">0.00</span></div>
            </div>
        </div>

        <div id="autExceso" class="alm-card" style="padding:20px 22px; margin-bottom:20px; display:none; border-color:#fecaca; background:#fffafa;">
            <h3 style="font-size:15px; font-weight:800; color:#991b1b; margin:0 0 6px;"><i class="fas fa-user-shield"></i> <span id="autTitulo">Autorización de exceso de presupuesto</span></h3>
            <p id="autTexto" style="font-size:12.5px; color:#7f1d1d; margin:0 0 12px;"></p>
            <div id="autCampos">
                <label id="autCheckWrap" style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#7f1d1d; margin-bottom:10px; cursor:pointer;">
                    <input type="checkbox" name="autorizar_exceso" value="1" id="chkAutorizar"> Autorizo que este vale supere el presupuesto de las actividades indicadas
                </label>
                <div class="alm-field"><label>Motivo de la autorización <span id="autReq">*</span></label>
                    <textarea name="motivo_exceso" id="txtMotivoExceso" rows="2" maxlength="500" placeholder="Ej.: Reposición urgente por pedido adicional del cliente…"><?= $h($post['motivo_exceso'] ?? '') ?></textarea></div>
            </div>
        </div>

        <div class="alm-card" style="padding:22px;">
            <div class="alm-field" style="margin-bottom:16px;"><label>Observaciones (receptor, frente de trabajo, etc.)</label><textarea name="observaciones" rows="2"><?= $h($post['observaciones'] ?? '') ?></textarea></div>
            <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <a href="<?= BASE_URL ?>/almacen" style="padding:10px 20px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:13.5px; font-weight:600; text-decoration:none;">Cancelar</a>
                <button type="submit" id="btnGuardar" style="background:linear-gradient(135deg,#f97316 0%,#ea580c 100%); color:#fff; border:none; padding:10px 26px; border-radius:10px; font-weight:700; font-size:13.5px; cursor:pointer;"><i class="fas fa-check-circle"></i> Registrar Vale de Salida</button>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../presupuesto/_live.php'; ?>
<script>
const BASE_URL_JS = <?= $jsonSeguro(BASE_URL) ?>;
const MATERIALES = <?= $jsonSeguro($catalogo['materiales']) ?>;
const EQUIPOS = <?= $jsonSeguro($catalogo['equipos']) ?>;
const ITEMS_INICIALES = <?= $jsonSeguro($items) ?>;
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = n => Number(n || 0).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
let tareas = [], idx = 0, puedeAutorizar = false;

function opcionesActividad(sel) {
    const verPadres = document.getElementById('chkPadres').checked;
    let html = '<option value="">— Elige la actividad —</option>';
    tareas.forEach(t => {
        if (!t.es_hoja && !verPadres && String(t.id) !== String(sel)) return;
        html += `<option value="${t.id}" ${String(t.id) === String(sel) ? 'selected' : ''}>${'— '.repeat(t.nivel)}${esc(t.codigo)} · ${esc(t.nombre)}</option>`;
    });
    return html;
}

function opcionesItem(it) {
    const s = it.material_id ? 'm:' + it.material_id : (it.equipo_id ? 'e:' + it.equipo_id : '');
    return '<option value="">— Elige un ítem —</option>' +
        '<optgroup label="Materiales">' + MATERIALES.map(m => `<option value="m:${m.id}" ${s === 'm:' + m.id ? 'selected' : ''}>${esc(m.codigo)} · ${esc(m.nombre)}</option>`).join('') + '</optgroup>' +
        '<optgroup label="Equipos (horas)">' + EQUIPOS.map(e => `<option value="e:${e.id}" ${s === 'e:' + e.id ? 'selected' : ''}>${esc(e.codigo)} · ${esc(e.nombre)}</option>`).join('') + '</optgroup>';
}

function agregarFila(it) {
    it = it || {};
    const i = idx++;
    const tr = document.createElement('tr');
    tr.className = 'fila alm-form-cell';
    tr.innerHTML = `
        <td class="n" style="color:#94a3b8; font-weight:700; text-align:center;"></td>
        <td><select class="sel-act" name="items[${i}][tarea_id]" data-tarea="${it.tarea_id || ''}" required onchange="this.dataset.tarea = this.value; this.style.borderColor = this.value ? '#cbd5e1' : '#f59e0b'; actualizarControl()">${opcionesActividad(it.tarea_id)}</select></td>
        <td><select class="sel-item" onchange="elegirItem(this)">${opcionesItem(it)}</select>
            <input type="hidden" class="h-mat" name="items[${i}][material_id]" value="${it.material_id || ''}">
            <input type="hidden" class="h-eq" name="items[${i}][equipo_id]" value="${it.equipo_id || ''}"></td>
        <td class="disp" style="text-align:right; font-size:12.5px; color:#64748b;">—</td>
        <td><input class="num cant" type="number" step="0.01" min="0.01" name="items[${i}][cantidad]" value="${it.cantidad ?? 1}" required oninput="recalcular()"></td>
        <td class="cu" style="text-align:right;">0.00</td>
        <td class="imp" style="text-align:right; font-weight:800;">0.00</td>
        <td style="text-align:center;"><button type="button" onclick="this.closest('tr').remove(); renumerar(); recalcular();" style="background:transparent; border:none; color:#cbd5e1; cursor:pointer;" title="Quitar"><i class="fas fa-trash-alt"></i></button></td>`;
    document.getElementById('tbodySalida').appendChild(tr);
    renumerar();
    elegirItem(tr.querySelector('.sel-item'), true);
}

function renumerar() { document.querySelectorAll('#tbodySalida tr .n').forEach((td, i) => td.textContent = i + 1); }

function elegirItem(sel, silencioso) {
    const tr = sel.closest('tr');
    tr.querySelector('.h-mat').value = ''; tr.querySelector('.h-eq').value = '';
    tr.dataset.costo = 0; tr.dataset.stock = '';
    const disp = tr.querySelector('.disp');
    if (sel.value) {
        const [tipo, id] = sel.value.split(':');
        if (tipo === 'm') {
            const m = MATERIALES.find(x => String(x.id) === id);
            tr.querySelector('.h-mat').value = id; tr.dataset.costo = m.costo; tr.dataset.stock = m.stock_actual;
            disp.innerHTML = `<strong style="color:${Number(m.stock_actual) > 0 ? '#15803d' : '#dc2626'};">${fmt(m.stock_actual)}</strong> ${esc(m.unidad_medida)}`;
        } else {
            const e = EQUIPOS.find(x => String(x.id) === id);
            tr.querySelector('.h-eq').value = id; tr.dataset.costo = e.costo;
            disp.innerHTML = '<span style="color:#94a3b8;">horas</span>';
        }
    } else { disp.textContent = '—'; }
    recalcular();
}

function recalcular() {
    let total = 0;
    const usado = {};
    document.querySelectorAll('#tbodySalida tr.fila').forEach(tr => {
        const q = parseFloat(tr.querySelector('.cant').value) || 0;
        const costo = parseFloat(tr.dataset.costo) || 0;
        const mid = tr.querySelector('.h-mat').value;
        if (mid) usado[mid] = (usado[mid] || 0) + q;
        tr.querySelector('.cu').textContent = fmt(costo);
        tr.querySelector('.imp').textContent = fmt(q * costo);
        total += q * costo;
    });
    // Alerta de stock sobre el total agregado por material
    document.querySelectorAll('#tbodySalida tr.fila').forEach(tr => {
        const mid = tr.querySelector('.h-mat').value;
        const exceso = mid && usado[mid] > (parseFloat(tr.dataset.stock) || 0) + 0.004;
        tr.querySelector('.cant').style.borderColor = exceso ? '#dc2626' : '#cbd5e1';
        tr.querySelector('.cant').title = exceso ? 'Supera el stock disponible' : '';
    });
    document.getElementById('totalVale').textContent = fmt(total);
    actualizarControl();
}

/* ── Control presupuestal en vivo + autorización de exceso ─────────────── */
function actualizarControl() {
    const cargos = {};
    document.querySelectorAll('#tbodySalida tr.fila').forEach(tr => {
        const act = tr.querySelector('.sel-act').value;
        if (!act) return;
        cargos[act] = (cargos[act] || 0) + (parseFloat(tr.querySelector('.cant').value) || 0) * (parseFloat(tr.dataset.costo) || 0);
    });
    const ev = CtlLive.render(document.getElementById('ctlLiveVale'), CtlLive.evaluar(cargos), {
        titulo: 'Control presupuestal de este vale',
        msgBloquear: 'Este vale supera el presupuesto: para registrarlo debe autorizarse el exceso con un motivo.',
        msgAdvertir: 'Podrás registrarlo: el exceso quedará anotado en la bitácora de control presupuestal.'
    });
    const caja = document.getElementById('autExceso'), btn = document.getElementById('btnGuardar');
    const mostrar = ev.hayExceso && ev.modo !== 'informar';
    caja.style.display = mostrar ? 'block' : 'none';
    const chk = document.getElementById('chkAutorizar'), txt = document.getElementById('txtMotivoExceso');
    const bloquea = mostrar && ev.modo === 'bloquear';
    const exige = bloquea && puedeAutorizar;
    document.getElementById('autCheckWrap').style.display = exige ? 'flex' : 'none';
    document.getElementById('autCampos').style.display = (bloquea && !puedeAutorizar) ? 'none' : 'block';
    chk.required = exige; txt.required = exige; txt.minLength = exige ? 10 : 0;
    document.getElementById('autReq').style.display = bloquea ? 'inline' : 'none';
    document.getElementById('autTitulo').textContent = bloquea ? 'Autorización de exceso de presupuesto' : 'Registro del exceso (modo advertir)';
    document.getElementById('autTexto').textContent = !mostrar ? '' : (bloquea
        ? (puedeAutorizar ? 'Tu usuario puede autorizar excesos. Queda registrado a tu nombre con el motivo que indiques.'
                          : 'El proyecto está en modo bloquear y tu usuario no puede autorizar excesos. Reduce las cantidades, cambia de actividad o pide a un responsable que registre el vale.')
        : 'El vale se registrará igual. Puedes dejar un motivo (opcional) para la bitácora.');
    btn.disabled = bloquea && !puedeAutorizar;
    btn.style.opacity = btn.disabled ? '.5' : '1'; btn.style.cursor = btn.disabled ? 'not-allowed' : 'pointer';
    btn.title = btn.disabled ? 'Excede el presupuesto y no tienes permiso para autorizarlo' : '';
}

/* ── Actividades del proyecto (AJAX) ───────────────────────────────────── */
function refrescarActividades() {
    document.querySelectorAll('#tbodySalida .sel-act').forEach(s => { s.innerHTML = opcionesActividad(s.dataset.tarea || ''); });
}

function cargarActividades(pid, conservar) {
    const est = document.getElementById('estadoActividades');
    if (!pid) { tareas = []; est.textContent = 'Selecciona un proyecto para cargar sus actividades.'; refrescarActividades(); return Promise.resolve(); }
    est.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando actividades…';
    return fetch(`${BASE_URL_JS}/almacen/tareasProyecto/${pid}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            tareas = d.success ? d.tareas : [];
            CtlLive.setDatos(tareas, d.control); puedeAutorizar = !!d.puede_autorizar;
            const hojas = tareas.filter(t => t.es_hoja).length;
            est.innerHTML = tareas.length ? `${hojas} actividades operativas disponibles.`
                : `<span style="color:#b45309;">Este proyecto no tiene actividades: no se puede imputar el consumo. <a href="${BASE_URL_JS}/tareas?proyecto_id=${pid}" style="color:#ea580c; font-weight:700;">Crear el WBS</a></span>`;
            document.querySelectorAll('#tbodySalida .sel-act').forEach(s => {
                if (!conservar || !tareas.some(t => String(t.id) === s.dataset.tarea)) s.dataset.tarea = '';
                s.innerHTML = opcionesActividad(s.dataset.tarea);
            });
            actualizarControl();
        })
        .catch(() => { est.innerHTML = '<span style="color:#b91c1c;">No se pudieron cargar las actividades.</span>'; });
}

document.addEventListener('DOMContentLoaded', () => {
    const sp = document.getElementById('selProyecto');
    (ITEMS_INICIALES.length ? ITEMS_INICIALES : [{}]).forEach(agregarFila);
    sp.addEventListener('change', () => cargarActividades(sp.value, false));
    cargarActividades(sp.value, true).then(() => {
        const ids = new Set(ITEMS_INICIALES.map(i => String(i.tarea_id)).filter(Boolean));
        if (tareas.some(t => !t.es_hoja && ids.has(String(t.id)))) { document.getElementById('chkPadres').checked = true; refrescarActividades(); }
    });

    document.getElementById('formSalida').addEventListener('submit', e => {
        const sinAct = [...document.querySelectorAll('#tbodySalida .sel-act')].some(s => !s.value);
        if (sinAct) { e.preventDefault(); alert('Cada ítem del vale debe cargarse a una actividad del WBS.'); }
    });
});
</script>
