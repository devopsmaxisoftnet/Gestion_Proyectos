<!-- Crear/Editar Proveedor -->
<?php $item = $data['proveedor'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar proveedor' : 'Nuevo proveedor' ?></h2>
        <p><?= $item ? 'Modifica los datos del proveedor' : 'Registra un nuevo proveedor' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/proveedores" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=proveedores&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Razón social</label>
                <input type="text" name="razon_social" class="form-control" required value="<?= htmlspecialchars($item['razon_social'] ?? '') ?>" placeholder="Nombre de la empresa proveedora">
            </div>
            <div class="form-group">
                <label>RUC</label>
                <input type="text" name="ruc" class="form-control" value="<?= htmlspecialchars($item['ruc'] ?? '') ?>" placeholder="20XXXXXXXXX" maxlength="20">
            </div>
            <div class="form-group">
                <label>Especialidad</label>
                <input type="text" name="especialidad" class="form-control" value="<?= htmlspecialchars($item['especialidad'] ?? '') ?>" placeholder="Ej: Equipos y alquiler">
            </div>
            <div class="form-group full-width">
                <label>Dirección</label>
                <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($item['direccion'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($item['telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($item['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto principal</label>
                <input type="text" name="contacto_nombre" class="form-control" value="<?= htmlspecialchars($item['contacto_nombre'] ?? '') ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar proveedor' ?></button>
            <a href="<?= BASE_URL ?>/proveedores" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



