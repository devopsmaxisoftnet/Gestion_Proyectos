<!-- Crear/Editar Contrato -->
<?php $item = $data['contrato'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar contrato' : 'Nuevo contrato' ?></h2>
        <p><?= $item ? 'Modifica los datos del contrato' : 'Registra un nuevo contrato' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/contratos" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=contratos&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Objeto del contrato</label>
                <input type="text" name="objeto" class="form-control" required value="<?= htmlspecialchars($item['objeto'] ?? '') ?>" placeholder="Descripción del contrato">
            </div>
            <div class="form-group">
                <label>Oportunidad vinculada</label>
                <select name="oportunidad_id" class="form-control">
                    <option value="">-- Ninguna --</option>
                    <?php foreach ($data['oportunidades'] ?? [] as $op): ?>
                    <option value="<?= $op['id'] ?>" <?= (($item['oportunidad_id'] ?? '') == $op['id']) ? 'selected' : '' ?>><?= $op['codigo'] ?> - <?= htmlspecialchars(mb_substr($op['nombre'], 0, 40)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Cliente</label>
                <select name="cliente_id" class="form-control">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($data['clientes'] ?? [] as $cl): ?>
                    <option value="<?= $cl['id'] ?>" <?= (($item['cliente_id'] ?? '') == $cl['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cl['razon_social']) ?></option>
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
                <label>Monto del contrato (S/)</label>
                <input type="number" step="0.01" name="monto_contrato" class="form-control" value="<?= $item['monto_contrato'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Tipo de contrato</label>
                <input type="text" name="tipo_contrato" class="form-control" value="<?= htmlspecialchars($item['tipo_contrato'] ?? '') ?>" placeholder="Ej: Ejecución de obra">
            </div>
            <div class="form-group">
                <label>Fecha de firma</label>
                <input type="date" name="fecha_firma" class="form-control" value="<?= $item['fecha_firma'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?= $item['fecha_inicio'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Fecha de fin</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?= $item['fecha_fin'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Plazo (días)</label>
                <input type="number" name="plazo_dias" class="form-control" value="<?= $item['plazo_dias'] ?? '' ?>">
            </div>
            <?php if ($item): ?>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <?php foreach (['en_elaboracion','firmado','en_ejecucion','finalizado','resuelto'] as $est): ?>
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
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar contrato' ?></button>
            <a href="<?= BASE_URL ?>/contratos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



