<!-- Crear/Editar Cliente -->
<?php $item = $data['cliente'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar cliente' : 'Nuevo cliente' ?></h2>
        <p><?= $item ? 'Modifica los datos del cliente' : 'Registra un nuevo cliente o entidad' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/clientes" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=clientes&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Razón social</label>
                <input type="text" name="razon_social" class="form-control" required value="<?= htmlspecialchars($item['razon_social'] ?? '') ?>" placeholder="Nombre de la entidad o empresa">
            </div>
            <div class="form-group">
                <label>RUC</label>
                <input type="text" name="ruc" class="form-control" value="<?= htmlspecialchars($item['ruc'] ?? '') ?>" placeholder="20XXXXXXXXX" maxlength="20">
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="publico" <?= (($item['tipo'] ?? '') === 'publico') ? 'selected' : '' ?>>Entidad pública</option>
                    <option value="privado" <?= (($item['tipo'] ?? '') === 'privado') ? 'selected' : '' ?>>Empresa privada</option>
                </select>
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
                <label>Departamento</label>
                <input type="text" name="departamento" class="form-control" value="<?= htmlspecialchars($item['departamento'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Provincia</label>
                <input type="text" name="provincia" class="form-control" value="<?= htmlspecialchars($item['provincia'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Distrito</label>
                <input type="text" name="distrito" class="form-control" value="<?= htmlspecialchars($item['distrito'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto - Nombre</label>
                <input type="text" name="contacto_nombre" class="form-control" value="<?= htmlspecialchars($item['contacto_nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto - Cargo</label>
                <input type="text" name="contacto_cargo" class="form-control" value="<?= htmlspecialchars($item['contacto_cargo'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto - Teléfono</label>
                <input type="text" name="contacto_telefono" class="form-control" value="<?= htmlspecialchars($item['contacto_telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto - Email</label>
                <input type="email" name="contacto_email" class="form-control" value="<?= htmlspecialchars($item['contacto_email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar cliente' ?></button>
            <a href="<?= BASE_URL ?>/clientes" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



