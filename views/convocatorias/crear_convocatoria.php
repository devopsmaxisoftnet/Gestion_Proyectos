<!-- Crear / Editar Convocatoria Detectada -->
<?php 
$item = $data['convocatoria'] ?? null; 
$perfilesExistentes = $data['perfiles'] ?? [];
if (empty($perfilesExistentes) && $item) {
    // Si no tiene perfiles estructurados pero tiene requisitos_profesionales
    $perfilesExistentes = [];
}
?>

<div class="convocatoria-modal-wrap">
    <?php if (!$isIframeModal): ?>
    <!-- Header del Modal cuando se abre en página completa -->
    <div class="conv-modal-header">
        <div>
            <h2><?= $item ? 'Editar convocatoria detectada' : 'Registrar convocatoria detectada' ?></h2>
            <p>Registra la información publicada en la fuente del concurso antes de evaluarla como oportunidad.</p>
        </div>
        <button type="button" class="conv-modal-close" onclick="cancelarModalOVolver()" title="Cerrar"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <form method="post" id="formConvocatoria" action="<?= BASE_URL ?>/?page=convocatorias&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>" onsubmit="return validarFormularioConvocatoria(event)">
        
        <!-- Entidad Convocante -->
        <div class="conv-field-group">
            <label class="conv-label">Entidad convocante</label>
            <input type="text" name="entidad" id="entidadInput" class="conv-input" required 
                   value="<?= htmlspecialchars($item['entidad'] ?? '') ?>" 
                   placeholder="Nombre de la entidad">
        </div>

        <!-- Objeto de la Convocatoria -->
        <div class="conv-field-group">
            <label class="conv-label">Objeto de la convocatoria</label>
            <input type="text" name="nombre" class="conv-input" required 
                   value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" 
                   placeholder="Consultoría u obra convocada">
        </div>

        <!-- Fila: Monto referencial y Duración -->
        <div class="conv-row-2">
            <div class="conv-field-group">
                <label class="conv-label">Monto referencial</label>
                <input type="number" step="0.01" name="monto_referencial" class="conv-input" 
                       value="<?= $item['monto_referencial'] ?? '' ?>" 
                       placeholder="0.00">
            </div>
            <div class="conv-field-group">
                <label class="conv-label">Duración del proyecto (días)</label>
                <input type="number" name="duracion_dias" class="conv-input" 
                       value="<?= $item['duracion_dias'] ?? '' ?>" 
                       placeholder="Ej. 180">
            </div>
        </div>

        <!-- Fila: Línea probable y Web del concurso -->
        <div class="conv-row-2">
            <div class="conv-field-group">
                <label class="conv-label">Línea probable</label>
                <select name="linea_negocio_id" class="conv-select">
                    <option value="">-- Seleccionar línea --</option>
                    <?php foreach ($data['lineas_negocio'] ?? [] as $ln): ?>
                    <option value="<?= $ln['id'] ?>" <?= (($item['linea_negocio_id'] ?? '') == $ln['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ln['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="conv-field-group">
                <label class="conv-label">Web del concurso</label>
                <input type="text" name="url_fuente" class="conv-input" 
                       value="<?= htmlspecialchars($item['url_fuente'] ?? '') ?>" 
                       placeholder="https://...">
            </div>
        </div>

        <!-- Sección: Cronograma de Licitación -->
        <div class="conv-section-title">Cronograma de licitación</div>
        
        <div class="conv-row-4">
            <div class="conv-field-group">
                <label class="conv-label">Publicación</label>
                <input type="date" name="fecha_publicacion" class="conv-input" 
                       value="<?= $item['fecha_publicacion'] ?? '' ?>">
            </div>
            <div class="conv-field-group">
                <label class="conv-label">Consultas</label>
                <input type="date" name="fecha_consultas" class="conv-input" 
                       value="<?= $item['fecha_consultas'] ?? '' ?>">
            </div>
            <div class="conv-field-group">
                <label class="conv-label">Presentación ofertas</label>
                <input type="date" name="fecha_limite_oferta" class="conv-input" 
                       value="<?= $item['fecha_limite_oferta'] ?? '' ?>">
            </div>
            <div class="conv-field-group">
                <label class="conv-label">Buena pro</label>
                <input type="date" name="fecha_buena_pro" class="conv-input" 
                       value="<?= $item['fecha_buena_pro'] ?? '' ?>">
            </div>
        </div>

        <!-- Tarjeta de Requerimiento de Personal / Perfiles -->
        <div class="conv-card-perfiles">
            <div class="perfiles-card-header">
                <div class="perfiles-total-wrap">
                    <label class="conv-label">Total profesionales</label>
                    <input type="number" name="cantidad_profesionales" id="totalProfesionales" 
                           class="conv-input perfiles-total-input" min="1" 
                           value="<?= $item['cantidad_profesionales'] ?? (count($perfilesExistentes) ?: 1) ?>" 
                           oninput="calcularTotalPerfiles()">
                </div>
                <div class="perfiles-status-badge" id="statusBadgeAsignados">
                    <span class="status-badge-count">Asignados: <strong id="lblAsignados">0</strong> de <strong id="lblTotal">1</strong></span>
                    <span class="status-badge-warn" id="lblRevisar" style="display:none;">Revisar</span>
                </div>
                <button type="button" class="btn-agregar-perfil" onclick="agregarFilaPerfil()">
                    <i class="fas fa-plus"></i> Agregar perfil
                </button>
            </div>

            <!-- Lista dinámica de perfiles -->
            <div id="contenedorPerfiles" class="perfiles-items-container">
                <!-- Se poblará vía JS o perfiles existentes -->
            </div>

            <div class="perfiles-card-footer">
                <button type="button" class="btn-perfil-inexistente" onclick="mostrarModalNuevoPerfil()">
                    <i class="fas fa-plus"></i> Perfil no existente
                </button>
                <div class="perfiles-footnote" id="footnotePerfiles">
                    Suma debe ser igual al total.
                </div>
            </div>
        </div>

        <!-- Acciones del Modal -->
        <div class="conv-modal-actions">
            <button type="button" class="btn-pill-cancel" onclick="cancelarModalOVolver()">Cancelar</button>
            <button type="submit" class="btn-pill-submit" id="btnGuardarConvocatoria">Guardar convocatoria</button>
        </div>
    </form>
</div>

<style>
/* ══════════════════════════════════════════════════════════
   ESTILOS EXACTOS MODAL: REGISTRAR CONVOCATORIA DETECTADA
   ══════════════════════════════════════════════════════════ */
.convocatoria-modal-wrap {
    max-width: 100%;
    margin: 0 auto;
    padding: 0;
    background: #ffffff;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1e293b;
}

.conv-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f5f9;
}
.conv-modal-header h2 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 2px 0;
    letter-spacing: -0.2px;
}
.conv-modal-header p {
    font-size: 11.5px;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}
