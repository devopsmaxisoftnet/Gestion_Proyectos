<!-- Crear/Editar Convocatoria -->
<?php $item = $data['convocatoria'] ?? null; ?>

<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar convocatoria' : 'Nueva convocatoria' ?></h2>
        <p><?= $item ? 'Modifica los datos de la convocatoria' : 'Registra una nueva convocatoria detectada' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/convocatorias" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=convocatorias&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Nombre / Objeto de la convocatoria</label>
                <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" placeholder="Ej: Construcción de vía asfaltada...">
            </div>
            
            <div class="form-group">
                <label>Entidad convocante</label>
                <input type="text" name="entidad" class="form-control" value="<?= htmlspecialchars($item['entidad'] ?? '') ?>" placeholder="Ej: Municipalidad de...">
            </div>
            
            <div class="form-group">
                <label>Cliente (si existe)</label>
                <select name="cliente_id" class="form-control">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($data['clientes'] ?? [] as $cl): ?>
                    <option value="<?= $cl['id'] ?>" <?= (($item['cliente_id'] ?? '') == $cl['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cl['razon_social']) ?></option>
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
                <label>Tipo de gestión</label>
                <input type="text" name="tipo_gestion" class="form-control" value="<?= htmlspecialchars($item['tipo_gestion'] ?? '') ?>" placeholder="Ej: Ejecución de obra">
            </div>
            
            <div class="form-group">
                <label>Monto referencial (S/)</label>
                <input type="number" step="0.01" name="monto_referencial" class="form-control" value="<?= $item['monto_referencial'] ?? '' ?>" placeholder="0.00">
            </div>
            
            <div class="form-group">
                <label>Fecha publicación</label>
                <input type="date" name="fecha_publicacion" class="form-control" value="<?= $item['fecha_publicacion'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label>Fecha límite oferta</label>
                <input type="date" name="fecha_limite_oferta" class="form-control" value="<?= $item['fecha_limite_oferta'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label>Plazo de ejecución</label>
                <input type="text" name="plazo_ejecucion" class="form-control" value="<?= htmlspecialchars($item['plazo_ejecucion'] ?? '') ?>" placeholder="Ej: 240 días calendario">
            </div>
            
            <div class="form-group">
                <label>Requisitos profesionales</label>
                <input type="text" name="requisitos_profesionales" class="form-control" value="<?= htmlspecialchars($item['requisitos_profesionales'] ?? '') ?>" placeholder="Ej: 8 profesionales">
            </div>
            
            <div class="form-group">
                <label>Departamento</label>
                <input type="text" name="departamento" class="form-control" value="<?= htmlspecialchars($item['departamento'] ?? '') ?>" placeholder="Ej: Ucayali">
            </div>
            
            <div class="form-group">
                <label>Fuente</label>
                <input type="text" name="fuente" class="form-control" value="<?= htmlspecialchars($item['fuente'] ?? 'SEACE') ?>">
            </div>
            
            <?php if ($item): ?>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="detectada" <?= ($item['estado'] === 'detectada') ? 'selected' : '' ?>>Detectada</option>
                    <option value="en_revision" <?= ($item['estado'] === 'en_revision') ? 'selected' : '' ?>>En revisión</option>
                    <option value="convertida" <?= ($item['estado'] === 'convertida') ? 'selected' : '' ?>>Convertida</option>
                    <option value="descartada" <?= ($item['estado'] === 'descartada') ? 'selected' : '' ?>>Descartada</option>
                </select>
            </div>
            
            <div class="form-group full-width">
                <label>Observaciones</label>
                <textarea name="observaciones" class="form-control"><?= htmlspecialchars($item['observaciones'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar convocatoria' ?>
            </button>
            <a href="<?= BASE_URL ?>/convocatorias" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>



