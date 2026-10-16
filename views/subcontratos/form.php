<?php
/**
 * Vista: Crear / Editar Subcontrato (Fase 3) con control presupuestal en vivo.
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$jsonSeguro = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$sub = $data['sub'];
$post = $data['post'];
$proyectos = $data['proyectos'];
$proveedores = $data['proveedores'];
$esEdicion = $sub !== null;
$subId = $esEdicion ? (int)$sub['id'] : 0;
$val = fn($k, $def = '') => $post[$k] ?? ($sub[$k] ?? $def);
$bloqueado = $esEdicion && (int)$sub['n_val'] > 0;      // con valorizaciones no cambian proveedor, actividad ni retención
$accion = $esEdicion ? BASE_URL . '/subcontratos/editar/' . $subId : BASE_URL . '/subcontratos/crear';
$valorizado = $esEdicion ? (float)$sub['valorizado'] : 0.0;
$estiloInput = 'width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; color:#1e293b; background:#fff; box-sizing:border-box;';
$estiloLabel = 'display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;';
include __DIR__ . '/../presupuesto/_live.php';
?>
<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px; max-width:1100px; margin:0 auto;">

    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:18px 22px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:12px; background:linear-gradient(135deg,#14b8a6,#0f766e); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px;"><i class="fas fa-handshake"></i></div>
            <div>
                <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0 0 2px;"><?= $esEdicion ? 'Editar subcontrato ' . $h($sub['codigo']) : 'Nuevo subcontrato' ?></h2>
                <span style="font-size:12.5px; color:#64748b;">Contrato con un tercero imputado a una actividad del WBS · se valoriza por avance</span>
            </div>
        </div>
        <a href="<?= BASE_URL ?><?= $esEdicion ? '/subcontratos/ver/' . $subId : '/subcontratos' ?>" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:8px 16px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none;"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if ($bloqueado): ?>
    <div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; border-radius:12px; padding:11px 16px; margin-bottom:16px; font-size:12.5px;">
        <i class="fas fa-info-circle"></i> Este contrato ya tiene valorizaciones: el subcontratista, la actividad y la retención de garantía no pueden cambiar. El monto no puede ser menor a lo valorizado (S/ <?= number_format($valorizado, 2) ?>) y el adelanto no menor a lo amortizado (S/ <?= number_format((float)$sub['amortizado'], 2) ?>).
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= $accion ?>" id="formSub">
        <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
        <?php if ($esEdicion): ?><input type="hidden" name="proyecto_id" value="<?= (int)$sub['proyecto_id'] ?>"><?php endif; ?>

        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:22px; margin-bottom:20px;">
            <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0 0 16px; padding-bottom:10px; border-bottom:1px solid #f1f5f9;"><i class="fas fa-info-circle" style="color:#0f766e;"></i> Datos del contrato</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                <div>
                    <label style="<?= $estiloLabel ?>">Proyecto *</label>
                    <select <?= $esEdicion ? 'disabled' : 'name="proyecto_id" required' ?> id="selProyecto" style="<?= $estiloInput ?><?= $esEdicion ? ' background:#f1f5f9;' : '' ?>">
                        <option value="">— Selecciona el proyecto —</option>
                        <?php foreach ($proyectos as $p):
                            $sel = (int)($esEdicion ? $sub['proyecto_id'] : ($post['proyecto_id'] ?? 0)) === (int)$p['id']; ?>
                            <option value="<?= (int)$p['id'] ?>" <?= $sel ? 'selected' : '' ?>>[<?= $h($p['codigo']) ?>] <?= $h($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="<?= $estiloLabel ?>">Actividad del WBS *</label>
                    <select name="tarea_id" id="selTarea" required data-actual="<?= (int)$val('tarea_id', 0) ?>" <?= $bloqueado ? 'style="' . $estiloInput . ' background:#f1f5f9;" onfocus="this.blur()"' : 'style="' . $estiloInput . '"' ?>>
                        <option value="">— Elige el proyecto primero —</option>
                    </select>
                    <div id="estadoTareas" style="font-size:11.5px; color:#64748b; margin-top:5px; min-height:16px;"></div>
                </div>
                <div>
                    <label style="<?= $estiloLabel ?>">Subcontratista *</label>
                    <select name="proveedor_id" required <?= $bloqueado ? 'style="' . $estiloInput . ' background:#f1f5f9;" onfocus="this.blur()"' : 'style="' . $estiloInput . '"' ?>>
                        <option value="">— Selecciona un proveedor registrado —</option>
                        <?php foreach ($proveedores as $pv): ?>
                            <option value="<?= (int)$pv['id'] ?>" <?= (int)$val('proveedor_id', 0) === (int)$pv['id'] ? 'selected' : '' ?>><?= $h($pv['razon_social']) ?> · RUC <?= $h($pv['ruc']) ?><?= $pv['especialidad'] ? ' · ' . $h($pv['especialidad']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="font-size:11.5px; color:#64748b; margin-top:5px;">¿No está? <a href="<?= BASE_URL ?>/compras?tab=proveedores" style="color:#0f766e; font-weight:700;">Regístralo en Catálogos</a></div>
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="<?= $estiloLabel ?>">Servicio subcontratado *</label>
                    <input type="text" name="servicio" required maxlength="255" value="<?= $h($val('servicio')) ?>" placeholder="Ej.: Montaje de estructura metálica y cobertura" style="<?= $estiloInput ?>">
                </div>
            </div>
        </div>

        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:22px; margin-bottom:20px;">
            <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0 0 16px; padding-bottom:10px; border-bottom:1px solid #f1f5f9;"><i class="fas fa-coins" style="color:#d97706;"></i> Condiciones económicas</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:16px;">
                <div><label style="<?= $estiloLabel ?>">Monto contratado (S/, sin IGV) *</label>
                    <input type="number" name="monto" id="inMonto" required step="0.01" min="0.01" value="<?= $h($val('monto')) ?>" style="<?= $estiloInput ?> font-weight:700; text-align:right;"></div>
                <div><label style="<?= $estiloLabel ?>">Adelanto entregado (S/)</label>
                    <input type="number" name="adelanto_monto" id="inAdelanto" step="0.01" min="0" value="<?= $h($val('adelanto_monto', '0')) ?>" style="<?= $estiloInput ?> text-align:right;">
                    <div id="pctAdelanto" style="font-size:11.5px; color:#94a3b8; margin-top:4px;"></div></div>
                <div><label style="<?= $estiloLabel ?>">Retención de garantía (%)</label>
                    <input type="number" name="retencion_pct" step="0.5" min="0" max="30" value="<?= $h($val('retencion_pct', '5')) ?>" <?= $bloqueado ? 'readonly style="' . $estiloInput . ' background:#f1f5f9; text-align:right;"' : 'style="' . $estiloInput . ' text-align:right;"' ?>>
                    <div style="font-size:11.5px; color:#94a3b8; margin-top:4px;">Se descuenta de cada valorización</div></div>
                <div><label style="<?= $estiloLabel ?>">Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?= $h($val('fecha_inicio')) ?>" style="<?= $estiloInput ?>"></div>
                <div><label style="<?= $estiloLabel ?>">Fin</label>
                    <input type="date" name="fecha_fin" value="<?= $h($val('fecha_fin')) ?>" style="<?= $estiloInput ?>"></div>
            </div>
            <div style="margin-top:16px;"><label style="<?= $estiloLabel ?>">Observaciones</label>
                <textarea name="observaciones" rows="2" maxlength="2000" style="<?= $estiloInput ?> font-family:inherit;"><?= $h($val('observaciones')) ?></textarea></div>
        </div>

        <div id="ctlLiveSub"></div>

        <div id="autExceso" style="background:#fffafa; border:1px solid #fecaca; border-radius:16px; padding:20px 22px; margin-bottom:20px; display:none;">
            <h3 style="font-size:15px; font-weight:800; color:#991b1b; margin:0 0 6px;"><i class="fas fa-user-shield"></i> <span id="autTitulo">Autorización de exceso de presupuesto</span></h3>
            <p id="autTexto" style="font-size:12.5px; color:#7f1d1d; margin:0 0 12px;"></p>
            <div id="autCampos">
                <label id="autCheckWrap" style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#7f1d1d; margin-bottom:10px; cursor:pointer;">
                    <input type="checkbox" name="autorizar_exceso" value="1" id="chkAutorizar"> Autorizo que este contrato supere el presupuesto de la actividad
                </label>
                <label style="<?= $estiloLabel ?>">Motivo de la autorización <span id="autReq">*</span></label>
                <textarea name="motivo_exceso" id="txtMotivoExceso" rows="2" maxlength="500" placeholder="Ej.: Alcance adicional aprobado por el cliente, se compensará con el adicional N.° 2…" style="<?= $estiloInput ?> font-family:inherit;"><?= $h($post['motivo_exceso'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <a href="<?= BASE_URL ?><?= $esEdicion ? '/subcontratos/ver/' . $subId : '/subcontratos' ?>" style="padding:10px 20px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:13.5px; font-weight:600; text-decoration:none;">Cancelar</a>
            <button type="submit" id="btnGuardar" style="background:linear-gradient(135deg,#14b8a6,#0f766e); color:#fff; border:none; padding:10px 26px; border-radius:10px; font-weight:700; font-size:13.5px; cursor:pointer; box-shadow:0 4px 12px rgba(15,118,110,.28);"><i class="fas fa-save"></i> <?= $esEdicion ? 'Guardar cambios' : 'Registrar subcontrato' ?></button>
        </div>
    </form>
</div>

<script>
const BASE_URL_JS = <?= $jsonSeguro(BASE_URL) ?>;
const SUB_ID = <?= $subId ?>;
const VALORIZADO = <?= json_encode($valorizado) ?>;
const BLOQUEADO = <?= $bloqueado ? 'true' : 'false' ?>;
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let tareas = [], puedeAutorizar = false;

function opcionesTareas(sel) {
    let html = '<option value="">— Elige la actividad —</option>';
    tareas.forEach(t => {
        // Se ofrecen las hojas; un padre solo si ya estaba seleccionado
        if (!t.es_hoja && String(t.id) !== String(sel)) return;
        html += `<option value="${t.id}" ${String(t.id) === String(sel) ? 'selected' : ''}>${'— '.repeat(t.nivel)}${esc(t.codigo)} · ${esc(t.nombre)}</option>`;
    });
    return html;
}

function cargarTareas(pid, conservar) {
    const sel = document.getElementById('selTarea'), est = document.getElementById('estadoTareas');
    if (!pid) { tareas = []; sel.innerHTML = '<option value="">— Elige el proyecto primero —</option>'; est.textContent = ''; return Promise.resolve(); }
    est.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando actividades…';
    return fetch(`${BASE_URL_JS}/subcontratos/tareasProyecto/${pid}${SUB_ID ? '?excluir_sub=' + SUB_ID : ''}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            tareas = d.success ? d.tareas : [];
            CtlLive.setDatos(tareas, d.control); puedeAutorizar = !!d.puede_autorizar;
            const previa = conservar ? sel.dataset.actual : '';
            sel.innerHTML = opcionesTareas(previa);
            const hojas = tareas.filter(t => t.es_hoja).length;
            est.innerHTML = tareas.length ? `${hojas} actividades operativas disponibles.`
                : `<span style="color:#b45309;">Este proyecto no tiene actividades. <a href="${BASE_URL_JS}/tareas?proyecto_id=${pid}" style="color:#0f766e; font-weight:700;">Crear el WBS</a></span>`;
            actualizarControl();
        })
        .catch(() => { est.innerHTML = '<span style="color:#b91c1c;">No se pudieron cargar las actividades.</span>'; });
}

function actualizarControl() {
    const tarea = document.getElementById('selTarea').value;
    const monto = parseFloat(document.getElementById('inMonto').value) || 0;
    const adel = parseFloat(document.getElementById('inAdelanto').value) || 0;
    document.getElementById('pctAdelanto').textContent = monto > 0 && adel > 0 ? (adel / monto * 100).toFixed(1) + ' % del contrato' : '';
    // Lo ya valorizado está en el costo real: solo el saldo nuevo compromete presupuesto
    const cargos = tarea ? {[tarea]: Math.max(0, monto - VALORIZADO)} : {};
    const ev = CtlLive.render(document.getElementById('ctlLiveSub'), CtlLive.evaluar(cargos), {
        titulo: 'Control presupuestal de este contrato',
        msgBloquear: 'El contrato supera el presupuesto de la actividad: para guardarlo debe autorizarse el exceso con un motivo.',
        msgAdvertir: 'Podrás guardarlo: el exceso quedará registrado en la bitácora de control presupuestal.'
    });
    const mostrar = ev.hayExceso && ev.modo !== 'informar', bloquea = mostrar && ev.modo === 'bloquear', exige = bloquea && puedeAutorizar;
    const txt = document.getElementById('txtMotivoExceso'), chk = document.getElementById('chkAutorizar'), btn = document.getElementById('btnGuardar');
    document.getElementById('autExceso').style.display = mostrar ? 'block' : 'none';
    document.getElementById('autCheckWrap').style.display = exige ? 'flex' : 'none';
    document.getElementById('autCampos').style.display = (bloquea && !puedeAutorizar) ? 'none' : 'block';
    chk.required = exige; txt.required = exige; txt.minLength = exige ? 10 : 0;
    document.getElementById('autReq').style.display = bloquea ? 'inline' : 'none';
    document.getElementById('autTitulo').textContent = bloquea ? 'Autorización de exceso de presupuesto' : 'Registro del exceso (modo advertir)';
    document.getElementById('autTexto').textContent = !mostrar ? '' : (bloquea
        ? (puedeAutorizar ? 'Tu usuario puede autorizar excesos. Queda registrado a tu nombre con el motivo que indiques.'
                          : 'El proyecto está en modo bloquear y tu usuario no puede autorizar excesos. Reduce el monto, cambia de actividad o pide a un responsable que lo registre.')
        : 'El contrato se registrará igual. Puedes dejar un motivo (opcional) para la bitácora.');
    btn.disabled = bloquea && !puedeAutorizar;
    btn.style.opacity = btn.disabled ? '.5' : '1'; btn.style.cursor = btn.disabled ? 'not-allowed' : 'pointer';
}

document.addEventListener('DOMContentLoaded', () => {
    const sp = document.getElementById('selProyecto');
    const pid = sp.value || document.querySelector('input[name=proyecto_id]')?.value || '';
    sp.addEventListener('change', () => { document.getElementById('selTarea').dataset.actual = ''; cargarTareas(sp.value, false); });
    cargarTareas(pid, true);
    ['selTarea', 'inMonto', 'inAdelanto'].forEach(id => document.getElementById(id).addEventListener(id === 'selTarea' ? 'change' : 'input', actualizarControl));
    actualizarControl();
});
</script>
