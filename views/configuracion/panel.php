<?php
$isIframeModal = isset($_GET['modal_iframe']) && $_GET['modal_iframe'] == 1;
$success = isset($_GET['success']) && $_GET['success'] == 1;
?>

<style>
/* Animaciones y estilos del panel de configuración */
@keyframes pulseSubtle {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

@keyframes driveFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-3px); }
}

.drive-hero-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 50%, #eff6ff 100%);
    border: 1.5px solid #bae6fd;
    border-radius: 14px;
    padding: 16px 18px;
    margin-bottom: 20px;
    box-shadow: 0 4px 14px rgba(14, 165, 233, 0.08);
    position: relative;
    overflow: hidden;
}

.drive-hero-card::after {
    content: '';
    position: absolute;
    top: -30px;
    right: -30px;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle, rgba(56, 189, 248, 0.2) 0%, transparent 70%);
    pointer-events: none;
}

.steps-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 14px;
}

.step-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    transition: all 0.25s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.step-card:hover {
    border-color: #38bdf8;
    transform: translateX(3px);
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.12);
}

.step-number {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #0284c7;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}

.step-content {
    flex: 1;
    font-size: 12px;
    color: #334155;
    line-height: 1.45;
}

.copy-badge-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    border: 1.5px solid #cbd5e1;
    color: #0f172a;
    padding: 3px 9px;
    border-radius: 6px;
    font-family: monospace;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    user-select: all;
    margin: 2px 0;
}

.copy-badge-btn:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
}

.editor-badge {
    display: inline-block;
    background: #fef3c7;
    color: #92400e;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 4px;
    font-size: 11px;
    border: 1px solid #fde68a;
}
</style>

<?php if (!$isIframeModal): ?>
<div class="page-header">
    <div>
        <h2>Configuración del Sistema</h2>
        <p>Datos corporativos de la empresa que opera el software y sincronización con Google Drive.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div style="background: #ecfdf5; border: 1.5px solid #a7f3d0; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #065f46; font-size: 13px; font-weight: 500; animation: pulseSubtle 0.5s ease;">
    <i class="fas fa-check-circle" style="color: #10b981; font-size: 16px;"></i>
    <span>¡Configuración guardada exitosamente! Los datos y sincronización de Google Drive han sido actualizados.</span>
</div>
<?php endif; ?>

