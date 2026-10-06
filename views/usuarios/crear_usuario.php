<!-- Crear/Editar Usuario -->
<?php $item = $data['usuario'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
        <p><?= $item ? 'Modifica los datos del usuario' : 'Registra un nuevo usuario del sistema' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/usuarios" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=usuarios&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($item['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" class="form-control" required value="<?= htmlspecialchars($item['apellido'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($item['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="rol_id" class="form-control" required>
                    <option value="">-- Seleccionar rol --</option>
                    <?php foreach ($data['roles'] ?? [] as $rol): ?>
                    <option value="<?= $rol['id'] ?>" <?= (($item['rol_id'] ?? '') == $rol['id']) ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= $item ? 'Nueva contraseña (dejar vacío para mantener)' : 'Contraseña' ?></label>
                <input type="password" name="password" class="form-control" <?= $item ? '' : 'required' ?> placeholder="••••••••">
            </div>
            <?php if ($item): ?>
            <div class="form-group">
                <label>Estado</label>
                <select name="activo" class="form-control">
                    <option value="1" <?= $item['activo'] ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= !$item['activo'] ? 'selected' : '' ?>>Suspendido</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar usuario' ?></button>
            <a href="<?= BASE_URL ?>/usuarios" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



