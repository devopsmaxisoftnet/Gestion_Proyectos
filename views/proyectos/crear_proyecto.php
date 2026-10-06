<!-- Crear/Editar Proyecto -->
<?php $item = $data['proyecto'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar proyecto' : 'Nuevo proyecto' ?></h2>
        <p><?= $item ? 'Modifica los datos del proyecto' : 'Registra un nuevo proyecto' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/proyectos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=proyectos&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Nombre del proyecto</label>
                <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($item['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contrato vinculado</label>
                <select name="contrato_id" class="form-control">
                    <option value="">-- Ninguno --</option>
                    <?php foreach ($data['contratos'] ?? [] as $ct): ?>
                    <option value="<?= $ct['id'] ?>" <?= (($item['contrato_id'] ?? '') == $ct['id']) ? 'selected' : '' ?>><?= $ct['codigo'] ?> - <?= htmlspecialchars(mb_substr($ct['objeto'] ?? $ct['codigo'], 0, 40)) ?></option>
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
                <label>Fecha de inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?= $item['fecha_inicio'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Fecha fin planificada</label>
                <input type="date" name="fecha_fin_planificada" class="form-control" value="<?= $item['fecha_fin_planificada'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Presupuesto (S/)</label>
                <input type="number" step="0.01" name="monto_presupuesto" class="form-control" value="<?= $item['monto_presupuesto'] ?? '' ?>">
            </div>
            <?php if ($item): ?>
            <div class="form-group">
                <label>Monto ejecutado (S/)</label>
                <input type="number" step="0.01" name="monto_ejecutado" class="form-control" value="<?= $item['monto_ejecutado'] ?? 0 ?>">
            </div>
            <div class="form-group">
                <label>Avance (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="avance_porcentaje" class="form-control" value="<?= $item['avance_porcentaje'] ?? 0 ?>">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <?php foreach (['planificacion','en_ejecucion','suspendido','finalizado','liquidado'] as $est): ?>
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
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar proyecto' ?></button>
            <a href="<?= BASE_URL ?>/proyectos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



