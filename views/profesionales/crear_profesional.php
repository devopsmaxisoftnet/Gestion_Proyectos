<?php 
$prof = $data['profesional'] ?? null;
$fromEmpresa = $_GET['from_empresa'] ?? null;
?>
<div class="page-header">
    <h2><?= $prof ? 'Editar Profesional' : 'Añadir Profesional' ?></h2>
</div>

<div class="form-card" style="padding:0">
    <form action="<?= BASE_URL ?>/profesionales/<?= $prof ? 'editar/'.$prof['id'] : 'crear' ?>" method="POST" style="padding: 24px;">
        
        <?php if($fromEmpresa): ?>
        <input type="hidden" name="from_empresa" value="<?= htmlspecialchars($fromEmpresa) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Nombre Completo <span style="color:var(--rose-500)">*</span></label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($prof['nombre'] ?? '') ?>" required>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Tipo (Ej. Ingeniero, Técnico)</label>
                <input type="text" name="tipo_profesional" class="form-control" value="<?= htmlspecialchars($prof['tipo_profesional'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Especialidad</label>
                <input type="text" name="especialidad" class="form-control" value="<?= htmlspecialchars($prof['especialidad'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($prof['telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($prof['email'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group" style="margin-top: 10px;">
            <label>Vincular a Empresa</label>
            <select name="empresa_id" class="form-control">
                <option value="">-- Ninguna (Independiente) --</option>
                <?php foreach ($data['empresas'] ?? [] as $emp): ?>
                <option value="<?= $emp['id'] ?>" <?= (($prof['empresa_id'] ?? $fromEmpresa) == $emp['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($emp['razon_social']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-actions" style="margin-top:20px">
            <?php if(empty($_GET['ajax'])): ?>
            <a href="javascript:history.back()" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $prof ? 'Guardar Cambios' : 'Registrar Profesional' ?>
            </button>
        </div>
    </form>
</div>
