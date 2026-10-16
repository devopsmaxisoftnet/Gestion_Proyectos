<?php
/**
 * Vista: Catálogo y Lista Detallada de Áreas Organizacionales
 * Gestión de Proyectos - ERP Core
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$isAdmin = in_array($userRol, ['superadmin', 'admin']);

$areas = $data['areasFlat'] ?? [];
$totalAreas = $data['totalAreas'] ?? count($areas);
$totalCargos = $data['totalCargos'] ?? 0;
$totalUsuarios = $data['totalUsuarios'] ?? 0;

// Calcular nivel máximo
$maxNivel = 0;
foreach ($areas as $a) {
    if ((int)$a['nivel'] > $maxNivel) $maxNivel = (int)$a['nivel'];
}
?>

<div class="content-area animate__animated animate__fadeIn" style="padding: 16px 24px 40px;">

    <!-- Barra Superior Unificada del Módulo -->
    <?php
    $activeTab = 'areas';
    $orgNavTitle = 'Catálogo de Áreas Organizacionales';
    $orgNavSubtitle = 'Estructura departamental, jerarquías y centros de costo corporativos';
    include __DIR__ . '/navbar_organizacion.php';
    ?>

    <!-- Barra de KPIs Compacta -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:20px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#ede9fe; color:#7c3aed; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-sitemap"></i>
            </div>
            <div>
                <div style="font-size:20px; font-weight:800; color:#0f172a; line-height:1;"><?= $totalAreas ?></div>
                <div style="font-size:11.5px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px;">Áreas Registradas</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-briefcase"></i>
            </div>
            <div>
                <div style="font-size:20px; font-weight:800; color:#0f172a; line-height:1;"><?= $totalCargos ?></div>
                <div style="font-size:11.5px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px;">Cargos Vinculados</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div style="font-size:20px; font-weight:800; color:#0f172a; line-height:1;"><?= $totalUsuarios ?></div>
                <div style="font-size:11.5px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px;">Personal Activo</div>
            </div>
        </div>

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:18px;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <div style="font-size:20px; font-weight:800; color:#0f172a; line-height:1;"><?= $maxNivel + 1 ?> Niveles</div>
                <div style="font-size:11.5px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px;">Profundidad Jerárquica</div>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal: Filtros y Tabla -->
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
        
        <!-- Filtros Rápidos -->
        <div style="padding:14px 20px; border-bottom:1px solid #f1f5f9; background:#fafafa; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:10px; flex:1; max-width:400px;">
                <div style="position:relative; width:100%;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;"></i>
                    <input type="text" id="filtroTextoArea" onkeyup="filtrarTablaAreas()" placeholder="Buscar por nombre, código o función..." style="width:100%; padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; outline:none; box-sizing:border-box;">
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <label style="font-size:12px; font-weight:600; color:#64748b;">Nivel Jerárquico:</label>
                <select id="filtroNivelArea" onchange="filtrarTablaAreas()" style="padding:7px 12px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:12.5px; font-weight:600; color:#334155; outline:none; background:#ffffff;">
                    <option value="todos">Todos los niveles</option>
                    <option value="0">Nivel 0 · Dirección Ejecutiva</option>
                    <option value="1">Nivel 1 · Gerencias de Línea</option>
                    <option value="2">Nivel 2 · Departamentos y Jefaturas</option>
                    <option value="3">Nivel 3 · Unidades Operativas</option>
                </select>
            </div>
        </div>

        <!-- Tabla de Áreas -->
        <div style="overflow-x:auto;">
            <table id="tablaAreas" style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                        <th style="padding:12px 18px;">Área / Departamento</th>
                        <th style="padding:12px 14px; width:110px;">Código</th>
                        <th style="padding:12px 14px; width:150px;">Jerarquía</th>
                        <th style="padding:12px 14px;">Depende de (Padre)</th>
                        <th style="padding:12px 14px; text-align:center; width:100px;">Cargos</th>
                        <th style="padding:12px 14px; text-align:center; width:120px;">Colaboradores</th>
                        <th style="padding:12px 14px; width:90px; text-align:center;">Estado</th>
                        <?php if ($isAdmin): ?>
                        <th style="padding:12px 18px; text-align:right; width:120px;">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($areas)): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 8 : 7 ?>" style="padding:40px 20px; text-align:center; color:#94a3b8;">
                            <i class="fas fa-sitemap" style="font-size:32px; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                            No se encontraron áreas organizacionales registradas.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($areas as $area): 
                            $nivel = (int)$area['nivel'];
                            $color = !empty($area['color']) ? $area['color'] : '#6366f1';
                            $icono = !empty($area['icono']) ? $area['icono'] : 'fas fa-sitemap';
                            
                            $nivelInfo = [
                                0 => ['label' => 'Dirección', 'bg' => '#f5f3ff', 'text' => '#6d28d9', 'border' => '#ddd6fe'],
                                1 => ['label' => 'Gerencia', 'bg' => '#eff6ff', 'text' => '#1d4ed8', 'border' => '#bfdbfe'],
                                2 => ['label' => 'Jefatura / Depto', 'bg' => '#f0fdf4', 'text' => '#15803d', 'border' => '#bbf7d0'],
                                3 => ['label' => 'Unidad Operativa', 'bg' => '#fff7ed', 'text' => '#c2410c', 'border' => '#fed7aa']
                            ];
                            $badge = $nivelInfo[$nivel] ?? ['label' => 'Nivel ' . $nivel, 'bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#e2e8f0'];
                        ?>
                        <tr class="fila-area" data-nivel="<?= $nivel ?>" style="border-bottom:1px solid #f1f5f9; transition:background 0.15s;" onmouseover="this.style.background='#fbfcfd'" onmouseout="this.style.background='transparent'">
                            <td style="padding:14px 18px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:36px; height:36px; border-radius:10px; background:<?= $color ?>18; color:<?= $color ?>; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; border:1px solid <?= $color ?>30;">
                                        <i class="<?= htmlspecialchars($icono) ?>"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; color:#1e293b; font-size:13.5px; display:flex; align-items:center; gap:6px;">
                                            <?= htmlspecialchars($area['nombre']) ?>
                                        </div>
                                        <?php if (!empty($area['descripcion'])): ?>
                                        <div style="font-size:11.5px; color:#64748b; margin-top:2px; max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?= htmlspecialchars($area['descripcion']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:14px 14px;">
                                <span style="font-family:monospace; font-weight:700; background:#f1f5f9; color:#334155; padding:4px 8px; border-radius:6px; font-size:11.5px; border:1px solid #e2e8f0;">
                                    <?= htmlspecialchars($area['codigo']) ?>
                                </span>
                            </td>
                            <td style="padding:14px 14px;">
                                <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:<?= $badge['bg'] ?>; color:<?= $badge['text'] ?>; border:1px solid <?= $badge['border'] ?>;">
                                    <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span>
                                    N<?= $nivel ?> · <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td style="padding:14px 14px;">
                                <?php if (!empty($area['padre_nombre'])): ?>
                                    <span style="color:#475569; font-weight:600; display:inline-flex; align-items:center; gap:5px;">
                                        <i class="fas fa-level-up-alt fa-rotate-90" style="color:#94a3b8; font-size:11px;"></i>
                                        <?= htmlspecialchars($area['padre_nombre']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-style:italic; font-size:12px;">— Raíz Corporativa</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:14px 14px; text-align:center;">
                                <a href="<?= BASE_URL ?>/organizacion/cargos?area_id=<?= $area['id'] ?>" style="display:inline-flex; align-items:center; gap:5px; background:#fffbeb; color:#b45309; border:1px solid #fef3c7; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700; text-decoration:none;" title="Ver cargos de esta área">
                                    <i class="fas fa-briefcase" style="font-size:10px;"></i> <?= (int)$area['total_cargos'] ?>
                                </a>
                            </td>
                            <td style="padding:14px 14px; text-align:center;">
                                <span style="display:inline-flex; align-items:center; gap:5px; background:#ecfdf5; color:#047857; border:1px solid #d1fae5; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700;">
                                    <i class="fas fa-user-check" style="font-size:10px;"></i> <?= (int)$area['total_usuarios'] ?>
                                </span>
                            </td>
                            <td style="padding:14px 14px; text-align:center;">
                                <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#10b981;">
                                    <i class="fas fa-circle" style="font-size:7px;"></i> Activa
                                </span>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td style="padding:14px 18px; text-align:right;">
                                <div style="display:inline-flex; gap:6px;">
                                    <button onclick="editarArea(<?= (int)$area['id'] ?>, '<?= addslashes($area['nombre']) ?>', '<?= addslashes($area['codigo']) ?>', '<?= addslashes($area['descripcion'] ?? '') ?>', <?= $area['parent_id'] ? (int)$area['parent_id'] : 'null' ?>, '<?= addslashes($area['icono'] ?? 'fas fa-sitemap') ?>', '<?= addslashes($area['color'] ?? '#6366f1') ?>', <?= (int)$area['orden'] ?>)" title="Editar Área" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:12px; transition:all 0.15s;">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <a href="<?= BASE_URL ?>/organizacion/eliminarArea/<?= $area['id'] ?>?csrf_token=<?= $csrfToken ?>" onclick="return confirm('¿Confirmas eliminar el área <?= htmlspecialchars(addslashes($area['nombre'])) ?>? No debe tener dependencias activas.')" title="Eliminar Área" style="background:#fef2f2; border:1px solid #fee2e2; color:#dc2626; width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-size:12px; transition:all 0.15s;">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
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

<!-- Modal para Crear / Editar Área Organizacional -->
<div id="modalArea" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#ffffff; border-radius:18px; width:95%; max-width:580px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden; animation:slideInUpFade 0.3s ease-out;">
        <div style="background:linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); padding:18px 24px; color:#ffffff; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:10px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-sitemap" id="modalAreaIconHeader"></i>
                </div>
                <h3 id="modalAreaTitulo" style="margin:0; font-size:16px; font-weight:700;">Nueva Área Organizacional</h3>
            </div>
            <button onclick="cerrarModalArea()" style="background:transparent; border:none; color:rgba(255,255,255,0.7); font-size:18px; cursor:pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formArea" method="POST" action="<?= BASE_URL ?>/organizacion/crearArea" style="padding:22px 24px;">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div style="display:grid; grid-template-columns:2fr 1fr; gap:14px; margin-bottom:14px;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Nombre del Área *</label>
                    <input type="text" name="nombre" id="areaNombre" required placeholder="Ej: Control de Calidad" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Código *</label>
                    <input type="text" name="codigo" id="areaCodigo" required placeholder="Ej: CC" style="text-transform:uppercase; width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box;">
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Área Superior (Padre)</label>
                <select name="parent_id" id="areaParentId" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box; background:#ffffff;">
                    <option value="">-- Ninguna (Área Raíz / Nivel 0) --</option>
                    <?php foreach ($areas as $af): ?>
                        <option value="<?= $af['id'] ?>">
                            <?= str_repeat('— ', (int)$af['nivel']) . htmlspecialchars($af['nombre']) ?> (<?= $af['codigo'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Icono FontAwesome</label>
                    <input type="text" name="icono" id="areaIcono" value="fas fa-sitemap" placeholder="fas fa-building" style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Color Identificador</label>
                    <input type="color" name="color" id="areaColor" value="#6366f1" style="width:100%; height:40px; padding:2px; border:1.5px solid #cbd5e1; border-radius:9px; box-sizing:border-box; cursor:pointer;">
                </div>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Descripción de Funciones</label>
                <textarea name="descripcion" id="areaDescripcion" rows="2" placeholder="Responsabilidades del departamento..." style="width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #f1f5f9;">
                <button type="button" onclick="cerrarModalArea()" style="padding:9px 16px; border-radius:9px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="background:linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); color:#ffffff; border:none; padding:9px 20px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer;">
                    <i class="fas fa-save"></i> Guardar Área
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCrearArea() {
    document.getElementById('modalAreaTitulo').textContent = 'Nueva Área Organizacional';
    document.getElementById('modalAreaIconHeader').className = 'fas fa-plus';
    document.getElementById('formArea').action = '<?= BASE_URL ?>/organizacion/crearArea';
    document.getElementById('areaNombre').value = '';
    document.getElementById('areaCodigo').value = '';
    document.getElementById('areaParentId').value = '';
    document.getElementById('areaIcono').value = 'fas fa-sitemap';
    document.getElementById('areaColor').value = '#6366f1';
    document.getElementById('areaDescripcion').value = '';
    
    const modal = document.getElementById('modalArea');
    modal.style.display = 'flex';
}

function editarArea(id, nombre, codigo, descripcion, parentId, icono, color, orden) {
    document.getElementById('modalAreaTitulo').textContent = 'Editar Área: ' + nombre;
    document.getElementById('modalAreaIconHeader').className = 'fas fa-edit';
    document.getElementById('formArea').action = '<?= BASE_URL ?>/organizacion/editarArea/' + id;
    document.getElementById('areaNombre').value = nombre;
    document.getElementById('areaCodigo').value = codigo;
    document.getElementById('areaParentId').value = parentId || '';
    document.getElementById('areaIcono').value = icono || 'fas fa-sitemap';
    document.getElementById('areaColor').value = color || '#6366f1';
    document.getElementById('areaDescripcion').value = descripcion || '';
    
    const modal = document.getElementById('modalArea');
    modal.style.display = 'flex';
}

function cerrarModalArea() {
    document.getElementById('modalArea').style.display = 'none';
}

function filtrarTablaAreas() {
    const q = (document.getElementById('filtroTextoArea').value || '').toLowerCase().trim();
    const nivel = document.getElementById('filtroNivelArea').value;
    const filas = document.querySelectorAll('#tablaAreas tbody tr.fila-area');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const filaNivel = fila.getAttribute('data-nivel');
        
        const coincideTexto = !q || texto.includes(q);
        const coincideNivel = (nivel === 'todos') || (filaNivel === nivel);
        
        fila.style.display = (coincideTexto && coincideNivel) ? '' : 'none';
    });
}
</script>

<!-- Modal Unificado de Asignación de Puesto -->
<?php include __DIR__ . '/modal_asignar_puesto.php'; ?>
