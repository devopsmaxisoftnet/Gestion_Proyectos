<?php
/**
 * Vista: Crear / Editar Orden de Compra (Fase 3: Logística)
 * Cada línea se imputa a una actividad del WBS (cargada por AJAX según el proyecto) y
 * opcionalmente a un ítem del catálogo de materiales o equipos.
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$jsonSeguro = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

$modo = $data['modo'] ?? 'crear';
$esEdicion = $modo === 'editar';
$ordenId = (int)($data['ordenId'] ?? 0);
$orden = $data['orden'] ?? [];
$items = $data['items'] ?? [];
$proyectos = $data['proyectos'] ?? [];
$proveedores = $data['proveedores'] ?? [];
$catalogoItems = $data['catalogoItems'] ?? ['materiales' => [], 'equipos' => []];

$val = fn($k, $def = '') => $orden[$k] ?? $def;
$accion = $esEdicion ? BASE_URL . '/compras/editar/' . $ordenId : BASE_URL . '/compras/crear';
$condiciones = ['Contado contra entrega', 'Crédito 15 días', 'Crédito 30 días', 'Crédito 60 días', '50% adelanto, 50% conformidad'];
$condSel = $val('condicion_pago', 'Crédito 30 días');

// Líneas iniciales para el JS (solo campos necesarios)
$itemsIniciales = array_map(fn($i) => [
    'tarea_id' => !empty($i['tarea_id']) ? (int)$i['tarea_id'] : null,
    'material_id' => !empty($i['material_id']) ? (int)$i['material_id'] : null,
    'equipo_id' => !empty($i['equipo_id']) ? (int)$i['equipo_id'] : null,
    'descripcion' => (string)($i['descripcion'] ?? ''),
    'unidad_medida' => (string)($i['unidad_medida'] ?? 'UND'),
    'cantidad' => (float)($i['cantidad'] ?? 1),
    'precio_unitario' => (float)($i['precio_unitario'] ?? 0),
], $items);

$estiloInput = 'width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; color:#1e293b; background:#ffffff; box-sizing:border-box;';
$estiloLabel = 'display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;';
?>

<style>
.oc-cell input, .oc-cell select { width:100%; padding:8px 10px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:12.5px; box-sizing:border-box; background:#fff; color:#334155; }
.oc-cell input.num { text-align:right; font-weight:700; }
.oc-sin-actividad { border-color:#fcd34d !important; background:#fffbeb !important; }
#tablaItemsOC td { vertical-align:top; padding:10px 8px; }
</style>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:18px 22px; margin-bottom:20px; box-shadow:0 2px 10px rgba(0,0,0,0.02); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg, #f97316 0%, #ea580c 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:20px; box-shadow:0 4px 12px rgba(249,115,22,0.25);">
                <i class="fas <?= $esEdicion ? 'fa-edit' : 'fa-file-invoice' ?>"></i>
            </div>
            <div>
                <h2 style="font-size:18px; font-weight:800; color:#0f172a; margin:0 0 2px;">
                    <?= $esEdicion ? 'Editar Orden de Compra ' . $h($data['codigo'] ?? '') : 'Generar Nueva Orden de Compra' ?>
                </h2>
                <span style="font-size:12.5px; color:#64748b;">Cada línea se imputa a una actividad del WBS y alimenta su costo real</span>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/compras<?= $esEdicion ? '/ver/' . $ordenId : '' ?>" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; padding:8px 16px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form method="POST" action="<?= $h($accion) ?>" id="formNuevaOC">
        <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; margin-bottom:20px; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
            <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0 0 18px; border-bottom:1px solid #f1f5f9; padding-bottom:10px; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-info-circle" style="color:#f97316;"></i> Datos Generales de la Orden
            </h3>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px; margin-bottom:16px;">
                <div>
                    <label style="<?= $estiloLabel ?>">Proyecto Destino (Centro de Costo) *</label>
                    <select name="proyecto_id" id="selectProyecto" required style="<?= $estiloInput ?> font-weight:600;">
                        <option value="">-- Selecciona el proyecto --</option>
                        <?php foreach ($proyectos as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$val('proyecto_id', 0) === (int)$p['id']) ? 'selected' : '' ?>>[<?= $h($p['codigo']) ?>] <?= $h($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="estadoActividades" style="font-size:11.5px; color:#64748b; margin-top:5px; min-height:16px;"></div>
                </div>

                <div>
                    <label style="<?= $estiloLabel ?>">Proveedor de Bienes o Servicios *</label>
                    <select name="proveedor_id" id="selectProveedor" onchange="actualizarNombreProveedor(this)" required style="<?= $estiloInput ?> font-weight:600;">
                        <option value="">-- Selecciona un proveedor registrado --</option>
                        <?php foreach ($proveedores as $pv): ?>
                            <option value="<?= (int)$pv['id'] ?>" data-nombre="<?= $h($pv['razon_social']) ?>" <?= ((int)$val('proveedor_id', 0) === (int)$pv['id']) ? 'selected' : '' ?>>
                                <?= $h($pv['razon_social']) ?><?= !empty($pv['ruc']) ? ' (RUC: ' . $h($pv['ruc']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="proveedor_nombre" id="inputProveedorNombre" value="<?= $h($val('proveedor_nombre')) ?>">
                    <div style="font-size:11.5px; color:#64748b; margin-top:5px;">¿Falta el proveedor? <a href="<?= BASE_URL ?>/compras#proveedores" style="color:#ea580c; font-weight:700;">Regístralo en Catálogos</a></div>
                </div>

                <div>
                    <label style="<?= $estiloLabel ?>">Moneda</label>
                    <select name="moneda" id="selectMoneda" onchange="actualizarSimboloMoneda(this.value)" style="<?= $estiloInput ?> font-weight:600;">
                        <option value="PEN" <?= $val('moneda', 'PEN') === 'PEN' ? 'selected' : '' ?>>Soles (PEN S/)</option>
                        <option value="USD" <?= $val('moneda', 'PEN') === 'USD' ? 'selected' : '' ?>>Dólares Americanos (USD $)</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px;">
                <div>
                    <label style="<?= $estiloLabel ?>">Fecha de Emisión *</label>
                    <input type="date" name="fecha_emision" value="<?= $h($val('fecha_emision', date('Y-m-d'))) ?>" required style="<?= $estiloInput ?>">
                </div>
                <div>
                    <label style="<?= $estiloLabel ?>">Fecha de Entrega Requerida</label>
                    <input type="date" name="fecha_entrega" value="<?= $h($val('fecha_entrega', $esEdicion ? '' : date('Y-m-d', strtotime('+15 days')))) ?>" style="<?= $estiloInput ?>">
                </div>
                <div>
                    <label style="<?= $estiloLabel ?>">Condición de Pago</label>
                    <select name="condicion_pago" style="<?= $estiloInput ?> font-weight:600;">
                        <?php foreach ($condiciones as $c): ?>
                            <option value="<?= $h($c) ?>" <?= $condSel === $c ? 'selected' : '' ?>><?= $h($c) ?></option>
                        <?php endforeach; ?>
                        <?php if (!in_array($condSel, $condiciones, true)): ?>
                            <option value="<?= $h($condSel) ?>" selected><?= $h($condSel) ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label style="<?= $estiloLabel ?>">Lugar de Entrega / Despacho</label>
                    <input type="text" name="lugar_entrega" value="<?= $h($val('lugar_entrega')) ?>" placeholder="Ej: Almacén Principal Obra, Lima" style="<?= $estiloInput ?>">
                </div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; margin-bottom:20px; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #f1f5f9; padding-bottom:10px; flex-wrap:wrap; gap:10px;">
                <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-boxes" style="color:#f97316;"></i> Insumos, Materiales y Servicios Requeridos
                </h3>
                <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                    <label style="display:inline-flex; align-items:center; gap:6px; font-size:12px; color:#475569; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="chkPadres" onchange="refrescarSelectoresActividad()"> Incluir actividades padre
                    </label>
                    <button type="button" onclick="agregarFilaItem()" style="background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; padding:6px 14px; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fas fa-plus"></i> Agregar Fila
                    </button>
                </div>
            </div>

            <div id="ctlLiveOC"></div>
            <div style="overflow-x:auto;">
                <table id="tablaItemsOC" style="width:100%; min-width:1000px; border-collapse:collapse; text-align:left; font-size:13px;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                            <th style="padding:10px 8px; width:36px; text-align:center;">#</th>
                            <th style="padding:10px 8px; width:210px;">Actividad (WBS)</th>
                            <th style="padding:10px 8px; width:200px;">Ítem del catálogo</th>
                            <th style="padding:10px 8px;">Descripción *</th>
                            <th style="padding:10px 8px; width:90px;">Unidad</th>
                            <th style="padding:10px 8px; width:90px; text-align:center;">Cantidad</th>
                            <th style="padding:10px 8px; width:110px; text-align:right;">P. Unitario</th>
                            <th style="padding:10px 8px; width:100px; text-align:right;">Subtotal</th>
                            <th style="padding:10px 8px; width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItemsOC"></tbody>
                </table>
            </div>
            <datalist id="listaUnidades">
                <?php foreach (['UND', 'BOLSA', 'KG', 'M', 'M2', 'M3', 'GLN', 'VAR', 'PZA', 'GLB', 'HR', 'DIA', 'SERV'] as $u): ?><option value="<?= $u ?>"><?php endforeach; ?>
            </datalist>

            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px; flex-wrap:wrap; margin-top:20px;">
                <div id="avisoSinActividad" style="display:none; flex:1; min-width:240px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:10px; padding:10px 14px; font-size:12px;">
                    <i class="fas fa-exclamation-triangle"></i> Hay líneas <strong>sin actividad</strong>: una vez aprobada la orden, ese gasto no se reflejará en el costo real de ninguna actividad del WBS.
                </div>
                <div style="width:320px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-left:auto;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px; color:#64748b;">
                        <span>Subtotal Neto:</span>
                        <strong style="color:#1e293b;"><span class="moneda-simbolo">S/</span> <span id="resSubtotal">0.00</span></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:13px; color:#64748b;">
                        <span>I.G.V. (18%):</span>
                        <strong style="color:#1e293b;"><span class="moneda-simbolo">S/</span> <span id="resIgv">0.00</span></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding-top:10px; border-top:1.5px solid #cbd5e1; font-size:15px; font-weight:800; color:#0f172a;">
                        <span>TOTAL GENERAL:</span>
                        <span style="color:#ea580c;"><span class="moneda-simbolo">S/</span> <span id="resTotal">0.00</span></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
            <div style="margin-bottom:18px;">
                <label style="<?= $estiloLabel ?>">Observaciones, Especificaciones Técnicas o Términos de Referencia</label>
                <textarea name="observaciones" rows="3" placeholder="Instrucciones especiales para el proveedor, certificados de calidad requeridos..." style="<?= $estiloInput ?>"><?= $h($val('observaciones')) ?></textarea>
            </div>

            <?php if ($esEdicion && ($orden['estado'] ?? '') === 'rechazada'): ?>
            <div style="background:#eff6ff; border:1px solid #dbeafe; color:#1e40af; border-radius:10px; padding:10px 14px; font-size:12.5px; margin-bottom:16px;">
                <i class="fas fa-info-circle"></i> Esta orden fue rechazada. Al guardar los cambios volverá a quedar <strong>pendiente de aprobación</strong>.
            </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <a href="<?= BASE_URL ?>/compras<?= $esEdicion ? '/ver/' . $ordenId : '' ?>" style="padding:10px 20px; border-radius:10px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13.5px; font-weight:600; text-decoration:none;">Cancelar</a>
                <button type="submit" style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%); color:#ffffff; border:none; padding:10px 26px; border-radius:10px; font-weight:700; font-size:13.5px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(249,115,22,0.3);">
                    <i class="fas fa-check-circle"></i> <?= $esEdicion ? 'Guardar Cambios' : 'Emitir Orden de Compra' ?>
                </button>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../presupuesto/_live.php'; ?>
<script>
const OC_ID = <?= $ordenId ?>;
const BASE_URL_JS = <?= $jsonSeguro(BASE_URL) ?>;
const CAT_MATERIALES = <?= $jsonSeguro($catalogoItems['materiales']) ?>;
const CAT_EQUIPOS = <?= $jsonSeguro($catalogoItems['equipos']) ?>;
const ITEMS_INICIALES = <?= $jsonSeguro($itemsIniciales) ?>;

const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let tareasProyecto = [];
let filaIndex = 0;

function actualizarNombreProveedor(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('inputProveedorNombre').value = opt ? (opt.getAttribute('data-nombre') || '') : '';
}

function actualizarSimboloMoneda(m) {
    const s = (m === 'USD') ? '$' : 'S/';
    document.querySelectorAll('.moneda-simbolo').forEach(el => el.textContent = s);
}

/* ── Actividades del proyecto (AJAX) ───────────────────────────────────── */
function opcionesActividad(seleccionada) {
    const verPadres = document.getElementById('chkPadres').checked;
    let html = '<option value="">— Sin imputar a actividad —</option>';
    tareasProyecto.forEach(t => {
        // Se muestran las hojas; un padre solo si se pidió o si ya está seleccionado
        if (!t.es_hoja && !verPadres && String(t.id) !== String(seleccionada)) return;
        html += `<option value="${t.id}" ${String(t.id) === String(seleccionada) ? 'selected' : ''}>${'— '.repeat(t.nivel)}${esc(t.codigo)} · ${esc(t.nombre)}</option>`;
    });
    return html;
}

