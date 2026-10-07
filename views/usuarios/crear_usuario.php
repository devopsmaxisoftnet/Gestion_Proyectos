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
                <label>Rol principal</label>
                <select name="rol_id" class="form-control" required>
                    <option value="">-- Seleccionar rol --</option>
                    <?php 
                    // Evitar duplicados agrupando por nombre temporalmente
                    $rolesAgrupados = [];
                    foreach ($data['roles'] ?? [] as $rol) {
                        if (!isset($rolesAgrupados[$rol['nombre']])) {
                            $rolesAgrupados[$rol['nombre']] = $rol;
                        }
                    }
                    foreach ($rolesAgrupados as $rol): ?>
                    <option value="<?= $rol['id'] ?>" <?= (($item['rol_id'] ?? '') == $rol['id']) ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tipo de Usuario</label>
                <select name="tipo_usuario_id" class="form-control">
                    <option value="">-- Seleccionar tipo --</option>
                    <?php foreach ($data['tipos'] ?? [] as $tipo): ?>
                    <option value="<?= $tipo['id'] ?>" <?= (($item['tipo_usuario_id'] ?? '') == $tipo['id']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Área</label>
                <select name="area_id" class="form-control">
                    <option value="">-- Seleccionar área --</option>
                    <?php foreach ($data['areas'] ?? [] as $area): ?>
                    <option value="<?= $area['id'] ?>" <?= (($item['area_id'] ?? '') == $area['id']) ? 'selected' : '' ?>><?= htmlspecialchars($area['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar usuario' ?></button>
            <a href="<?= BASE_URL ?>/usuarios" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



