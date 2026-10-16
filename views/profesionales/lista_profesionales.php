<!-- =====================================================
     PANEL DE PROFESIONALES Y ESPECIALISTAS
     ===================================================== -->
<?php
$profesionales = $data['profesionales'] ?? [];
$totalProfesionales = count($profesionales);
$activosCount = 0;
$especialidadesSet = [];
$empresasSet = [];

foreach ($profesionales as $p) {
    if (!empty($p['activo'])) $activosCount++;
    if (!empty($p['especialidad'])) $especialidadesSet[$p['especialidad']] = true;
    if (!empty($p['empresa_nombre'])) $empresasSet[$p['empresa_nombre']] = true;
}
$totalEspecialidades = count($especialidadesSet);
$totalEmpresas = count($empresasSet);
?>

<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h2>Staff de Profesionales y Especialistas</h2>
        <p>Directorio técnico de consultores, residentes y especialistas acreditados para licitaciones y proyectos.</p>
    </div>
    <div style="display:flex; align-items:center; gap: 12px;">
        <button type="button" class="btn btn-primary" onclick="abrirGlobalModal('Nuevo Profesional', '<?= BASE_URL ?>/profesionales/crear?modal_iframe=1', 'fas fa-user-plus', 'Registro en catálogo de profesionales y especialistas')" style="background:#4f46e5; border-color:#4f46e5;">
            <i class="fas fa-user-plus"></i> Añadir profesional
        </button>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="dashboard-kpis" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 22px;">
    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; color: #2563eb; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Profesionales</span>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2;"><?= $totalProfesionales ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #f5f3ff; color: #7c3aed; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-award"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Especialidades Cubiertas</span>
            <div style="font-size: 22px; font-weight: 800; color: #7c3aed; line-height: 1.2;"><?= $totalEspecialidades ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #ecfdf5; color: #059669; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Disponibles / Activos</span>
            <div style="font-size: 22px; font-weight: 800; color: #059669; line-height: 1.2;"><?= $activosCount ?></div>
        </div>
    </div>

    <div class="kpi-card" style="padding: 16px 20px; border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.03); display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #d97706; display:flex; align-items:center; justify-content:center; font-size: 19px;">
            <i class="fas fa-building"></i>
        </div>
        <div>
            <span style="font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase;">Empresas Vinculadas</span>
            <div style="font-size: 22px; font-weight: 800; color: #d97706; line-height: 1.2;"><?= $totalEmpresas ?></div>
        </div>
    </div>
</div>

