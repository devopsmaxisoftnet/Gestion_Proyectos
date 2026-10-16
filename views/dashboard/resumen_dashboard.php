<!-- =====================================================
     DASHBOARD / RESUMEN
     ===================================================== -->

<?php
// Extracción segura de valores numéricos para evitar advertencias de tipo
$valPortafolio = is_array($data['valor_portafolio'] ?? null) ? ($data['valor_portafolio']['total'] ?? 0) : ($data['valor_portafolio'] ?? 0);
$evalPortafolio = is_numeric($data['evaluacion_portafolio'] ?? null) ? (int)$data['evaluacion_portafolio'] : (is_array($data['evaluacion_portafolio'] ?? null) ? (int)($data['evaluacion_portafolio']['total'] ?? 0) : 0);
$opActivasTotal = is_array($data['oportunidades_activas'] ?? null) ? ($data['oportunidades_activas']['total'] ?? 0) : ($data['oportunidades_activas'] ?? 0);
$proxVencCount = is_countable($data['proximos_vencimientos'] ?? null) ? count($data['proximos_vencimientos']) : 0;
$licitTotal = is_array($data['licitaciones'] ?? null) ? ($data['licitaciones']['total'] ?? 0) : ($data['licitaciones'] ?? 0);
$licitPrep = is_array($data['licitaciones_preparacion'] ?? null) ? ($data['licitaciones_preparacion']['total'] ?? count($data['licitaciones_preparacion'])) : ($data['licitaciones_preparacion'] ?? 0);
$propTotal = is_array($data['propuestas'] ?? null) ? ($data['propuestas']['total'] ?? 0) : ($data['propuestas'] ?? 0);
$propMonto = is_array($data['propuestas'] ?? null) ? ($data['propuestas']['monto_total'] ?? 0) : 0;
$contTotal = is_array($data['contratos_vigentes'] ?? null) ? ($data['contratos_vigentes']['total'] ?? 0) : ($data['contratos_vigentes'] ?? 0);
$contMonto = is_array($data['contratos_vigentes'] ?? null) ? ($data['contratos_vigentes']['monto_total'] ?? 0) : 0;
$userRol = $_SESSION['user']['rol'] ?? 'usuario';
$esColaborador = !in_array($userRol, ['superadmin', 'admin']);
?>