<div class="form-card" style="max-width: 100%;">
    <form method="post" action="<?= BASE_URL ?>/configuracion/guardar<?= $isIframeModal ? '?modal_iframe=1' : '' ?>">
        
        <!-- Tarjeta Interactiva con Pasos para Añadir a DevOps como Editor -->
        <div class="drive-hero-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #ffffff; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.06); animation: driveFloat 3s ease-in-out infinite;">
                        <i class="fab fa-google-drive" style="color: #0284c7; font-size: 18px;"></i>
                    </div>
                    <div>
                        <strong style="color: #0c4a6e; font-size: 13.5px; display: block;">Pasos para Sincronizar tu Carpeta de Google Drive</strong>
                        <span style="font-size: 11px; color: #0369a1;">Sigue estos pasos para que el sistema cree carpetas y adjunte documentos automáticamente</span>
                    </div>
                </div>
                <?php if (!empty($empresaSoftware['drive_folder_id'])): ?>
                <a href="https://drive.google.com/drive/folders/<?= htmlspecialchars($empresaSoftware['drive_folder_id']) ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; color: #0284c7; text-decoration: none; background: #ffffff; padding: 5px 12px; border-radius: 8px; border: 1px solid #bae6fd; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.2s;" onmouseover="this.style.background='#0284c7';this.style.color='#ffffff';" onmouseout="this.style.background='#ffffff';this.style.color='#0284c7';">
                    <i class="fas fa-external-link-alt"></i> Abrir en Drive
                </a>
                <?php endif; ?>
            </div>

            <!-- Pasos Animados -->
            <div class="steps-container">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        Crea o abre en tu Google Drive la carpeta principal donde deseas almacenar los archivos del sistema comercial.
                    </div>
                </div>

                <div class="step-card" style="border-left: 3px solid #0284c7;">
                    <div class="step-number" style="background: #0369a1;"><i class="fas fa-user-plus" style="font-size: 9px;"></i></div>
                    <div class="step-content">
                        Haz clic derecho en la carpeta &gt; <strong>Compartir</strong> y añade a la cuenta de servicio del sistema:
                        <div style="margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="copy-badge-btn" onclick="copiarDevopsEmail(this)" title="Clic para copiar">
                                <i class="fas fa-copy" style="font-size: 11px; color: #0284c7;"></i>
                                <span id="devopsEmailText">devops.maxisoftnet@gmail.com</span>
                            </span>
                            <span>con rol asignado de <span class="editor-badge"><i class="fas fa-pen"></i> Editor</span></span>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        Copia el ID o enlace de la carpeta y pégalo abajo junto con <strong>tu correo propietario</strong>.
                    </div>
                </div>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group full-width">
                <label><i class="fab fa-google-drive" style="color: #0284c7;"></i> ID o Enlace de la Carpeta Drive Raíz</label>
                <input type="text" name="drive_folder_id" class="form-control" required
                       value="<?= htmlspecialchars($empresaSoftware['drive_folder_id'] ?? '') ?>" 
                       placeholder="Ej. 16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr o enlace completo de la carpeta">
                <small style="color: #64748b; font-size: 11px;">El sistema extraerá automáticamente el identificador limpio.</small>
            </div>

            <div class="form-group full-width">
                <label><i class="fas fa-user-check" style="color: #0284c7;"></i> Correo Propietario de la Carpeta (Tu Correo)</label>
                <input type="email" name="drive_email" class="form-control" required
                       value="<?= htmlspecialchars($empresaSoftware['drive_email'] ?? '') ?>" 
                       placeholder="tu-correo@gmail.com">
                <small style="color: #64748b; font-size: 11px;">Tu cuenta personal o corporativa dueña de la carpeta en Google Drive.</small>
            </div>

            <div style="grid-column: 1 / -1; margin: 12px 0 4px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 4px; font-size: 13px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-building" style="color: #6366f1;"></i> Datos de la Empresa (Uso del Sistema)
                </h4>
                <span style="font-size: 11.5px; color: #64748b;">Información corporativa de la entidad titular de esta plataforma comercial</span>
            </div>

            <div class="form-group full-width">
                <label>Razón Social / Nombre Comercial</label>
                <input type="text" name="razon_social" class="form-control" required 
                       value="<?= htmlspecialchars($empresaSoftware['razon_social'] ?? '') ?>" 
                       placeholder="Nombre de la empresa operadora">
            </div>

            <div class="form-group">
                <label>RUC</label>
                <input type="text" name="ruc" class="form-control" maxlength="20"
                       value="<?= htmlspecialchars($empresaSoftware['ruc'] ?? '') ?>" 
                       placeholder="20XXXXXXXXX">
            </div>

            <div class="form-group">
                <label>Teléfono / WhatsApp</label>
                <input type="text" name="telefono" class="form-control"
                       value="<?= htmlspecialchars($empresaSoftware['telefono'] ?? '') ?>" 
                       placeholder="Ej. 912345678">
            </div>

            <div class="form-group">
                <label>Representante Legal</label>
                <input type="text" name="representante" class="form-control"
                       value="<?= htmlspecialchars($empresaSoftware['representante'] ?? '') ?>" 
                       placeholder="Nombre y apellido">
            </div>

            <div class="form-group">
                <label>Contacto Administrativo</label>
                <input type="text" name="contacto_administrativo" class="form-control"
                       value="<?= htmlspecialchars($empresaSoftware['contacto_administrativo'] ?? '') ?>" 
                       placeholder="Nombre de contacto">
            </div>

            <div class="form-group full-width">
                <label>Correo Electrónico Corporativo</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($empresaSoftware['email'] ?? '') ?>" 
                       placeholder="contacto@empresa.com">
            </div>

            <div class="form-group full-width">
                <label>Dirección Fiscal / Oficina</label>
                <input type="text" name="direccion" class="form-control"
                       value="<?= htmlspecialchars($empresaSoftware['direccion'] ?? '') ?>" 
                       placeholder="Av. Principal 123, Lima">
            </div>
        </div>

        <div class="form-actions" style="margin-top: 18px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 8px 22px; font-weight: 600;">
                <i class="fas fa-save"></i> Guardar Configuración
            </button>
            <?php if (!$isIframeModal): ?>
            <a href="<?= BASE_URL ?>/dashboard" class="btn btn-secondary">Volver al Inicio</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function copiarDevopsEmail(btn) {
    const email = document.getElementById('devopsEmailText').innerText;
    navigator.clipboard.writeText(email).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check" style="color: #10b981;"></i> ¡Copiado!';
        btn.style.background = '#dcfce7';
        btn.style.borderColor = '#86efac';
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.style.background = '#f8fafc';
            btn.style.borderColor = '#cbd5e1';
        }, 1800);
    });
}
</script>
