<?php
// Ruta: views/organizacion/solicitudes_acceso.php

/**
 * Vista: Aprobación y Asignación de Solicitudes de Acceso
 * Módulo: Organización y Estructura Corporativa
 * Gestión de Proyectos - ERP Core MVC
 */

require_once __DIR__ . '/../../helpers/PermisosHelper.php';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
require_once __DIR__ . '/../../models/OrganizacionModel.php';

PermisosHelper::verificarAcceso('organizacion');

$csrfToken = CsrfHelper::generarToken();
$orgModel  = new OrganizacionModel();

// Carga segura de datos (admite inyección desde el Controlador o consulta de respaldo)
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

    <!-- Barra de Navegación del Módulo Organización -->
    <?php
    $activeTab = 'solicitudes';
    $orgNavTitle = 'Aprobación de Solicitudes & Roles';
    $orgNavSubtitle = 'Habilita nuevos colaboradores asignándoles obligatoriamente un Área y Cargo del Organigrama';
    if (file_exists(__DIR__ . '/navbar_organizacion.php')) {
        include __DIR__ . '/navbar_organizacion.php';
    }
    ?>

    <!-- Tarjetas KPI de Estado -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3" style="background:#ffffff; border-left:4px solid #f59e0b !important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:19px;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark lh-1"><?= (int)$pendientesCount ?></div>
                        <div class="text-uppercase fw-semibold mt-1" style="font-size:11px; color:#d97706; letter-spacing:0.5px;">Pendientes de Aprobación</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3" style="background:#ffffff; border-left:4px solid #10b981 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:19px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark lh-1"><?= (int)$aprobadasCount ?></div>
                        <div class="text-uppercase fw-semibold mt-1" style="font-size:11px; color:#15803d; letter-spacing:0.5px;">Aprobadas & Asignadas</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3" style="background:#ffffff; border-left:4px solid #ef4444 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:19px;">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark lh-1"><?= (int)$rechazadasCount ?></div>
                        <div class="text-uppercase fw-semibold mt-1" style="font-size:11px; color:#b91c1c; letter-spacing:0.5px;">Rechazadas</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3" style="background:#ffffff; border-left:4px solid #6366f1 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:#e0e7ff; color:#4338ca; display:flex; align-items:center; justify-content:center; font-size:19px;">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark lh-1"><?= (int)$totalSolicitudes ?></div>
                        <div class="text-uppercase fw-semibold mt-1" style="font-size:11px; color:#4338ca; letter-spacing:0.5px;">Total Solicitudes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal: Filtros y Tabla -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background:#ffffff;">
        <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="fs-6 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="fas fa-user-clock text-primary"></i>
                    Bandeja de Solicitudes de Ingreso
                </h3>
                <p class="text-secondary mb-0" style="font-size:12.5px;">
                    Define el Área y Cargo oficial en el organigrama para activar el acceso al ERP.
                </p>
            </div>

            <!-- Controles de Filtrado -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="position-relative" style="min-width:240px;">
                    <i class="fas fa-search position-absolute text-muted" style="left:12px; top:50%; transform:translateY(-50%); font-size:12px;"></i>
                    <input type="text" id="filtroTextoSol" onkeyup="filtrarTablaSolicitudes()" class="form-control form-control-sm ps-5 rounded-3" placeholder="Buscar colaborador o email...">
                </div>
                <select id="filtroEstadoSol" onchange="filtrarTablaSolicitudes()" class="form-select form-select-sm rounded-3 fw-semibold text-secondary" style="width:auto;">
                    <option value="">Todos los Estados</option>
                    <option value="pendiente" selected>Solo Pendientes (<?= (int)$pendientesCount ?>)</option>
                    <option value="aprobada">Aprobadas</option>
                    <option value="rechazada">Rechazadas</option>
                </select>
            </div>
        </div>

        <!-- Tabla Responsive -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaSolicitudesAcceso" style="font-size:13px;">
                <thead class="table-light">
                    <tr class="text-uppercase text-secondary" style="font-size:11px; letter-spacing:0.5px;">
                        <th class="py-3 px-4">Colaborador</th>
                        <th class="py-3 px-4">Motivo / Mensaje</th>
                        <th class="py-3 px-4">Fecha Solicitud</th>
                        <th class="py-3 px-4">Estado</th>
                        <th class="py-3 px-4">Área & Cargo Asignado</th>
                        <th class="py-3 px-4 text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                                No hay solicitudes de acceso registradas en la base de datos.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes as $s): 
                            $estado = $s['estado'] ?? 'pendiente';
                            $inicial = strtoupper(substr($s['usuario_nombre'] ?? 'U', 0, 1));
                            $textoFiltro = strtolower(($s['usuario_nombre'] ?? '') . ' ' . ($s['usuario_email'] ?? '') . ' ' . ($s['mensaje'] ?? ''));
                        ?>
                            <tr class="fila-solicitud" data-estado="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>" data-texto="<?= htmlspecialchars($textoFiltro, ENT_QUOTES, 'UTF-8') ?>">
                                <!-- Colaborador -->
                                <td class="py-3 px-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($s['avatar'])): ?>
                                            <img src="<?= htmlspecialchars($s['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:38px; height:38px; border-radius:10px; object-fit:cover; border:1px solid #e2e8f0;">
                                        <?php else: ?>
                                            <div style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:#ffffff; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(99,102,241,0.25);">
                                                <?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size:13px;">
                                                <?= htmlspecialchars($s['usuario_nombre'] ?? 'Sin nombre', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="text-secondary" style="font-size:11.5px;">
                                                <?= htmlspecialchars($s['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Mensaje / Justificación -->
                                <td class="py-3 px-4" style="max-width:260px;">
                                    <?php if (!empty($s['mensaje'])): ?>
                                        <div class="p-2 rounded-3 bg-light border text-secondary fst-italic" style="font-size:12px;">
                                            "<?= htmlspecialchars($s['mensaje'], ENT_QUOTES, 'UTF-8') ?>"
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:12px;">(Sin mensaje adjunto)</span>
                                    <?php endif; ?>

                                    <?php if (!empty($s['observaciones'])): ?>
                                        <div class="mt-1 text-primary" style="font-size:11px;">
                                            <strong>Nota Admin:</strong> <?= htmlspecialchars($s['observaciones'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Fecha -->
                                <td class="py-3 px-4 text-secondary" style="font-size:12px; white-space:nowrap;">
                                    <div><i class="far fa-calendar-alt me-1"></i><?= date('d/m/Y', strtotime($s['created_at'])) ?></div>
                                    <div class="text-muted" style="font-size:10.5px;"><?= date('h:i A', strtotime($s['created_at'])) ?></div>
                                </td>

                                <!-- Estado Badge -->
                                <td class="py-3 px-4">
                                    <?php if ($estado === 'pendiente'): ?>
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis px-3 py-2 fw-bold" style="font-size:11.5px;">
                                            <i class="fas fa-clock me-1"></i> Pendiente
                                        </span>
                                    <?php elseif ($estado === 'aprobada'): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2 fw-bold" style="font-size:11.5px;">
                                            <i class="fas fa-check-circle me-1"></i> Aprobada
                                        </span>
                                    <?php elseif ($estado === 'rechazada'): ?>
                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis px-3 py-2 fw-bold" style="font-size:11.5px;">
                                            <i class="fas fa-times-circle me-1"></i> Rechazada
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Puesto & Área Asignada -->
                                <td class="py-3 px-4">
                                    <?php if ($estado === 'aprobada' && !empty($s['cargo_nombre'])): ?>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="fw-bold text-dark" style="font-size:12.5px;">
                                                <?= htmlspecialchars($s['cargo_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span class="text-primary fw-semibold d-inline-flex align-items-center gap-1" style="font-size:11px;">
                                                <i class="fas fa-sitemap"></i>
                                                <?= htmlspecialchars($s['area_nombre'] ?? 'Área Corporativa', ENT_QUOTES, 'UTF-8') ?>
                                                <?php if (!empty($s['area_codigo'])): ?>
                                                    [<?= htmlspecialchars($s['area_codigo'], ENT_QUOTES, 'UTF-8') ?>]
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php elseif ($estado === 'pendiente'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1">
                                            <i class="fas fa-exclamation-triangle me-1"></i> Por asignar
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:12px;">Acceso denegado</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 px-4 text-end text-nowrap">
                                    <?php if ($estado === 'pendiente'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-success fw-bold px-3 rounded-3 shadow-sm"
                                                onclick="abrirModalAprobar(<?= (int)$s['id'] ?>, <?= (int)$s['usuario_id'] ?>, '<?= htmlspecialchars(addslashes($s['usuario_nombre']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($s['usuario_email']), ENT_QUOTES, 'UTF-8') ?>', <?= (int)($s['org_area_id'] ?? 0) ?>, <?= (int)($s['cargo_id'] ?? 0) ?>)">
                                            <i class="fas fa-user-check me-1"></i> Asignar Cargo & Área
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger rounded-3 ms-1"
                                                title="Rechazar Solicitud"
                                                onclick="abrirModalRechazar(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes($s['usuario_nombre']), ENT_QUOTES, 'UTF-8') ?>')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php elseif ($estado === 'aprobada'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary fw-semibold px-3 rounded-3"
                                                onclick="abrirModalAprobar(<?= (int)$s['id'] ?>, <?= (int)$s['usuario_id'] ?>, '<?= htmlspecialchars(addslashes($s['usuario_nombre']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($s['usuario_email']), ENT_QUOTES, 'UTF-8') ?>', <?= (int)($s['org_area_id'] ?? 0) ?>, <?= (int)($s['cargo_id'] ?? 0) ?>)">
                                            <i class="fas fa-sync-alt text-primary me-1"></i> Reasignar
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic" style="font-size:12px;">Archivada</span>
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
<!-- MODAL BOOTSTRAP 5: APROBAR Y ASIGNAR PUESTO & ÁREA                       -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalAprobarSolicitud" tabindex="-1" aria-labelledby="modalAprobarLabel" aria-hidden="true" style="z-index:10500;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header text-white px-4 py-3" style="background:linear-gradient(135deg, #059669 0%, #047857 100%);">
                <div>
                    <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="modalAprobarLabel">
                        <i class="fas fa-id-card-alt"></i> Asignar Puesto & Habilitar Acceso
                    </h5>
                    <p class="mb-0 opacity-75" style="font-size:12px;">
                        Configura el Área y Cargo oficial en el Organigrama de la empresa
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarModalAprobar()" aria-label="Cerrar"></button>
            </div>

            <form id="formAprobarSolicitud" onsubmit="enviarAprobacion(event)" class="p-4">
                <!-- Token CSRF Obligatorio -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="solicitud_id" id="modalSolId" value="">
                <input type="hidden" name="usuario_id" id="modalUserId" value="">

                <!-- Ficha del Colaborador -->
                <div class="p-3 bg-light rounded-3 border d-flex align-items-center gap-3 mb-3">
                    <div style="width:40px; height:40px; border-radius:10px; background:#10b981; color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:17px;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <div id="modalUserName" class="fw-bold text-dark" style="font-size:13.5px;">Cargando usuario...</div>
                        <div id="modalUserEmail" class="text-secondary" style="font-size:12px;">email@ejemplo.com</div>
                    </div>
                </div>

                <!-- 1. Área Organizacional Obligatoria -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark mb-1" style="font-size:12.5px;">
                        <i class="fas fa-sitemap text-primary me-1"></i> 1. Área Organizacional *
                    </label>
                    <select id="modalAreaSelect" name="area_id" required onchange="filtrarCargosPorArea(this.value)" class="form-select rounded-3">
                        <option value="">-- Selecciona el Área de trabajo --</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= (int)$a['id'] ?>" data-codigo="<?= htmlspecialchars($a['codigo'], ENT_QUOTES, 'UTF-8') ?>" data-nivel="<?= (int)$a['nivel'] ?>">
                                <?= str_repeat('— ', (int)$a['nivel']) . htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?> [<?= htmlspecialchars($a['codigo'], ENT_QUOTES, 'UTF-8') ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted" style="font-size:11px;">
                        Determina la pertenencia funcional del usuario (ej. Proyectos, Licitaciones, Administración).
                    </div>
                </div>

                <!-- 2. Cargo Corporativo Obligatorio -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark mb-1" style="font-size:12.5px;">
                        <i class="fas fa-id-badge text-info me-1"></i> 2. Cargo / Rol Jerárquico *
                    </label>
                    <select id="modalCargoSelect" name="cargo_id" required onchange="sugerirRolSegunCargo(this)" class="form-select rounded-3">
                        <option value="">-- Selecciona el puesto oficial --</option>
                        <?php foreach ($cargos as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" 
                                    data-area-id="<?= (int)$c['area_id'] ?>" 
                                    data-nivel-jerarquico="<?= (int)$c['nivel_jerarquico'] ?>" 
                                    data-codigo="<?= htmlspecialchars($c['codigo'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?> [<?= htmlspecialchars($c['codigo'], ENT_QUOTES, 'UTF-8') ?>] · Nivel <?= (int)$c['nivel_jerarquico'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted" style="font-size:11px;">
                        Define el nivel de responsabilidad dentro del organigrama (Gerencia, Jefatura, Especialista o Asistente).
                    </div>
                </div>

                <!-- 3. Rol del Sistema -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark mb-1" style="font-size:12.5px;">
                        <i class="fas fa-user-lock text-warning me-1"></i> 3. Tipo de Acceso al Sistema *
                    </label>
                    <select id="modalRolSelect" name="rol" required class="form-select rounded-3">
                        <option value="usuario" selected>Colaborador / Operativo (Acceso acotado a su área)</option>
                        <option value="admin">Administrador Global (Acceso total al ERP)</option>
                    </select>
                    <div id="rolExplicacion" class="form-text fw-semibold text-success mt-1" style="font-size:11px;">
                        Jefaturas y Asistentes tendrán permisos estrictos acordes a su puesto y departamento.
                    </div>
                </div>

                <!-- 4. Observaciones -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary mb-1" style="font-size:12px;">
                        Observaciones o Mensaje de Bienvenida (Opcional)
                    </label>
                    <input type="text" name="observaciones" id="modalObservaciones" class="form-control rounded-3" placeholder="Ej: Aprobado para el equipo de licitaciones...">
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-3" onclick="cerrarModalAprobar()">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarAprobacion" class="btn btn-success fw-bold px-4 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fas fa-check"></i>
                        <span>Confirmar y Habilitar Acceso</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL BOOTSTRAP 5: RECHAZAR SOLICITUD DE ACCESO                          -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalRechazarSolicitud" tabindex="-1" aria-labelledby="modalRechazarLabel" aria-hidden="true" style="z-index:10500;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header bg-danger text-white px-4 py-3">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2" id="modalRechazarLabel">
                    <i class="fas fa-ban"></i> Rechazar Solicitud de Acceso
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarModalRechazar()" aria-label="Cerrar"></button>
            </div>

            <form id="formRechazarSolicitud" onsubmit="enviarRechazo(event)" class="p-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="solicitud_id" id="modalRechazoSolId" value="">

                <p class="text-secondary mb-3" style="font-size:13px;">
                    ¿Confirmas el rechazo de la solicitud para <strong id="modalRechazoUserName" class="text-dark">el usuario</strong>?
                </p>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary mb-1" style="font-size:12px;">
                        Motivo del rechazo (Opcional):
                    </label>
                    <textarea name="observaciones" id="modalRechazoObservaciones" class="form-control rounded-3" rows="3" placeholder="Indica el motivo para notificar al colaborador..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-3" onclick="cerrarModalRechazar()">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarRechazo" class="btn btn-danger fw-bold px-4 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="fas fa-times-circle"></i>
                        <span>Rechazar Solicitud</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT / AJAX REACTIVO CON SOPORTE BOOTSTRAP 5 & FALLBACK SEGURO     -->
<!-- ========================================================================= -->
<script>
// Catálogo local de cargos para filtrado dinámico según el área seleccionada
const todosLosCargos = <?= json_encode(array_map(function($c) {
    return [
        'id'               => (int)$c['id'],
        'nombre'           => $c['nombre'],
        'codigo'           => $c['codigo'],
        'area_id'          => (int)$c['area_id'],
        'nivel_jerarquico' => (int)$c['nivel_jerarquico'],
        'area_nombre'      => $c['area_nombre'] ?? ''
    ];
}, $cargos), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// Instancias de Modal con compatibilidad Bootstrap 5 o DOM Fallback
function toggleBootstrapModal(modalId, show) {
    const el = document.getElementById(modalId);
    if (!el) return;

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(el);
        if (show) {
            modalInstance.show();
        } else {
            modalInstance.hide();
        }
    } else {
        // Fallback en caso de que bootstrap.bundle no esté en alcance
        if (show) {
            el.classList.add('show');
            el.style.display = 'block';
            el.style.backgroundColor = 'rgba(15,23,42,0.6)';
            document.body.classList.add('modal-open');
        } else {
            el.classList.remove('show');
            el.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    }
}

// ── Abrir y Cerrar Modal de Aprobación ──
function abrirModalAprobar(solId, userId, userName, userEmail, areaPrevia = 0, cargoPrevio = 0) {
    document.getElementById('modalSolId').value = solId;
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('modalUserEmail').textContent = userEmail;
    document.getElementById('modalObservaciones').value = '';

    const areaSelect = document.getElementById('modalAreaSelect');
    const cargoSelect = document.getElementById('modalCargoSelect');

    areaSelect.value = areaPrevia || '';
    if (areaPrevia) {
        filtrarCargosPorArea(areaPrevia);
    } else {
        restaurarTodosLosCargos();
    }

    cargoSelect.value = cargoPrevio || '';
    if (cargoPrevio) {
        sugerirRolSegunCargo(cargoSelect);
    }

    toggleBootstrapModal('modalAprobarSolicitud', true);
}

function cerrarModalAprobar() {
    toggleBootstrapModal('modalAprobarSolicitud', false);
}

// ── Abrir y Cerrar Modal de Rechazo ──
function abrirModalRechazar(solId, userName) {
    document.getElementById('modalRechazoSolId').value = solId;
    document.getElementById('modalRechazoUserName').textContent = userName;
    document.getElementById('modalRechazoObservaciones').value = '';
    toggleBootstrapModal('modalRechazarSolicitud', true);
}

function cerrarModalRechazar() {
    toggleBootstrapModal('modalRechazarSolicitud', false);
}

// ── Filtro de Cargos en Cascada ──
function restaurarTodosLosCargos() {
    const sel = document.getElementById('modalCargoSelect');
    sel.innerHTML = '<option value="">-- Selecciona el puesto oficial --</option>';
    todosLosCargos.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.setAttribute('data-area-id', c.area_id);
        opt.setAttribute('data-nivel-jerarquico', c.nivel_jerarquico);
        opt.setAttribute('data-codigo', c.codigo);
        opt.textContent = `${c.nombre} [${c.codigo}] · Nivel ${c.nivel_jerarquico}`;
        sel.appendChild(opt);
    });
}

function filtrarCargosPorArea(areaId) {
    const sel = document.getElementById('modalCargoSelect');
    const cargoActual = sel.value;
    sel.innerHTML = '<option value="">-- Selecciona el puesto oficial --</option>';

    const filtrados = areaId ? todosLosCargos.filter(c => c.area_id == areaId) : todosLosCargos;
    filtrados.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.setAttribute('data-area-id', c.area_id);
        opt.setAttribute('data-nivel-jerarquico', c.nivel_jerarquico);
        opt.setAttribute('data-codigo', c.codigo);
        opt.textContent = `${c.nombre} [${c.codigo}] · Nivel ${c.nivel_jerarquico}`;
        sel.appendChild(opt);
    });

    if (cargoActual && filtrados.some(c => c.id == cargoActual)) {
        sel.value = cargoActual;
    }
}

function sugerirRolSegunCargo(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const nivelJerarquico = parseInt(opt.getAttribute('data-nivel-jerarquico') || '3');
    const areaId = opt.getAttribute('data-area-id');
    const rolSelect = document.getElementById('modalRolSelect');
    const explicacion = document.getElementById('rolExplicacion');

    // Auto-vincular área en cascada si no estaba seleccionada
    const areaSelect = document.getElementById('modalAreaSelect');
    if (areaId && areaSelect.value != areaId) {
        areaSelect.value = areaId;
    }

    if (nivelJerarquico <= 1) {
        rolSelect.value = 'admin';
        explicacion.textContent = 'Nivel 0 / 1 (Dirección / CEO): Privilegios globales de Administrador asignados.';
        explicacion.className = 'form-text fw-semibold text-primary mt-1';
    } else if (nivelJerarquico === 2) {
        rolSelect.value = 'usuario';
        explicacion.textContent = 'Nivel 2 (Gerencia de Área): Gestión completa de su división y áreas dependientes.';
        explicacion.className = 'form-text fw-semibold text-info mt-1';
    } else if (nivelJerarquico === 3) {
        rolSelect.value = 'usuario';
        explicacion.textContent = 'Nivel 3 (Jefatura de Área): Gestión operativa de su departamento.';
        explicacion.className = 'form-text fw-semibold text-warning mt-1';
    } else {
        rolSelect.value = 'usuario';
        explicacion.textContent = 'Nivel 4 (Asistente / Operativo): Acceso restringido exclusivamente a tareas de soporte.';
        explicacion.className = 'form-text fw-semibold text-muted mt-1';
    }
}

// ── Enviar Aprobación vía Fetch/AJAX con CSRF ──
async function enviarAprobacion(e) {
    e.preventDefault();
    const btn = document.getElementById('btnConfirmarAprobacion');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';

    const formData = new FormData(document.getElementById('formAprobarSolicitud'));

    try {
        const response = await fetch('<?= BASE_URL ?>/organizacion/aprobarSolicitud', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert('¡Éxito! ' + data.message);
            cerrarModalAprobar();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo procesar la aprobación.'));
        }
    } catch (err) {
        alert('Ocurrió un error en la comunicación con el servidor.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ── Enviar Rechazo vía Fetch/AJAX con CSRF ──
async function enviarRechazo(e) {
    e.preventDefault();
    const btn = document.getElementById('btnConfirmarRechazo');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';

    const formData = new FormData(document.getElementById('formRechazarSolicitud'));

    try {
        const response = await fetch('<?= BASE_URL ?>/organizacion/rechazarSolicitud', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert('Solicitud rechazada exitosamente.');
            cerrarModalRechazar();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo rechazar la solicitud.'));
        }
    } catch (err) {
        alert('Ocurrió un error al procesar el rechazo.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ── Filtrado en Tiempo Real de la Tabla ──
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
