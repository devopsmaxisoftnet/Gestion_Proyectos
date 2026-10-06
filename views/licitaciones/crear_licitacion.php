<!-- Crear/Editar Licitación -->
<?php $item = $data['licitacion'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar licitación' : 'Nueva licitación' ?></h2>
        <p><?= $item ? 'Modifica los datos de la licitación' : 'Registra una nueva licitación' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/licitaciones" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=licitaciones&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Oportunidad vinculada</label>
                <select name="oportunidad_id" class="form-control" required>
                    <option value="">-- Seleccionar oportunidad --</option>
                    <?php foreach ($data['oportunidades'] ?? [] as $op): ?>
                    <option value="<?= $op['id'] ?>" <?= (($item['oportunidad_id'] ?? '') == $op['id']) ? 'selected' : '' ?>>
                        <?= $op['codigo'] ?> - <?= htmlspecialchars(mb_substr($op['nombre'], 0, 60)) ?>
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
                <label>Fecha de presentación</label>
                <input type="date" name="fecha_presentacion" class="form-control" value="<?= $item['fecha_presentacion'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Monto ofertado (S/)</label>
                <input type="number" step="0.01" name="monto_ofertado" class="form-control" value="<?= $item['monto_ofertado'] ?? '' ?>">
            </div>
            <?php if ($item): ?>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <?php foreach (['en_preparacion','revisada','presentada','en_evaluacion','ganada','perdida'] as $est): ?>
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
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar licitación' ?></button>
            <a href="<?= BASE_URL ?>/licitaciones" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



