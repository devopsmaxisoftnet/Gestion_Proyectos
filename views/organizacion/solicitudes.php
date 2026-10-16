<?php
/**
 * Vista: Aprobación y Asignación de Solicitudes de Acceso
 * Gestión de Proyectos - ERP Core de Seguridad
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$isAdmin = in_array($userRol, ['superadmin', 'admin']);

$solicitudes = $data['solicitudes'] ?? [];
$areas = $data['areas'] ?? [];
$cargos = $data['cargos'] ?? [];
$totalSolicitudes = $data['totalSolicitudes'] ?? count($solicitudes);
$pendientesCount = $data['pendientesCount'] ?? 0;
$aprobadasCount = $data['aprobadasCount'] ?? 0;
$rechazadasCount = $data['rechazadasCount'] ?? 0;
?>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <!-- Barra Superior Unificada del Módulo -->
    <?php
    $activeTab = 'solicitudes';
    $orgNavTitle = 'Aprobación de Solicitudes & Roles';
    $orgNavSubtitle = 'Habilita nuevos colaboradores asignándoles su Área, Cargo y Rol jerárquico según el Organigrama';
    include __DIR__ . '/navbar_organizacion.php';
    ?>

    <!-- Barra de KPIs Compacta -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:20px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; line-height:1;"><?= $pendientesCount ?></div>
                <div style="font-size:11.5px; color:#d97706; font-weight:700; text-transform:uppercase; margin-top:4px;">Pendientes de Aprobación</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; line-height:1;"><?= $aprobadasCount ?></div>
                <div style="font-size:11.5px; color:#15803d; font-weight:700; text-transform:uppercase; margin-top:4px;">Aprobadas & Asignadas</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-ban"></i>
            </div>
            <div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; line-height:1;"><?= $rechazadasCount ?></div>
                <div style="font-size:11.5px; color:#b91c1c; font-weight:700; text-transform:uppercase; margin-top:4px;">Rechazadas</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#e0e7ff; color:#4338ca; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-inbox"></i>
            </div>
            <div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; line-height:1;"><?= $totalSolicitudes ?></div>
                <div style="font-size:11.5px; color:#4338ca; font-weight:700; text-transform:uppercase; margin-top:4px;">Total Solicitudes</div>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal: Filtros y Tabla de Solicitudes -->
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
        
        <!-- Barra de Búsqueda y Filtros -->
        <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:10px; flex:1; max-width:400px;">
                <div style="position:relative; width:100%;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input type="text" id="filtroTextoSol" onkeyup="filtrarTablaSolicitudes()" placeholder="Buscar por colaborador, email o mensaje..." style="width:100%; padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; outline:none; box-sizing:border-box;">
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <select id="filtroEstadoSol" onchange="filtrarTablaSolicitudes()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; outline:none; background:#ffffff;">
                    <option value="todos">Todos los Estados</option>
                    <option value="pendiente" selected>🟡 Solo Pendientes</option>
                    <option value="aprobada">🟢 Aprobadas</option>
                    <option value="rechazada">🔴 Rechazadas</option>
                </select>
            </div>
        </div>

        <!-- Tabla de Solicitudes de Acceso -->
        <div style="overflow-x:auto;">
            <table id="tablaSolicitudesAcceso" style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                        <th style="padding:12px 18px;">Colaborador</th>
                        <th style="padding:12px 14px;">Email</th>
                        <th style="padding:12px 14px;">Fecha Solicitud</th>
                        <th style="padding:12px 14px;">Mensaje / Referencia</th>
                        <th style="padding:12px 14px;">Estado</th>
                        <th style="padding:12px 14px;">Puesto Asignado</th>
                        <th style="padding:12px 18px; text-align:right; width:190px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="7" style="padding:40px 20px; text-align:center; color:#94a3b8;">
                            <i class="fas fa-inbox" style="font-size:32px; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                            No hay solicitudes de acceso registradas en el sistema.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes as $s): 
                            $initials = strtoupper(substr($s['usuario_nombre'] ?? 'C', 0, 2));
                            $isPendiente = ($s['estado'] === 'pendiente');
                            $isAprobada = ($s['estado'] === 'aprobada');
                            $isRechazada = ($s['estado'] === 'rechazada');

                            $estadoBadge = [
                                'pendiente' => ['bg' => '#fef3c7', 'text' => '#b45309', 'label' => 'Pendiente', 'icon' => 'fas fa-clock'],
                                'aprobada'  => ['bg' => '#dcfce7', 'text' => '#15803d', 'label' => 'Aprobada', 'icon' => 'fas fa-check-circle'],
                                'rechazada' => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'label' => 'Rechazada', 'icon' => 'fas fa-times-circle'],
                            ][$s['estado']] ?? ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => ucfirst($s['estado']), 'icon' => 'fas fa-info-circle'];
                        ?>
                        <tr class="fila-solicitud <?= $isPendiente ? 'pendiente-highlight' : '' ?>" data-estado="<?= $s['estado'] ?>" id="fila-sol-<?= $s['id'] ?>" style="border-bottom:1px solid #f1f5f9; transition:background 0.15s; <?= $isPendiente ? 'background:#fffbeb;' : '' ?>">
                            <td style="padding:14px 18px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg, #0284c7 0%, #0d9488 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; flex-shrink:0;">
                                        <?= $initials ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; color:#1e293b; font-size:13.5px;">
                                            <?= htmlspecialchars($s['usuario_nombre']) ?>
                                        </div>
                                        <div style="font-size:11px; color:#94a3b8;">
                                            Usuario #<?= (int)$s['usuario_id'] ?> · Solicitud #<?= (int)$s['id'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:14px 14px; color:#475569;">
                                <span style="font-size:12.5px;"><?= htmlspecialchars($s['usuario_email']) ?></span>
                            </td>
                            <td style="padding:14px 14px; color:#64748b; font-size:12px;">
                                <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?>
                            </td>
                            <td style="padding:14px 14px; color:#334155; font-size:12.5px; max-width:240px;">
                                <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($s['mensaje'] ?? '') ?>">
                                    <?= htmlspecialchars($s['mensaje'] ?: 'Solicitud de acceso al sistema') ?>
                                </div>
                            </td>
                            <td style="padding:14px 14px;">
                                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:700; background:<?= $estadoBadge['bg'] ?>; color:<?= $estadoBadge['text'] ?>;">
                                    <i class="<?= $estadoBadge['icon'] ?>" style="font-size:10px;"></i>
                                    <?= $estadoBadge['label'] ?>
                                </span>
                            </td>
                            <td style="padding:14px 14px;" id="puesto-sol-<?= $s['id'] ?>">
                                <?php if (!empty($s['cargo_nombre']) || !empty($s['cargo_sugerido'])): ?>
                                    <div style="font-weight:700; color:#1e293b; font-size:12px;">
                                        <?= htmlspecialchars($s['cargo_nombre'] ?? $s['cargo_sugerido']) ?>
                                    </div>
                                    <div style="font-size:11px; color:#64748b;">
                                        <?= htmlspecialchars($s['area_nombre'] ?? $s['area_sugerida'] ?? 'Área Corporativa') ?>
                                    </div>
                                <?php elseif ($isPendiente): ?>
                                    <span style="color:#d97706; font-size:11.5px; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fas fa-exclamation-triangle"></i> Pendiente Asignación
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:11.5px; font-style:italic;">— Sin puesto</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:14px 18px; text-align:right;">
                                <?php if ($isAdmin): ?>
                                    <?php if ($isPendiente): ?>
                                        <div style="display:inline-flex; gap:6px;">
                                            <button type="button" class="btn btn-sm" onclick="abrirModalAprobar(<?= (int)$s['id'] ?>, <?= (int)$s['usuario_id'] ?>, '<?= addslashes($s['usuario_nombre']) ?>', '<?= addslashes($s['usuario_email']) ?>')" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; border:none; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 2px 6px rgba(16,185,129,0.25);">
                                                <i class="fas fa-user-plus"></i> Aprobar
                                            </button>
                                            <button type="button" class="btn btn-sm" onclick="rechazarSolicitud(<?= (int)$s['id'] ?>)" style="background:#ffffff; border:1px solid #fecaca; color:#dc2626; padding:6px 10px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;" title="Rechazar solicitud">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm" onclick="abrirModalAprobar(<?= (int)$s['id'] ?>, <?= (int)$s['usuario_id'] ?>, '<?= addslashes($s['usuario_nombre']) ?>', '<?= addslashes($s['usuario_email']) ?>', <?= (int)($s['area_id'] ?? 0) ?>, <?= (int)($s['cargo_id'] ?? 0) ?>)" style="background:#ffffff; border:1px solid #cbd5e1; color:#475569; padding:5px 10px; border-radius:8px; font-size:11.5px; font-weight:600; cursor:pointer;">
                                            <i class="fas fa-edit"></i> Reasignar
                                        </button>
                                    <?php endif; ?>
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

<!-- MODAL: Asignación de Área, Cargo y Rol según Organigrama -->
<div id="modalAprobarSolicitud" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.65); backdrop-filter:blur(6px); z-index:10000; align-items:center; justify-content:center;">
    <div style="background:#ffffff; border-radius:20px; width:95%; max-width:580px; box-shadow:0 25px 60px rgba(0,0,0,0.25); overflow:hidden; animation:slideInUpFade 0.3s ease-out;">
        
        <!-- Header del Modal -->
        <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding:20px 24px; color:#ffffff; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.1);">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:12px; background:linear-gradient(135deg, #10b981 0%, #059669 100%); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:18px;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:800; letter-spacing:-0.2px;">Aprobar y Asignar Puesto Oficial</h3>
                    <span style="font-size:11.5px; color:#94a3b8;">Establece los privilegios y el área correspondiente en el ERP</span>
                </div>
            </div>
            <button onclick="cerrarModalAprobar()" style="background:transparent; border:none; color:#94a3b8; font-size:18px; cursor:pointer;" title="Cerrar ventana">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Formulario de Aprobación -->
        <form id="formAprobarSolicitud" onsubmit="enviarAprobacion(event)" style="padding:22px 26px;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" id="modalSolId" name="solicitud_id" value="">
            <input type="hidden" id="modalUserId" name="usuario_id" value="">

            <!-- Tarjeta Info Usuario -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 16px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <div id="modalUserName" style="font-size:14px; font-weight:800; color:#0f172a;">—</div>
                    <div id="modalUserEmail" style="font-size:12px; color:#64748b;">—</div>
                </div>
                <span style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; font-size:11px; font-weight:700; padding:3px 8px; border-radius:6px;">
                    <i class="fas fa-shield-alt"></i> Nuevo Acceso
                </span>
            </div>

            <!-- Selector 1: Área Organizacional -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                    <i class="fas fa-sitemap" style="color:#6366f1;"></i> 1. Área Organizacional *
                </label>
                <select id="modalAreaSelect" name="area_id" required onchange="filtrarCargosPorArea(this.value)" style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; color:#1e293b; background:#ffffff; outline:none; transition:border-color 0.2s;">
                    <option value="">-- Selecciona el Área Departamental --</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>" data-codigo="<?= $a['codigo'] ?>" data-nivel="<?= $a['nivel'] ?>">
                            <?= str_repeat('— ', (int)$a['nivel']) . htmlspecialchars($a['nombre']) ?> [<?= $a['codigo'] ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
                <span style="font-size:11px; color:#64748b; margin-top:4px; display:block;">
                    Determina el módulo raíz al que pertenecerá el colaborador (ej. Licitaciones, Finanzas, etc.).
                </span>
            </div>

            <!-- Selector 2: Cargo Corporativo -->
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                    <i class="fas fa-id-badge" style="color:#0ea5e9;"></i> 2. Cargo Corporativo Oficial *
                </label>
                <select id="modalCargoSelect" name="cargo_id" required onchange="sugerirRolSegunCargo(this)" style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; color:#1e293b; background:#ffffff; outline:none; transition:border-color 0.2s;">
                    <option value="">-- Selecciona el puesto oficial --</option>
                    <?php foreach ($cargos as $c): ?>
                        <option value="<?= $c['id'] ?>" data-area-id="<?= $c['area_id'] ?>" data-nivel-jerarquico="<?= $c['nivel_jerarquico'] ?>" data-codigo="<?= $c['codigo'] ?>">
                            <?= htmlspecialchars($c['nombre']) ?> [<?= $c['codigo'] ?>] · Nivel <?= (int)$c['nivel_jerarquico'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Selector 3: Nivel de Rol / Alcance de Seguridad -->
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                    <i class="fas fa-user-lock" style="color:#f59e0b;"></i> 3. Nivel de Autorización (Organigrama) *
                </label>
                <select id="modalRolSelect" name="rol" required style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; color:#1e293b; background:#ffffff; outline:none;">
                    <option value="usuario" selected>Nivel 2 / 3: Colaborador / Jefe de Área (Acceso estricto a su módulo)</option>
                    <option value="admin">Nivel 0 / 1: Gerencia / CEO (Acceso total o a módulos hijos)</option>
                </select>
                <span id="rolExplicacion" style="font-size:11px; color:#059669; font-weight:600; margin-top:4px; display:block;">
                    Jefes de Área y Asistentes solo tienen acceso operativo a su propio módulo asignado.
                </span>
            </div>

            <!-- Observaciones opcionales -->
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:5px;">
                    Observaciones o Nota de Bienvenida (Opcional)
                </label>
                <input type="text" name="observaciones" id="modalObservaciones" placeholder="Ej: Aprobado para el equipo de licitaciones de obra..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:12.5px; box-sizing:border-box;">
            </div>

            <!-- Botones de Acción -->
            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" onclick="cerrarModalAprobar()" style="padding:9px 18px; border-radius:10px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit" id="btnConfirmarAprobacion" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; border:none; padding:9px 22px; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 14px rgba(16,185,129,0.3);">
                    <i class="fas fa-check"></i>
                    <span>Confirmar y Habilitar Acceso</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Caché local de todos los cargos para filtrado dinámico
const todosLosCargos = <?= json_encode(array_map(function($c) {
    return [
        'id' => (int)$c['id'],
        'nombre' => $c['nombre'],
        'codigo' => $c['codigo'],
        'area_id' => (int)$c['area_id'],
        'nivel_jerarquico' => (int)$c['nivel_jerarquico'],
        'area_nombre' => $c['area_nombre'] ?? ''
    ];
}, $cargos)) ?>;

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

    document.getElementById('modalAprobarSolicitud').style.display = 'flex';
}

function cerrarModalAprobar() {
    document.getElementById('modalAprobarSolicitud').style.display = 'none';
}

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

    // Auto-completar área si no estaba seleccionada
    const areaSelect = document.getElementById('modalAreaSelect');
    if (areaId && areaSelect.value != areaId) {
        areaSelect.value = areaId;
    }

    if (nivelJerarquico <= 1) {
        rolSelect.value = 'admin';
        explicacion.textContent = 'Nivel 0 (CEO / Dirección): Acceso total a todo el sistema.';
        explicacion.style.color = '#7c3aed';
    } else if (nivelJerarquico === 2) {
        rolSelect.value = 'usuario';
        explicacion.textContent = 'Nivel 1 (Gerente): Tendrá acceso automático a todas las áreas hijas.';
        explicacion.style.color = '#0284c7';
    } else if (nivelJerarquico === 3) {
        rolSelect.value = 'usuario';
        explicacion.textContent = 'Nivel 2 (Jefe de Área): Acceso ESTRICTO solo a su módulo específico.';
        explicacion.style.color = '#d97706';
    } else {
        rolSelect.value = 'usuario';
        explicacion.textContent = 'Nivel 3 (Asistente / Operativo): Acceso limitado solo a tareas de su área.';
        explicacion.style.color = '#64748b';
    }
}

// ── Enviar Aprobación vía Fetch/AJAX Seguro ──
async function enviarAprobacion(e) {
    e.preventDefault();
    const btn = document.getElementById('btnConfirmarAprobacion');
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

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

// ── Rechazar Solicitud vía Fetch/AJAX Seguro ──
async function rechazarSolicitud(solId) {
    const obs = prompt('Ingresa el motivo del rechazo para informar al colaborador (opcional):');
    if (obs === null) return; // Cancelado

    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrfToken ?>');
    formData.append('solicitud_id', solId);
    formData.append('observaciones', obs);

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
            alert('Solicitud rechazada correctamente.');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo rechazar la solicitud.'));
        }
    } catch (err) {
        alert('Error en la comunicación con el servidor.');
    }
}

// ── Filtros en vivo de la tabla ──
function filtrarTablaSolicitudes() {
    const q = (document.getElementById('filtroTextoSol').value || '').toLowerCase().trim();
    const estado = document.getElementById('filtroEstadoSol').value;
    const filas = document.querySelectorAll('#tablaSolicitudesAcceso tbody tr.fila-solicitud');

    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const filaEstado = fila.getAttribute('data-estado');

        const coincideTexto = !q || texto.includes(q);
        const coincideEstado = (estado === 'todos') || (filaEstado === estado);

        fila.style.display = (coincideTexto && coincideEstado) ? '' : 'none';
    });
}

// Inicializar filtro según valor por defecto
document.addEventListener('DOMContentLoaded', filtrarTablaSolicitudes);
</script>

<!-- Modal Unificado de Asignación de Puesto -->
<?php include __DIR__ . '/modal_asignar_puesto.php'; ?>
