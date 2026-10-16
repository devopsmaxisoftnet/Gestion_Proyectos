<?php
/**
 * Vista: Asignación de Cargos Corporativos y Áreas a Colaboradores
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$isAdmin = in_array($userRol, ['superadmin', 'admin']);

$usuarios = $data['usuarios'] ?? [];
$areas = $data['areas'] ?? [];
$cargos = $data['cargos'] ?? [];
$totalUsuarios = count($usuarios);

$asignadosCount = 0;
foreach ($usuarios as $u) {
    if (!empty($u['cargo_id'])) $asignadosCount++;
}
$sinAsignarCount = $totalUsuarios - $asignadosCount;
?>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <!-- Barra Superior Unificada del Módulo -->
    <?php
    $activeTab = 'roles';
    $orgNavTitle = 'Asignación de Cargos & Estructura';
    $orgNavSubtitle = 'Vincula a cada usuario con su puesto oficial en el organigrama y permisos del área';
    include __DIR__ . '/navbar_organizacion.php';
    ?>

    <!-- Barra de KPIs Compacta -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:20px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#dbeafe; color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div style="font-size:20px; font-weight:800; color:#0f172a; line-height:1;"><?= $totalUsuarios ?></div>
                <div style="font-size:11.5px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px;">Usuarios Registrados</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-id-card-alt"></i>
            </div>
            <div>
                <div style="font-size:20px; font-weight:800; color:#0f172a; line-height:1;"><?= $asignadosCount ?></div>
                <div style="font-size:11.5px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px;">Con Cargo Corporativo</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#fef2f2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-user-clock"></i>
            </div>
            <div>
                <div style="font-size:20px; font-weight:800; color:#0f172a; line-height:1;"><?= $sinAsignarCount ?></div>
                <div style="font-size:11.5px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px;">Pendientes de Asignar</div>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal: Filtros y Tabla de Asignación -->
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
        
        <!-- Barra de Búsqueda y Filtros -->
        <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:10px; flex:1; max-width:400px;">
                <div style="position:relative; width:100%;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input type="text" id="filtroTextoUser" onkeyup="filtrarTablaUsuarios()" placeholder="Buscar por nombre, email o cargo..." style="width:100%; padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; outline:none; box-sizing:border-box;">
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div>
                    <select id="filtroAreaUser" onchange="filtrarTablaUsuarios()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; outline:none; background:#ffffff;">
                        <option value="todas">Todas las áreas</option>
                        <option value="sin_area">-- Sin Área Asignada --</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla de Usuarios y Cargos -->
        <div style="overflow-x:auto;">
            <table id="tablaUsuariosEstructura" style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                        <th style="padding:12px 18px;">Colaborador</th>
                        <th style="padding:12px 14px;">Email / Cuenta</th>
                        <th style="padding:12px 14px;">Rol de Sistema</th>
                        <th style="padding:12px 14px;">Cargo Corporativo</th>
                        <th style="padding:12px 14px;">Área Asignada</th>
                        <?php if ($isAdmin): ?>
                        <th style="padding:12px 18px; text-align:right; width:150px;">Acción</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 6 : 5 ?>" style="padding:40px 20px; text-align:center; color:#94a3b8;">
                            <i class="fas fa-users-slash" style="font-size:32px; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                            No se encontraron colaboradores registrados en el sistema.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): 
                            $initials = strtoupper(substr($u['nombre'] ?? 'U', 0, 2));
                            $tieneCargo = !empty($u['cargo_id']);
                            $tieneArea = !empty($u['area_id']);
                            $areaColor = !empty($u['area_color']) ? $u['area_color'] : '#64748b';
                        ?>
                        <tr class="fila-usuario" data-area-id="<?= $u['area_id'] ?? '' ?>" style="border-bottom:1px solid #f1f5f9; transition:background 0.15s;" onmouseover="this.style.background='#fbfcfd'" onmouseout="this.style.background='transparent'">
                            <td style="padding:14px 18px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; flex-shrink:0;">
                                        <?= $initials ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; color:#1e293b; font-size:13.5px;">
                                            <?= htmlspecialchars($u['nombre']) ?>
                                        </div>
                                        <div style="font-size:11px; color:#94a3b8;">
                                            ID #<?= $u['id'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:14px 14px; color:#475569;">
                                <span style="font-size:12.5px;"><?= htmlspecialchars($u['email']) ?></span>
                            </td>
                            <td style="padding:14px 14px;">
                                <?php
                                $rolBadges = [
                                    'superadmin' => ['bg' => '#ede9fe', 'text' => '#6d28d9', 'label' => 'Superadmin'],
                                    'admin' => ['bg' => '#fef3c7', 'text' => '#b45309', 'label' => 'Administrador'],
                                    'usuario' => ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => 'Colaborador']
                                ];
                                $rB = $rolBadges[$u['rol']] ?? ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => ucfirst($u['rol'])];
                                ?>
                                <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:<?= $rB['bg'] ?>; color:<?= $rB['text'] ?>;">
                                    <?= $rB['label'] ?>
                                </span>
                            </td>
                            <td style="padding:14px 14px;">
                                <?php if ($tieneCargo): ?>
                                    <div style="display:inline-flex; align-items:center; gap:6px;">
                                        <span style="font-weight:700; color:#1e293b; font-size:12.5px;">
                                            <?= htmlspecialchars($u['cargo_nombre']) ?>
                                        </span>
                                        <span style="font-family:monospace; font-size:10.5px; background:#f1f5f9; color:#64748b; padding:2px 5px; border-radius:4px;">
                                            <?= htmlspecialchars($u['cargo_codigo']) ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#ef4444; font-size:11.5px; font-weight:600; display:inline-flex; align-items:center; gap:4px; background:#fef2f2; padding:3px 8px; border-radius:6px;">
                                        <i class="fas fa-exclamation-circle"></i> Sin Cargo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:14px 14px;">
                                <?php if ($tieneArea): ?>
                                    <span style="display:inline-flex; align-items:center; gap:5px; background:<?= $areaColor ?>15; color:<?= $areaColor ?>; border:1px solid <?= $areaColor ?>30; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700;">
                                        <i class="fas fa-building" style="font-size:10px;"></i> <?= htmlspecialchars($u['area_nombre']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:12px; font-style:italic;">— Sin Área</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td style="padding:14px 18px; text-align:right;">
                                <button onclick="abrirModalAsignarIndividual(<?= (int)$u['id'] ?>, '<?= addslashes($u['nombre']) ?>', <?= $u['cargo_id'] ? (int)$u['cargo_id'] : 'null' ?>, <?= $u['area_id'] ? (int)$u['area_id'] : 'null' ?>)" style="background:#ffffff; border:1.5px solid #cbd5e1; color:#334155; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all 0.15s;">
                                    <i class="fas fa-user-edit" style="color:#7c3aed;"></i> Asignar
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Unificado de Asignación de Cargo y Área -->
<?php include __DIR__ . '/modal_asignar_puesto.php'; ?>

<script>

function filtrarTablaUsuarios() {
    const q = (document.getElementById('filtroTextoUser').value || '').toLowerCase().trim();
    const area = document.getElementById('filtroAreaUser').value;
    const filas = document.querySelectorAll('#tablaUsuariosEstructura tbody tr.fila-usuario');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const filaArea = fila.getAttribute('data-area-id');
        
        const coincideTexto = !q || texto.includes(q);
        let coincideArea = true;
        if (area === 'sin_area') {
            coincideArea = !filaArea;
        } else if (area !== 'todas') {
            coincideArea = (filaArea === area);
        }
        
        fila.style.display = (coincideTexto && coincideArea) ? '' : 'none';
    });
}
</script>
