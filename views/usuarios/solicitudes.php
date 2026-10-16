<?php require_once __DIR__ . '/../../helpers/PermisosHelper.php'; PermisosHelper::verificarAcceso('usuarios'); ?>
<?php
/**
 * Vista: Aprobación y Asignación de Solicitudes de Acceso (Módulo de Usuarios)
 * Archivo: views/usuarios/solicitudes.php
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
require_once __DIR__ . '/../../models/OrganizacionModel.php';

$csrfToken = CsrfHelper::getToken();
$orgModel  = new OrganizacionModel();

// Carga segura de datos (soporta inyección del controlador o carga autónoma)
$areas  = $data['areas'] ?? $orgModel->getAllAreas();
$cargos = $data['cargos'] ?? $orgModel->getAllCargos();

$solicitudes = $data['solicitudes'] ?? null;
if ($solicitudes === null) {
    $solicitudes = dbQueryAll("
        SELECT sa.*, 
               u.nombre as usuario_nombre, 
               u.email as usuario_email, 
               u.avatar as usuario_avatar, 
               u.created_at as usuario_registro,
               oa.nombre as area_nombre, 
               oa.codigo as area_codigo, 
               oa.color as area_color,
               oc.nombre as cargo_nombre, 
               oc.nivel_jerarquico, 
               oc.codigo as cargo_codigo
        FROM solicitudes_acceso sa
        JOIN usuarios u ON sa.usuario_id = u.id
        LEFT JOIN org_areas oa ON sa.org_area_id = oa.id
        LEFT JOIN org_cargos oc ON sa.cargo_id = oc.id
        ORDER BY (sa.estado = 'pendiente') DESC, sa.created_at DESC
    ");
}

$pendientesCount = 0;
$aprobadasCount  = 0;
$rechazadasCount = 0;
foreach ($solicitudes as $s) {
    if ($s['estado'] === 'pendiente') {
        $pendientesCount++;
    } elseif ($s['estado'] === 'aprobada') {
        $aprobadasCount++;
    } elseif ($s['estado'] === 'rechazada') {
        $rechazadasCount++;
    }
}
$totalSolicitudes = count($solicitudes);
?>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <!-- Barra Superior de Navegación del Módulo de Usuarios -->
    <?php
    $activeUserTab = 'solicitudes';
    include __DIR__ . '/navbar_usuarios.php';
    ?>

    <!-- KPIs Estadísticos -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:20px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="width:42px; height:42px; border-radius:10px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; line-height:1;"><?= (int)$pendientesCount ?></div>
                <div style="font-size:11.5px; color:#d97706; font-weight:700; text-transform:uppercase; margin-top:4px;">Pendientes de Aprobación</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="width:42px; height:42px; border-radius:10px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; line-height:1;"><?= (int)$aprobadasCount ?></div>
                <div style="font-size:11.5px; color:#15803d; font-weight:700; text-transform:uppercase; margin-top:4px;">Aprobadas & Asignadas</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="width:42px; height:42px; border-radius:10px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-ban"></i>
            </div>
            <div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; line-height:1;"><?= (int)$rechazadasCount ?></div>
                <div style="font-size:11.5px; color:#b91c1c; font-weight:700; text-transform:uppercase; margin-top:4px;">Rechazadas</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="width:42px; height:42px; border-radius:10px; background:#e0e7ff; color:#4338ca; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; line-height:1;"><?= (int)$totalSolicitudes ?></div>
                <div style="font-size:11.5px; color:#4338ca; font-weight:700; text-transform:uppercase; margin-top:4px;">Total Solicitudes</div>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal: Filtros y Tabla de Solicitudes -->
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.03);">
        <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px;">
            <div>
                <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-user-clock" style="color:#059669;"></i>
                    Bandeja de Solicitudes de Ingreso
                </h3>
                <p style="font-size:12.5px; color:#64748b; margin:3px 0 0;">
                    Gestiona los accesos de nuevos colaboradores asignándoles su Área y Cargo oficial en el sistema.
                </p>
            </div>

            <!-- Filtros de Búsqueda -->
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="position:relative; min-width:240px;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px;"></i>
                    <input type="text" id="filtroTextoSol" onkeyup="filtrarTablaSolicitudes()" placeholder="Buscar por colaborador, email o mensaje..." style="width:100%; padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; outline:none; box-sizing:border-box;">
                </div>
                <select id="filtroEstadoSol" onchange="filtrarTablaSolicitudes()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; outline:none; background:#ffffff;">
                    <option value="">Todos los Estados</option>
                    <option value="pendiente" selected>Solo Pendientes (<?= (int)$pendientesCount ?>)</option>
                    <option value="aprobada">Aprobadas</option>
                    <option value="rechazada">Rechazadas</option>
                </select>
            </div>
        </div>

        <!-- Tabla de Solicitudes -->
        <div style="overflow-x:auto;">
            <table id="tablaSolicitudesAcceso" style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; text-transform:uppercase; letter-spacing:0.5px;">
                        <th style="padding:12px 18px; font-weight:700;">Colaborador</th>
                        <th style="padding:12px 18px; font-weight:700;">Motivo / Mensaje</th>
                        <th style="padding:12px 18px; font-weight:700;">Fecha Solicitud</th>
                        <th style="padding:12px 18px; font-weight:700;">Estado</th>
                        <th style="padding:12px 18px; font-weight:700;">Área & Cargo Asignado</th>
                        <th style="padding:12px 18px; font-weight:700; text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="6" style="padding:40px; text-align:center; color:#94a3b8;">
                                <i class="fas fa-inbox" style="font-size:32px; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                                No hay solicitudes de acceso registradas en el sistema.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes as $s): 
                            $estado = $s['estado'] ?? 'pendiente';
                            $estadoBadge = '';
                            if ($estado === 'pendiente') {
                                $estadoBadge = '<span style="background:#fef3c7; color:#b45309; padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:700; display:inline-flex; align-items:center; gap:5px;"><i class="fas fa-clock"></i> Pendiente</span>';
                            } elseif ($estado === 'aprobada') {
                                $estadoBadge = '<span style="background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:700; display:inline-flex; align-items:center; gap:5px;"><i class="fas fa-check-circle"></i> Aprobada</span>';
                            } elseif ($estado === 'rechazada') {
                                $estadoBadge = '<span style="background:#fee2e2; color:#b91c1c; padding:4px 10px; border-radius:20px; font-size:11.5px; font-weight:700; display:inline-flex; align-items:center; gap:5px;"><i class="fas fa-times-circle"></i> Rechazada</span>';
                            }

                            $inicial = strtoupper(substr($s['usuario_nombre'] ?? 'U', 0, 1));
                            $textoFiltro = strtolower(($s['usuario_nombre'] ?? '') . ' ' . ($s['usuario_email'] ?? '') . ' ' . ($s['mensaje'] ?? ''));
                        ?>
                            <tr class="fila-solicitud" data-estado="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>" data-texto="<?= htmlspecialchars($textoFiltro, ENT_QUOTES, 'UTF-8') ?>" style="border-bottom:1px solid #f1f5f9; transition:background 0.15s;">
                                <!-- Colaborador -->
                                <td style="padding:14px 18px; vertical-align:middle;">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <?php 
                                        $userAvatar = !empty($s['usuario_avatar']) ? $s['usuario_avatar'] : (!empty($s['avatar']) ? $s['avatar'] : '');
                                        if (!empty($userAvatar)): ?>
                                            <img src="<?= htmlspecialchars($userAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:38px; height:38px; border-radius:10px; object-fit:cover; border:1.5px solid #e2e8f0;">
                                        <?php else: ?>
                                            <div style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #059669 0%, #047857 100%); color:#ffffff; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(5,150,105,0.25);">
                                                <?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:700; color:#0f172a; font-size:13px;">
                                                <?= htmlspecialchars($s['usuario_nombre'] ?? 'Sin nombre', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div style="font-size:11.5px; color:#64748b;">
                                                <?= htmlspecialchars($s['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Mensaje / Justificación -->
                                <td style="padding:14px 18px; vertical-align:middle; max-width:260px;">
                                    <?php if (!empty($s['mensaje'])): ?>
                                        <div style="font-size:12px; color:#334155; line-height:1.4; background:#f8fafc; padding:6px 10px; border-radius:8px; border:1px solid #e2e8f0; font-style:italic;">
                                            "<?= htmlspecialchars($s['mensaje'], ENT_QUOTES, 'UTF-8') ?>"
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">(Sin mensaje adjunto)</span>
                                    <?php endif; ?>

                                    <?php if (!empty($s['observaciones'])): ?>
                                        <div style="font-size:11px; color:#059669; margin-top:4px;">
                                            <strong>Nota Admin:</strong> <?= htmlspecialchars($s['observaciones'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Fecha -->
                                <td style="padding:14px 18px; vertical-align:middle; color:#64748b; font-size:12px; white-space:nowrap;">
                                    <div><i class="far fa-calendar-alt" style="margin-right:4px;"></i><?= date('d/m/Y', strtotime($s['created_at'])) ?></div>
                                    <div style="font-size:10.5px; color:#94a3b8;"><?= date('h:i A', strtotime($s['created_at'])) ?></div>
                                </td>

                                <!-- Estado -->
                                <td style="padding:14px 18px; vertical-align:middle;">
                                    <?= $estadoBadge ?>
                                </td>

                                <!-- Puesto & Área Asignada -->
                                <td style="padding:14px 18px; vertical-align:middle;">
                                    <?php if ($estado === 'aprobada' && !empty($s['cargo_nombre'])): ?>
                                        <div style="display:inline-flex; flex-direction:column; gap:3px;">
                                            <span style="font-weight:700; color:#0f172a; font-size:12.5px;">
                                                <?= htmlspecialchars($s['cargo_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span style="font-size:11px; color:#059669; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                                <i class="fas fa-sitemap"></i>
                                                <?= htmlspecialchars($s['area_nombre'] ?? 'Área Corporativa', ENT_QUOTES, 'UTF-8') ?>
                                                <?php if (!empty($s['area_codigo'])): ?>
                                                    [<?= htmlspecialchars($s['area_codigo'], ENT_QUOTES, 'UTF-8') ?>]
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php elseif ($estado === 'pendiente'): ?>
                                        <span style="color:#d97706; font-size:12px; font-weight:600; background:#fef3c7; padding:3px 8px; border-radius:6px;">
                                            <i class="fas fa-exclamation-triangle" style="font-size:10px;"></i> Por asignar
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">Acceso denegado</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Acciones -->
                                <td style="padding:14px 18px; vertical-align:middle; text-align:right; white-space:nowrap;">
                                    <?php if ($estado === 'pendiente'): ?>
                                        <button type="button" 
                                                onclick="abrirModalAprobar(<?= (int)$s['id'] ?>, <?= (int)$s['usuario_id'] ?>, '<?= htmlspecialchars(addslashes($s['usuario_nombre']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($s['usuario_email']), ENT_QUOTES, 'UTF-8') ?>', <?= (int)($s['org_area_id'] ?? 0) ?>, <?= (int)($s['cargo_id'] ?? 0) ?>)" 
                                                style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; border:none; padding:7px 14px; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 6px rgba(16,185,129,0.3); transition:transform 0.1s;">
                                            <i class="fas fa-user-check"></i>
                                            <span>Asignar Rol & Área</span>
                                        </button>
                                        <button type="button" 
                                                onclick="rechazarSolicitud(<?= (int)$s['id'] ?>)" 
                                                title="Rechazar Solicitud"
                                                style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:7px 10px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; margin-left:6px; transition:background 0.15s;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif ($estado === 'aprobada'): ?>
                                        <button type="button" 
                                                onclick="abrirModalAprobar(<?= (int)$s['id'] ?>, <?= (int)$s['usuario_id'] ?>, '<?= htmlspecialchars(addslashes($s['usuario_nombre']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($s['usuario_email']), ENT_QUOTES, 'UTF-8') ?>', <?= (int)($s['org_area_id'] ?? 0) ?>, <?= (int)($s['cargo_id'] ?? 0) ?>)" 
                                                style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:6px 12px; border-radius:8px; font-weight:600; font-size:11.5px; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                                            <i class="fas fa-sync-alt" style="color:#059669;"></i>
                                            <span>Reasignar</span>
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size:11.5px; color:#94a3b8; font-style:italic;">Archivada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- ESTILOS EXCLUSIVOS PARA SELECTORES VISUALES Y MODAL ORGANIGRAMA          -->
<!-- ========================================================================= -->
<style>
/* Animaciones */
@keyframes modalSlideDown {
    from { opacity: 0; transform: translateY(-24px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes modalZoomIn {
    from { opacity: 0; transform: scale(0.92); }
    to { opacity: 1; transform: scale(1); }
}
@keyframes pulseSelect {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Selector Personalizado de Área */
.custom-area-trigger {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
    box-sizing: border-box;
}
.custom-area-trigger:hover {
    border-color: #059669;
    background: #f0fdf4;
    box-shadow: 0 2px 8px rgba(5, 150, 105, 0.08);
}
.custom-area-trigger.is-open {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}
.custom-area-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 100%;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    box-shadow: 0 14px 35px -5px rgba(15, 23, 42, 0.2);
    z-index: 100010;
    display: none;
    max-height: 380px;
    overflow-y: auto;
    padding: 8px;
    box-sizing: border-box;
    animation: modalSlideDown 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.custom-area-dropdown.is-open {
    display: block;
}
.custom-area-group-title {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #64748b;
    padding: 8px 10px 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.custom-area-option {
    padding: 9px 12px;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    transition: all 0.15s ease;
    border: 1px solid transparent;
    margin-bottom: 3px;
}
.custom-area-option:hover {
    background: #f0fdf4;
    border-color: #bbf7d0;
    transform: translateX(3px);
}
.custom-area-option.is-selected {
    background: #dcfce7;
    border-color: #86efac;
}

/* Tarjetas Interactivas de Puestos/Cargos */
.cargo-card-item {
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-sizing: border-box;
}
.cargo-card-item:hover {
    border-color: #0284c7;
    background: #f0f9ff;
    transform: translateY(-1.5px);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08);
}
.cargo-card-item.is-selected {
    border-color: #059669 !important;
    background: #f0fdf4 !important;
    box-shadow: 0 0 0 2.5px rgba(5, 150, 105, 0.2), 0 4px 14px rgba(5, 150, 105, 0.12) !important;
}

/* Indicador Radio Circular de Selección de Cargo */
.cargo-radio-circle {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    background: #ffffff;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    flex-shrink: 0;
    transition: all 0.2s ease;
    pointer-events: none;
}
.cargo-card-item.is-selected .cargo-radio-circle {
    border-color: #059669 !important;
    background: #059669 !important;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.25) !important;
    color: #ffffff !important;
}
.cargo-radio-btn {
    background: #f1f5f9;
    color: #334155;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    pointer-events: none;
}
.cargo-card-item.is-selected .cargo-radio-btn {
    background: #059669 !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25) !important;
}

/* Tarjetas de Selección de Rol */
.role-card-item {
    flex: 1;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    box-sizing: border-box;
}
.role-card-item:hover {
    border-color: #6366f1;
    background: #f5f3ff;
}
.role-card-item.is-selected {
    border-color: #6366f1 !important;
    background: #f5f3ff !important;
    box-shadow: 0 0 0 2.5px rgba(99, 102, 241, 0.2) !important;
}

/* Nodos del Organigrama Visual */
.org-node-card {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 14px;
    padding: 12px 14px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
    position: relative;
    box-sizing: border-box;
}
.org-node-card:hover {
    border-color: #059669;
    transform: translateY(-2.5px);
    box-shadow: 0 10px 22px rgba(5, 150, 105, 0.12);
}
.org-node-card.is-selected {
    border-color: #059669 !important;
    background: #ecfdf5 !important;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.25) !important;
}

/* Filtros de Nivel en Organigrama */
.btn-org-filter {
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-org-filter:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.btn-org-filter.active {
    background: #059669;
    color: #ffffff;
    border-color: #059669;
    box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);
}

/* Animaciones para el Modal de Feedback del Admin */
@keyframes modalPopUp {
    0% { opacity: 0; transform: scale(0.92) translateY(12px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes spinClockwise {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes spinCounter {
    0% { transform: rotate(360deg); }
    100% { transform: rotate(0deg); }
}
@keyframes floatCenter {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.spinRingAdmin {
    animation: spinClockwise 1.2s linear infinite;
}
.spinRingRevAdmin {
    animation: spinCounter 0.9s linear infinite;
}
.floatShieldAdmin {
    animation: floatCenter 2s ease-in-out infinite;
}

/* Checkmark SVG Animado */
.checkmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 3;
    stroke: #10b981;
    fill: none;
    animation: strokeCircle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.checkmark-check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: strokeCheck 0.35s cubic-bezier(0.65, 0, 0.45, 1) 0.45s forwards;
}
@keyframes strokeCircle {
    100% { stroke-dashoffset: 0; }
}
@keyframes strokeCheck {
    100% { stroke-dashoffset: 0; }
}
</style>

<!-- ========================================================================= -->
<!-- MODAL DE ASIGNACIÓN DE PUESTO Y ÁREA ORGANIZACIONAL (AJAX / CSRF)        -->
<!-- ========================================================================= -->
<div id="modalAprobarSolicitud" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); backdrop-filter:blur(6px); z-index:99999; justify-content:center; align-items:center; padding:16px; box-sizing:border-box;">
    <div style="background:#ffffff; border-radius:22px; width:100%; max-width:620px; max-height:92vh; display:flex; flex-direction:column; box-shadow:0 25px 60px -15px rgba(0,0,0,0.3); overflow:hidden; animation:modalSlideDown 0.25s cubic-bezier(0.16, 1, 0.3, 1); border:1px solid #e2e8f0;">
        
        <!-- Encabezado del Modal -->
        <div style="background:linear-gradient(135deg, #059669 0%, #047857 50%, #064e3b 100%); padding:20px 24px; color:#ffffff; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:42px; height:42px; border-radius:12px; background:rgba(255,255,255,0.18); display:flex; align-items:center; justify-content:center; font-size:19px; border:1px solid rgba(255,255,255,0.25);">
                    <i class="fas fa-id-card-alt"></i>
                </div>
                <div>
                    <h4 style="margin:0; font-size:17px; font-weight:800; letter-spacing:-0.3px;">
                        Asignar Puesto & Habilitar Acceso
                    </h4>
                    <p style="margin:3px 0 0; font-size:12px; opacity:0.9;">
                        Configura el Área y Cargo oficial en el Organigrama corporativo
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalAprobar()" style="background:rgba(255,255,255,0.18); border:none; color:#ffffff; width:32px; height:32px; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:14px; transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.18)'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Formulario Seguro con CSRF (Contenedor Scrollable) -->
        <form id="formAprobarSolicitud" onsubmit="enviarAprobacion(event)" style="padding:22px 24px; overflow-y:auto; flex:1;">
            <!-- Token CSRF Obligatorio -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="solicitud_id" id="modalSolId" value="">
            <input type="hidden" name="usuario_id" id="modalUserId" value="">

            <!-- Alerta Inline para Validaciones sin alertas nativas del navegador -->
            <div id="alertaAprobacionInline" style="display:none; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; border-radius:12px; padding:10px 14px; font-size:12.5px; font-weight:600; margin-bottom:14px; align-items:center; gap:8px;">
                <i class="fas fa-exclamation-circle" style="font-size:16px;"></i>
                <span id="alertaAprobacionTexto"></span>
            </div>

            <!-- Ficha resumida del colaborador -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:12px 16px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:42px; height:42px; border-radius:12px; background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:17px; font-weight:800; box-shadow:0 3px 10px rgba(16,185,129,0.25);">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <div id="modalUserName" style="font-weight:800; color:#0f172a; font-size:14px;">Cargando usuario...</div>
                        <div id="modalUserEmail" style="font-size:12px; color:#64748b;">email@ejemplo.com</div>
                    </div>
                </div>
                <span style="font-size:11px; font-weight:700; background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:3px 10px; border-radius:20px;">
                    <i class="fas fa-hourglass-half"></i> Pendiente
                </span>
            </div>

            <!-- SELECTOR 1: ÁREA ORGANIZACIONAL (MEJORADO CON ORGANIGRAMA VISUAL) -->
            <div style="margin-bottom:18px; position:relative;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <label style="font-size:12.5px; font-weight:700; color:#1e293b; margin:0; display:flex; align-items:center; gap:6px;">
                        <i class="fas fa-sitemap" style="color:#059669;"></i> 1. Área Organizacional *
                    </label>
                    <button type="button" onclick="abrirModalOrganigramaPicker()" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; font-size:11.5px; font-weight:700; padding:4px 11px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s;" onmouseover="this.style.background='#d1fae5'" onmouseout="this.style.background='#ecfdf5'">
                        <i class="fas fa-project-diagram"></i> <span>Ver Organigrama Visual</span>
                    </button>
                </div>

                <!-- Input oculto para compatibilidad total con el formulario -->
                <select id="modalAreaSelect" name="area_id" required style="display:none;">
                    <option value="">-- Selecciona el Área de trabajo --</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= (int)$a['id'] ?>" data-codigo="<?= htmlspecialchars($a['codigo'], ENT_QUOTES, 'UTF-8') ?>" data-nivel="<?= (int)$a['nivel'] ?>">
                            <?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?> [<?= htmlspecialchars($a['codigo'], ENT_QUOTES, 'UTF-8') ?>]
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Trigger Personalizado y Estilizado -->
                <div class="custom-area-trigger" id="customAreaTrigger" onclick="toggleAreaDropdown()">
                    <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                        <div id="customAreaIconBox" style="width:32px; height:32px; border-radius:8px; background:#f1f5f9; color:#64748b; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0;">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <div style="min-width:0; text-align:left;">
                            <div id="customAreaText" style="font-size:13px; font-weight:700; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                -- Selecciona el Área de trabajo o elígela en el Organigrama --
                            </div>
                            <div id="customAreaSub" style="font-size:11px; color:#64748b;">
                                Clic para buscar o abre el organigrama interactivo
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span id="customAreaPill" style="display:none; font-size:10.5px; font-weight:800; padding:2px 8px; border-radius:6px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;"></span>
                        <i class="fas fa-chevron-down" id="customAreaChevron" style="color:#94a3b8; font-size:12px; transition:transform 0.2s;"></i>
                    </div>
                </div>

                <!-- Menú Desplegable con Búsqueda Rápida y Jerarquía -->
                <div class="custom-area-dropdown" id="customAreaDropdown">
                    <!-- Buscador en tiempo real -->
                    <div style="padding:4px 6px 8px; border-bottom:1px solid #f1f5f9; position:sticky; top:0; background:#ffffff; z-index:2;">
                        <div style="position:relative;">
                            <i class="fas fa-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px;"></i>
                            <input type="text" id="inputBuscarArea" placeholder="Escribe para buscar área por nombre o código..." oninput="filtrarAreasDropdown(this.value)" style="width:100%; padding:7px 10px 7px 30px; border:1px solid #cbd5e1; border-radius:8px; font-size:12px; outline:none; box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="padding:6px 0;">
                        <button type="button" onclick="abrirModalOrganigramaPicker(); toggleAreaDropdown(false);" style="width:100%; background:#f0fdf4; border:1px dashed #86efac; border-radius:8px; padding:8px 12px; color:#059669; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:7px; margin-bottom:6px;">
                            <i class="fas fa-project-diagram"></i> Ver Organigrama Visual Completo
                        </button>
                    </div>

                    <!-- Lista de Áreas Agrupadas por Nivel -->
                    <div id="listaOpcionesAreas">
                        <!-- Generado dinámicamente por JavaScript -->
                    </div>
                </div>

                <!-- Banner Informativo del Área Seleccionada -->
                <div id="bannerAreaActiva" style="display:none; margin-top:8px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:8px 12px; display:none; align-items:center; justify-content:space-between; font-size:11.5px; color:#15803d;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-check-circle" style="color:#10b981;"></i>
                        <span id="bannerAreaActivaTexto">Área seleccionada: <strong>Gerencia General</strong></span>
                    </div>
                    <a href="javascript:void(0)" onclick="abrirModalOrganigramaPicker()" style="color:#059669; font-weight:700; text-decoration:none;">Cambiar en Organigrama &rarr;</a>
                </div>
            </div>

            <!-- SELECTOR 2: CARGO / ROL JERÁRQUICO (TARJETAS INTERACTIVAS CON NIVELES) -->
            <div style="margin-bottom:18px;" id="seccionCargosContainer">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <label style="font-size:12.5px; font-weight:700; color:#1e293b; margin:0; display:flex; align-items:center; gap:6px;">
                        <i class="fas fa-id-badge" style="color:#0284c7;"></i> 2. Cargo / Rol Jerárquico *
                    </label>
                    <span id="cargoCounterPill" style="font-size:11px; font-weight:700; color:#64748b;">
                        Puestos oficiales
                    </span>
                </div>

                <!-- Input oculto para compatibilidad total -->
                <select id="modalCargoSelect" name="cargo_id" required style="display:none;">
                    <option value="">-- Selecciona el puesto oficial --</option>
                    <?php foreach ($cargos as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" 
                                data-area-id="<?= (int)$c['area_id'] ?>" 
                                data-nivel-jerarquico="<?= (int)$c['nivel_jerarquico'] ?>" 
                                data-codigo="<?= htmlspecialchars($c['codigo'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?> [<?= htmlspecialchars($c['codigo'], ENT_QUOTES, 'UTF-8') ?>]
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Grid de Tarjetas de Puestos -->
                <div id="contenedorTarjetasCargos" style="display:flex; flex-direction:column; gap:8px; max-height:240px; overflow-y:auto; padding-right:2px;">
                    <!-- Renderizado dinámico -->
                </div>
            </div>

            <!-- SELECTOR 3: NIVEL DE ACCESO AL SISTEMA (TARJETAS RADIO ELEGANTES) -->
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#1e293b; margin-bottom:8px;">
                    <i class="fas fa-user-lock" style="color:#f59e0b;"></i> 3. Tipo de Acceso al Sistema *
                </label>
                
                <!-- Input select oculto para sincronización -->
                <select id="modalRolSelect" name="rol" required style="display:none;">
                    <option value="usuario" selected>Colaborador / Operativo</option>
                    <option value="admin">Administrador del Sistema</option>
                </select>

                <div style="display:flex; gap:12px;">
                    <!-- Opción Colaborador -->
                    <div class="role-card-item is-selected" id="roleCardUsuario" onclick="seleccionarRolSistema('usuario')">
                        <div style="width:34px; height:34px; border-radius:10px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:12.5px; font-weight:800; color:#0f172a; display:flex; align-items:center; justify-content:space-between;">
                                <span>Colaborador / Operativo</span>
                                <i class="fas fa-check-circle" id="roleCheckUsuario" style="color:#10b981; font-size:14px;"></i>
                            </div>
                            <div style="font-size:11px; color:#64748b; margin-top:2px; line-height:1.3;">
                                Acceso estricto a las pantallas y módulos de su área funcional designada.
                            </div>
                        </div>
                    </div>

                    <!-- Opción Administrador -->
                    <div class="role-card-item" id="roleCardAdmin" onclick="seleccionarRolSistema('admin')">
                        <div style="width:34px; height:34px; border-radius:10px; background:#f3e8ff; color:#7c3aed; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0;">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:12.5px; font-weight:800; color:#0f172a; display:flex; align-items:center; justify-content:space-between;">
                                <span>Administrador Global</span>
                                <i class="far fa-circle" id="roleCheckAdmin" style="color:#cbd5e1; font-size:14px;"></i>
                            </div>
                            <div style="font-size:11px; color:#64748b; margin-top:2px; line-height:1.3;">
                                Control total y privilegios administrativos sobre todas las fases del ERP.
                            </div>
                        </div>
                    </div>
                </div>

                <div id="rolExplicacion" style="font-size:11px; color:#059669; font-weight:600; margin-top:8px; padding:6px 12px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0;">
                    Jefaturas y Asistentes solo tienen acceso operativo a su área funcional designada.
                </div>
            </div>

            <!-- Observaciones / Comentario de Bienvenida -->
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:5px;">
                    Observaciones o Nota de Aprobación (Opcional)
                </label>
                <input type="text" name="observaciones" id="modalObservaciones" placeholder="Ej: Aprobado para el equipo de licitaciones y contratos..." style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; box-sizing:border-box; outline:none; transition:border-color 0.2s;" onfocus="this.style.borderColor='#059669'" onblur="this.style.borderColor='#cbd5e1'">
            </div>

            <!-- Botones de Acción -->
            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #f1f5f9; margin-top:10px;">
                <button type="button" onclick="cerrarModalAprobar()" style="padding:9px 18px; border-radius:10px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit" id="btnConfirmarAprobacion" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; border:none; padding:10px 24px; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 4px 14px rgba(16,185,129,0.3); transition:transform 0.15s ease;">
                    <i class="fas fa-check"></i>
                    <span>Confirmar y Habilitar Acceso</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL ANIMADO DE PROCESAMIENTO Y ÉXITO DE APROBACIÓN (SPINNER & CELEBRACIÓN) -->
<!-- ========================================================================= -->
<div id="modalFeedbackAprobacionAdmin" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.75); backdrop-filter:blur(8px); z-index:100010; justify-content:center; align-items:center; padding:16px; box-sizing:border-box;">
    <div style="background:#ffffff; border-radius:24px; width:100%; max-width:480px; box-shadow:0 25px 60px -15px rgba(0,0,0,0.35); overflow:hidden; border:1px solid #e2e8f0; animation:modalPopUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); text-align:center;">
        
        <!-- Estado 1: Cargando con Spinner Órbita Dual -->
        <div id="feedbackAdminCargando" style="padding:42px 28px; display:flex; flex-direction:column; align-items:center;">
            <div style="position:relative; width:90px; height:90px; margin-bottom:24px;">
                <div class="spinRingAdmin" style="position:absolute; inset:0; border:4px solid transparent; border-top-color:#059669; border-right-color:#10b981; border-radius:50%;"></div>
                <div class="spinRingRevAdmin" style="position:absolute; inset:10px; border:4px solid transparent; border-bottom-color:#047857; border-left-color:#34d399; border-radius:50%;"></div>
                <div class="floatShieldAdmin" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#059669; font-size:26px;">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
            <h3 style="margin:0 0 8px; font-size:20px; font-weight:800; color:#0f172a;">Habilitando Colaborador...</h3>
            <p style="margin:0; font-size:13.5px; color:#64748b; line-height:1.5; max-width:340px;">
                Asignando puesto jerárquico, vinculando área y desbloqueando permisos operativos en el organigrama.
            </p>
            <div style="margin-top:20px; display:inline-flex; align-items:center; gap:8px; background:#ecfdf5; border:1px solid #a7f3d0; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700; color:#047857;">
                <i class="fas fa-sync-alt fa-spin"></i> Sincronizando ERP en tiempo real
            </div>
        </div>

        <!-- Estado 2: Éxito con Animación Checkmark SVG y Datos Asignados -->
        <div id="feedbackAdminExito" style="display:none; padding:36px 28px; flex-direction:column; align-items:center;">
            <!-- SVG Checkmark Animado -->
            <div style="width:84px; height:84px; margin-bottom:18px; position:relative; display:flex; align-items:center; justify-content:center;">
                <svg class="checkmark-svg" viewBox="0 0 52 52" style="width:80px; height:80px; border-radius:50%; display:block; stroke-width:3.5; stroke:#10b981; stroke-miterlimit:10;">
                    <circle class="checkmark-circle" cx="26" cy="26" r="23" fill="none"/>
                    <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            
            <span style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:800; padding:4px 12px; border-radius:20px; text-transform:uppercase; letter-spacing:0.5px; border:1px solid #bbf7d0; margin-bottom:8px;">
                ¡Aprobación Exitosa!
            </span>
            <h3 style="margin:0 0 6px; font-size:20px; font-weight:800; color:#0f172a;">Acceso Habilitado</h3>
            <p id="feedbackAdminNombre" style="margin:0 0 18px; font-size:13px; color:#64748b; font-weight:500;">
                El colaborador ya tiene acceso a su portal y módulos designados.
            </p>

            <!-- Ficha de confirmación de Puesto y Área Asignados -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:16px; width:100%; box-sizing:border-box; text-align:left; margin-bottom:20px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px;">
                        <span style="font-size:10.5px; color:#64748b; font-weight:700; text-transform:uppercase; display:flex; align-items:center; gap:5px; margin-bottom:3px;">
                            <i class="fas fa-sitemap" style="color:#059669;"></i> Área Asignada
                        </span>
                        <strong id="feedbackAdminArea" style="font-size:13px; color:#0f172a; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            -
                        </strong>
                    </div>
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px;">
                        <span style="font-size:10.5px; color:#64748b; font-weight:700; text-transform:uppercase; display:flex; align-items:center; gap:5px; margin-bottom:3px;">
                            <i class="fas fa-id-badge" style="color:#2563eb;"></i> Cargo Oficial
                        </span>
                        <strong id="feedbackAdminCargo" style="font-size:13px; color:#0f172a; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            -
                        </strong>
                    </div>
                </div>

                <div style="margin-top:10px; display:flex; align-items:center; justify-content:space-between; padding-top:10px; border-top:1px dashed #e2e8f0; font-size:11.5px; color:#475569;">
                    <span><i class="fas fa-key" style="color:#f59e0b;"></i> Permisos del Puesto:</span>
                    <strong id="feedbackAdminPermisos" style="color:#059669; font-weight:800;">Activos</strong>
                </div>
            </div>

            <!-- Botón de Continuar -->
            <button type="button" onclick="finalizarAprobacionAdmin()" style="width:100%; background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; border:none; padding:12px 20px; border-radius:12px; font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 14px rgba(16,185,129,0.3); transition:all 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="fas fa-check-double"></i>
                <span>Continuar</span>
            </button>
        </div>

        <!-- Estado 3: Error -->
        <div id="feedbackAdminError" style="display:none; padding:36px 28px; flex-direction:column; align-items:center;">
            <div style="width:64px; height:64px; border-radius:50%; background:#fee2e2; color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:28px; margin-bottom:18px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="margin:0 0 8px; font-size:19px; font-weight:800; color:#991b1b;">Error al Habilitar Acceso</h3>
            <p id="feedbackAdminErrorMsg" style="margin:0 0 20px; font-size:13px; color:#64748b; line-height:1.5;">
                Ocurrió un problema al procesar la solicitud.
            </p>
            <button type="button" onclick="reintentarModalAprobacion()" style="padding:10px 22px; border-radius:10px; border:none; background:#0f172a; color:#ffffff; font-weight:700; font-size:13px; cursor:pointer;">
                Volver a Intentar
            </button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE ORGANIGRAMA CORPORATIVO INTERACTIVO (ÁRBOL COMPLETO)             -->
<!-- ========================================================================= -->
<div id="modalOrganigramaPicker" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.75); backdrop-filter:blur(6px); z-index:100005; justify-content:center; align-items:center; padding:16px; box-sizing:border-box;">
    <div style="background:#f8fafc; border-radius:24px; width:100%; max-width:1060px; max-height:92vh; display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4); overflow:hidden; border:1px solid #cbd5e1; animation:modalZoomIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Header del Organigrama -->
        <div style="background:linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%); padding:18px 24px; color:#ffffff; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:44px; height:44px; border-radius:12px; background:rgba(255,255,255,0.18); display:flex; align-items:center; justify-content:center; font-size:20px; border:1.5px solid rgba(255,255,255,0.3);">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div>
                    <h4 style="margin:0; font-size:17.5px; font-weight:800; letter-spacing:-0.3px; display:flex; align-items:center; gap:8px;">
                        Organigrama Corporativo · Asignación Visual
                    </h4>
                    <p style="margin:3px 0 0; font-size:12px; opacity:0.9;">
                        Haz clic en un área para seleccionarla y configurar puestos para <strong id="orgModalUserPill" style="background:rgba(255,255,255,0.22); padding:2px 8px; border-radius:6px; color:#ffffff;">Colaborador</strong>
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalOrganigramaPicker()" style="background:rgba(255,255,255,0.18); border:none; color:#ffffff; width:34px; height:34px; border-radius:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:15px; transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.18)'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Barra de Búsqueda y Filtros de Nivel -->
        <div style="padding:12px 24px; background:#ffffff; border-bottom:1px solid #e2e8f0; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:12px; flex-shrink:0;">
            <div style="position:relative; flex:1; min-width:260px; max-width:440px;">
                <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                <input type="text" id="orgSearchInput" placeholder="Buscar área por nombre o código (ej: GP, Licitaciones, Finanzas)..." oninput="filtrarArbolOrganigrama(this.value)" style="width:100%; padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; color:#1e293b; outline:none; box-sizing:border-box;">
            </div>
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;" id="orgLevelFilterButtons">
                <button type="button" class="btn-org-filter active" data-nivel="all" onclick="filtrarArbolPorNivel('all')">Todas (<?= count($areas) ?>)</button>
                <button type="button" class="btn-org-filter" data-nivel="0" onclick="filtrarArbolPorNivel(0)">👑 Nivel 0: CEO</button>
                <button type="button" class="btn-org-filter" data-nivel="1" onclick="filtrarArbolPorNivel(1)">🏛️ Nivel 1: Gerencias</button>
                <button type="button" class="btn-org-filter" data-nivel="2" onclick="filtrarArbolPorNivel(2)">🏢 Nivel 2: Dptos</button>
                <button type="button" class="btn-org-filter" data-nivel="3" onclick="filtrarArbolPorNivel(3)">⚙️ Nivel 3: Unidades</button>
            </div>
        </div>

        <!-- Canvas del Árbol Organigrama (Scrollable) -->
        <div id="orgTreeCanvas" style="padding:22px 24px; overflow-y:auto; flex:1; max-height:calc(92vh - 165px);">
            <!-- El Árbol completo se genera dinámicamente -->
        </div>

        <!-- Footer del Modal Organigrama -->
        <div style="padding:12px 24px; background:#ffffff; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <div style="font-size:12px; color:#64748b; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-mouse-pointer" style="color:#059669;"></i>
                <span>Haz clic sobre el cuadro del Área que deseas asignar a este colaborador.</span>
            </div>
            <button type="button" onclick="cerrarModalOrganigramaPicker()" style="padding:7px 18px; border-radius:8px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:12.5px; font-weight:700; cursor:pointer;">
                Cerrar Organigrama
            </button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT / AJAX REACTIVO Y FILTRADO SEGURO                             -->
<!-- ========================================================================= -->
<script>
// ── Datos Base en Memoria (Áreas y Cargos de la Empresa) ──
const todasLasAreas = <?= json_encode(array_map(function($a) {
    return [
        'id'            => (int)$a['id'],
        'nombre'        => $a['nombre'],
        'codigo'        => $a['codigo'],
        'descripcion'   => $a['descripcion'] ?? '',
        'parent_id'     => $a['parent_id'] !== null ? (int)$a['parent_id'] : null,
        'padre_nombre'  => $a['padre_nombre'] ?? '',
        'nivel'         => (int)$a['nivel'],
        'icono'         => !empty($a['icono']) ? $a['icono'] : 'fas fa-sitemap',
        'color'         => !empty($a['color']) ? $a['color'] : '#059669',
        'total_cargos'  => (int)($a['total_cargos'] ?? 0),
        'total_usuarios'=> (int)($a['total_usuarios'] ?? 0)
    ];
}, $areas), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

const todosLosCargos = <?= json_encode(array_map(function($c) {
    return [
        'id'               => (int)$c['id'],
        'nombre'           => $c['nombre'],
        'codigo'           => $c['codigo'],
        'area_id'          => (int)$c['area_id'],
        'nivel_jerarquico' => (int)$c['nivel_jerarquico'],
        'puede_aprobar'    => (int)($c['puede_aprobar'] ?? 0),
        'area_nombre'      => $c['area_nombre'] ?? '',
        'total_permisos'   => (int)($c['total_permisos'] ?? 0)
    ];
}, $cargos), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// Estado actual
let areaSeleccionadaId = null;
let cargoSeleccionadoId = null;
let nivelFiltroActual = 'all';

// Mapas de ayuda para niveles
const NIVELES_CONFIG = {
    0: { nombre: 'Alta Dirección (CEO)', badge: 'Nivel 0', color: '#7c3aed', bg: '#f3e8ff', border: '#d8b4fe', icon: 'fas fa-crown' },
    1: { nombre: 'Gerencias de División', badge: 'Nivel 1', color: '#0284c7', bg: '#e0f2fe', border: '#bae6fd', icon: 'fas fa-briefcase' },
    2: { nombre: 'Departamentos Funcionales', badge: 'Nivel 2', color: '#059669', bg: '#dcfce7', border: '#bbf7d0', icon: 'fas fa-building' },
    3: { nombre: 'Unidades Especializadas', badge: 'Nivel 3', color: '#d97706', bg: '#fef3c7', border: '#fde68a', icon: 'fas fa-layer-group' }
};

const CARGOS_NIVELES = {
    1: { label: '⭐ Nivel 1 · CEO / Alta Dirección', color: '#7c3aed', bg: '#f3e8ff' },
    2: { label: '🔷 Nivel 2 · Gerencia de Área', color: '#0284c7', bg: '#e0f2fe' },
    3: { label: '🔶 Nivel 3 · Jefatura de Departamento', color: '#d97706', bg: '#fef3c7' },
    4: { label: '🟢 Nivel 4 · Especialista / Analista', color: '#059669', bg: '#dcfce7' },
    5: { label: '⚪ Nivel 5 · Asistente / Operativo', color: '#475569', bg: '#f1f5f9' }
};

// ── Inicialización y Renderizado de Opciones de Áreas ──
function renderizarListaAreasDropdown(areasFiltradas = todasLasAreas) {
    const contenedor = document.getElementById('listaOpcionesAreas');
    if (!contenedor) return;

    if (!areasFiltradas || areasFiltradas.length === 0) {
        contenedor.innerHTML = '<div style="padding:16px; text-align:center; color:#94a3b8; font-size:12px;"><i class="fas fa-search mb-1"></i> No se encontraron áreas coincidentes</div>';
        return;
    }

    // Agrupar por nivel
    const niveles = [0, 1, 2, 3];
    let html = '';

    niveles.forEach(nivel => {
        const delNivel = areasFiltradas.filter(a => a.nivel === nivel);
        if (delNivel.length > 0) {
            const cfg = NIVELES_CONFIG[nivel] || { nombre: `Nivel ${nivel}`, icon: 'fas fa-sitemap', color: '#64748b' };
            html += `
                <div class="custom-area-group-title">
                    <i class="${cfg.icon}" style="color:${cfg.color}; font-size:11px;"></i> ${cfg.nombre}
                </div>
            `;
            delNivel.forEach(a => {
                const isSelected = (areaSeleccionadaId === a.id);
                html += `
                    <div class="custom-area-option ${isSelected ? 'is-selected' : ''}" onclick="seleccionarArea(${a.id})">
                        <div style="display:flex; align-items:center; gap:9px; min-width:0;">
                            <div style="width:28px; height:28px; border-radius:8px; background:${cfg.bg}; color:${cfg.color}; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0;">
                                <i class="${a.icono}"></i>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:12.5px; font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    ${a.nombre} <span style="font-size:10.5px; color:#64748b; font-weight:700;">[${a.codigo}]</span>
                                </div>
                                <div style="font-size:10.5px; color:#64748b;">
                                    ${a.padre_nombre ? `Depende de: ${a.padre_nombre}` : cfg.nombre}
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:6px; background:${cfg.bg}; color:${cfg.color}; border:1px solid ${cfg.border};">
                                ${a.total_cargos} puestos
                            </span>
                            ${isSelected ? '<i class="fas fa-check" style="color:#10b981; font-size:12px;"></i>' : ''}
                        </div>
                    </div>
                `;
            });
        }
    });

    contenedor.innerHTML = html;
}

// ── Abrir/Cerrar Dropdown de Área ──
function toggleAreaDropdown(force = null) {
    const dd = document.getElementById('customAreaDropdown');
    const trigger = document.getElementById('customAreaTrigger');
    const chevron = document.getElementById('customAreaChevron');
    const isOpen = (force !== null) ? force : !dd.classList.contains('is-open');

    if (isOpen) {
        dd.classList.add('is-open');
        trigger.classList.add('is-open');
        chevron.style.transform = 'rotate(180deg)';
        const input = document.getElementById('inputBuscarArea');
        if (input) {
            input.value = '';
            setTimeout(() => input.focus(), 50);
        }
        renderizarListaAreasDropdown(todasLasAreas);
    } else {
        dd.classList.remove('is-open');
        trigger.classList.remove('is-open');
        chevron.style.transform = 'rotate(0deg)';
    }
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(e) {
    const trigger = document.getElementById('customAreaTrigger');
    const dd = document.getElementById('customAreaDropdown');
    if (trigger && dd && !trigger.contains(e.target) && !dd.contains(e.target)) {
        toggleAreaDropdown(false);
    }
});

function filtrarAreasDropdown(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
        renderizarListaAreasDropdown(todasLasAreas);
        return;
    }
    const filtradas = todasLasAreas.filter(a => 
        a.nombre.toLowerCase().includes(q) || 
        a.codigo.toLowerCase().includes(q) ||
        (a.padre_nombre && a.padre_nombre.toLowerCase().includes(q))
    );
    renderizarListaAreasDropdown(filtradas);
}

// ── Seleccionar Área (desde Dropdown o desde Organigrama) ──
function seleccionarArea(areaId) {
    areaId = parseInt(areaId);
    const area = todasLasAreas.find(a => a.id === areaId);
    if (!area) return;

    areaSeleccionadaId = areaId;
    document.getElementById('modalAreaSelect').value = areaId;

    // Actualizar Trigger Visual
    const cfg = NIVELES_CONFIG[area.nivel] || { bg: '#f1f5f9', color: '#059669', badge: `Nivel ${area.nivel}` };
    document.getElementById('customAreaIconBox').style.background = cfg.bg;
    document.getElementById('customAreaIconBox').style.color = cfg.color;
    document.getElementById('customAreaIconBox').innerHTML = `<i class="${area.icono}"></i>`;
    document.getElementById('customAreaText').textContent = `${area.nombre} [${area.codigo}]`;
    document.getElementById('customAreaText').style.color = '#0f172a';
    document.getElementById('customAreaSub').textContent = area.padre_nombre ? `Depende de: ${area.padre_nombre} · ${cfg.nombre}` : cfg.nombre;

    const pill = document.getElementById('customAreaPill');
    pill.style.display = 'inline-block';
    pill.textContent = cfg.badge;
    pill.style.background = cfg.bg;
    pill.style.color = cfg.color;
    pill.style.borderColor = cfg.border;

    // Actualizar Banner
    const banner = document.getElementById('bannerAreaActiva');
    const bannerTxt = document.getElementById('bannerAreaActivaTexto');
    banner.style.display = 'flex';
    bannerTxt.innerHTML = `Área activa: <strong>${area.nombre} [${area.codigo}]</strong> (${cfg.nombre})`;

    toggleAreaDropdown(false);

    // Filtrar y renderizar Cargos de esta área
    filtrarCargosPorArea(areaId);

    // Resaltar suavemente la sección de cargos
    const cargoSec = document.getElementById('seccionCargosContainer');
    if (cargoSec) {
        cargoSec.style.animation = 'pulseSelect 0.8s ease-out';
        setTimeout(() => cargoSec.style.animation = '', 800);
    }
}

// ── Filtrar y Renderizar Cargos Disponibles para el Área ──
function filtrarCargosPorArea(areaId) {
    const contenedor = document.getElementById('contenedorTarjetasCargos');
    const counter = document.getElementById('cargoCounterPill');
    const modalCargoSelect = document.getElementById('modalCargoSelect');

    const filtrados = areaId ? todosLosCargos.filter(c => c.area_id == areaId) : [];

    // Limpiar select nativo oculto
    modalCargoSelect.innerHTML = '<option value="">-- Selecciona el puesto oficial --</option>';
    filtrados.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.setAttribute('data-area-id', c.area_id);
        opt.setAttribute('data-nivel-jerarquico', c.nivel_jerarquico);
        opt.setAttribute('data-codigo', c.codigo);
        opt.textContent = `${c.nombre} [${c.codigo}]`;
        modalCargoSelect.appendChild(opt);
    });

    counter.textContent = `${filtrados.length} puestos disponibles`;

    if (!areaId) {
        contenedor.innerHTML = `
            <div style="background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:12px; padding:18px; text-align:center; color:#64748b;">
                <i class="fas fa-arrow-up" style="color:#059669; font-size:18px; margin-bottom:6px; display:block;"></i>
                <div style="font-size:12.5px; font-weight:700; color:#334155;">Selecciona un Área primero</div>
                <div style="font-size:11.5px; margin-top:2px;">Elige el área arriba o en el Organigrama para desplegar sus puestos oficiales disponibles.</div>
            </div>
        `;
        return;
    }

    if (filtrados.length === 0) {
        contenedor.innerHTML = `
            <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:14px 16px; color:#92400e; font-size:12px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-exclamation-triangle" style="font-size:16px; color:#d97706;"></i>
                <div>
                    <strong>Esta Área no tiene puestos creados aún.</strong><br>
                    Puedes crear cargos en <a href="<?= BASE_URL ?>/organizacion/cargos" target="_blank" style="color:#b45309; font-weight:700; text-decoration:underline;">Organización &gt; Cargos</a> o elegir otra área.
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    filtrados.forEach(c => {
        const isSelected = (cargoSeleccionadoId === c.id);
        const ncfg = CARGOS_NIVELES[c.nivel_jerarquico] || { label: `Nivel ${c.nivel_jerarquico}`, color: '#475569', bg: '#f1f5f9' };
        
        html += `
            <div class="cargo-card-item ${isSelected ? 'is-selected' : ''}" id="cargoCard_${c.id}" data-cargo-id="${c.id}" onclick="seleccionarCargo(${c.id})">
                <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                    <div class="cargo-radio-circle">
                        <i class="fas fa-check" style="${isSelected ? 'display:inline-block;' : 'display:none;'}"></i>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-size:13px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            <span>${c.nombre}</span>
                            <span style="font-size:10.5px; font-weight:700; color:#64748b;">[${c.codigo}]</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px; margin-top:3px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:700; padding:2px 7px; border-radius:5px; background:${ncfg.bg}; color:${ncfg.color};">
                                ${ncfg.label}
                            </span>
                            ${c.puede_aprobar ? '<span style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:5px; background:#dcfce7; color:#15803d;"><i class="fas fa-check-double"></i> Puede Aprobar</span>' : ''}
                            ${c.total_permisos ? `<span style="font-size:10px; font-weight:600; color:#64748b;"><i class="fas fa-shield-alt"></i> ${c.total_permisos} permisos</span>` : ''}
                        </div>
                    </div>
                </div>
                <div>
                    <button type="button" class="cargo-radio-btn">
                        ${isSelected ? '<i class="fas fa-check"></i> Seleccionado' : 'Elegir'}
                    </button>
                </div>
            </div>
        `;
    });

    contenedor.innerHTML = html;
}

// ── Seleccionar Cargo ──
function seleccionarCargo(cargoId) {
    cargoId = parseInt(cargoId);
    cargoSeleccionadoId = cargoId;
    const modalCargoSelect = document.getElementById('modalCargoSelect');
    modalCargoSelect.value = cargoId;

    // Resaltar tarjeta seleccionada y activar visualmente el circulito radio con checkmark verde
    document.querySelectorAll('.cargo-card-item').forEach(card => {
        const id = parseInt(card.getAttribute('data-cargo-id'));
        const icon = card.querySelector('.cargo-radio-circle i');
        const btn = card.querySelector('.cargo-radio-btn');

        if (id === cargoId) {
            card.classList.add('is-selected');
            if (icon) icon.style.display = 'inline-block';
            if (btn) btn.innerHTML = '<i class="fas fa-check"></i> Seleccionado';
        } else {
            card.classList.remove('is-selected');
            if (icon) icon.style.display = 'none';
            if (btn) btn.textContent = 'Elegir';
        }
    });

    // Auto-sugerir rol del sistema según nivel jerárquico del cargo
    const cargo = todosLosCargos.find(c => c.id === cargoId);
    if (cargo) {
        sugerirRolSegunNivel(cargo.nivel_jerarquico, cargo.nombre);
    }
}

// ── Sugerir Rol del Sistema ──
function sugerirRolSegunNivel(nivel, nombreCargo) {
    const explicacion = document.getElementById('rolExplicacion');
    if (nivel <= 1) {
        seleccionarRolSistema('admin');
        explicacion.innerHTML = `<strong>Nivel 0 / 1 (${nombreCargo}):</strong> Cargo ejecutivo/CEO asignado automáticamente con privilegios de <strong>Administrador Global</strong>.`;
        explicacion.style.color = '#7c3aed';
        explicacion.style.background = '#f3e8ff';
        explicacion.style.borderColor = '#d8b4fe';
    } else if (nivel === 2) {
        seleccionarRolSistema('usuario');
        explicacion.innerHTML = `<strong>Nivel 2 (Gerencia de Área):</strong> Colaborador con gestión operativa y aprobación sobre su área y dependencias.`;
        explicacion.style.color = '#0284c7';
        explicacion.style.background = '#e0f2fe';
        explicacion.style.borderColor = '#bae6fd';
    } else if (nivel === 3) {
        seleccionarRolSistema('usuario');
        explicacion.innerHTML = `<strong>Nivel 3 (Jefatura de Departamento):</strong> Acceso de Jefatura enfocado en los procesos de su departamento.`;
        explicacion.style.color = '#d97706';
        explicacion.style.background = '#fef3c7';
        explicacion.style.borderColor = '#fde68a';
    } else {
        seleccionarRolSistema('usuario');
        explicacion.innerHTML = `<strong>Nivel 4 / 5 (${nombreCargo}):</strong> Colaborador Operativo. Acceso acotado exclusivamente a las tareas de su área.`;
        explicacion.style.color = '#059669';
        explicacion.style.background = '#f0fdf4';
        explicacion.style.borderColor = '#bbf7d0';
    }
}

// ── Seleccionar Rol del Sistema (Radio Cards) ──
function seleccionarRolSistema(rol) {
    document.getElementById('modalRolSelect').value = rol;

    const cardUser = document.getElementById('roleCardUsuario');
    const cardAdmin = document.getElementById('roleCardAdmin');
    const checkUser = document.getElementById('roleCheckUsuario');
    const checkAdmin = document.getElementById('roleCheckAdmin');

    if (rol === 'admin') {
        cardAdmin.classList.add('is-selected');
        cardUser.classList.remove('is-selected');
        checkAdmin.className = 'fas fa-check-circle';
        checkAdmin.style.color = '#7c3aed';
        checkUser.className = 'far fa-circle';
        checkUser.style.color = '#cbd5e1';
    } else {
        cardUser.classList.add('is-selected');
        cardAdmin.classList.remove('is-selected');
        checkUser.className = 'fas fa-check-circle';
        checkUser.style.color = '#10b981';
        checkAdmin.className = 'far fa-circle';
        checkAdmin.style.color = '#cbd5e1';
    }
}

// =========================================================================
// MODAL ORGANIGRAMA INTERACTIVO (ÁRBOL CORPORATIVO)
// =========================================================================
function abrirModalOrganigramaPicker() {
    const userName = document.getElementById('modalUserName').textContent;
    document.getElementById('orgModalUserPill').textContent = userName;
    document.getElementById('orgSearchInput').value = '';
    nivelFiltroActual = 'all';

    // Marcar botón de filtro 'Todas'
    document.querySelectorAll('#orgLevelFilterButtons button').forEach(b => b.classList.remove('active'));
    const btnAll = document.querySelector('#orgLevelFilterButtons button[data-nivel="all"]');
    if (btnAll) btnAll.classList.add('active');

    renderizarCanvasOrganigrama();
    document.getElementById('modalOrganigramaPicker').style.display = 'flex';
}

function cerrarModalOrganigramaPicker() {
    document.getElementById('modalOrganigramaPicker').style.display = 'none';
}

function filtrarArbolPorNivel(nivel) {
    nivelFiltroActual = nivel;
    document.querySelectorAll('#orgLevelFilterButtons button').forEach(b => b.classList.remove('active'));
    const btn = document.querySelector(`#orgLevelFilterButtons button[data-nivel="${nivel}"]`);
    if (btn) btn.classList.add('active');

    const query = (document.getElementById('orgSearchInput').value || '').toLowerCase().trim();
    renderizarCanvasOrganigrama(query, nivel);
}

function filtrarArbolOrganigrama(query) {
    renderizarCanvasOrganigrama(query, nivelFiltroActual);
}

// ── Renderizado del Árbol Organigrama en el Canvas ──
function renderizarCanvasOrganigrama(query = '', nivelFiltro = 'all') {
    const canvas = document.getElementById('orgTreeCanvas');
    if (!canvas) return;

    const q = (query || '').toLowerCase().trim();

    // Filtrar áreas
    let areasFiltradas = todasLasAreas;
    if (nivelFiltro !== 'all') {
        const n = parseInt(nivelFiltro);
        areasFiltradas = areasFiltradas.filter(a => a.nivel === n);
    }
    if (q) {
        areasFiltradas = areasFiltradas.filter(a => 
            a.nombre.toLowerCase().includes(q) || 
            a.codigo.toLowerCase().includes(q) ||
            (a.padre_nombre && a.padre_nombre.toLowerCase().includes(q))
        );
    }

    if (areasFiltradas.length === 0) {
        canvas.innerHTML = `
            <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:16px; padding:32px; text-align:center; color:#64748b;">
                <i class="fas fa-search" style="font-size:24px; color:#cbd5e1; margin-bottom:8px; display:block;"></i>
                <div style="font-size:14px; font-weight:700; color:#0f172a;">No se encontraron áreas coincidentes</div>
                <div style="font-size:12px; margin-top:4px;">Intenta con otro término o selecciona "Todas" en los filtros de nivel.</div>
            </div>
        `;
        return;
    }

    // Identificar niveles presentes
    const ceoArea = areasFiltradas.find(a => a.nivel === 0);
    const gerencias = areasFiltradas.filter(a => a.nivel === 1);
    const departamentos = areasFiltradas.filter(a => a.nivel === 2);
    const unidades = areasFiltradas.filter(a => a.nivel === 3);

    let html = '';

    // Si no estamos buscando texto específico, pintar la estructura jerárquica
    if (!q && nivelFiltro === 'all') {
        // ── 1. CEO / NIVEL 0 ──
        if (ceoArea) {
            const isSel = (areaSeleccionadaId === ceoArea.id);
            html += `
                <div style="display:flex; justify-content:center; margin-bottom:14px;">
                    <div class="org-node-card ${isSel ? 'is-selected' : ''}" style="width:100%; max-width:480px; background:linear-gradient(135deg, #ffffff 0%, #faf5ff 100%); border-color:#d8b4fe;" onclick="seleccionarAreaDesdeOrganigrama(${ceoArea.id})">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:40px; height:40px; border-radius:12px; background:linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:18px; box-shadow:0 3px 10px rgba(124,58,237,0.25);">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div>
                                    <div style="font-size:14px; font-weight:800; color:#0f172a;">
                                        ${ceoArea.nombre} <span style="font-size:11px; color:#7c3aed; font-weight:800;">[${ceoArea.codigo}]</span>
                                    </div>
                                    <div style="font-size:11px; color:#64748b;">Dirección General Ejecutiva (CEO)</div>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <span style="font-size:10.5px; font-weight:800; padding:3px 9px; border-radius:6px; background:#f3e8ff; color:#7c3aed; border:1px solid #d8b4fe;">
                                    👑 Nivel 0 · ${ceoArea.total_cargos} puestos
                                </span>
                                <div style="margin-top:4px;">
                                    <span style="font-size:11px; font-weight:700; color:#059669;"><i class="fas fa-check"></i> Asignar Área</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Línea conectora central -->
                <div style="width:2px; height:20px; background:#cbd5e1; margin:0 auto 14px;"></div>
            `;
        }

        // ── 2. GERENCIAS (NIVEL 1) CON SUS DEPARTAMENTOS HIJOS ──
        html += '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">';
        gerencias.forEach(g => {
            const isSel = (areaSeleccionadaId === g.id);
            const hijosDeptos = todasLasAreas.filter(a => a.parent_id === g.id && a.nivel === 2);

            html += `
                <div style="background:#ffffff; border:1.5px solid #e2e8f0; border-radius:16px; padding:14px; display:flex; flex-direction:column; gap:10px; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                    <!-- Cabecera de Gerencia (Nivel 1) -->
                    <div class="org-node-card ${isSel ? 'is-selected' : ''}" style="background:#f0f9ff; border-color:#bae6fd;" onclick="seleccionarAreaDesdeOrganigrama(${g.id})">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                <div style="width:30px; height:30px; border-radius:8px; background:#0284c7; color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0;">
                                    <i class="${g.icono}"></i>
                                </div>
                                <div style="min-width:0;">
                                    <div style="font-size:12.5px; font-weight:800; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        ${g.nombre}
                                    </div>
                                    <div style="font-size:10px; font-weight:800; color:#0284c7;">${g.codigo} · NIVEL 1</div>
                                </div>
                            </div>
                            <span style="font-size:10px; font-weight:700; background:#ffffff; color:#0284c7; padding:2px 6px; border-radius:5px; border:1px solid #bae6fd; flex-shrink:0;">
                                ${g.total_cargos} puestos
                            </span>
                        </div>
                    </div>

                    <!-- Departamentos Hijos (Nivel 2) -->
                    <div style="display:flex; flex-direction:column; gap:6px; padding-left:8px; border-left:2px solid #e2e8f0; margin-left:12px;">
            `;

            if (hijosDeptos.length > 0) {
                hijosDeptos.forEach(d => {
                    const isDeptSel = (areaSeleccionadaId === d.id);
                    const hijosUnidades = todasLasAreas.filter(a => a.parent_id === d.id && a.nivel === 3);

                    html += `
                        <div class="org-node-card ${isDeptSel ? 'is-selected' : ''}" style="padding:9px 11px; background:#f8fafc;" onclick="seleccionarAreaDesdeOrganigrama(${d.id})">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                                <div style="display:flex; align-items:center; gap:7px; min-width:0;">
                                    <i class="fas fa-building" style="color:#059669; font-size:11px; flex-shrink:0;"></i>
                                    <span style="font-size:11.5px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        ${d.nombre} <small style="color:#64748b;">[${d.codigo}]</small>
                                    </span>
                                </div>
                                <span style="font-size:9.5px; font-weight:700; background:#dcfce7; color:#15803d; padding:1.5px 5px; border-radius:4px; flex-shrink:0;">
                                    ${d.total_cargos} p.
                                </span>
                            </div>
                        </div>
                    `;

                    // Unidades Hijas (Nivel 3)
                    if (hijosUnidades.length > 0) {
                        html += '<div style="display:flex; flex-direction:column; gap:4px; padding-left:10px; border-left:1.5px dashed #cbd5e1; margin-left:8px;">';
                        hijosUnidades.forEach(u => {
                            const isUnitSel = (areaSeleccionadaId === u.id);
                            html += `
                                <div class="org-node-card ${isUnitSel ? 'is-selected' : ''}" style="padding:6px 9px; background:#fffbeb; border-color:#fde68a;" onclick="seleccionarAreaDesdeOrganigrama(${u.id})">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                                        <div style="display:flex; align-items:center; gap:6px; min-width:0;">
                                            <i class="fas fa-layer-group" style="color:#d97706; font-size:10px;"></i>
                                            <span style="font-size:11px; font-weight:700; color:#78350f; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                ${u.nombre} [${u.codigo}]
                                            </span>
                                        </div>
                                        <span style="font-size:9px; font-weight:700; color:#b45309;">${u.total_cargos} p.</span>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }
                });
            } else {
                html += '<div style="font-size:10.5px; color:#94a3b8; font-style:italic;">Sin departamentos registrados</div>';
            }

            html += `
                    </div>
                </div>
            `;
        });
        html += '</div>';
    } else {
        // Vista de Grid Plano cuando se busca por texto o se filtra por un solo nivel
        html += '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:12px;">';
        areasFiltradas.forEach(a => {
            const isSel = (areaSeleccionadaId === a.id);
            const cfg = NIVELES_CONFIG[a.nivel] || { bg: '#f1f5f9', color: '#059669', badge: `Nivel ${a.nivel}`, icon: 'fas fa-sitemap' };

            html += `
                <div class="org-node-card ${isSel ? 'is-selected' : ''}" onclick="seleccionarAreaDesdeOrganigrama(${a.id})">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                            <div style="width:34px; height:34px; border-radius:10px; background:${cfg.bg}; color:${cfg.color}; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0;">
                                <i class="${a.icono}"></i>
                            </div>
                            <div style="min-width:0;">
                                <div style="font-size:13px; font-weight:800; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    ${a.nombre}
                                </div>
                                <div style="font-size:10.5px; color:#64748b;">
                                    ${a.codigo} · ${a.padre_nombre ? `Depende de: ${a.padre_nombre}` : cfg.nombre}
                                </div>
                            </div>
                        </div>
                        <div style="text-align:right; flex-shrink:0;">
                            <span style="font-size:10px; font-weight:800; padding:2px 7px; border-radius:6px; background:${cfg.bg}; color:${cfg.color}; border:1px solid ${cfg.border};">
                                ${cfg.badge}
                            </span>
                            <div style="font-size:10.5px; font-weight:700; color:#059669; margin-top:3px;">
                                ${a.total_cargos} puestos
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
    }

    canvas.innerHTML = html;
}

// ── Clic en Nodo del Organigrama ──
function seleccionarAreaDesdeOrganigrama(areaId) {
    seleccionarArea(areaId);
    cerrarModalOrganigramaPicker();
}

// =========================================================================
// GESTIÓN DEL MODAL PRINCIPAL DE APROBACIÓN
// =========================================================================
function abrirModalAprobar(solId, userId, userName, userEmail, areaPrevia = 0, cargoPrevio = 0) {
    document.getElementById('modalSolId').value = solId;
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('modalUserEmail').textContent = userEmail;
    document.getElementById('modalObservaciones').value = '';

    areaSeleccionadaId = null;
    cargoSeleccionadoId = null;

    // Resetear UI
    document.getElementById('customAreaIconBox').style.background = '#f1f5f9';
    document.getElementById('customAreaIconBox').style.color = '#64748b';
    document.getElementById('customAreaIconBox').innerHTML = '<i class="fas fa-sitemap"></i>';
    document.getElementById('customAreaText').textContent = '-- Selecciona el Área de trabajo o elígela en el Organigrama --';
    document.getElementById('customAreaText').style.color = '#334155';
    document.getElementById('customAreaSub').textContent = 'Clic para buscar o abre el organigrama interactivo';
    document.getElementById('customAreaPill').style.display = 'none';
    document.getElementById('bannerAreaActiva').style.display = 'none';

    renderizarListaAreasDropdown(todasLasAreas);

    if (areaPrevia && parseInt(areaPrevia) > 0) {
        seleccionarArea(areaPrevia);
    } else {
        filtrarCargosPorArea(0);
    }

    if (cargoPrevio && parseInt(cargoPrevio) > 0) {
        seleccionarCargo(cargoPrevio);
    } else {
        seleccionarRolSistema('usuario');
    }

    document.getElementById('modalAprobarSolicitud').style.display = 'flex';
}

function cerrarModalAprobar() {
    document.getElementById('modalAprobarSolicitud').style.display = 'none';
    toggleAreaDropdown(false);
}

function mostrarErrorAprobacionInline(msg) {
    const alertBox = document.getElementById('alertaAprobacionInline');
    const alertText = document.getElementById('alertaAprobacionTexto');
    if (alertBox && alertText) {
        alertText.textContent = msg;
        alertBox.style.display = 'flex';
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function ocultarErrorAprobacionInline() {
    const alertBox = document.getElementById('alertaAprobacionInline');
    if (alertBox) alertBox.style.display = 'none';
}

function finalizarAprobacionAdmin() {
    const modalFeedback = document.getElementById('modalFeedbackAprobacionAdmin');
    if (modalFeedback) modalFeedback.style.display = 'none';
    window.location.reload();
}

function reintentarModalAprobacion() {
    const modalFeedback = document.getElementById('modalFeedbackAprobacionAdmin');
    if (modalFeedback) modalFeedback.style.display = 'none';
    const modalAprobar = document.getElementById('modalAprobarSolicitud');
    if (modalAprobar) modalAprobar.style.display = 'flex';
}

// ── Enviar Aprobación vía Fetch/AJAX Seguro con Feedback Modal Animado ──
async function enviarAprobacion(e) {
    e.preventDefault();

    const areaVal = document.getElementById('modalAreaSelect').value;
    const cargoVal = document.getElementById('modalCargoSelect').value;

    if (!areaVal) {
        mostrarErrorAprobacionInline('Por favor selecciona un Área Organizacional en el selector o en el Organigrama.');
        toggleAreaDropdown(true);
        return;
    }

    if (!cargoVal) {
        mostrarErrorAprobacionInline('Por favor selecciona un Cargo oficial para el colaborador.');
        document.getElementById('seccionCargosContainer').scrollIntoView({ behavior: 'smooth' });
        return;
    }

    ocultarErrorAprobacionInline();

    // 1. Ocultar modal de formulario y mostrar modal de feedback animado en estado "Cargando"
    document.getElementById('modalAprobarSolicitud').style.display = 'none';
    
    const feedbackModal = document.getElementById('modalFeedbackAprobacionAdmin');
    const estadoCargando = document.getElementById('feedbackAdminCargando');
    const estadoExito = document.getElementById('feedbackAdminExito');
    const estadoError = document.getElementById('feedbackAdminError');

    estadoCargando.style.display = 'flex';
    estadoExito.style.display = 'none';
    estadoError.style.display = 'none';
    feedbackModal.style.display = 'flex';

    const formData = new FormData(document.getElementById('formAprobarSolicitud'));
    const startTime = Date.now();

    try {
        const response = await fetch('<?= BASE_URL ?>/usuarios/aprobarSolicitud', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        // Garantizar al menos 600ms de spinner animado para que la transición sea fluida y agradable
        const elapsed = Date.now() - startTime;
        if (elapsed < 600) {
            await new Promise(r => setTimeout(r, 600 - elapsed));
        }

        if (data.success) {
            // Rellenar datos en la ficha de éxito
            const nombreEl = document.getElementById('feedbackAdminNombre');
            const areaEl = document.getElementById('feedbackAdminArea');
            const cargoEl = document.getElementById('feedbackAdminCargo');
            const permisosEl = document.getElementById('feedbackAdminPermisos');

            if (nombreEl) {
                nombreEl.innerHTML = `<strong>${escapeHtml(data.colaborador || 'Colaborador')}</strong> habilitado exitosamente en el ERP.`;
            }
            if (areaEl) {
                areaEl.textContent = (data.area_nombre || 'Área Asignada') + (data.area_codigo ? ' [' + data.area_codigo + ']' : '');
            }
            if (cargoEl) {
                cargoEl.textContent = (data.cargo_nombre || 'Puesto Oficial') + (data.cargo_codigo ? ' [' + data.cargo_codigo + ']' : '');
            }
            if (permisosEl) {
                permisosEl.textContent = data.permisos_count ? `${data.permisos_count} permisos autorizados` : 'Permisos del Puesto Activos';
            }

            // Cambiar a Estado de Éxito con checkmark animado
            estadoCargando.style.display = 'none';
            estadoExito.style.display = 'flex';
        } else {
            document.getElementById('feedbackAdminErrorMsg').textContent = data.message || 'No se pudo procesar la aprobación.';
            estadoCargando.style.display = 'none';
            estadoError.style.display = 'flex';
        }
    } catch (err) {
        document.getElementById('feedbackAdminErrorMsg').textContent = 'Ocurrió un error en la comunicación con el servidor.';
        estadoCargando.style.display = 'none';
        estadoError.style.display = 'flex';
    }
}

// ── Rechazar Solicitud vía Fetch/AJAX Seguro ──
async function rechazarSolicitud(solId) {
    const obs = prompt('Ingresa el motivo del rechazo para informar al colaborador (opcional):');
    if (obs === null) return; // Cancelado por el usuario

    const formData = new FormData();
    formData.append('csrf_token', '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>');
    formData.append('solicitud_id', solId);
    formData.append('observaciones', obs);

    try {
        const response = await fetch('<?= BASE_URL ?>/usuarios/rechazarSolicitud', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert('Solicitud rechazada exitosamente.');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo rechazar la solicitud.'));
        }
    } catch (err) {
        alert('Ocurrió un error al procesar la solicitud.');
    }
}

// ── Filtrar Tabla de Solicitudes ──
function filtrarTablaSolicitudes() {
    const texto = (document.getElementById('filtroTextoSol').value || '').toLowerCase().trim();
    const estado = (document.getElementById('filtroEstadoSol').value || '').toLowerCase().trim();
    const filas = document.querySelectorAll('#tablaSolicitudesAcceso tbody tr.fila-solicitud');

    filas.forEach(f => {
        const filaEstado = (f.getAttribute('data-estado') || '').toLowerCase();
        const filaTexto = (f.getAttribute('data-texto') || '').toLowerCase();

        const coincideTexto = !texto || filaTexto.includes(texto);
        const coincideEstado = !estado || filaEstado === estado;

        f.style.display = (coincideTexto && coincideEstado) ? '' : 'none';
    });
}

// Inicializar filtros al cargar la vista
document.addEventListener('DOMContentLoaded', filtrarTablaSolicitudes);
</script>