.conv-modal-close {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: #94a3b8;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.conv-modal-close:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.conv-field-group {
    margin-bottom: 7px;
}
.conv-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
}
.conv-input, .conv-select {
    width: 100%;
    padding: 6px 11px;
    height: 35px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12.5px;
    color: #0f172a;
    font-family: inherit;
    transition: all 0.2s ease;
    outline: none;
    box-sizing: border-box;
}
.conv-input:focus, .conv-select:focus {
    background: #ffffff;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
}
.conv-select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
    padding-right: 32px;
    cursor: pointer;
}

.conv-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 6px;
}
.conv-row-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 6px;
}
@media (max-width: 520px) {
    .conv-row-4 {
        grid-template-columns: 1fr 1fr;
    }
}

.conv-section-title {
    font-size: 11.5px;
    font-weight: 700;
    color: #0f172a;
    margin: 6px 0 3px;
}

/* ── Tarjeta de Requerimiento de Personal (Compacta) ── */
.conv-card-perfiles {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    margin-top: 6px;
    margin-bottom: 8px;
}
.perfiles-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}
.perfiles-total-wrap {
    flex: 1;
    max-width: 170px;
}
.perfiles-total-input {
    background: #f8fafc;
    font-weight: 600;
    height: 30px;
    padding: 3px 8px;
}
.perfiles-status-badge {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 6px;
    padding: 3px 8px;
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}
.status-badge-count {
    font-size: 10.5px;
    font-weight: 700;
    color: #b45309;
}
.status-badge-warn {
    font-size: 9.5px;
    font-weight: 600;
    color: #d97706;
}

