<!-- =====================================================
     DASHBOARD / RESUMEN
     ===================================================== -->

<!-- Hero Banner -->
<div class="hero-banner">
    <span class="hero-badge"><i class="fas fa-gem" style="margin-right:6px"></i> Portafolio comercial</span>
    <h2 class="hero-title">Decisiones claras desde la oportunidad hasta el contrato.</h2>
    <p class="hero-subtitle">Centraliza convocatorias, responsables, propuestas y vencimientos antes de transferir al proyecto.</p>
    
    <div class="hero-stats">
        <div class="hero-stats-label">Valor de oportunidades</div>
        <div class="hero-stats-value"><?= formatMoney($data['valor_portafolio']['total'] ?? 0) ?></div>
        <div class="hero-progress">
            <span class="hero-progress-label">Evaluación del portafolio</span>
            <span style="font-weight:700"><?= $data['evaluacion_portafolio'] ?>%</span>
        </div>
        <div class="hero-progress-bar" style="margin-top:6px">
            <div class="hero-progress-fill" style="width: <?= $data['evaluacion_portafolio'] ?>%"></div>
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
        <div class="stat-card-value"><?= $data['oportunidades_activas']['total'] ?></div>
        <div class="stat-card-detail"><?= count($data['proximos_vencimientos']) ?> próximas a vencer</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Licitaciones</span>
            <div class="stat-card-icon teal"><i class="fas fa-wrench"></i></div>
        </div>
        <div class="stat-card-value"><?= $data['licitaciones']['total'] ?></div>
        <div class="stat-card-detail"><?= $data['licitaciones_preparacion'] ?> en preparación</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Propuestas presentadas</span>
            <div class="stat-card-icon rose"><i class="fas fa-paper-plane"></i></div>
        </div>
        <div class="stat-card-value"><?= $data['propuestas']['total'] ?></div>
        <div class="stat-card-detail"><?= formatMoney($data['propuestas']['monto_total'] ?? 0) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Contratos vigentes</span>
            <div class="stat-card-icon amber"><i class="fas fa-file-signature"></i></div>
        </div>
        <div class="stat-card-value"><?= $data['contratos_vigentes']['total'] ?></div>
        <div class="stat-card-detail"><?= formatMoney($data['contratos_vigentes']['monto_total'] ?? 0) ?></div>
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
    <?php foreach ($data['cartera_lineas'] as $linea): ?>
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
            <span><?= formatMoney($linea['valor_estimado']) ?></span>
        </div>
        <?php foreach ($linea['oportunidades'] as $op): ?>
        <div class="linea-card-item">
            <div>
                <span class="linea-card-item-name"><?= htmlspecialchars(mb_substr($op['nombre'], 0, 40)) ?>...</span>
                <span class="linea-card-item-code"><?= $op['codigo'] ?></span>
            </div>
            <span class="badge <?= getEstadoClass($op['estado']) ?>"><?= getEstadoLabel($op['estado']) ?></span>
        </div>
        <?php endforeach; ?>
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
        $maxFlujo = max(array_values($data['flujo_comercial'])) ?: 1;
        $colors = ['purple', 'amber', 'teal', 'rose'];
        $labels = ['Detectadas', 'En evaluación', 'En propuesta', 'Adjudicadas'];
        $keys = ['detectadas', 'en_evaluacion', 'en_propuesta', 'adjudicadas'];
        ?>
        
        <?php foreach ($keys as $i => $key): ?>
        <div class="flow-item">
            <span class="flow-item-label"><?= $labels[$i] ?></span>
            <div style="display:flex;align-items:center;gap:8px">
                <span class="flow-item-value"><?= $data['flujo_comercial'][$key] ?></span>
                <div class="flow-item-bar">
                    <div class="flow-item-bar-fill <?= $colors[$i] ?>" style="width: <?= ($data['flujo_comercial'][$key] / $maxFlujo) * 100 ?>%"></div>
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



