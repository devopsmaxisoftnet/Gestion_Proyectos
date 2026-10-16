<?php
/**
 * Modal Unificado y Premium: Asignar Cargo y Área Organizacional
 * Proporciona previsualización dinámica del colaborador, nivel jerárquico y sincronización de área.
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$modalCsrfToken = CsrfHelper::getToken();

// Carga segura de datos si no estuvieran disponibles en el scope
if (empty($usuariosModal)) {
    $usuariosModal = dbQueryAll("
        SELECT u.id, u.nombre, u.email, u.avatar, u.cargo_id, u.org_area_id,
               c.nombre as cargo_nombre, c.codigo as cargo_codigo, c.nivel_jerarquico,
               a.nombre as area_nombre, a.codigo as area_codigo
        FROM usuarios u
        LEFT JOIN org_cargos c ON u.cargo_id = c.id
        LEFT JOIN org_areas a ON u.org_area_id = a.id
        WHERE u.activo = 1
        ORDER BY u.nombre ASC
    ");
}

if (empty($cargosModal)) {
    $cargosModal = dbQueryAll("
        SELECT c.id, c.nombre, c.codigo, c.area_id, c.nivel_jerarquico,
               a.nombre as area_nombre, a.codigo as area_codigo
        FROM org_cargos c
        LEFT JOIN org_areas a ON c.area_id = a.id
        WHERE c.activo = 1
        ORDER BY c.nivel_jerarquico ASC, c.nombre ASC
    ");
}

if (empty($areasModal)) {
    $areasModal = dbQueryAll("
        SELECT id, nombre, codigo, nivel, parent_id
        FROM org_areas
        WHERE activo = 1
        ORDER BY nivel ASC, orden ASC, nombre ASC
    ");
}
?>

<!-- Estilos Scoped para el Modal de Asignación -->
<style>
@keyframes modalPopIn {
    0% { opacity: 0; transform: scale(0.92) translateY(16px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-asig-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.68);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.modal-asig-card {
    background: #ffffff;
    border-radius: 22px;
    width: 100%;
    max-width: 560px;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.2);
    overflow: hidden;
    animation: modalPopIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}

.modal-asig-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #31104b 100%);
    padding: 22px 26px;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.modal-asig-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
    flex-shrink: 0;
}

.modal-asig-close {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-asig-close:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    transform: rotate(90deg);
}

.modal-asig-body {
    padding: 24px 28px;
    background: #ffffff;
}

/* Tarjeta interactiva del Colaborador */
.modal-asig-user-preview {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.25s;
}

.modal-asig-user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.25);
    flex-shrink: 0;
}

.modal-asig-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 6px;
}

.modal-asig-select-wrap {
    position: relative;
    margin-bottom: 18px;
}

.modal-asig-select-wrap i.prefix-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
    pointer-events: none;
}

.modal-asig-select {
    width: 100%;
    padding: 11px 14px 11px 40px;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 500;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
    cursor: pointer;
    font-family: inherit;
}

.modal-asig-select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

/* Card Dinámica de Nivel Jerárquico */
.modal-asig-hierarchy-pill {
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    border-radius: 12px;
    padding: 10px 14px;
    margin-top: -8px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    animation: slideInUpFade 0.25s ease-out;
}

.modal-asig-hierarchy-title {
    font-size: 12px;
    font-weight: 700;
    color: #7c3aed;
    display: flex;
    align-items: center;
    gap: 6px;
}

.modal-asig-hierarchy-desc {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

.modal-asig-footer {
    padding: 16px 28px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
}

.btn-asig-cancel {
    padding: 10px 18px;
    border-radius: 11px;
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-asig-cancel:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}

.btn-asig-submit {
    padding: 10px 22px;
    border-radius: 11px;
    border: none;
    background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);
    color: #ffffff;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);
    transition: all 0.25s;
}

.btn-asig-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(124, 58, 237, 0.4);
}
</style>