.btn-agregar-perfil {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 9px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    transition: all 0.2s;
    height: 30px;
}
.btn-agregar-perfil:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.perfiles-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: 5px;
    padding-top: 4px;
    border-top: 1px dashed #f1f5f9;
}

/* Fila de Perfil Dinámico (Compacta en 1 sola fila) */
.perfil-item-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 5px 8px;
    margin-bottom: 5px;
    animation: fadeInRow 0.2s ease-out;
}
.perfil-single-row {
    display: grid;
    grid-template-columns: 1fr 135px 55px 30px;
    gap: 6px;
    align-items: center;
}
.perfil-single-row select.conv-select,
.perfil-single-row input.conv-input {
    height: 30px !important;
    font-size: 11.5px !important;
    padding: 3px 6px !important;
}
.perfil-item-cantidad {
    text-align: center;
}
.btn-eliminar-perfil {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #94a3b8;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.btn-eliminar-perfil:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #ef4444;
}

.btn-perfil-inexistente {
    background: none;
    border: none;
    color: #0d9488;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    padding: 3px 0;
    margin-top: 3px;
}
.btn-perfil-inexistente:hover {
    text-decoration: underline;
    color: #0f766e;
}

.perfiles-footnote {
    font-size: 10.5px;
    color: #b45309;
    margin-top: 6px;
    line-height: 1.35;
}