<!-- Filtros y Búsqueda -->
<div style="display:flex; flex-wrap:wrap; gap: 12px; margin-bottom: 18px; align-items:center; background:#ffffff; padding:14px 18px; border-radius:12px; border:1px solid #e2e8f0;">
    <div class="search-bar" style="margin:0; flex:1; min-width:240px;">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar por nombre, especialidad, correo..." id="search-prof"
               value="<?= htmlspecialchars($data['filtros']['busqueda'] ?? '') ?>"
               onkeyup="if(event.key==='Enter') aplicarFiltrosProfesionales()">
    </div>

    <div style="min-width: 220px;">
        <select id="filter-especialidad" class="form-control" onchange="aplicarFiltrosProfesionales()" style="height:38px; font-size:12.5px;">
            <option value="">-- Todas las Especialidades (<?= count($data['especialidades'] ?? []) ?>) --</option>
            <?php foreach ($data['especialidades'] ?? [] as $esp): ?>
            <option value="<?= $esp['id'] ?>" <?= (($data['filtros']['especialidad_id'] ?? '') == $esp['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($esp['nombre']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="min-width: 200px;">
        <select id="filter-empresa" class="form-control" onchange="aplicarFiltrosProfesionales()" style="height:38px; font-size:12.5px;">
            <option value="">-- Todas las Empresas --</option>
            <?php foreach ($data['empresas'] ?? [] as $emp): ?>
            <option value="<?= $emp['id'] ?>" <?= (($data['filtros']['empresa_id'] ?? '') == $emp['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($emp['razon_social']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if(!empty($data['filtros']['busqueda']) || !empty($data['filtros']['especialidad_id']) || !empty($data['filtros']['empresa_id'])): ?>
        <a href="<?= BASE_URL ?>/profesionales" class="btn btn-secondary" style="font-size:12px; height:38px; display:inline-flex; align-items:center;">
            <i class="fas fa-times"></i> Limpiar
        </a>
    <?php endif; ?>
</div>

<script>
function aplicarFiltrosProfesionales() {
    const q = encodeURIComponent(document.getElementById('search-prof').value);
    const esp = encodeURIComponent(document.getElementById('filter-especialidad').value);
    const emp = encodeURIComponent(document.getElementById('filter-empresa').value);
    window.location = '<?= BASE_URL ?>/?page=profesionales&busqueda=' + q + '&especialidad_id=' + esp + '&empresa_id=' + emp;
}
</script>

<div class="data-card data-section full-width" style="border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0;">
    <table class="data-table" id="tabla-profesionales">
        <thead>
            <tr>
                <th>Profesional / Especialista</th>
                <th>Rol / Perfil</th>
                <th>Especialidad Técnica (Catálogo)</th>
                <th>Empresa / Consorcio</th>
                <th>Contacto</th>
                <th style="text-align:right;">Tarifa H-H</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($profesionales)): ?>
            <tr>
                <td colspan="8">
                    <div class="empty-state" style="padding: 40px 20px;">
                        <i class="fas fa-users-slash" style="font-size: 40px; color: #cbd5e1; margin-bottom: 12px;"></i>
                        <h3 style="font-size: 16px; color: #334155; margin-bottom: 6px;">No se encontraron profesionales</h3>
                        <p style="font-size: 13px; color: #64748b;">Intente ajustar los filtros de búsqueda o registre un nuevo profesional.</p>
                    </div>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($profesionales as $prof): 
                // Iniciales para el avatar
                $partesNombre = explode(' ', trim($prof['nombre']));
                $iniciales = '';
                foreach ($partesNombre as $pn) {
                    if (in_array(strtolower($pn), ['ing.', 'arq.', 'abg.', 'lic.', 'dr.', 'dra.', 'sr.', 'sra.'])) continue;
                    if (!empty($pn) && strlen($iniciales) < 2) {
                        $iniciales .= strtoupper(mb_substr($pn, 0, 1));
                    }
                }
                if (empty($iniciales)) $iniciales = 'PR';
            ?>
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap: 12px;">
                        <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: #ffffff; display:flex; align-items:center; justify-content:center; font-weight: 700; font-size: 13px; box-shadow: 0 2px 6px rgba(99, 102, 241, 0.25); flex-shrink:0;">
                            <?= $iniciales ?>
                        </div>
                        <div>
                            <span class="cell-main" style="font-weight: 700; color: #0f172a; font-size: 13px; display:block;">
                                <?= htmlspecialchars($prof['nombre']) ?>
                            </span>
                            <span style="font-size: 11px; color: #64748b;">ID: #<?= $prof['id'] ?></span>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="display:inline-flex; align-items:center; gap:4px; background:#f1f5f9; color:#334155; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:6px; border:1px solid #e2e8f0;">
                        <i class="fas fa-hard-hat" style="color:#64748b; font-size:10px;"></i> <?= htmlspecialchars($prof['tipo_profesional'] ?? 'Profesional') ?>
                    </span>
                </td>
                <td>
                    <span style="display:inline-flex; align-items:center; gap:5px; background:#f5f3ff; color:#6d28d9; font-size:11.5px; font-weight:600; padding:4px 10px; border-radius:8px; border:1px solid #ddd6fe;">
                        <i class="fas fa-certificate" style="font-size:10.5px; color:#8b5cf6;"></i>
                        <?= htmlspecialchars($prof['especialidad_catalogo'] ?? $prof['especialidad'] ?? '-') ?>
                    </span>
                </td>
                <td>
                    <?php if (!empty($prof['empresa_nombre'])): ?>
                        <span style="font-size: 12px; font-weight: 600; color: #1e293b; display:inline-flex; align-items:center; gap:5px;">
                            <i class="fas fa-building" style="color:#94a3b8; font-size:11px;"></i>
                            <?= htmlspecialchars(mb_substr($prof['empresa_nombre'], 0, 32)) ?>
                        </span>
                    <?php else: ?>
                        <span style="font-size: 11.5px; color: #94a3b8; font-style: italic;">Independiente</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex; flex-direction:column; gap:3px;">
                        <?php if(!empty($prof['telefono'])): ?>
                            <a href="tel:<?= htmlspecialchars($prof['telefono']) ?>" style="font-size:11.5px; color:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                <i class="fas fa-phone-alt" style="font-size:10px; color:#60a5fa;"></i> <?= htmlspecialchars($prof['telefono']) ?>
                            </a>
                        <?php endif; ?>
                        <?php if(!empty($prof['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($prof['email']) ?>" style="font-size:11.5px; color:#475569; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                <i class="fas fa-envelope" style="font-size:10px; color:#94a3b8;"></i> <?= htmlspecialchars($prof['email']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="text-align:right; white-space:nowrap;">
                    <strong style="color:#0f172a; font-size:12.5px;">S/ <?= number_format((float)($prof['costo_hora'] ?? 0), 2) ?></strong>
                    <div style="font-size:11px; color:#d97706;">extra S/ <?= number_format((float)($prof['costo_hora_extra'] ?? 0), 2) ?></div>
                </td>
                <td>
                    <?php if (($prof['activo'] ?? 1) == 1): ?>
                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#059669; background:#ecfdf5; padding:3px 8px; border-radius:12px; border:1px solid #a7f3d0;">
                            <span style="width:6px; height:6px; border-radius:50%; background:#10b981;"></span> Activo
                        </span>
                    <?php else: ?>
                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#64748b; background:#f1f5f9; padding:3px 8px; border-radius:12px; border:1px solid #e2e8f0;">
                            <span style="width:6px; height:6px; border-radius:50%; background:#94a3b8;"></span> En Proyecto
                        </span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <div class="table-actions" style="justify-content: flex-end; gap:6px;">
                        <button type="button" onclick="abrirGlobalModal('Editar Profesional', '<?= BASE_URL ?>/profesionales/editar/<?= $prof['id'] ?>?modal_iframe=1', 'fas fa-user-edit', 'Modificación de perfil y especialidad')" class="btn-icon" title="Editar Profesional" style="border:none; background:#f8fafc; width:30px; height:30px; border-radius:8px; cursor:pointer; color:#334155; display:inline-flex; align-items:center; justify-content:center;">
                            <i class="fas fa-pen" style="font-size:11px;"></i>
                        </button>
                        <a href="<?= BASE_URL ?>/profesionales/eliminar/<?= $prof['id'] ?>" class="btn-icon" style="color:#ef4444; background:#fef2f2; width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none;" onclick="return confirm('¿Está seguro de eliminar este profesional?');" title="Eliminar">
                            <i class="fas fa-trash" style="font-size:11px;"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
