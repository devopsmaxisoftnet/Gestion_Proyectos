<!-- Detalle Oportunidad -->
<?php $item = $data['oportunidad']; ?>

<div class="page-header">
    <div>
        <h2>Detalle de oportunidad</h2>
        <p><?= $item['codigo'] ?></p>
    </div>
    <a href="<?= BASE_URL ?>/oportunidades" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="detail-card">
    <div class="detail-header">
        <h2><?= htmlspecialchars($item['nombre']) ?></h2>
        <span class="detail-code"><?= $item['codigo'] ?> · <?= htmlspecialchars($item['entidad'] ?? '-') ?></span>
    </div>
    <div class="detail-body">
        <div class="detail-grid">
            <div class="detail-field"><label>Línea</label><span><?= htmlspecialchars($item['linea'] ?? '-') ?></span></div>
            <div class="detail-field"><label>Monto estimado</label><span><?= formatMoney($item['monto_estimado']) ?></span></div>
            <div class="detail-field"><label>Fecha límite</label><span><?= formatDate($item['fecha_limite']) ?></span></div>
            <div class="detail-field"><label>Estado</label><span class="badge <?= getEstadoClass($item['estado']) ?>"><?= getEstadoLabel($item['estado']) ?></span></div>
            <div class="detail-field"><label>Prioridad</label><span><?= ucfirst($item['prioridad']) ?></span></div>
            <div class="detail-field"><label>Responsable</label><span><?= ($item['resp_nombre'] ?? '-') . ' ' . ($item['resp_apellido'] ?? '') ?></span></div>
        </div>
        <?php if ($item['descripcion']): ?>
        <div class="detail-field mt-2"><label>Descripción</label><span><?= nl2br(htmlspecialchars($item['descripcion'])) ?></span></div>
        <?php endif; ?>
    </div>
    <div class="detail-actions">
        <a href="<?= BASE_URL ?>/oportunidades/editar/<?= $item['id'] ?>" class="btn btn-primary"><i class="fas fa-pen"></i> Editar</a>
        <a href="<?= BASE_URL ?>/oportunidades/eliminar/<?= $item['id'] ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i> Eliminar</a>
    </div>
</div>



