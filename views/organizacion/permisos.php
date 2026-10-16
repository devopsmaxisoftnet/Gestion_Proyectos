<?php
/**
 * Matriz Interactiva de Permisos por Cargo
 * Diseño Moderno con Switches Animados y Guardado Rápido
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$cargo = $data['cargo'];
$catalogo = $data['catalogoPermisos'];
$asignados = $data['permisosAsignados'];
$cargos = $data['cargos'];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
:root {
    --perm-primary: #6366f1;
    --perm-success: #10b981;
}

.perm-container {
    padding: 16px 24px 100px; /* Espacio para barra flotante */
    max-width: 100%;
    margin: 0 auto;
    box-sizing: border-box;
    animation: fadeIn 0.4s ease-out;
}

/* ─── Top Bar Unificado ─── */
.org-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 18px;
    background: #ffffff;
    padding: 14px 20px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
}

.org-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.org-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 10px rgba(124, 58, 237, 0.15);
}

.org-header-text h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.3px;
}

.org-header-text span {
    font-size: 12.5px;
    color: #64748b;
}

.org-top-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.org-view-modes {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 12px;
    gap: 4px;
}

.view-mode-btn {
    border: none;
    background: transparent;
    padding: 8px 16px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    text-decoration: none;
}

.view-mode-btn.active {
    background: #ffffff;
    color: #4f46e5;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.btn-volver-cargos {
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-volver-cargos:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

.perm-header-card {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    border-radius: 20px;
    padding: 24px 28px;
    color: #ffffff;
    box-shadow: 0 12px 28px -6px rgba(79, 70, 229, 0.35);
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.perm-header-info h2 {
    margin: 0 0 6px;
    font-size: 24px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 12px;
}

.perm-header-info p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

.perm-cargo-selector {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 14px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.perm-cargo-selector select {
    background: #ffffff;
    color: #0f172a;
    border: none;
    border-radius: 10px;
    padding: 8px 14px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    outline: none;
}

/* ─── Search & Bulk Actions ─── */
.perm-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
    background: #ffffff;
    padding: 16px 20px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.perm-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 450px;
}

.perm-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.perm-search-box input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border-radius: 10px;
    border: 1.5px solid #cbd5e1;
    font-size: 14px;
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
}

.perm-search-box input:focus {
    border-color: var(--perm-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.perm-bulk-buttons {
    display: flex;
    gap: 10px;
}

.btn-perm-bulk {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-perm-bulk:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* ─── Grid de Módulos ─── */
.perm-modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 22px;
}

.perm-module-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    transition: all 0.25s ease;
}

.perm-module-card:hover {
    box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}

.perm-module-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.perm-module-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    text-transform: capitalize;
}

.perm-module-title i {
    color: var(--perm-primary);
    font-size: 16px;
}

.perm-toggle-all-label {
    font-size: 12px;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    user-select: none;
}

.perm-items-list {
    padding: 14px 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.perm-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    border-radius: 10px;
    transition: background 0.15s;
}

.perm-item-row:hover {
    background: #f1f5f9;
}

.perm-item-info {
    flex: 1;
    min-width: 0;
    padding-right: 12px;
}

.perm-item-name {
    font-size: 13.5px;
    font-weight: 600;
    color: #1e293b;
    display: block;
    text-transform: capitalize;
}

.perm-item-desc {
    font-size: 11.5px;
    color: #64748b;
    display: block;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ─── Switch Animado CSS ─── */
.switch-perm {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.switch-perm input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider-perm {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 24px;
}

.slider-perm:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input:checked + .slider-perm {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

input:checked + .slider-perm:before {
    transform: translateX(20px);
}

/* ─── Floating Sticky Save Bar ─── */
.perm-sticky-bar {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.92);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 12px 28px;
    border-radius: 50px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 24px;
    color: #ffffff;
    animation: fadeInSlide 0.3s ease-out;
}

.perm-status-text {
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.perm-badge-counter {
    background: #6366f1;
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 13px;
}

.btn-guardar-flotante {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: none;
    padding: 10px 24px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-guardar-flotante:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}

/* Toast flotante de éxito */
.org-toast {
    position: fixed;
    top: 24px;
    right: 24px;
    background: #10b981;
    color: white;
    padding: 14px 22px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
    display: none;
    z-index: 10000;
    align-items: center;
    gap: 10px;
    animation: fadeInSlide 0.3s;
}
</style>

<div class="perm-container">
    <div class="org-toast" id="permToast">
        <i class="fas fa-check-circle"></i> <span id="toastMsg">Cambios guardados</span>
    </div>

    <!-- Top Unified Navigation Bar -->
    <?php
    $activeTab = 'cargos';
    $orgNavTitle = 'Matriz de Permisos & Seguridad Granular';
    $orgNavSubtitle = 'Configuración de autorizaciones para: ' . htmlspecialchars($cargo['nombre']);
    $customOrgActions = '<a href="' . BASE_URL . '/organizacion/cargos" class="btn-nav-secondary"><i class="fas fa-arrow-left"></i> Volver a Cargos</a>';
    include __DIR__ . '/navbar_organizacion.php';
    ?>

    <!-- Header Card -->
    <div class="perm-header-card">
        <div class="perm-header-info">
            <h2>
                <i class="fas fa-shield-alt"></i>
                Matriz de Permisos: <?= htmlspecialchars($cargo['nombre']) ?>
            </h2>
            <p>Área: <strong><?= htmlspecialchars($cargo['area_nombre']) ?></strong> · Código: <code><?= htmlspecialchars($cargo['codigo']) ?></code> · Nivel Jerárquico: <?= (int)$cargo['nivel_jerarquico'] ?></p>
        </div>

        <div class="perm-cargo-selector">
            <span>Cambiar Cargo:</span>
            <select onchange="window.location.href='<?= BASE_URL ?>/organizacion/permisos/' + this.value">
                <?php foreach ($cargos as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($c['id'] == $cargo['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['area_nombre']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Formulario Completo -->
    <form id="formMatrizPermisos" method="POST" action="<?= BASE_URL ?>/organizacion/guardarPermisos">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="cargo_id" value="<?= $cargo['id'] ?>">

        <!-- Toolbar -->
        <div class="perm-toolbar">
            <div class="perm-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="filtroPermisos" placeholder="Filtrar por módulo o acción (ej: compras, aprobar)..." oninput="filtrarMatriz()">
            </div>
            <div class="perm-bulk-buttons">
                <button type="button" class="btn-perm-bulk" onclick="marcarTodosGlobal(true)">
                    <i class="fas fa-check-double"></i> Seleccionar Todos
                </button>
                <button type="button" class="btn-perm-bulk" onclick="marcarTodosGlobal(false)">
                    <i class="fas fa-times"></i> Desmarcar Todos
                </button>
            </div>
        </div>

        <!-- Módulos Grid -->
        <div class="perm-modules-grid" id="gridModulos">
            <?php foreach ($catalogo as $modulo => $permisos): ?>
                <?php
                $moduloIcon = match($modulo) {
                    'convocatorias' => 'fas fa-search',
                    'oportunidades' => 'fas fa-bullseye',
                    'licitaciones' => 'fas fa-gavel',
                    'propuestas' => 'fas fa-paper-plane',
                    'contratos' => 'fas fa-file-contract',
                    'proyectos' => 'fas fa-project-diagram',
                    'edt' => 'fas fa-sitemap',
                    'compras' => 'fas fa-shopping-cart',
                    'almacenes' => 'fas fa-warehouse',
                    'personal' => 'fas fa-user-friends',
                    'subcontratos' => 'fas fa-handshake',
                    'valorizaciones' => 'fas fa-calculator',
                    'facturacion' => 'fas fa-file-invoice-dollar',
                    'tesoreria' => 'fas fa-money-bill-wave',
                    'cuentas' => 'fas fa-wallet',
                    'analisis' => 'fas fa-chart-line',
                    'clientes' => 'fas fa-building',
                    'empresas' => 'fas fa-city',
                    'profesionales' => 'fas fa-users-cog',
                    'usuarios' => 'fas fa-user-shield',
                    'configuracion' => 'fas fa-cog',
                    'organizacion' => 'fas fa-sitemap',
                    default => 'fas fa-cube'
                };
                ?>
                <div class="perm-module-card" data-modulo="<?= strtolower($modulo) ?>">
                    <div class="perm-module-header">
                        <div class="perm-module-title">
                            <i class="<?= $moduloIcon ?>"></i>
                            <span><?= htmlspecialchars($modulo) ?></span>
                        </div>
                        <label class="perm-toggle-all-label">
                            <input type="checkbox" onchange="toggleModulo('<?= $modulo ?>', this.checked)">
                            <span>Marcar todo</span>
                        </label>
                    </div>

                    <div class="perm-items-list" id="modulo-list-<?= $modulo ?>">
                        <?php foreach ($permisos as $p): ?>
                            <?php $checked = in_array((int)$p['id'], $asignados); ?>
                            <div class="perm-item-row" data-accion="<?= strtolower($p['accion']) ?>">
                                <div class="perm-item-info">
                                    <span class="perm-item-name"><?= htmlspecialchars($p['accion']) ?></span>
                                    <span class="perm-item-desc"><?= htmlspecialchars($p['descripcion'] ?: "Permite {$p['accion']} en {$modulo}") ?></span>
                                </div>
                                <label class="switch-perm">
                                    <input type="checkbox" name="permisos[]" value="<?= $p['id'] ?>" class="perm-checkbox-item perm-check-<?= $modulo ?>" <?= $checked ? 'checked' : '' ?> onchange="actualizarContador()">
                                    <span class="slider-perm"></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sticky Floating Save Bar -->
        <div class="perm-sticky-bar">
            <div class="perm-status-text">
                <i class="fas fa-lock-open"></i> Permisos Asignados:
                <span class="perm-badge-counter" id="contadorPermisos"><?= count($asignados) ?></span>
            </div>
            <button type="submit" class="btn-guardar-flotante" id="btnGuardar">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
function actualizarContador() {
    const checked = document.querySelectorAll('.perm-checkbox-item:checked').length;
    document.getElementById('contadorPermisos').innerText = checked;
}

function toggleModulo(modulo, state) {
    document.querySelectorAll('.perm-check-' + modulo).forEach(cb => {
        cb.checked = state;
    });
    actualizarContador();
}

function marcarTodosGlobal(state) {
    document.querySelectorAll('.perm-checkbox-item').forEach(cb => {
        cb.checked = state;
    });
    actualizarContador();
}

function filtrarMatriz() {
    const q = document.getElementById('filtroPermisos').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.perm-module-card');

    cards.forEach(card => {
        const modName = card.getAttribute('data-modulo');
        let cardMatches = modName.includes(q);

        const rows = card.querySelectorAll('.perm-item-row');
        let childMatches = 0;

        rows.forEach(r => {
            const acc = r.getAttribute('data-accion');
            const desc = r.querySelector('.perm-item-desc').innerText.toLowerCase();
            if (acc.includes(q) || desc.includes(q) || cardMatches) {
                r.style.display = 'flex';
                childMatches++;
            } else {
                r.style.display = 'none';
            }
        });

        if (cardMatches || childMatches > 0) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Guardado vía AJAX con feedback fluido
document.getElementById('formMatrizPermisos').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardar');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;

    const formData = new FormData(this);

    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        if (data.success) {
            mostrarToast(data.message || 'Permisos actualizados correctamente');
            if (typeof data.totalAsignados !== 'undefined') {
                document.getElementById('contadorPermisos').innerText = data.totalAsignados;
            }
        } else {
            alert('Error: ' + (data.message || 'No se pudo guardar'));
        }
    })
    .catch(err => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        // Si falló el AJAX, hacer submit tradicional
        document.getElementById('formMatrizPermisos').submit();
    });
});

function mostrarToast(msg) {
    const toast = document.getElementById('permToast');
    document.getElementById('toastMsg').innerText = msg;
    toast.style.display = 'flex';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3500);
}
</script>