/* ── Acciones / Botones Inferiores ── */
.conv-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
}
.btn-pill-cancel {
    padding: 7px 18px;
    border-radius: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-pill-cancel:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.btn-pill-submit {
    padding: 7px 20px;
    border-radius: 8px;
    background: #0f766e;
    border: none;
    color: #ffffff;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(15, 118, 110, 0.2);
}
.btn-pill-submit:hover {
    background: #0d9488;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 118, 110, 0.3);
}

@keyframes fadeInRow {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
// Catálogo precargado de perfiles
const catalogoPerfiles = <?= json_encode(array_column($data['catalogo_perfiles'] ?? [], 'nombre')) ?>;
const perfilesIniciales = <?= json_encode($perfilesExistentes) ?>;

function cancelarModalOVolver() {
    if (window.parent && window.parent.cerrarGlobalModal) {
        window.parent.cerrarGlobalModal();
    } else {
        window.location.href = '<?= BASE_URL ?>/convocatorias';
    }
}

function calcularTotalPerfiles() {
    const totalReq = parseInt(document.getElementById('totalProfesionales').value) || 0;
    const inputs = document.querySelectorAll('.perfil-item-cantidad');
    let suma = 0;
    inputs.forEach(inp => {
        suma += parseInt(inp.value) || 0;
    });

    const lblAsignados = document.getElementById('lblAsignados');
    const lblTotal = document.getElementById('lblTotal');
    const lblRevisar = document.getElementById('lblRevisar');
    const statusBadge = document.getElementById('statusBadgeAsignados');
    const footnote = document.getElementById('footnotePerfiles');

    if (lblAsignados) lblAsignados.textContent = suma;
    if (lblTotal) lblTotal.textContent = totalReq;

    if (suma !== totalReq || totalReq === 0) {
        if (lblRevisar) lblRevisar.style.display = 'block';
        if (statusBadge) {
            statusBadge.style.background = '#fffbeb';
            statusBadge.style.borderColor = '#fef3c7';
        }
        if (footnote) {
            footnote.style.color = '#dc2626';
            footnote.innerHTML = '⚠ La suma actual (' + suma + ') no coincide con el total de profesionales (' + totalReq + '). Ajusta las cantidades antes de guardar.';
        }
    } else {
        if (lblRevisar) lblRevisar.style.display = 'none';
        if (statusBadge) {
            statusBadge.style.background = '#ecfdf5';
            statusBadge.style.borderColor = '#a7f3d0';
        }
        if (footnote) {
            footnote.style.color = '#059669';
            footnote.innerHTML = '✔ La suma de profesionales coincide exactamente con el total requerido.';
        }
    }
}

function agregarFilaPerfil(datos = null) {
    const contenedor = document.getElementById('contenedorPerfiles');
    const pNombre = datos ? datos.perfil_nombre || datos.nombre || '' : '';
    const pTipo = datos ? datos.tipo_personal || datos.tipo || 'Profesional clave' : 'Profesional clave';
    const pCantidad = datos ? datos.cantidad || 1 : 1;

    let optionsHtml = '<option value="">Seleccionar perfil</option>';
    catalogoPerfiles.forEach(p => {
        const sel = (p === pNombre) ? 'selected' : '';
        optionsHtml += `<option value="${p}" ${sel}>${p}</option>`;
    });

    // Si viene un nombre personalizado que no está en el catálogo, agregarlo
    if (pNombre && !catalogoPerfiles.includes(pNombre)) {
        optionsHtml += `<option value="${pNombre}" selected>${pNombre}</option>`;
    }

    const rowId = 'perfil_' + Math.random().toString(36).substr(2, 9);
    const div = document.createElement('div');
    div.className = 'perfil-item-box';
    div.id = rowId;
    div.innerHTML = `
        <div class="perfil-single-row">
            <select name="perfil_nombre[]" class="conv-select perfil-select-nombre" required title="Perfil profesional">
                ${optionsHtml}
            </select>
            <select name="perfil_tipo[]" class="conv-select perfil-select-tipo" title="Tipo de personal">
                <option value="Profesional clave" ${pTipo === 'Profesional clave' ? 'selected' : ''}>Profesional clave</option>
                <option value="Profesional de apoyo" ${pTipo === 'Profesional de apoyo' ? 'selected' : ''}>Profesional de apoyo</option>
                <option value="Especialista técnico" ${pTipo === 'Especialista técnico' ? 'selected' : ''}>Especialista técnico</option>
                <option value="Consultor externo" ${pTipo === 'Consultor externo' ? 'selected' : ''}>Consultor externo</option>
            </select>
            <input type="number" name="perfil_cantidad[]" class="conv-input perfil-item-cantidad" min="1" value="${pCantidad}" oninput="calcularTotalPerfiles()" title="Cantidad" placeholder="1">
            <button type="button" class="btn-eliminar-perfil" onclick="eliminarFilaPerfil('${rowId}')" title="Eliminar perfil">
                <i class="far fa-trash-alt"></i>
            </button>
        </div>
    `;

    contenedor.appendChild(div);
    calcularTotalPerfiles();
}

function eliminarFilaPerfil(rowId) {
    const el = document.getElementById(rowId);
    if (el) {
        el.remove();
        calcularTotalPerfiles();
    }
}

function mostrarModalNuevoPerfil() {
    const nuevoPerfil = prompt("Ingresa el nombre del nuevo perfil profesional:");
    if (nuevoPerfil && nuevoPerfil.trim()) {
        const nombreLimpio = nuevoPerfil.trim();
        if (!catalogoPerfiles.includes(nombreLimpio)) {
            catalogoPerfiles.push(nombreLimpio);
        }
        // Agregar inmediatamente como fila
        agregarFilaPerfil({ nombre: nombreLimpio, tipo: 'Profesional clave', cantidad: 1 });
    }
}

function validarFormularioConvocatoria(e) {
    const totalReq = parseInt(document.getElementById('totalProfesionales').value) || 0;
    const inputs = document.querySelectorAll('.perfil-item-cantidad');
    let suma = 0;
    inputs.forEach(inp => { suma += parseInt(inp.value) || 0; });

    if (inputs.length === 0) {
        alert("Debes agregar al menos un perfil profesional requerido.");
        e.preventDefault();
        return false;
    }

    if (suma !== totalReq) {
        alert("La suma de las cantidades de perfiles (" + suma + ") debe ser exactamente igual al total de profesionales (" + totalReq + ").");
        const totalInput = document.getElementById('totalProfesionales');
        totalInput.focus();
        totalInput.style.borderColor = '#ef4444';
        e.preventDefault();
        return false;
    }

    return true;
}

// Cargar filas iniciales al iniciar
document.addEventListener('DOMContentLoaded', function() {
    if (perfilesIniciales && perfilesIniciales.length > 0) {
        perfilesIniciales.forEach(p => agregarFilaPerfil(p));
    } else {
        // Fila por defecto inicial
        agregarFilaPerfil({ nombre: 'Ingeniero Residente', tipo: 'Profesional clave', cantidad: 1 });
    }
    calcularTotalPerfiles();
});
</script>
