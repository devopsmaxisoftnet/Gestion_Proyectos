<?php
/**
 * Vista de Delegaciones Temporales de Permisos
 * Diseño Unificado, Moderno y Altamente Animado
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$delegaciones = $data['delegaciones'] ?? [];
$usuarios = $data['usuarios'] ?? [];
$areas = $data['areas'] ?? [];
$catalogo = $data['catalogoPermisos'] ?? [];
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$isAdmin = in_array($userRol, ['superadmin', 'admin']);

// Calcular estadísticas rápidas
$totalActivas = 0;
$ahora = time();
foreach ($delegaciones as $d) {
    if ($d['activo'] == 1 && strtotime($d['fecha_fin']) >= $ahora) {
        $totalActivas++;
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
/* ─── Variables y Animaciones ─── */
:root {
    --del-primary: #06b6d4;
    --del-primary-dark: #0891b2;
    --del-border: #e2e8f0;
}

@keyframes slideInUpFade {
    0% { opacity: 0; transform: translateY(16px); }
    100% { opacity: 1; transform: translateY(0); }
}

.del-dashboard-wrapper {
    padding: 16px 24px 40px;
    max-width: 100%;
    margin: 0 auto;
    box-sizing: border-box;
    animation: slideInUpFade 0.4s ease-out;
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
    border: 1px solid var(--del-border);
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
    background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
    color: #0891b2;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 10px rgba(8, 145, 178, 0.15);
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

/* ─── Mode Switch Tabs ─── */
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
    color: #0891b2;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* ─── Botones de Acción ─── */
.btn-del-brand {
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    color: #ffffff;
    border: none;
    padding: 8px 18px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.25);
    transition: all 0.2s;
}

.btn-del-brand:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(8, 145, 178, 0.35);
}

/* ─── KPI Pills Bar ─── */
.del-kpi-bar {
    display: flex;
    gap: 14px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.del-kpi-pill {
    flex: 1;
    min-width: 170px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    transition: all 0.25s ease;
}

.del-kpi-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}

