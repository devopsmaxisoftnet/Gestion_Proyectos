<!-- Crear/Editar Empresa -->
<?php 
$item = $data['empresa'] ?? null; 
$profesionales = $data['profesionales'] ?? [];
$isIframeModal = isset($_GET['modal_iframe']) && $_GET['modal_iframe'] == 1;
?>

<?php if (!$isIframeModal): ?>
<div class="page-header">
    <div>
        <h2><?= $item ? 'Editar empresa' : 'Nueva empresa' ?></h2>
        <p><?= $item ? 'Modifica los datos de la empresa y gestiona sus profesionales' : 'Registra una nueva empresa' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/empresas" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>
<?php endif; ?>

<div class="form-card">
    <form method="post" action="<?= BASE_URL ?>/?page=empresas&action=<?= $item ? 'editar&id='.$item['id'] : 'crear' ?><?= $isIframeModal ? '&modal_iframe=1' : '' ?>">
        <div class="form-grid">
            <div class="form-group full-width">
                <label>Razón social</label>
                <input type="text" name="razon_social" class="form-control" required value="<?= htmlspecialchars($item['razon_social'] ?? '') ?>" placeholder="Nombre de la empresa">
            </div>
            <div class="form-group">
                <label>RUC</label>
                <input type="text" name="ruc" class="form-control" value="<?= htmlspecialchars($item['ruc'] ?? '') ?>" placeholder="20XXXXXXXXX" maxlength="20">
            </div>
            <div class="form-group">
                <label>Tipo de Empresa</label>
                <select name="tipo_empresa" class="form-control" required>
                    <option value="externa" <?= ($item['tipo_empresa'] ?? '') === 'externa' ? 'selected' : '' ?>>Externa</option>
                    <option value="propia" <?= ($item['tipo_empresa'] ?? '') === 'propia' ? 'selected' : '' ?>>Propia</option>
                </select>
            </div>
            <div class="form-group">
                <label>Especialidad / Rubro</label>
                <select name="especialidad_id" class="form-control dynamic-select" data-tipo="especialidad">
                    <option value="">-- Seleccionar --</option>
                    <option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nueva especialidad...</option>
                    <?php if(!empty($data['cat_especialidades'])): ?>
                        <?php foreach($data['cat_especialidades'] as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($item && ($item['especialidad_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Número de Cuenta</label>
                <input type="text" name="num_cuenta" class="form-control" value="<?= htmlspecialchars($item['num_cuenta'] ?? '') ?>" placeholder="Cta. Bancaria / CCI">
            </div>
            <div class="form-group full-width">
                <label>Dirección</label>
                <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($item['direccion'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Representante Legal</label>
                <input type="text" name="representante" class="form-control" value="<?= htmlspecialchars($item['representante'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto Administrativo</label>
                <input type="text" name="contacto_administrativo" class="form-control" value="<?= htmlspecialchars($item['contacto_administrativo'] ?? '') ?>">
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
                <label><i class="fab fa-google-drive" style="color: #4285F4;"></i> ID Carpeta Google Drive</label>
                <input type="text" name="drive_folder_id" class="form-control" value="<?= htmlspecialchars($item['drive_folder_id'] ?? '') ?>" placeholder="Ej. 16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr o enlace">
            </div>
            <div class="form-group">
                <label><i class="fab fa-google-drive" style="color: #4285F4;"></i> Correo Propietario (Drive)</label>
                <input type="email" name="drive_email" class="form-control" value="<?= htmlspecialchars($item['drive_email'] ?? '') ?>" placeholder="ejemplo@gmail.com">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $item ? 'Guardar cambios' : 'Registrar empresa' ?></button>
            <?php if (!$isIframeModal): ?>
            <a href="<?= BASE_URL ?>/empresas" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($item): ?>
<!-- SECCIÓN DE PROFESIONALES -->
<div class="data-card mt-4" style="margin-top: 24px; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
    <h3 style="font-size: 15px; margin-bottom: 16px; color: #0f172a;"><i class="fas fa-users" style="color: #6366f1;"></i> Profesionales de la empresa</h3>
    
    <table class="data-table" style="margin-bottom: 16px;">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Especialidad</th>
                <th>Tel/Email</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($profesionales)): ?>
            <tr><td colspan="5"><div class="empty-state" style="padding: 10px;">No hay profesionales registrados.</div></td></tr>
            <?php else: ?>
            <?php foreach ($profesionales as $prof): ?>
            <tr>
                <td><strong><?= htmlspecialchars($prof['nombre']) ?></strong></td>
                <td><span class="badge badge-default"><?= htmlspecialchars($prof['tipo_profesional']) ?></span></td>
                <td><?= htmlspecialchars($prof['especialidad'] ?? '-') ?></td>
                <td>
                    <div style="font-size:11px;"><?= htmlspecialchars($prof['telefono']) ?></div>
                    <div style="font-size:11px;"><?= htmlspecialchars($prof['email']) ?></div>
                </td>
                <td>
                    <a href="javascript:void(0)" onclick="if(confirm('¿Desvincular profesional?')) window.location='<?= BASE_URL ?>/?page=empresas&action=unlinkProfesional&id=<?= $prof['id'] ?>&empresa_id=<?= $item['id'] ?><?= $isIframeModal ? '&modal_iframe=1' : '' ?>'" class="text-danger" title="Desvincular"><i class="fas fa-unlink"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <form method="post" action="<?= BASE_URL ?>/?page=empresas&action=linkProfesional&id=<?= $item['id'] ?><?= $isIframeModal ? '&modal_iframe=1' : '' ?>" style="background: #ffffff; padding: 16px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 12px;">
        <h4 style="font-size: 13px; margin-bottom: 12px;">Vincular Profesional Existente</h4>
        <div class="form-grid" style="grid-template-columns: 1fr auto;">
            <div class="form-group">
                <select name="profesional_id" class="form-control" required>
                    <option value="">-- Seleccionar profesional --</option>
                    <?php if(!empty($data['all_profesionales'])): ?>
                        <?php foreach($data['all_profesionales'] as $p): ?>
                            <?php if ($p['empresa_id'] != $item['id']): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['nombre']) ?> 
                                <?= $p['especialidad'] ? ' - ' . htmlspecialchars($p['especialidad']) : '' ?>
                                <?= $p['empresa_id'] ? '(Pertenece a otra empresa)' : '(Independiente)' ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="height:35px;"><i class="fas fa-link"></i> Vincular</button>
            </div>
        </div>
        <div style="font-size:12px; color:var(--slate-500); margin-top:8px;">
            Si el profesional no existe, primero debes crearlo desde el módulo de <a href="<?= BASE_URL ?>/profesionales" target="_blank" style="color:var(--accent-600);font-weight:600;">Profesionales</a>.
        </div>
    </form>
        </div>
    </form>
</div>

<!-- SECCIÓN DE DOCUMENTOS LEGALES -->
<?php $documentos = $data['documentos'] ?? []; ?>
<div class="data-card mt-4" style="margin-top: 24px; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
    <h3 style="font-size: 15px; margin-bottom: 16px; color: #0f172a;"><i class="fas fa-file-contract" style="color: #6366f1;"></i> Documentos Legales</h3>
    
    <table class="data-table" style="margin-bottom: 16px;">
        <thead>
            <tr>
                <th>Documento</th>
                <th>Tipo</th>
                <th>Emisión</th>
                <th>Vencimiento</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($documentos)): ?>
            <tr><td colspan="5"><div class="empty-state" style="padding: 10px;">No hay documentos registrados.</div></td></tr>
            <?php else: ?>
            <?php foreach ($documentos as $doc): ?>
            <tr>
                <td><strong><?= htmlspecialchars($doc['nombre_documento']) ?></strong></td>
                <td><span class="badge badge-default"><?= htmlspecialchars($doc['tipo_documento'] ?? '-') ?></span></td>
                <td><?= !empty($doc['fecha_emision']) ? date('d/m/Y', strtotime($doc['fecha_emision'])) : '-' ?></td>
                <td>
                    <?php 
                    $vencido = false;
                    if (!empty($doc['fecha_vencimiento'])) {
                        $vencido = strtotime($doc['fecha_vencimiento']) < time();
                        echo '<span style="color:'.($vencido ? '#ef4444' : '#10b981').'">'.date('d/m/Y', strtotime($doc['fecha_vencimiento'])).'</span>';
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td>
                    <a href="javascript:void(0)" onclick="if(confirm('¿Eliminar documento?')) window.location='<?= BASE_URL ?>/?page=empresas&action=eliminarDocumento&id=<?= $doc['id'] ?>&empresa_id=<?= $item['id'] ?><?= $isIframeModal ? '&modal_iframe=1' : '' ?>'" class="text-danger" title="Eliminar"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <form method="post" action="<?= BASE_URL ?>/?page=empresas&action=addDocumento&id=<?= $item['id'] ?><?= $isIframeModal ? '&modal_iframe=1' : '' ?>" enctype="multipart/form-data" style="background: #ffffff; padding: 16px; border-radius: 8px; border: 1px dashed #cbd5e1;">
        <h4 style="font-size: 13px; margin-bottom: 12px;">Añadir Documento</h4>
        <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
            <div class="form-group">
                <label>Nombre / Título</label>
                <input type="text" name="nombre_documento" class="form-control" required placeholder="Ej. Vigencia de poder">
            </div>
            <div class="form-group">
                <label>Tipo de Documento</label>
                <select name="tipo_documento_id" class="form-control dynamic-select" data-tipo="tipo_documento" required>
                    <option value="">-- Seleccionar --</option>
                    <option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo tipo...</option>
                    <?php if(!empty($data['cat_tipos_documento'])): ?>
                        <?php foreach($data['cat_tipos_documento'] as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Archivo PDF/Word</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="archivo_documento" id="archivo_documento" class="file-upload-input" accept=".pdf,.doc,.docx">
                    <label for="archivo_documento" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span id="file-upload-text">Haz clic para seleccionar o arrastra un archivo...</span>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Fecha Emisión</label>
                <input type="date" name="fecha_emision" class="form-control">
            </div>
            <div class="form-group">
                <label>Fecha Vencimiento</label>
                <input type="date" name="fecha_vencimiento" class="form-control">
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:100%; height:35px;"><i class="fas fa-upload"></i> Registrar Doc</button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<style>
/* Custom File Upload */
.file-upload-wrapper {
    position: relative;
    width: 100%;
    height: 130px;
    margin-top: 4px;
}
.file-upload-input {
    opacity: 0;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    z-index: 2;
}
.file-upload-label {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 8px;
    color: #64748b;
    font-size: 13px;
    transition: all 0.2s;
    z-index: 1;
}
.file-upload-label i {
    margin-right: 0;
    margin-bottom: 8px;
    font-size: 28px;
    color: #8b5cf6;
    transition: all 0.2s;
}
#file-upload-text {
    text-align: center;
    line-height: 1.2;
}
.file-upload-input:hover + .file-upload-label,
.file-upload-input:focus + .file-upload-label {
    border-color: #8b5cf6;
    background: #f5f3ff;
    color: #4f46e5;
}

/* Custom Prompt Modal */
.custom-prompt-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease-out;
}
.custom-prompt-overlay.visible {
    display: flex;
}
.custom-prompt-modal {
    background: #ffffff;
    width: 100%;
    max-width: 400px;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.2);
    animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.custom-prompt-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}
.custom-prompt-desc {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 16px;
}
.custom-prompt-input {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    transition: all 0.2s;
}
.custom-prompt-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
.custom-prompt-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.btn-cancel-prompt {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 18px;
    font-size: 13.5px;
    font-weight: 600;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.btn-cancel-prompt:hover {
    background: #e2e8f0;
    color: #0f172a;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleUp {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

<!-- Custom Prompt HTML -->
<div class="custom-prompt-overlay" id="customPromptOverlay">
    <div class="custom-prompt-modal">
        <div class="custom-prompt-title" id="customPromptTitle">Añadir nueva opción</div>
        <div class="custom-prompt-desc" id="customPromptDesc">Escribe el nombre de la nueva categoría para agregarla al catálogo.</div>
        <input type="text" class="custom-prompt-input" id="customPromptInput" placeholder="Nombre...">
        <div class="custom-prompt-actions">
            <button type="button" class="btn-cancel-prompt" onclick="closeCustomPrompt(false)">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="closeCustomPrompt(true)">Añadir al catálogo</button>
        </div>
    </div>
</div>

<script>
let promptResolve = null;

function openCustomPrompt(tipo) {
    return new Promise(resolve => {
        promptResolve = resolve;
        const overlay = document.getElementById('customPromptOverlay');
        const input = document.getElementById('customPromptInput');
        
        let label = tipo.replace('_id', '').replace('_', ' ');
        document.getElementById('customPromptTitle').innerText = 'Añadir nuevo: ' + label;
        
        input.value = '';
        overlay.classList.add('visible');
        setTimeout(() => input.focus(), 100);
    });
}

function closeCustomPrompt(confirm) {
    const overlay = document.getElementById('customPromptOverlay');
    const input = document.getElementById('customPromptInput');
    overlay.classList.remove('visible');
    
    if (promptResolve) {
        if (confirm && input.value.trim() !== '') {
            promptResolve(input.value.trim());
        } else {
            promptResolve(null);
        }
        promptResolve = null;
    }
}

// File input interaction
const fileInput = document.getElementById('archivo_documento');
const fileLabel = document.querySelector('.file-upload-label');
if(fileInput && fileLabel) {
    const originalHtml = fileLabel.innerHTML;
    fileInput.addEventListener('change', function(e) {
        if(this.files && this.files.length > 0) {
            const file = this.files[0];
            const ext = file.name.split('.').pop().toLowerCase();
            let icon = 'fa-file-alt';
            if (ext === 'pdf') icon = 'fa-file-pdf';
            if (ext === 'doc' || ext === 'docx') icon = 'fa-file-word';
            
            fileLabel.innerHTML = `
                <i class="fas ${icon}" style="color: #10b981; font-size: 34px;"></i>
                <span style="color: #0f172a; font-weight: 700; text-align: center; line-height: 1.2; word-break: break-all; margin-top: 6px;">${file.name}</span>
                <span style="color: #64748b; font-size: 11px; margin-top: 4px;">Listo para subir</span>
            `;
            fileLabel.style.borderColor = '#10b981';
            fileLabel.style.background = '#ecfdf5';
        } else {
            fileLabel.innerHTML = originalHtml;
            fileLabel.style.borderColor = '';
            fileLabel.style.background = '';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const dynamicSelects = document.querySelectorAll('.dynamic-select');
    
    dynamicSelects.forEach(select => {
        let previousValue = select.value;
        
        select.addEventListener('focus', function() {
            previousValue = this.value;
        });

        select.addEventListener('change', async function() {
            if (this.value === 'ADD_NEW') {
                const tipo = this.getAttribute('data-tipo');
                
                // Use custom prompt instead of native prompt
                const nombreNuevo = await openCustomPrompt(tipo);
                
                if (nombreNuevo && nombreNuevo !== '') {
                    // Send AJAX request
                    const formData = new URLSearchParams();
                    formData.append('tipo', tipo);
                    formData.append('nombre', nombreNuevo);
                    
                    try {
                        const response = await fetch('<?= BASE_URL ?>/?page=empresas&action=addCatalogItem', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            const newOption = document.createElement('option');
                            newOption.value = result.id;
                            newOption.textContent = result.nombre;
                            
                            this.insertBefore(newOption, this.children[2]);
                            this.value = result.id;
                            previousValue = result.id;
                        } else {
                            alert('Error al añadir: ' + (result.error || 'Error desconocido'));
                            this.value = previousValue;
                        }
                    } catch(e) {
                        alert('Error de conexión.');
                        this.value = previousValue;
                    }
                } else {
                    this.value = previousValue;
                }
            } else {
                previousValue = this.value;
            }
        });
    });
});
</script>