<!-- MODAL UNIFICADO: Asignar Puesto y Área -->
<div class="modal-asig-overlay" id="modalAsignarUnificado" onclick="if(event.target===this) cerrarModalAsignar()">
    <div class="modal-asig-card">
        
        <!-- Header -->
        <div class="modal-asig-header">
            <div style="display:flex; align-items:center; gap:14px;">
                <div class="modal-asig-header-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:16.5px; font-weight:800; letter-spacing:-0.2px;">Asignar Cargo y Jerarquía Oficial</h3>
                    <span style="font-size:12px; color:#cbd5e1; opacity:0.85;">Establece el puesto en el organigrama y el alcance en el ERP</span>
                </div>
            </div>
            <button class="modal-asig-close" onclick="cerrarModalAsignar()" title="Cerrar modal (Esc)">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Formulario -->
        <form method="POST" action="<?= BASE_URL ?>/organizacion/asignarUsuario" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= $modalCsrfToken ?>">

            <div class="modal-asig-body">

                <!-- Vista Previa del Colaborador Seleccionado -->
                <div class="modal-asig-user-preview" id="modalAsigUserCard">
                    <div class="modal-asig-user-avatar" id="modalAsigAvatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div id="modalAsigNombre" style="font-size:14px; font-weight:800; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            Selecciona un colaborador
                        </div>
                        <div id="modalAsigEmail" style="font-size:12px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            El puesto asignará automáticamente sus permisos de área
                        </div>
                    </div>
                    <span id="modalAsigBadgeActual" style="background:#ede9fe; color:#7c3aed; font-size:11px; font-weight:700; padding:4px 8px; border-radius:8px; white-space:nowrap;">
                        Pendiente
                    </span>
                </div>

                <!-- Selector 1: Colaborador / Usuario -->
                <div class="modal-asig-label">
                    <span><i class="fas fa-user-check" style="color:#6366f1;"></i> 1. Colaborador / Usuario *</span>
                    <span style="font-size:11px; color:#64748b; font-weight:500;">Requerido</span>
                </div>
                <div class="modal-asig-select-wrap">
                    <i class="fas fa-user prefix-icon"></i>
                    <select name="usuario_id" id="modalAsigUsuarioSelect" required onchange="alCambiarUsuarioModal(this)" class="modal-asig-select">
                        <option value="">-- Seleccionar Colaborador --</option>
                        <?php foreach ($usuariosModal as $u): ?>
                            <?php 
                                $ini = strtoupper(substr($u['nombre'] ?? 'U', 0, 2));
                                $puestoActual = !empty($u['cargo_nombre']) ? $u['cargo_nombre'] : 'Sin puesto asignado';
                            ?>
                            <option value="<?= $u['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($u['nombre']) ?>"
                                    data-email="<?= htmlspecialchars($u['email']) ?>"
                                    data-initials="<?= $ini ?>"
                                    data-cargo-id="<?= $u['cargo_id'] ?? '' ?>"
                                    data-cargo-nombre="<?= htmlspecialchars($puestoActual) ?>"
                                    data-area-id="<?= $u['org_area_id'] ?? '' ?>">
                                <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['email']) ?>) <?= !empty($u['cargo_nombre']) ? '— [' . htmlspecialchars($u['cargo_nombre']) . ']' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Selector 2: Cargo Corporativo -->
                <div class="modal-asig-label">
                    <span><i class="fas fa-briefcase" style="color:#0ea5e9;"></i> 2. Cargo Corporativo Oficial *</span>
                    <span style="font-size:11px; color:#64748b; font-weight:500;">Organigrama</span>
                </div>
                <div class="modal-asig-select-wrap" style="margin-bottom:10px;">
                    <i class="fas fa-id-badge prefix-icon"></i>
                    <select name="cargo_id" id="modalAsigCargoSelect" required onchange="alCambiarCargoModal(this)" class="modal-asig-select">
                        <option value="">-- Seleccionar Cargo Oficial --</option>
                        <?php foreach ($cargosModal as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                    data-area-id="<?= $c['area_id'] ?>"
                                    data-area-nombre="<?= htmlspecialchars($c['area_nombre'] ?? '') ?>"
                                    data-nivel="<?= (int)$c['nivel_jerarquico'] ?>"
                                    data-codigo="<?= $c['codigo'] ?>">
                                <?= htmlspecialchars($c['nombre']) ?> [<?= $c['codigo'] ?>] · <?= htmlspecialchars($c['area_nombre'] ?? 'Sin área') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Card Dinámica de Nivel Jerárquico -->
                <div class="modal-asig-hierarchy-pill" id="modalAsigHierarchyBox" style="display:none;">
                    <div>
                        <div class="modal-asig-hierarchy-title" id="modalAsigNivelBadge">
                            <i class="fas fa-shield-alt"></i> Nivel Jerárquico
                        </div>
                        <div class="modal-asig-hierarchy-desc" id="modalAsigNivelDesc">
                            Alcance de acceso a módulos del ERP.
                        </div>
                    </div>
                    <span id="modalAsigAreaSugerida" style="font-size:11px; font-weight:700; color:#4338ca; background:#e0e7ff; padding:3px 8px; border-radius:6px;">
                        Área vinculada
                    </span>
                </div>

                <!-- Selector 3: Área / Departamento -->
                <div class="modal-asig-label" style="margin-top:14px;">
                    <span><i class="fas fa-sitemap" style="color:#f59e0b;"></i> 3. Área / Departamento Organizacional</span>
                    <span style="font-size:11px; color:#059669; font-weight:600;" id="modalAsigAreaAutoMsg">Auto-asignada por el Cargo</span>
                </div>
                <div class="modal-asig-select-wrap">
                    <i class="fas fa-building prefix-icon"></i>
                    <select name="area_id" id="modalAsigAreaSelect" class="modal-asig-select">
                        <option value="">-- Área predeterminada según el Cargo --</option>
                        <?php foreach ($areasModal as $a): ?>
                            <option value="<?= $a['id'] ?>" data-codigo="<?= $a['codigo'] ?>" data-nivel="<?= $a['nivel'] ?>">
                                <?= str_repeat('— ', (int)$a['nivel']) . htmlspecialchars($a['nombre']) ?> [<?= $a['codigo'] ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Footer con Botones -->
            <div class="modal-asig-footer">
                <button type="button" class="btn-asig-cancel" onclick="cerrarModalAsignar()">
                    Cancelar
                </button>
                <button type="submit" class="btn-asig-submit" id="btnAsigConfirmar">
                    <i class="fas fa-check-circle"></i> Confirmar y Asignar Puesto
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lógica JS Universal para el Modal de Asignación -->
<script>
function abrirModalAsignar(userId = null, userName = null, cargoId = null, areaId = null) {
    const modal = document.getElementById('modalAsignarUnificado');
    if (!modal) return;

    const userSelect = document.getElementById('modalAsigUsuarioSelect');
    const cargoSelect = document.getElementById('modalAsigCargoSelect');
    const areaSelect = document.getElementById('modalAsigAreaSelect');

    if (userId) {
        userSelect.value = userId;
        alCambiarUsuarioModal(userSelect);
    } else {
        userSelect.value = '';
        alCambiarUsuarioModal(userSelect);
    }

    if (cargoId) {
        cargoSelect.value = cargoId;
        alCambiarCargoModal(cargoSelect);
    } else {
        cargoSelect.value = '';
        document.getElementById('modalAsigHierarchyBox').style.display = 'none';
    }

    if (areaId) {
        areaSelect.value = areaId;
    }

    modal.style.display = 'flex';
}

// Compatibilidad con llamados directos anteriores
function abrirModalAsignarDirecto() {
    abrirModalAsignar();
}

function abrirModalAsignarIndividual(userId, userName, cargoId, areaId) {
    abrirModalAsignar(userId, userName, cargoId, areaId);
}

function cerrarModalAsignar() {
    const modal = document.getElementById('modalAsignarUnificado');
    if (modal) modal.style.display = 'none';
    
    // También cerramos backdrops antiguos si existiesen en la vista
    const oldBackdrop = document.getElementById('modalAsignarBackdrop');
    if (oldBackdrop) oldBackdrop.style.display = 'none';
    const oldModalRol = document.getElementById('modalAsignarRol');
    if (oldModalRol) oldModalRol.style.display = 'none';
}

function alCambiarUsuarioModal(sel) {
    const opt = sel.options[sel.selectedIndex];
    const cardNombre = document.getElementById('modalAsigNombre');
    const cardEmail = document.getElementById('modalAsigEmail');
    const cardAvatar = document.getElementById('modalAsigAvatar');
    const cardBadge = document.getElementById('modalAsigBadgeActual');

    if (!opt || !opt.value) {
        cardNombre.textContent = 'Selecciona un colaborador';
        cardEmail.textContent = 'El puesto asignará automáticamente sus permisos de área';
        cardAvatar.innerHTML = '<i class="fas fa-user"></i>';
        cardBadge.textContent = 'Sin Selección';
        cardBadge.style.background = '#f1f5f9';
        cardBadge.style.color = '#64748b';
        return;
    }

    const nombre = opt.getAttribute('data-nombre') || opt.text;
    const email = opt.getAttribute('data-email') || '';
    const initials = opt.getAttribute('data-initials') || 'U';
    const cargoNombre = opt.getAttribute('data-cargo-nombre') || 'Sin puesto asignado';

    cardNombre.textContent = nombre;
    cardEmail.textContent = email;
    cardAvatar.textContent = initials;
    cardBadge.textContent = 'Actual: ' + cargoNombre;
    cardBadge.style.background = '#ede9fe';
    cardBadge.style.color = '#7c3aed';
}

function alCambiarCargoModal(sel) {
    const opt = sel.options[sel.selectedIndex];
    const hierarchyBox = document.getElementById('modalAsigHierarchyBox');
    const nivelBadge = document.getElementById('modalAsigNivelBadge');
    const nivelDesc = document.getElementById('modalAsigNivelDesc');
    const areaSugerida = document.getElementById('modalAsigAreaSugerida');
    const areaSelect = document.getElementById('modalAsigAreaSelect');

    if (!opt || !opt.value) {
        hierarchyBox.style.display = 'none';
        return;
    }

    const areaId = opt.getAttribute('data-area-id');
    const areaNombre = opt.getAttribute('data-area-nombre') || 'Área Oficial';
    const nivel = parseInt(opt.getAttribute('data-nivel') || '3');

    // Auto-seleccionar área
    if (areaId && areaSelect) {
        areaSelect.value = areaId;
    }

    hierarchyBox.style.display = 'flex';
    areaSugerida.textContent = areaNombre;

    if (nivel <= 1) {
        nivelBadge.innerHTML = '<i class="fas fa-crown" style="color:#eab308;"></i> Nivel 0 / 1 · Alta Dirección / Gerencia';
        nivelDesc.textContent = 'Acceso gerencial amplio con supervisión de todas las sub-áreas dependientes.';
        hierarchyBox.style.background = '#fefce8';
        hierarchyBox.style.borderColor = '#fef08a';
    } else if (nivel === 2 || nivel === 3) {
        nivelBadge.innerHTML = '<i class="fas fa-user-shield" style="color:#6366f1;"></i> Nivel 2 · Jefatura de Área';
        nivelDesc.textContent = 'Acceso ESTRICTO y exclusivo a las operaciones del módulo de su área.';
        hierarchyBox.style.background = '#eef2ff';
        hierarchyBox.style.borderColor = '#c7d2fe';
    } else {
        nivelBadge.innerHTML = '<i class="fas fa-user-cog" style="color:#059669;"></i> Nivel 3 · Asistente / Especialista Operativo';
        nivelDesc.textContent = 'Operación y registro en el módulo de su área (sin autorizaciones críticas).';
        hierarchyBox.style.background = '#f0fdf4';
        hierarchyBox.style.borderColor = '#bbf7d0';
    }
}

// Cerrar con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalAsignar();
    }
});
</script>