.del-kpi-pill-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.del-kpi-pill-val {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.del-kpi-pill-lbl {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    margin-top: 3px;
}

/* ─── Tabla Estilizada ─── */
.del-table-card {
    background: #ffffff;
    border: 1px solid var(--del-border);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

.del-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.del-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.del-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13.5px;
    color: #1e293b;
    vertical-align: middle;
}

.del-table tr:hover td {
    background: #f8fafc;
}

.pill-status {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.status-active {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.status-expired {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

/* ─── Modal Overlay (Oculto por defecto con display: none) ─── */
.org-modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.org-modal-dialog {
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 540px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    border: 1px solid #e2e8f0;
    animation: slideInUpFade 0.25s ease-out;
    overflow: hidden;
}

.org-modal-header {
    padding: 18px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.org-modal-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}

.org-modal-close {
    background: transparent;
    border: none;
    font-size: 20px;
    color: #94a3b8;
    cursor: pointer;
    transition: color 0.2s;
}

.org-modal-close:hover {
    color: #ef4444;
}

.org-modal-body {
    padding: 22px;
    max-height: 75vh;
    overflow-y: auto;
}

.form-group-org {
    margin-bottom: 14px;
}

.form-group-org label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 5px;
}

.form-group-org input,
.form-group-org select,
.form-group-org textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 13.5px;
    color: #0f172a;
    box-sizing: border-box;
    transition: all 0.2s;
}

.form-group-org input:focus,
.form-group-org select:focus,
.form-group-org textarea:focus {
    border-color: var(--del-primary);
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.15);
    outline: none;
}

.org-modal-footer {
    padding: 14px 22px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<div class="del-dashboard-wrapper">
    <!-- Top Unified Navigation Bar -->
    <?php
    $activeTab = 'delegaciones';
    $orgNavTitle = 'Delegaciones Temporales de Permisos';
    $orgNavSubtitle = 'Otorgamiento temporal de facultades o aprobaciones por vacaciones o ausencias';
    include __DIR__ . '/navbar_organizacion.php';
    ?>

    <!-- KPI Compact Pills Bar -->
    <div class="del-kpi-bar">
        <div class="del-kpi-pill">
            <div class="del-kpi-pill-icon" style="background:#cffafe; color:#0891b2;">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <div class="del-kpi-pill-val"><?= $totalActivas ?></div>
                <div class="del-kpi-pill-lbl">Delegaciones Vigentes</div>
            </div>
        </div>

        <div class="del-kpi-pill">
            <div class="del-kpi-pill-icon" style="background:#ede9fe; color:#7c3aed;">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <div class="del-kpi-pill-val"><?= count($delegaciones) ?></div>
                <div class="del-kpi-pill-lbl">Historial Total</div>
            </div>
        </div>

        <div class="del-kpi-pill">
            <div class="del-kpi-pill-icon" style="background:#dcfce7; color:#15803d;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <div class="del-kpi-pill-val" style="font-size:15px; color:#15803d;">Auto-Expiración</div>
                <div class="del-kpi-pill-lbl">Control por Fecha y Hora</div>
            </div>
        </div>

        <div class="del-kpi-pill">
            <div class="del-kpi-pill-icon" style="background:#fef3c7; color:#d97706;">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <div class="del-kpi-pill-val" style="font-size:15px; color:#d97706;">Auditoría Activa</div>
                <div class="del-kpi-pill-lbl">Trazabilidad de Quién Otorgó</div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="del-table-card">
        <table class="del-table">
            <thead>
                <tr>
                    <th>Colaborador Beneficiario</th>
                    <th>Permiso Delegado</th>
                    <th>Ámbito / Área</th>
                    <th>Periodo de Vigencia</th>
                    <th>Otorgado Por / Justificación</th>
                    <th>Estado</th>
                    <?php if ($isAdmin): ?>
                    <th style="text-align: right;">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($delegaciones)): ?>
                    <?php foreach ($delegaciones as $d): ?>
                        <?php
                        $fin = strtotime($d['fecha_fin']);
                        $estaVigente = ($d['activo'] == 1 && $fin >= $ahora);
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($d['usuario_nombre']) ?></strong>
                                <br><small style="color:#64748b;"><?= htmlspecialchars($d['usuario_email']) ?></small>
                            </td>
                            <td>
                                <span style="background:#e0e7ff; color:#3730a3; padding:3px 8px; border-radius:6px; font-weight:700; font-size:12px;">
                                    <?= htmlspecialchars($d['modulo']) ?>.<?= htmlspecialchars($d['accion']) ?>
                                </span>
                                <br><small style="color:#64748b;"><?= htmlspecialchars($d['permiso_desc'] ?: '') ?></small>
                            </td>
                            <td>
                                <?= !empty($d['area_nombre']) ? htmlspecialchars($d['area_nombre']) : '<span style="color:#059669; font-weight:600;"><i class="fas fa-globe"></i> Todas las Áreas</span>' ?>
                            </td>
                            <td>
                                <i class="far fa-calendar-alt" style="color:#94a3b8;"></i> <?= date('d/m/Y H:i', strtotime($d['fecha_inicio'])) ?>
                                <br><strong style="color:<?= $estaVigente ? '#e11d48' : '#94a3b8' ?>;"><i class="fas fa-hourglass-end"></i> <?= date('d/m/Y H:i', $fin) ?></strong>
                            </td>
                            <td>
                                <span style="color:#334155; font-weight:600;"><?= htmlspecialchars($d['otorgado_por_nombre']) ?></span>
                                <br><small style="color:#64748b;"><?= htmlspecialchars($d['motivo'] ?: 'Sin justificación registrada') ?></small>
                            </td>
                            <td>
                                <?php if ($estaVigente): ?>
                                    <span class="pill-status status-active"><i class="fas fa-check-circle"></i> Vigente</span>
                                <?php else: ?>
                                    <span class="pill-status status-expired"><i class="fas fa-times-circle"></i> <?= $d['activo'] ? 'Expirado' : 'Revocado' ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td style="text-align: right;">
                                <?php if ($estaVigente): ?>
                                <form method="POST" action="<?= BASE_URL ?>/organizacion/revocarDelegacion/<?= $d['id'] ?>" style="display:inline;" onsubmit="return confirm('¿Deseas revocar esta delegación anticipadamente?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:6px 12px; border-radius:8px; font-weight:600; font-size:12px; cursor:pointer;" title="Revocar delegación">
                                        <i class="fas fa-ban"></i> Revocar
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:12px;">Finalizado</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 50px 20px; color:#64748b;">
                            <i class="fas fa-clock" style="font-size:36px; color:#cbd5e1; margin-bottom:12px; display:block;"></i>
                            No existen delegaciones temporales activas o registradas en el historial.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Delegar Permiso (Oculto por defecto con display: none) -->
<div class="org-modal-backdrop" id="modalDelBackdrop">
    <div class="org-modal-dialog">
        <div class="org-modal-header">
            <h3><i class="fas fa-clock" style="color:#0891b2;"></i> Otorgar Delegación Temporal</h3>
            <button class="org-modal-close" onclick="cerrarModalDelegacion()">&times;</button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/organizacion/crearDelegacion" style="margin:0;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="org-modal-body">
                <div class="form-group-org">
                    <label>Colaborador que Recibe el Permiso *</label>
                    <select name="usuario_id" required>
                        <option value="">-- Seleccionar Colaborador --</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['email']) ?>) 
                                <?= !empty($u['cargo_nombre']) ? '— ' . htmlspecialchars($u['cargo_nombre']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-org">
                    <label>Permiso a Delegar *</label>
                    <select name="permiso_id" required>
                        <option value="">-- Seleccionar Permiso --</option>
                        <?php foreach ($catalogo as $modulo => $permisos): ?>
                            <optgroup label="Módulo: <?= ucfirst($modulo) ?>">
                                <?php foreach ($permisos as $p): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= htmlspecialchars($modulo) ?> · <?= htmlspecialchars($p['accion']) ?> (<?= htmlspecialchars($p['descripcion'] ?: '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-org">
                    <label>Área Específica (Opcional, dejar vacío para global)</label>
                    <select name="area_id">
                        <option value="">-- Todas las áreas (Global) --</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= str_repeat('— ', (int)$a['nivel']) . htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:5px;">Fecha y Hora de Inicio *</label>
                        <input type="datetime-local" name="fecha_inicio" value="<?= date('Y-m-d\TH:i') ?>" required style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:#334155; margin-bottom:5px;">Fecha y Hora de Expiración *</label>
                        <input type="datetime-local" name="fecha_fin" value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>" required style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; box-sizing:border-box;">
                    </div>
                </div>

                <div class="form-group-org" style="margin-bottom:6px;">
                    <label>Motivo o Justificación de la Delegación</label>
                    <textarea name="motivo" rows="2" placeholder="Ej: Cubrir vacaciones del titular, comisión de servicios..."></textarea>
                </div>
            </div>
            <div class="org-modal-footer">
                <button type="button" onclick="cerrarModalDelegacion()" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:8px 16px; border-radius:9px; font-weight:600; font-size:13.5px; cursor:pointer;">Cancelar</button>
                <button type="submit" class="btn-del-brand"><i class="fas fa-check"></i> Activar Delegación</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalDelegacion() {
    document.getElementById('modalDelBackdrop').style.display = 'flex';
}
function cerrarModalDelegacion() {
    document.getElementById('modalDelBackdrop').style.display = 'none';
}
window.onclick = function(e) {
    if (e.target === document.getElementById('modalDelBackdrop')) {
        cerrarModalDelegacion();
    }
};
</script>

<!-- Modal Unificado de Asignación de Puesto -->
<?php include __DIR__ . '/modal_asignar_puesto.php'; ?>
