<!-- Detalle Convocatoria -->
<?php $item = $data['convocatoria']; ?>

<div class="page-header">
    <div>
        <h2>Detalle de convocatoria</h2>
        <p><?= $item['codigo'] ?></p>
    </div>
    <a href="<?= BASE_URL ?>/convocatorias" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="detail-card">
    <div class="detail-header">
        <h2><?= htmlspecialchars($item['nombre']) ?></h2>
        <span class="detail-code"><?= $item['codigo'] ?> · <?= htmlspecialchars($item['entidad'] ?? '-') ?></span>
    </div>
    <div class="detail-body">
        <div class="detail-grid">
            <div class="detail-field">
                <label>Línea de negocio</label>
                <span><?= htmlspecialchars($item['linea_negocio'] ?? '-') ?></span>
            </div>
            <div class="detail-field">
                <label>Tipo de gestión</label>
                <span><?= htmlspecialchars($item['tipo_gestion'] ?? '-') ?></span>
            </div>
            <div class="detail-field">
                <label>Monto referencial</label>
                <span><?= formatMoney($item['monto_referencial']) ?></span>
            </div>
            <div class="detail-field">
                <label>Fecha publicación</label>
                <span><?= formatDate($item['fecha_publicacion']) ?></span>
            </div>
            <div class="detail-field">
                <label>Fecha límite</label>
                <span><?= formatDate($item['fecha_limite_oferta']) ?></span>
            </div>
            <div class="detail-field">
                <label>Plazo ejecución</label>
                <span><?= htmlspecialchars($item['plazo_ejecucion'] ?? '-') ?></span>
            </div>
            <div class="detail-field">
                <label>Requisitos</label>
                <span><?= htmlspecialchars($item['requisitos_profesionales'] ?? '-') ?></span>
            </div>
            <div class="detail-field">
                <label>Departamento</label>
                <span><?= htmlspecialchars($item['departamento'] ?? '-') ?></span>
            </div>
            <div class="detail-field">
                <label>Estado</label>
                <span class="badge <?= getEstadoClass($item['estado']) ?>"><?= getEstadoLabel($item['estado']) ?></span>
            </div>
        </div>
    </div>
    <div class="detail-actions">
        <a href="<?= BASE_URL ?>/convocatorias/editar/<?= $item['id'] ?>" class="btn btn-primary"><i class="fas fa-pen"></i> Editar</a>
        <?php if ($item['estado'] === 'detectada'): ?>
        <a href="<?= BASE_URL ?>/convocatorias/evaluar/<?= $item['id'] ?>" class="btn btn-success"><i class="fas fa-check-circle"></i> Evaluar y convertir</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/convocatorias/eliminar/<?= $item['id'] ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i> Eliminar</a>
    </div>
</div>