function refrescarSelectoresActividad() {
    document.querySelectorAll('#tbodyItemsOC tr.fila-item .sel-actividad').forEach(sel => {
        sel.innerHTML = opcionesActividad(sel.dataset.tarea || '');
        marcarSinActividad(sel);
    });
    actualizarAvisoSinActividad();
}

function cargarActividades(proyectoId, conservarSeleccion) {
    const estado = document.getElementById('estadoActividades');
    if (!proyectoId) {
        tareasProyecto = [];
        estado.textContent = 'Selecciona un proyecto para cargar sus actividades.';
        refrescarSelectoresActividad();
        return Promise.resolve();
    }
    estado.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando actividades…';

    return fetch(`${BASE_URL_JS}/compras/tareasProyecto/${proyectoId}${OC_ID ? '?excluir_oc=' + OC_ID : ''}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(d => {
            tareasProyecto = d.success ? d.tareas : [];
            CtlLive.setDatos(tareasProyecto, d.control);
            const hojas = tareasProyecto.filter(t => t.es_hoja).length;
            if (!tareasProyecto.length) {
                estado.innerHTML = `<span style="color:#b45309;">Este proyecto aún no tiene actividades. <a href="${BASE_URL_JS}/tareas?proyecto_id=${proyectoId}" style="color:#ea580c; font-weight:700;">Crear el WBS</a></span>`;
            } else {
                estado.textContent = `${hojas} actividades operativas disponibles (${tareasProyecto.length} en total).`;
            }

            // Si cambia de proyecto, las selecciones previas dejan de ser válidas
            let reiniciadas = 0;
            document.querySelectorAll('#tbodyItemsOC tr.fila-item .sel-actividad').forEach(sel => {
                const previa = sel.dataset.tarea || '';
                const existe = tareasProyecto.some(t => String(t.id) === String(previa));
                if (previa && !(conservarSeleccion && existe)) { reiniciadas++; sel.dataset.tarea = ''; sel.innerHTML = opcionesActividad(''); }
                else { sel.innerHTML = opcionesActividad(previa); }
                marcarSinActividad(sel);
            });
            if (reiniciadas) estado.innerHTML += ` <span style="color:#b45309;">Se reiniciaron ${reiniciadas} imputaciones del proyecto anterior.</span>`;
            actualizarAvisoSinActividad();
            actualizarControl();
        })
        .catch(() => { estado.innerHTML = '<span style="color:#b91c1c;">No se pudieron cargar las actividades.</span>'; });
}

function marcarSinActividad(sel) {
    sel.classList.toggle('oc-sin-actividad', !sel.value);
}

function actualizarAvisoSinActividad() {
    const sin = [...document.querySelectorAll('#tbodyItemsOC .sel-actividad')].some(s => !s.value);
    document.getElementById('avisoSinActividad').style.display = sin ? 'block' : 'none';
}

/* ── Filas de ítems ────────────────────────────────────────────────────── */
function opcionesCatalogo(item) {
    const selM = item.material_id ? 'm:' + item.material_id : (item.equipo_id ? 'e:' + item.equipo_id : '');
    let html = '<option value="">— Texto libre / servicio —</option>';
    if (CAT_MATERIALES.length) {
        html += '<optgroup label="Materiales">' + CAT_MATERIALES.map(m =>
            `<option value="m:${m.id}" ${selM === 'm:' + m.id ? 'selected' : ''}>${esc(m.codigo)} · ${esc(m.nombre)}</option>`).join('') + '</optgroup>';
    }
    if (CAT_EQUIPOS.length) {
        html += '<optgroup label="Equipos">' + CAT_EQUIPOS.map(e =>
            `<option value="e:${e.id}" ${selM === 'e:' + e.id ? 'selected' : ''}>${esc(e.codigo)} · ${esc(e.nombre)}</option>`).join('') + '</optgroup>';
    }
    return html;
}

function agregarFilaItem(item) {
    item = item || {};
    const i = filaIndex++;
    const tr = document.createElement('tr');
    tr.className = 'fila-item oc-cell';
    tr.style.borderBottom = '1px solid #f1f5f9';
    tr.innerHTML = `
        <td style="text-align:center; color:#94a3b8; font-weight:700; padding-top:18px;" class="celda-num"></td>
        <td><select class="sel-actividad" name="items[${i}][tarea_id]" data-tarea="${item.tarea_id || ''}" onchange="this.dataset.tarea = this.value; marcarSinActividad(this); actualizarAvisoSinActividad(); actualizarControl()">${opcionesActividad(item.tarea_id)}</select></td>
        <td>
            <select class="sel-catalogo" onchange="aplicarCatalogo(this)">${opcionesCatalogo(item)}</select>
            <input type="hidden" class="h-material" name="items[${i}][material_id]" value="${item.material_id || ''}">
            <input type="hidden" class="h-equipo" name="items[${i}][equipo_id]" value="${item.equipo_id || ''}">
        </td>
        <td><input type="text" class="in-desc" name="items[${i}][descripcion]" required maxlength="255" value="${esc(item.descripcion)}" placeholder="Descripción del material o servicio..."></td>
        <td><input type="text" class="in-unidad" name="items[${i}][unidad_medida]" list="listaUnidades" maxlength="20" value="${esc(item.unidad_medida || 'UND')}"></td>
        <td><input type="number" class="num input-cantidad" step="0.01" min="0.01" name="items[${i}][cantidad]" value="${item.cantidad ?? 1}" required oninput="calcularTotales()"></td>
        <td><input type="number" class="num input-precio" step="0.01" min="0" name="items[${i}][precio_unitario]" value="${item.precio_unitario ?? 0}" required oninput="calcularTotales()"></td>
        <td style="text-align:right; padding-top:18px;"><span class="span-subtotal" style="font-weight:800; color:#0f172a; font-size:13px;">0.00</span></td>
        <td style="text-align:center; padding-top:12px;"><button type="button" onclick="eliminarFilaItem(this)" style="background:transparent; border:none; color:#cbd5e1; cursor:pointer; font-size:14px;" title="Eliminar fila"><i class="fas fa-trash-alt"></i></button></td>`;
    document.getElementById('tbodyItemsOC').appendChild(tr);
    marcarSinActividad(tr.querySelector('.sel-actividad'));
    renumerarFilas();
    calcularTotales();
    actualizarAvisoSinActividad();
}

/** Al elegir un ítem del catálogo: autocompleta descripción, unidad y precio referencial */
function aplicarCatalogo(sel) {
    const tr = sel.closest('tr');
    const hM = tr.querySelector('.h-material'), hE = tr.querySelector('.h-equipo');
    hM.value = ''; hE.value = '';
    if (!sel.value) return;

    const [tipo, id] = sel.value.split(':');
    if (tipo === 'm') {
        const m = CAT_MATERIALES.find(x => String(x.id) === id);
        if (!m) return;
        hM.value = m.id;
        tr.querySelector('.in-desc').value = m.nombre;
        tr.querySelector('.in-unidad').value = m.unidad_medida || 'UND';
        tr.querySelector('.input-precio').value = Number(m.precio_referencial || 0).toFixed(2);
    } else {
        const e = CAT_EQUIPOS.find(x => String(x.id) === id);
        if (!e) return;
        hE.value = e.id;
        tr.querySelector('.in-desc').value = e.nombre + ' (alquiler por hora)';
        tr.querySelector('.in-unidad').value = 'HR';
        tr.querySelector('.input-precio').value = Number(e.costo_hora || 0).toFixed(2);
    }
    calcularTotales();
}

function eliminarFilaItem(btn) {
    const tbody = document.getElementById('tbodyItemsOC');
    if (tbody.querySelectorAll('tr.fila-item').length <= 1) {
        alert('Debe existir al menos un ítem en la orden de compra.');
        return;
    }
    btn.closest('tr').remove();
    renumerarFilas();
    calcularTotales();
    actualizarAvisoSinActividad();
}

function renumerarFilas() {
    document.querySelectorAll('#tbodyItemsOC tr.fila-item .celda-num').forEach((td, i) => td.textContent = i + 1);
}

function calcularTotales() {
    let subtotal = 0;
    document.querySelectorAll('#tbodyItemsOC tr.fila-item').forEach(tr => {
        const tot = (parseFloat(tr.querySelector('.input-cantidad').value) || 0) * (parseFloat(tr.querySelector('.input-precio').value) || 0);
        subtotal += tot;
        tr.querySelector('.span-subtotal').textContent = tot.toFixed(2);
    });
    const igv = subtotal * 0.18;
    document.getElementById('resSubtotal').textContent = subtotal.toFixed(2);
    document.getElementById('resIgv').textContent = igv.toFixed(2);
    document.getElementById('resTotal').textContent = (subtotal + igv).toFixed(2);
    actualizarControl();
}

/* ── Control presupuestal en vivo: lo que esta orden agrega a cada actividad (sin IGV, igual que el costo del WBS) ── */
function actualizarControl() {
    const el = document.getElementById('ctlLiveOC');
    if (!el || typeof CtlLive === 'undefined') return;
    const cargos = {};
    document.querySelectorAll('#tbodyItemsOC tr.fila-item').forEach(tr => {
        const act = tr.querySelector('.sel-actividad').value;
        if (!act) return;
        const m = (parseFloat(tr.querySelector('.input-cantidad').value) || 0) * (parseFloat(tr.querySelector('.input-precio').value) || 0);
        cargos[act] = (cargos[act] || 0) + m;
    });
    CtlLive.render(el, CtlLive.evaluar(cargos), {
        titulo: 'Control presupuestal de esta orden',
        msgBloquear: 'Podrás guardarla, pero para aprobarla un usuario facultado deberá autorizar el exceso indicando un motivo.',
        msgAdvertir: 'Podrás guardarla y aprobarla: el exceso quedará registrado en la bitácora de control presupuestal.'
    });
}

/* ── Inicio ────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    const selProy = document.getElementById('selectProyecto');

    (ITEMS_INICIALES.length ? ITEMS_INICIALES : [{}]).forEach(agregarFilaItem);
    actualizarNombreProveedor(document.getElementById('selectProveedor'));
    actualizarSimboloMoneda(document.getElementById('selectMoneda').value);

    selProy.addEventListener('change', () => cargarActividades(selProy.value, false));
    // Carga inicial: conserva las imputaciones ya guardadas (edición / reintento tras error)
    cargarActividades(selProy.value, true).then(() => {
        // Si alguna línea apunta a una actividad padre, se activa el modo "incluir padres"
        const ids = new Set(ITEMS_INICIALES.map(i => String(i.tarea_id)).filter(Boolean));
        if (tareasProyecto.some(t => !t.es_hoja && ids.has(String(t.id)))) {
            document.getElementById('chkPadres').checked = true;
            refrescarSelectoresActividad();
        }
    });
});
</script>