<?php if ($esColaborador): ?>
<!-- Banner de Bienvenida y Accesos Rápidos del Colaborador -->
<div style="background: linear-gradient(135deg, #064e3b 0%, #047857 100%); color: #ffffff; border-radius: 16px; padding: 22px 28px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(4, 120, 87, 0.2); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
            <span style="background: #10b981; color: #ffffff; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 20px; text-transform: uppercase;">
                <i class="fas fa-check-circle"></i> Acceso Habilitado
            </span>
            <span style="font-size: 13px; opacity: 0.9;">Puesto Oficial: <strong><?= htmlspecialchars($_SESSION['user']['cargo_nombre'] ?? 'Colaborador') ?></strong></span>
        </div>
        <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff;">
            Área de <?= htmlspecialchars($_SESSION['user']['area_nombre'] ?? 'Operaciones') ?>
        </h2>
        <p style="margin: 6px 0 0; font-size: 13.5px; opacity: 0.85; max-width: 600px;">
            Bienvenido a tu portal de trabajo. Todos los módulos e instrumentos asignados a tu puesto corporativo se encuentran activos y disponibles para su operación.
        </p>
    </div>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <?php if (PermisosHelper::tieneAcceso('compras')): ?>
        <a href="<?= BASE_URL ?>/compras" class="btn" style="background: #ffffff; color: #065f46; font-weight: 700; padding: 11px 20px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <i class="fas fa-shopping-cart" style="color: #ea580c;"></i> Órdenes de Compra
        </a>
        <a href="<?= BASE_URL ?>/compras?tab=almacenes" class="btn" style="background: rgba(255,255,255,0.15); color: #ffffff; font-weight: 700; padding: 11px 20px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 8px; border: 1px solid rgba(255,255,255,0.3);">
            <i class="fas fa-warehouse"></i> Almacenes
        </a>
        <?php endif; ?>
        <?php if (PermisosHelper::tieneAcceso('proyectos')): ?>
        <a href="<?= BASE_URL ?>/proyectos" class="btn" style="background: #ffffff; color: #065f46; font-weight: 700; padding: 11px 20px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <i class="fas fa-project-diagram" style="color: #2563eb;"></i> Proyectos
        </a>
        <?php endif; ?>
        <?php if (PermisosHelper::tieneAcceso('convocatorias')): ?>
        <a href="<?= BASE_URL ?>/convocatorias" class="btn" style="background: #ffffff; color: #065f46; font-weight: 700; padding: 11px 20px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <i class="fas fa-search" style="color: #7c3aed;"></i> Convocatorias
        </a>
        <?php endif; ?>
        <?php if (PermisosHelper::tieneAcceso('asistencias')): ?>
        <a href="<?= BASE_URL ?>/asistencias" class="btn" style="background: #ffffff; color: #065f46; font-weight: 700; padding: 11px 20px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <i class="fas fa-user-clock" style="color: #059669;"></i> Tareo / RRHH
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Hero Banner -->
<div class="hero-banner">
    <span class="hero-badge"><i class="fas fa-gem" style="margin-right:6px"></i> Portafolio comercial</span>
    <h2 class="hero-title">Decisiones claras desde la oportunidad hasta el contrato.</h2>
    <p class="hero-subtitle">Centraliza convocatorias, responsables, propuestas y vencimientos antes de transferir al proyecto.</p>
    
    <div class="hero-stats">
        <div class="hero-stats-label">Valor de oportunidades</div>
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div class="hero-stats-value"><?= formatMoney($valPortafolio) ?></div>
            <button type="button" class="hero-btn-desglose" onclick="abrirModalDesglose('todas', 'Portafolio General de Oportunidades', '#6366f1', 'calculator')" title="Ver detalle de todos los precios del portafolio">
                <i class="fas fa-calculator"></i> Ver desglose
            </button>
        </div>
        <div class="hero-progress">
            <span class="hero-progress-label">Evaluación del portafolio</span>
            <span style="font-weight:700"><?= (int)$evalPortafolio ?>%</span>
        </div>
        <div class="hero-progress-bar" style="margin-top:6px">
            <div class="hero-progress-fill" style="width: <?= (int)$evalPortafolio ?>%"></div>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Oportunidades activas</span>
            <div class="stat-card-icon purple"><i class="fas fa-search"></i></div>
        </div>
        <div class="stat-card-value"><?= (int)$opActivasTotal ?></div>
        <div class="stat-card-detail"><?= (int)$proxVencCount ?> próximas a vencer</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Licitaciones</span>
            <div class="stat-card-icon teal"><i class="fas fa-wrench"></i></div>
        </div>
        <div class="stat-card-value"><?= (int)$licitTotal ?></div>
        <div class="stat-card-detail"><?= (int)$licitPrep ?> en preparación</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Propuestas presentadas</span>
            <div class="stat-card-icon rose"><i class="fas fa-paper-plane"></i></div>
        </div>
        <div class="stat-card-value"><?= (int)$propTotal ?></div>
        <div class="stat-card-detail"><?= formatMoney($propMonto) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Contratos vigentes</span>
            <div class="stat-card-icon amber"><i class="fas fa-file-signature"></i></div>
        </div>
        <div class="stat-card-value"><?= (int)$contTotal ?></div>
        <div class="stat-card-detail"><?= formatMoney($contMonto) ?></div>
    </div>
</div>

<!-- Cartera por línea de negocio -->
<div class="section-header">
    <div>
        <h3 class="section-title">Cartera por línea de negocio</h3>
        <p class="section-subtitle">Cómo se clasifica cada oportunidad.</p>
    </div>
    <a href="<?= BASE_URL ?>/oportunidades" class="section-action">
        Abrir bandeja <i class="fas fa-chevron-right"></i>
    </a>
</div>

