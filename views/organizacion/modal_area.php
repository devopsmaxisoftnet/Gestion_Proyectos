<?php
/**
 * Vista Formulario Standalone / Modal para Crear y Editar Áreas
 */
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$csrfToken = CsrfHelper::getToken();
$isEdit = !empty($area['id']);
$formAction = $isEdit ? BASE_URL . '/organizacion/editarArea/' . $area['id'] : BASE_URL . '/organizacion/crearArea';
?>

<div class="content-area animate__animated animate__fadeIn">
    <div style="max-width: 700px; margin: 20px auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0;">
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-sitemap' ?>" style="color: #6366f1;"></i>
                <?= $isEdit ? 'Editar Área: ' . htmlspecialchars($area['nombre']) : 'Nueva Área Organizacional' ?>
            </h2>
            <a href="<?= BASE_URL ?>/organizacion" class="btn-secondary" style="font-size: 13px; text-decoration: none; padding: 6px 14px; border-radius: 8px; border: 1px solid #cbd5e1; color: #475569;">
                <i class="fas fa-arrow-left"></i> Volver al Organigrama
            </a>
        </div>

        <form method="POST" action="<?= $formAction ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Nombre del Área *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($area['nombre'] ?? '') ?>" required placeholder="Ej: Tesorería y Finanzas" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Código Nemónico *</label>
                    <input type="text" name="codigo" value="<?= htmlspecialchars($area['codigo'] ?? '') ?>" required placeholder="Ej: TES" style="text-transform: uppercase; width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Área Superior (Padre)</label>
                <select name="parent_id" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box;">
                    <option value="">-- Ninguna (Área Raíz / Gerencia General) --</option>
                    <?php foreach ($areas as $af): ?>
                        <?php if ($isEdit && $af['id'] == $area['id']) continue; ?>
                        <option value="<?= $af['id'] ?>" <?= (($area['parent_id'] ?? null) == $af['id']) ? 'selected' : '' ?>>
                            <?= str_repeat('— ', (int)$af['nivel']) . htmlspecialchars($af['nombre']) ?> (<?= $af['codigo'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Icono FontAwesome</label>
                    <input type="text" name="icono" value="<?= htmlspecialchars($area['icono'] ?? 'fas fa-sitemap') ?>" placeholder="fas fa-building" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Color Identificador</label>
                    <input type="color" name="color" value="<?= htmlspecialchars($area['color'] ?? '#6366f1') ?>" style="width: 100%; height: 44px; padding: 2px; border: 1.5px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; cursor: pointer;">
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Descripción y Objetivos</label>
                <textarea name="descripcion" rows="3" placeholder="Funciones y alcance del departamento..." style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 14px; box-sizing: border-box;"><?= htmlspecialchars($area['descripcion'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                <a href="<?= BASE_URL ?>/organizacion" class="btn-org-secondary" style="padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; text-decoration: none; font-size: 14px; font-weight: 600; color: #475569;">Cancelar</a>
                <button type="submit" class="btn-org-primary" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: #ffffff; border: none; padding: 10px 22px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer;">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
