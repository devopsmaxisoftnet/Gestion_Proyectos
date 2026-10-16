<?php 
$prof = $data['profesional'] ?? null;
$fromEmpresa = $_GET['from_empresa'] ?? null;
$isModal = !empty($_GET['modal_iframe']);
?>

<style>
.prof-form-container {
    max-width: 820px;
    margin: 0 auto;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #0f172a;
}
.prof-section-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 16px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
}
.prof-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 1px dashed #e2e8f0;
}
.prof-section-title i {
    font-size: 13px;
    color: #6366f1;
}
.prof-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.prof-input-icon {
    position: absolute;
    left: 12px;
    color: #94a3b8;
    font-size: 13px;
    pointer-events: none;
    z-index: 2;
}
.prof-control {
    width: 100% !important;
    height: 40px !important;
    background: #f8fafc !important;
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 9px !important;
    padding: 8px 12px 8px 36px !important;
    font-size: 12.5px !important;
    color: #0f172a !important;
    font-family: inherit !important;
    outline: none !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
}
.prof-control:focus {
    background: #ffffff !important;
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12) !important;
}
select.prof-control {
    cursor: pointer !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    background-size: 13px !important;
    padding-right: 36px !important;
    -webkit-appearance: none !important;
    appearance: none !important;
}
.prof-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
@media (max-width: 640px) {
    .prof-grid-2 { grid-template-columns: 1fr; }
}
.prof-field-label {
    display: block;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 5px;
    letter-spacing: 0.1px;
}
.prof-req {
    color: #ef4444;
}
.btn-save-prof {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 9px !important;
    padding: 9px 22px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3) !important;
    cursor: pointer !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    transition: all 0.2s ease !important;
}
.btn-save-prof:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.4) !important;
}
</style>

