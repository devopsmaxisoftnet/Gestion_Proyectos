<!-- Crear/Editar Propuesta -->
<?php $item = $data['propuesta'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar propuesta' : 'Nueva propuesta' ?></h2>
        <p><?= $item ? 'Modifica los datos de la propuesta' : 'Registra una nueva propuesta' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/propuestas" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=propuestas&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Licitación vinculada</label>
                <select name="licitacion_id" class="form-control" required>
                    <option value="">-- Seleccionar licitación --</option>
                    <?php foreach ($data['licitaciones'] ?? [] as $lic): ?>
                    <option value="<?= $lic['id'] ?>" <?= (($item['licitacion_id'] ?? '') == $lic['id']) ? 'selected' : '' ?>>
                        <?= $lic['codigo'] ?> - <?= htmlspecialchars(mb_substr($lic['oportunidad_nombre'], 0, 50)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Responsable</label>
                <select name="responsable_id" class="form-control">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($data['usuarios'] ?? [] as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (($item['responsable_id'] ?? '') == $u['id']) ? 'selected' : '' ?>><?= $u['nombre'] . ' ' . $u['apellido'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Monto propuesta (S/)</label>
                <input type="number" step="0.01" name="monto_propuesta" class="form-control" value="<?= $item['monto_propuesta'] ?? '' ?>">
            </div>
            <?php if ($item): ?>
            <div class="form-group">
                <label>Fecha de envío</label>
                <input type="date" name="fecha_envio" class="form-control" value="<?= $item['fecha_envio'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <?php foreach (['borrador','en_revision','enviada','aceptada','rechazada'] as $est): ?>
                    <option value="<?= $est ?>" <?= ($item['estado'] === $est) ? 'selected' : '' ?>><?= getEstadoLabel($est) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full-width">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control"><?= htmlspecialchars($item['observaciones'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar propuesta' ?></button>
            <a href="<?= BASE_URL ?>/propuestas" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



