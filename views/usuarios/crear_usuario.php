<?php 
$item = $data['usuario'] ?? null;
$userEmail = strtolower($item['email'] ?? '');
$esCuentaRaiz = in_array($userEmail, ['devops.maxisoftnet@gmail.com', 'asconsultti@gmail.com']);
$esEdicion = !empty($item);
$userName = trim(($item['nombre'] ?? '') . ' ' . ($item['apellido'] ?? ''));
if (empty($userName)) { $userName = $esEdicion ? 'Usuario Corporativo' : 'Nuevo Usuario'; }
$userRol = $item['rol'] ?? 'usuario';
$tipoUsuarioActual = (int)($item['tipo_usuario_id'] ?? ($userRol === 'admin' ? 1 : 2));
?>

<style>
/* Estilos modernos y ordenados para Crear / Editar Usuario */
.user-form-wrapper {
    max-width: 680px;
    margin: 0 auto;
    padding: 20px 24px 36px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

/* Ficha Resumida del Colaborador (Idéntica a Solicitudes) */
.user-summary-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
}
.user-summary-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}
.user-summary-avatar {
    width: 44px;
    height: 44px;
    border-radius: 13px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.28);
    flex-shrink: 0;
}
.user-summary-avatar.is-root {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
}
.user-summary-name {
    font-weight: 800;
    color: #0f172a;
    font-size: 14.5px;
    letter-spacing: -0.2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-summary-email {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.user-pill-root {
    background: #f3e8ff;
    color: #7c3aed;
    border: 1px solid #d8b4fe;
}
.user-pill-active {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.user-pill-pending {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

/* Banner de Cuenta Raíz Protegida */
.root-protected-banner {
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
    border: 1.5px solid #d8b4fe;
    border-radius: 14px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 3px 10px rgba(124, 58, 237, 0.06);
}
.root-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.root-banner-title {
    font-size: 13.5px;
    font-weight: 800;
    color: #581c87;
    margin: 0;
}
.root-banner-desc {
    font-size: 11.5px;
    color: #7e22ce;
    margin: 2px 0 0;
    line-height: 1.35;
}

/* Tarjeta Formulario Principal */
.form-section-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 22px 22px;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
}

.section-title-label {
    font-size: 12.5px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 7px;
    display: flex;
    align-items: center;
    gap: 7px;
    letter-spacing: -0.1px;
}

.form-grid-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 16px;
}
@media (max-width: 580px) {
    .form-grid-2col {
        grid-template-columns: 1fr;
    }
}

.input-group-custom {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.input-label-custom {
    font-size: 11.5px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.input-control-styled {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 13px;
    color: #0f172a;
    background: #ffffff;
    box-sizing: border-box;
    transition: all 0.2s ease;
    font-family: inherit;
}
.input-control-styled:focus {
    outline: none;
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12);
    background: #ffffff;
}
.input-control-styled[readonly] {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #64748b;
    cursor: not-allowed;
}

/* Selectores Personalizados */
select.input-control-styled {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
    padding-right: 36px;
    appearance: none;
    -webkit-appearance: none;
}
select.input-control-styled optgroup {
    font-weight: 800;
    color: #047857;
    background: #f0fdf4;
    padding: 6px 0;
}
select.input-control-styled option {
    font-weight: 500;
    color: #1e293b;
    background: #ffffff;
    padding: 6px 12px;
}

.counter-pill {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 2px 8px;
    border-radius: 6px;
}

.helper-text-box {
    margin-top: 7px;
    font-size: 11.5px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Tarjetas Radio de Tipo de Acceso (Idénticas a Solicitudes) */
.role-cards-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 8px;
}
@media (max-width: 580px) {
    .role-cards-grid {
        grid-template-columns: 1fr;
    }
}
.role-card-box {
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 13px 14px;
    background: #ffffff;
    cursor: pointer;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}
.role-card-box:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    transform: translateY(-1px);
}
.role-card-box.is-selected-colab {
    border-color: #10b981;
    background: #f0fdf4;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.12);
}
.role-card-box.is-selected-admin {
    border-color: #8b5cf6;
    background: #faf5ff;
    box-shadow: 0 4px 14px rgba(139, 92, 246, 0.12);
}
.role-icon-square {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.role-icon-colab {
    background: #dcfce7;
    color: #15803d;
}
.role-icon-admin {
    background: #f3e8ff;
    color: #7c3aed;
}
.role-title-text {
    font-size: 12.5px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.role-desc-text {
    font-size: 11px;
    color: #64748b;
    margin-top: 3px;
    line-height: 1.35;
}

.notice-banner-green {
    margin-top: 10px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 9px 12px;
    font-size: 11.5px;
    font-weight: 600;
    color: #15803d;
    display: flex;
    align-items: center;
    gap: 7px;
}

/* Botones de Acción Footer */
.form-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}
.btn-action-cancel {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 18px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-action-cancel:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.btn-action-save {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 10px 22px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.28);
    transition: all 0.2s;
}
.btn-action-save:hover {
    background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
    box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35);
    transform: translateY(-1px);
}
</style>

<div class="user-form-wrapper">

    <?php if (!isset($_GET['modal_iframe'])): ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h2 style="font-size:19px; font-weight:800; color:#0f172a; margin:0;"><?= $esEdicion ? 'Editar Usuario' : 'Nuevo Usuario' ?></h2>
            <p style="font-size:12.5px; color:#64748b; margin:3px 0 0;">Configura los datos del usuario corporativo y su cargo en el organigrama.</p>
        </div>
        <a href="<?= BASE_URL ?>/usuarios" class="btn-action-cancel"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    <?php endif; ?>

    <!-- 1. Ficha Resumida del Colaborador -->
    <div class="user-summary-card">
        <div class="user-summary-left">
            <div class="user-summary-avatar <?= $esCuentaRaiz ? 'is-root' : '' ?>">
                <?php if ($esCuentaRaiz): ?>
                    <i class="fas fa-crown"></i>
                <?php else: ?>
                    <i class="fas fa-user-check"></i>
                <?php endif; ?>
            </div>
            <div style="min-width:0;">
                <div class="user-summary-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-summary-email"><?= htmlspecialchars($item['email'] ?? 'usuario@empresa.com') ?></div>
            </div>
        </div>
        <div>
            <?php if ($esCuentaRaiz): ?>
                <span class="user-pill user-pill-root"><i class="fas fa-crown"></i> Cuenta Raíz</span>
            <?php elseif (!empty($item['acceso_aprobado'])): ?>
                <span class="user-pill user-pill-active"><i class="fas fa-check-circle"></i> Acceso Habilitado</span>
            <?php else: ?>
                <span class="user-pill user-pill-pending"><i class="fas fa-hourglass-half"></i> Pendiente</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($esCuentaRaiz): ?>
    <!-- Banner de Cuenta Raíz Protegida -->
    <div class="root-protected-banner">
        <div class="root-icon-badge">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div style="flex:1;">
            <h4 class="root-banner-title">Cuenta de Administrador Global Raíz (Inmutable)</h4>
            <p class="root-banner-desc">
                Esta cuenta posee el <strong>Cargo Raíz de Director General / CEO</strong> con permisos irrestrictos y control administrativo permanente sobre todos los módulos y fases del ERP.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulario de Configuración -->
    <form method="post" action="<?= BASE_URL ?>/?page=usuarios&action=<?= $esEdicion ? 'editar&id='.$item['id'] : 'crear' ?>">
        <!-- Contenedor de Datos del Formulario -->
        <div class="form-section-box">

            <!-- BLOQUE: DATOS PERSONALES -->
            <div class="form-grid-2col">
                <div class="input-group-custom">
                    <label class="input-label-custom"><i class="fas fa-user" style="color:#059669;"></i> Nombre *</label>
                    <input type="text" name="nombre" class="input-control-styled" required value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" placeholder="Ej: Jorge">
                </div>
                <div class="input-group-custom">
                    <label class="input-label-custom"><i class="fas fa-user" style="color:#059669;"></i> Apellido *</label>
                    <input type="text" name="apellido" class="input-control-styled" required value="<?= htmlspecialchars($item['apellido'] ?? '') ?>" placeholder="Ej: Palomino">
                </div>
            </div>

            <div class="input-group-custom" style="margin-bottom: 20px;">
                <label class="input-label-custom"><i class="fas fa-envelope" style="color:#0284c7;"></i> Correo Electrónico *</label>
                <input type="email" name="email" class="input-control-styled" required value="<?= htmlspecialchars($item['email'] ?? '') ?>" placeholder="usuario@gmail.com" <?= $esCuentaRaiz ? 'readonly' : '' ?>>
                <?php if ($esCuentaRaiz): ?>
                <div class="helper-text-box" style="color:#7c3aed;">
                    <i class="fas fa-lock"></i> Correo maestro de lista blanca protegido.
                </div>
                <?php endif; ?>
            </div>

            <hr style="border:0; border-top:1px dashed #e2e8f0; margin:18px 0 20px;">

            <?php if (!$esCuentaRaiz): ?>

            <!-- SELECTOR 1: ÁREA ORGANIZACIONAL -->
            <div class="input-group-custom" style="margin-bottom: 20px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label class="section-title-label">
                        <i class="fas fa-sitemap" style="color:#059669;"></i> 1. Área Organizacional *
                    </label>
                </div>
                <select name="area_id" id="userAreaSelect" class="input-control-styled" onchange="alCambiarAreaForm(this)" required>
                    <option value="">-- Seleccionar área de trabajo --</option>
                    <?php foreach ($data['areas'] ?? [] as $area): ?>
                    <option value="<?= $area['id'] ?>" <?= ((($item['org_area_id'] ?? $item['area_id'] ?? '') == $area['id'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($area['nombre']) ?> [<?= htmlspecialchars($area['codigo'] ?? '') ?>]
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- SELECTOR 2: CARGO CORPORATIVO -->
            <div class="input-group-custom" style="margin-bottom: 20px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label class="section-title-label">
                        <i class="fas fa-briefcase" style="color:#0284c7;"></i> 2. Cargo / Rol Jerárquico *
                    </label>
                    <span id="cargoCounterPill" class="counter-pill">Puestos oficiales</span>
                </div>
                <select name="cargo_id" id="userCargoSelect" class="input-control-styled" onchange="alCambiarCargoForm(this)" required>
                    <option value="">-- Sin cargo asignado --</option>
                    <?php 
                    $cargosPorNivel = [];
                    foreach ($data['cargos'] ?? [] as $cg) {
                        $cargosPorNivel[$cg['nivel_jerarquico']][] = $cg;
                    }
                    ksort($cargosPorNivel);
                    foreach ($cargosPorNivel as $nivel => $cgs):
                    ?>
                    <optgroup label="Nivel <?= $nivel ?> · Puestos Jerárquicos">
                        <?php foreach ($cgs as $cg): ?>
                        <option value="<?= $cg['id'] ?>" data-area="<?= $cg['area_id'] ?>" data-puede-aprobar="<?= $cg['puede_aprobar'] ?? 0 ?>" data-nivel="<?= $cg['nivel_jerarquico'] ?>" <?= (($item['cargo_id'] ?? '') == $cg['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cg['nombre']) ?> (<?= htmlspecialchars($cg['area_nombre']) ?>) [<?= htmlspecialchars($cg['codigo']) ?>]
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
                <div id="cargoHintInfo" class="helper-text-box">
                    <i class="fas fa-info-circle"></i> <span>Al elegir el área, se filtrarán automáticamente sus cargos (Jefe y Asistente).</span>
                </div>
            </div>

            <!-- SELECTOR 3: TIPO DE ACCESO AL SISTEMA (TARJETAS RADIO ELEGANTES) -->
            <div class="input-group-custom" style="margin-bottom: 8px;">
                <label class="section-title-label">
                    <i class="fas fa-user-lock" style="color:#f59e0b;"></i> 3. Tipo de Acceso al Sistema *
                </label>
                <input type="hidden" name="tipo_usuario_id" id="inputTipoUsuario" value="<?= $tipoUsuarioActual ?>">

                <div class="role-cards-grid">
                    <!-- Opción Colaborador -->
                    <div class="role-card-box <?= ($tipoUsuarioActual !== 1) ? 'is-selected-colab' : '' ?>" id="roleCardColab" onclick="seleccionarTipoAcceso(2)">
                        <div class="role-icon-square role-icon-colab">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div style="flex:1;">
                            <div class="role-title-text">
                                <span>Colaborador / Operativo</span>
                                <i class="fas fa-check-circle" id="checkIconColab" style="font-size:14px; <?= ($tipoUsuarioActual !== 1) ? 'color:#10b981;' : 'color:#cbd5e1;' ?>"></i>
                            </div>
                            <div class="role-desc-text">
                                Acceso estricto a las pantallas y módulos de su área funcional designada.
                            </div>
                        </div>
                    </div>

                    <!-- Opción Administrador -->
                    <div class="role-card-box <?= ($tipoUsuarioActual === 1) ? 'is-selected-admin' : '' ?>" id="roleCardAdmin" onclick="seleccionarTipoAcceso(1)">
                        <div class="role-icon-square role-icon-admin">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div style="flex:1;">
                            <div class="role-title-text">
                                <span>Administrador Global</span>
                                <i class="fas fa-check-circle" id="checkIconAdmin" style="font-size:14px; <?= ($tipoUsuarioActual === 1) ? 'color:#8b5cf6;' : 'color:#cbd5e1;' ?>"></i>
                            </div>
                            <div class="role-desc-text">
                                Control total y privilegios administrativos sobre todas las fases del ERP.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="notice-banner-green">
                    <i class="fas fa-shield-alt"></i> <span>Jefaturas y Asistentes solo tienen acceso operativo a su área funcional designada.</span>
                </div>
            </div>

            <?php else: ?>

            <!-- Datos fijos para cuenta raíz protegida -->
            <input type="hidden" name="area_id" value="1">
            <input type="hidden" name="cargo_id" value="1">
            <input type="hidden" name="tipo_usuario_id" value="1">

            <div class="input-group-custom" style="margin-bottom: 16px;">
                <label class="section-title-label"><i class="fas fa-sitemap" style="color:#059669;"></i> 1. Área Organizacional</label>
                <input type="text" class="input-control-styled" value="Gerencia General [GG] · Dirección Ejecutiva" readonly style="font-weight:700;">
            </div>

            <div class="input-group-custom" style="margin-bottom: 16px;">
                <label class="section-title-label"><i class="fas fa-briefcase" style="color:#0284c7;"></i> 2. Cargo Corporativo</label>
                <input type="text" class="input-control-styled" value="Director General / CEO [CEO] · Nivel 1" readonly style="font-weight:700;">
            </div>

            <div class="input-group-custom">
                <label class="section-title-label"><i class="fas fa-user-lock" style="color:#7c3aed;"></i> 3. Tipo de Acceso al Sistema</label>
                <div class="role-card-box is-selected-admin" style="cursor:default;">
                    <div class="role-icon-square role-icon-admin">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div style="flex:1;">
                        <div class="role-title-text">
                            <span>Administrador Global Raíz</span>
                            <i class="fas fa-check-circle" style="color:#7c3aed; font-size:14px;"></i>
                        </div>
                        <div class="role-desc-text">
                            Privilegios totales permanentes e irrestrictos sobre todas las fases de la plataforma.
                        </div>
                    </div>
                </div>
            </div>

            <?php endif; ?>

        </div>

        <!-- Botones de Acción -->
        <div class="form-modal-footer">
            <button type="button" onclick="if(window.parent && window.parent.cerrarGlobalModal) { window.parent.cerrarGlobalModal(); } else { window.history.back(); }" class="btn-action-cancel">
                Cancelar
            </button>
            <button type="submit" class="btn-action-save">
                <i class="fas fa-save"></i> <?= $esEdicion ? 'Guardar cambios' : 'Registrar usuario' ?>
            </button>
        </div>
    </form>
</div>

<script>
const todosLosCargos = <?= json_encode($data['cargos'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
const cargoInicialId = '<?= (string)($item['cargo_id'] ?? '') ?>';
const esCuentaRaizProtegida = <?= $esCuentaRaiz ? 'true' : 'false' ?>;

function seleccionarTipoAcceso(tipo) {
    if (esCuentaRaizProtegida) return;

    const inputTipo = document.getElementById('inputTipoUsuario');
    const cardColab = document.getElementById('roleCardColab');
    const cardAdmin = document.getElementById('roleCardAdmin');
    const checkColab = document.getElementById('checkIconColab');
    const checkAdmin = document.getElementById('checkIconAdmin');

    if (!inputTipo || !cardColab || !cardAdmin) return;

    inputTipo.value = tipo;

    if (tipo === 1) {
        // Administrador seleccionado
        cardAdmin.classList.add('is-selected-admin');
        cardColab.classList.remove('is-selected-colab');
        if (checkAdmin) checkAdmin.style.color = '#8b5cf6';
        if (checkColab) checkColab.style.color = '#cbd5e1';
    } else {
        // Colaborador seleccionado
        cardColab.classList.add('is-selected-colab');
        cardAdmin.classList.remove('is-selected-admin');
        if (checkColab) checkColab.style.color = '#10b981';
        if (checkAdmin) checkAdmin.style.color = '#cbd5e1';
    }
}

function alCambiarAreaForm(areaSel, esCargaInicial = false) {
    const selectedAreaId = areaSel ? String(areaSel.value || '') : '';
    const cargoSelect = document.getElementById('userCargoSelect');
    const hint = document.getElementById('cargoHintInfo');
    const pill = document.getElementById('cargoCounterPill');
    if (!cargoSelect) return;

    const valorActual = esCargaInicial ? cargoInicialId : cargoSelect.value;

    cargoSelect.innerHTML = '';
    const optVacia = document.createElement('option');
    optVacia.value = '';
    optVacia.textContent = selectedAreaId ? '-- Selecciona el puesto (Jefe o Asistente) --' : '-- Sin cargo asignado --';
    cargoSelect.appendChild(optVacia);

    if (selectedAreaId) {
        const filtrados = todosLosCargos.filter(c => String(c.area_id) === selectedAreaId);

        if (filtrados.length > 0) {
            if (pill) pill.textContent = `${filtrados.length} puestos disponibles`;

            const jefaturas = filtrados.filter(c => parseInt(c.puede_aprobar) === 1 || parseInt(c.nivel_jerarquico) <= 3);
            const operativos = filtrados.filter(c => parseInt(c.puede_aprobar) === 0 && parseInt(c.nivel_jerarquico) > 3);

            if (jefaturas.length > 0) {
                const groupJefes = document.createElement('optgroup');
                groupJefes.label = '👑 Jefaturas y Responsables (Pueden Aprobar)';
                jefaturas.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.setAttribute('data-area', c.area_id);
                    opt.setAttribute('data-nivel', c.nivel_jerarquico);
                    opt.setAttribute('data-codigo', c.codigo);
                    opt.textContent = `${c.nombre} [${c.codigo}] · Nivel ${c.nivel_jerarquico}`;
                    if (String(valorActual) === String(c.id)) opt.selected = true;
                    groupJefes.appendChild(opt);
                });
                cargoSelect.appendChild(groupJefes);
            }

            if (operativos.length > 0) {
                const groupOp = document.createElement('optgroup');
                groupOp.label = '💼 Asistencias y Operativos (Sin aprobación crítica)';
                operativos.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.setAttribute('data-area', c.area_id);
                    opt.setAttribute('data-nivel', c.nivel_jerarquico);
                    opt.setAttribute('data-codigo', c.codigo);
                    opt.textContent = `${c.nombre} [${c.codigo}] · Nivel ${c.nivel_jerarquico}`;
                    if (String(valorActual) === String(c.id)) opt.selected = true;
                    groupOp.appendChild(opt);
                });
                cargoSelect.appendChild(groupOp);
            }

            if (hint) {
                const areaNombre = areaSel.options[areaSel.selectedIndex] ? areaSel.options[areaSel.selectedIndex].textContent : 'esta área';
                hint.innerHTML = `<i class="fas fa-check-circle" style="color:#10b981;"></i> <strong style="color:#0f172a;">${filtrados.length} puestos disponibles</strong> en <em>${areaNombre}</em> (Jefe y Asistente).`;
            }
        } else {
            if (pill) pill.textContent = '0 puestos';
            if (hint) {
                hint.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> No hay cargos registrados para esta área aún.`;
            }
        }
    } else {
        if (pill) pill.textContent = 'Puestos oficiales';
        const porNivel = {};
        todosLosCargos.forEach(c => {
            if (!porNivel[c.nivel_jerarquico]) porNivel[c.nivel_jerarquico] = [];
            porNivel[c.nivel_jerarquico].push(c);
        });

        Object.keys(porNivel).sort().forEach(nivel => {
            const grp = document.createElement('optgroup');
            grp.label = `Nivel ${nivel} · Puestos Jerárquicos`;
            porNivel[nivel].forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.setAttribute('data-area', c.area_id);
                opt.setAttribute('data-nivel', c.nivel_jerarquico);
                opt.setAttribute('data-codigo', c.codigo);
                opt.textContent = `${c.nombre} (${c.area_nombre}) [${c.codigo}]`;
                if (String(valorActual) === String(c.id)) opt.selected = true;
                grp.appendChild(opt);
            });
            cargoSelect.appendChild(grp);
        });

        if (hint) {
            hint.innerHTML = `<i class="fas fa-info-circle"></i> Selecciona un área arriba para filtrar entre su Jefe o Asistente, o elige un puesto directo.`;
        }
    }
}

function alCambiarCargoForm(sel) {
    if (!sel || sel.selectedIndex < 0) return;
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const areaId = opt.getAttribute('data-area');
    const nivel = parseInt(opt.getAttribute('data-nivel') || '99');
    const codigo = String(opt.getAttribute('data-codigo') || '').toUpperCase();
    const areaSelect = document.getElementById('userAreaSelect');
    
    // Si seleccionó un cargo y no tenía área o era diferente, sincronizar el área
    if (areaId && areaSelect && areaSelect.value !== String(areaId)) {
        areaSelect.value = areaId;
        const hint = document.getElementById('cargoHintInfo');
        if (hint && areaSelect.selectedIndex >= 0) {
            const areaNombre = areaSelect.options[areaSelect.selectedIndex].textContent;
            hint.innerHTML = `<i class="fas fa-link" style="color:#6366f1;"></i> Vinculado automáticamente al área de <strong>${areaNombre}</strong>.`;
        }
    }

    // Auto-ajustar Tipo de Acceso según jerarquía
    if (nivel <= 1 || codigo === 'CEO') {
        seleccionarTipoAcceso(1); // Administrador
    } else {
        seleccionarTipoAcceso(2); // Colaborador
    }
}

// Inicializar al cargar la página si ya tiene un área seleccionada
document.addEventListener('DOMContentLoaded', function() {
    const areaSelect = document.getElementById('userAreaSelect');
    if (areaSelect && areaSelect.value) {
        alCambiarAreaForm(areaSelect, true);
    }
});
</script>