<div class="prof-form-container">

    <!-- Hero Card de Cabecera -->
    <div style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border: 1.5px solid #ddd6fe; border-radius: 12px; padding: 14px 18px; margin-bottom: 16px; display:flex; align-items:center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #6366f1; color:#ffffff; display:flex; align-items:center; justify-content:center; font-size: 20px; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3); flex-shrink:0;">
            <i class="fas fa-user-shield"></i>
        </div>
        <div>
            <h3 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #1e1b4b;">
                <?= $prof ? 'Actualizar Ficha Profesional' : 'Registro de Profesional y Especialista' ?>
            </h3>
            <p style="margin: 2px 0 0 0; font-size: 11.5px; color: #5b21b6;">
                Acredite el perfil técnico, rol en licitaciones y asigne su especialidad del catálogo oficial.
            </p>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/profesionales/<?= $prof ? 'editar/'.$prof['id'] : 'crear' ?>" method="POST">
        
        <?php if($fromEmpresa): ?>
        <input type="hidden" name="from_empresa" value="<?= htmlspecialchars($fromEmpresa) ?>">
        <?php endif; ?>

        <!-- Sección 1: Datos de Identidad y Rol -->
        <div class="prof-section-card">
            <div class="prof-section-title">
                <i class="fas fa-id-card"></i> 1. Identificación y Rol Principal
            </div>
            
            <div class="prof-grid-2">
                <div>
                    <label class="prof-field-label">Nombre Completo y Grado Académico <span class="prof-req">*</span></label>
                    <div class="prof-input-wrap">
                        <i class="fas fa-user prof-input-icon"></i>
                        <input type="text" name="nombre" class="prof-control" value="<?= htmlspecialchars($prof['nombre'] ?? '') ?>" placeholder="Ej: Ing. Carlos Mendoza Benavides" required>
                    </div>
                </div>

                <div>
                    <label class="prof-field-label">Rol Profesional / Perfil Técnico</label>
                    <div class="prof-input-wrap">
                        <i class="fas fa-hard-hat prof-input-icon"></i>
                        <select name="tipo_profesional_id" class="prof-control">
                            <option value="">-- Seleccionar Rol Profesional --</option>
                            <?php foreach ($data['tipos'] ?? [] as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (($prof['tipo_profesional_id'] ?? '') == $t['id'] || ($prof['tipo_profesional'] ?? '') == $t['nombre']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 2: Especialidad Oficial del Catálogo -->
        <div class="prof-section-card" style="border-left: 4px solid #6366f1; background: #faf5ff;">
            <div class="prof-section-title" style="color:#5b21b6; border-color:#e9d5ff;">
                <i class="fas fa-certificate" style="color:#7c3aed;"></i> 2. Especialidad Técnica Acreditada (Catálogo Oficial)
            </div>

            <div>
                <label class="prof-field-label" style="color: #4c1d95;">
                    Seleccione Especialidad de la Base de Datos <span class="prof-req">*</span>
                </label>
                <div class="prof-input-wrap">
                    <i class="fas fa-award prof-input-icon" style="color:#7c3aed;"></i>
                    <select name="especialidad_id" class="prof-control" required style="border-color:#d8b4fe !important; background:#ffffff !important;">
                        <option value="">-- Seleccione una Especialidad Oficial (56 disponibles) --</option>
                        <?php foreach ($data['especialidades'] ?? [] as $esp): ?>
                        <option value="<?= $esp['id'] ?>" <?= (($prof['especialidad_id'] ?? '') == $esp['id'] || ($prof['especialidad'] ?? '') == $esp['nombre']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($esp['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-top: 6px; display:flex; align-items:center; gap:6px; font-size: 11px; color: #6b21a8;">
                    <i class="fas fa-info-circle"></i>
                    <span>Catálogo homologado para requisitos mínimos en licitaciones de obras, consultoría y supervisión.</span>
                </div>
            </div>
        </div>

        <!-- Sección 3: Contacto Directo -->
        <div class="prof-section-card">
            <div class="prof-section-title">
                <i class="fas fa-address-book"></i> 3. Información de Contacto Directo
            </div>

            <div class="prof-grid-2">
                <div>
                    <label class="prof-field-label">Teléfono Móvil / WhatsApp</label>
                    <div class="prof-input-wrap">
                        <i class="fas fa-phone-alt prof-input-icon"></i>
                        <input type="text" name="telefono" class="prof-control" value="<?= htmlspecialchars($prof['telefono'] ?? '') ?>" placeholder="Ej: 987 654 321">
                    </div>
                </div>

                <div>
                    <label class="prof-field-label">Correo Electrónico Institucional</label>
                    <div class="prof-input-wrap">
                        <i class="fas fa-envelope prof-input-icon"></i>
                        <input type="email" name="email" class="prof-control" value="<?= htmlspecialchars($prof['email'] ?? '') ?>" placeholder="Ej: carlos.mendoza@empresa.pe">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 4: Empresa y Estado Operativo -->
        <div class="prof-section-card">
            <div class="prof-section-title">
                <i class="fas fa-building"></i> 4. Empresa Asociada y Disponibilidad
            </div>

            <div class="prof-grid-2">
                <div>
                    <label class="prof-field-label">Empresa / Consorcio Asociado</label>
                    <div class="prof-input-wrap">
                        <i class="fas fa-briefcase prof-input-icon"></i>
                        <select name="empresa_id" class="prof-control">
                            <option value="">-- Ninguna (Consultor Independiente) --</option>
                            <?php foreach ($data['empresas'] ?? [] as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= (($prof['empresa_id'] ?? $fromEmpresa) == $emp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['razon_social']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="prof-field-label">Estado Operativo / Disponibilidad</label>
                    <div class="prof-input-wrap">
                        <i class="fas fa-toggle-on prof-input-icon"></i>
                        <select name="activo" class="prof-control">
                            <option value="1" <?= (($prof['activo'] ?? 1) == 1) ? 'selected' : '' ?>>🟢 Activo / Disponible para Procesos</option>
                            <option value="0" <?= (($prof['activo'] ?? 1) == 0) ? 'selected' : '' ?>>🟡 En Ejecución / Asignado a Proyecto</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección 5: Tarifas hora-hombre (costeo del tareo) -->
        <div class="prof-section-card">
            <div class="prof-section-title">
                <i class="fas fa-coins"></i> 5. Tarifa Hora-Hombre (costeo por actividad)
            </div>
            <div class="prof-grid-2">
                <div>
                    <label class="prof-field-label">Costo por hora normal (S/)</label>
                    <div class="prof-input-wrap">
                        <i class="fas fa-clock prof-input-icon"></i>
                        <input type="number" step="0.01" min="0" max="99999" name="costo_hora" id="tarifaNormal" class="prof-control" value="<?= htmlspecialchars(number_format((float)($prof['costo_hora'] ?? 25), 2, '.', '')) ?>">
                    </div>
                </div>
                <div>
                    <label class="prof-field-label">Costo por hora extra (S/)</label>
                    <div class="prof-input-wrap">
                        <i class="fas fa-business-time prof-input-icon"></i>
                        <input type="number" step="0.01" min="0" max="99999" name="costo_hora_extra" id="tarifaExtra" class="prof-control" value="<?= htmlspecialchars(number_format((float)($prof['costo_hora_extra'] ?? 35), 2, '.', '')) ?>">
                    </div>
                </div>
            </div>
            <p style="margin:10px 0 0; font-size:11.5px; color:#64748b;">
                <i class="fas fa-info-circle"></i> El tareo multiplica las horas por estas tarifas y suma el costo a la actividad. Un cambio de tarifa afecta solo a los tareos que se registren desde ahora; los anteriores conservan su costo.
            </p>
        </div>

        <!-- Barra Inferior de Acciones -->
        <div style="margin-top: 20px; display:flex; justify-content:flex-end; align-items:center; gap: 12px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <?php if (!$isModal): ?>
                <a href="<?= BASE_URL ?>/profesionales" class="btn btn-secondary" style="font-size:12.5px; padding:8px 16px;">
                    Cancelar
                </a>
            <?php else: ?>
                <button type="button" onclick="if(window.parent&&window.parent.cerrarGlobalModal){window.parent.cerrarGlobalModal();}else{window.history.back();}" class="btn btn-secondary" style="font-size:12.5px; padding:8px 16px; border:1px solid #cbd5e1;">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            <?php endif; ?>

            <button type="submit" class="btn-save-prof">
                <i class="fas fa-save"></i> <?= $prof ? 'Guardar Cambios' : 'Registrar Profesional' ?>
            </button>
        </div>

    </form>
</div>