<div class="linea-grid">
    <?php unset($linea); foreach ($data['cartera_lineas'] as $linea): ?>
    <div class="linea-card">
        <div class="linea-card-header">
            <div class="linea-card-icon" style="background: <?= $linea['color'] ?>20; color: <?= $linea['color'] ?>">
                <i class="fas fa-<?= $linea['icono'] ?: 'briefcase' ?>"></i>
            </div>
            <span class="linea-card-count"><i class="fas fa-layer-group" style="margin-right:4px"></i> <?= $linea['num_oportunidades'] ?> oportunidades</span>
        </div>
        <h3><?= htmlspecialchars($linea['nombre']) ?></h3>
        <p><?= htmlspecialchars($linea['descripcion']) ?></p>
        
        <div class="linea-card-value">
            <label>Valor estimado</label>
            <div class="linea-card-monto"><?= formatMoney($linea['valor_estimado']) ?></div>
        </div>
        
        <div class="linea-card-items-wrap">
            <?php if (!empty($linea['oportunidades'])): ?>
                <?php foreach ($linea['oportunidades'] as $op): ?>
                <div class="linea-card-item">
                    <div class="linea-card-item-left">
                        <span class="linea-card-item-name" title="<?= htmlspecialchars($op['nombre']) ?>"><?= htmlspecialchars(mb_substr($op['nombre'], 0, 32)) ?>...</span>
                        <span class="linea-card-item-code"><?= $op['codigo'] ?></span>
                    </div>
                    <span class="badge <?= getEstadoClass($op['estado']) ?>"><?= getEstadoLabel($op['estado']) ?></span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="linea-card-empty-placeholder">
                    <i class="fas fa-folder-open"></i>
                    <span>Sin oportunidades activas registradas</span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="linea-card-footer-action">
            <button type="button" class="btn-linea-ver-todos" onclick="abrirModalDesglose(<?= (int)$linea['id'] ?>, '<?= htmlspecialchars(addslashes($linea['nombre'])) ?>', '<?= $linea['color'] ?>', '<?= $linea['icono'] ?: 'briefcase' ?>')">
                <i class="fas fa-calculator"></i> Ver desglose de precios (<?= (int)$linea['num_oportunidades'] ?>)
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Oportunidades prioritarias + Flujo comercial -->
<div class="data-section">
    <div class="data-card">
        <div class="data-card-header">
            <div class="section-header" style="margin-bottom:0">
                <div>
                    <h3 class="section-title">Oportunidades prioritarias</h3>
                    <p class="section-subtitle">Ordenadas por fecha límite</p>
                </div>
                <a href="<?= BASE_URL ?>/oportunidades" class="section-action">
                    Ver todas <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <table class="data-table" id="tabla-oportunidades-prioritarias">
            <thead>
                <tr>
                    <th>Oportunidad</th>
                    <th>Entidad</th>
                    <th>Línea</th>
                    <th>Monto</th>
                    <th>Fecha límite</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['oportunidades_prioritarias'] as $op): ?>
                <tr>
                    <td>
                        <span class="cell-main"><?= htmlspecialchars(mb_substr($op['nombre'], 0, 50)) ?></span>
                        <span class="cell-sub"><?= $op['codigo'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($op['entidad'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($op['linea'] ?? '-') ?></td>
                    <td class="cell-money"><?= formatMoney($op['monto_estimado']) ?></td>
                    <td><?= formatDate($op['fecha_limite']) ?></td>
                    <td><span class="badge <?= getEstadoClass($op['estado']) ?>"><?= getEstadoLabel($op['estado']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Flujo Comercial Widget -->
    <div class="flow-widget">
        <h3>Flujo comercial</h3>
        
        <?php
        $flujoRaw = is_array($data['flujo_comercial'] ?? null) ? $data['flujo_comercial'] : [];
        $flujoValores = [
            'detectadas'    => (int)($flujoRaw['detectadas'] ?? $flujoRaw['detectada'] ?? 0),
            'en_evaluacion' => (int)($flujoRaw['en_evaluacion'] ?? 0),
            'en_propuesta'  => (int)($flujoRaw['en_propuesta'] ?? 0),
            'adjudicadas'   => (int)($flujoRaw['adjudicadas'] ?? $flujoRaw['adjudicada'] ?? 0),
        ];
        $maxFlujo = (!empty($flujoValores) ? max(array_values($flujoValores)) : 0) ?: 1;
        $colors = ['purple', 'amber', 'teal', 'rose'];
        $labels = ['Detectadas', 'En evaluación', 'En propuesta', 'Adjudicadas'];
        $keys = ['detectadas', 'en_evaluacion', 'en_propuesta', 'adjudicadas'];
        ?>
        
        <?php foreach ($keys as $i => $key): 
            $val = $flujoValores[$key];
            $pct = round(($val / $maxFlujo) * 100);
        ?>
        <div class="flow-item">
            <span class="flow-item-label"><?= $labels[$i] ?></span>
            <div style="display:flex;align-items:center;gap:8px">
                <span class="flow-item-value"><?= $val ?></span>
                <div class="flow-item-bar">
                    <div class="flow-item-bar-fill <?= $colors[$i] ?>" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (count($data['proximos_vencimientos']) > 0): ?>
        <div class="vencimientos-alert">
            <div class="vencimientos-alert-icon">⏰</div>
            <h4><?= count($data['proximos_vencimientos']) ?> vencimientos</h4>
            <p>Requieren atención durante los próximos 10 días.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Desglose de Precios y Oportunidades -->
<?php include __DIR__ . '/modal_desglose_precios.php'; ?>




