<!-- Crear/Editar Oportunidad -->
<?php $item = $data['oportunidad'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar oportunidad' : 'Nueva oportunidad' ?></h2>
        <p><?= $item ? 'Modifica los datos de la oportunidad' : 'Registra una nueva oportunidad de negocio' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/oportunidades" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=oportunidades&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Nombre de la oportunidad</label>
                <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($item['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Cliente / Entidad</label>
                <select name="cliente_id" class="form-control">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($data['clientes'] ?? [] as $cl): ?>
                    <option value="<?= $cl['id'] ?>" <?= (($item['cliente_id'] ?? '') == $cl['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cl['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Origen de la oportunidad</label>
                <select name="origen" class="form-control" required>
                    <?php $origenes = ['licitacion_publica'=>'Licitación Pública', 'invitacion_privada'=>'Invitación / Privada', 'cliente_directo'=>'Cliente Directo', 'empresas_propias'=>'Empresas Propias', 'otras'=>'Otras']; ?>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($origenes as $val => $label): ?>
                    <option value="<?= $val ?>" <?= (($item['origen'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Línea de negocio</label>
                <select name="linea_negocio_id" class="form-control">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($data['lineas_negocio'] ?? [] as $ln): ?>
                    <option value="<?= $ln['id'] ?>" <?= (($item['linea_negocio_id'] ?? '') == $ln['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ln['nombre']) ?></option>
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
                <label>Monto estimado (S/)</label>
                <input type="number" step="0.01" name="monto_estimado" class="form-control" value="<?= $item['monto_estimado'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Fecha límite</label>
                <input type="date" name="fecha_limite" class="form-control" value="<?= $item['fecha_limite'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Prioridad</label>
                <select name="prioridad" class="form-control">
                    <option value="baja" <?= (($item['prioridad'] ?? '') === 'baja') ? 'selected' : '' ?>>Baja</option>
                    <option value="media" <?= (($item['prioridad'] ?? 'media') === 'media') ? 'selected' : '' ?>>Media</option>
                    <option value="alta" <?= (($item['prioridad'] ?? '') === 'alta') ? 'selected' : '' ?>>Alta</option>
                </select>
            </div>
            <?php if ($item): ?>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <?php foreach (['detectada','en_evaluacion','participar','no_participar','en_propuesta','adjudicada','no_adjudicada','cerrada'] as $est): ?>
                    <option value="<?= $est ?>" <?= ($item['estado'] === $est) ? 'selected' : '' ?>><?= getEstadoLabel($est) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group full-width">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
            </div>
            <?php if ($item): ?>
            <div class="form-group full-width">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control"><?= htmlspecialchars($item['observaciones'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar oportunidad' ?></button>
            <a href="<?= BASE_URL ?>/oportunidades" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



