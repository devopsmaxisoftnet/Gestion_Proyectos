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
                <select name="departamento_id" class="form-control dynamic-select" data-tipo="departamento">
                    <option value="">-- Seleccionar --</option>
                    <option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>
                    <?php if(!empty($data['cat_departamentos'])): ?>
                        <?php foreach($data['cat_departamentos'] as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($item['departamento_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Provincia</label>
                <select name="provincia_id" class="form-control dynamic-select" data-tipo="provincia">
                    <option value="">-- Seleccionar --</option>
                    <option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>
                    <?php if(!empty($data['cat_provincias'])): ?>
                        <?php foreach($data['cat_provincias'] as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($item['provincia_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Distrito</label>
                <select name="distrito_id" class="form-control dynamic-select" data-tipo="distrito">
                    <option value="">-- Seleccionar --</option>
                    <option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>
                    <?php if(!empty($data['cat_distritos'])): ?>
                        <?php foreach($data['cat_distritos'] as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($item['distrito_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Contacto - Nombre</label>
                <input type="text" name="contacto_nombre" class="form-control" value="<?= htmlspecialchars($item['contacto_nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Contacto - Cargo</label>
                <select name="contacto_cargo_id" class="form-control dynamic-select" data-tipo="cargo_cliente">
                    <option value="">-- Seleccionar --</option>
                    <option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>
                    <?php if(!empty($data['cat_cargos_cliente'])): ?>
                        <?php foreach($data['cat_cargos_cliente'] as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($item['contacto_cargo_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
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
            <button type="button" class="btn btn-secondary" onclick="if(window.parent && window.parent.cerrarGlobalModal) window.parent.cerrarGlobalModal(); else window.location.href='<?= BASE_URL ?>/clientes';">Cancelar</button>
        </div>
    </form>
</div>

<style>
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
.custom-prompt-overlay.visible { display: flex; }
.custom-prompt-modal {
    background: #ffffff;
    width: 100%;
    max-width: 400px;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.2);
    animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.custom-prompt-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
.custom-prompt-desc { font-size: 13px; color: #64748b; margin-bottom: 16px; }
.custom-prompt-input {
    width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0;
    border-radius: 8px; font-size: 14px; margin-bottom: 20px; transition: all 0.2s;
}
.custom-prompt-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
.custom-prompt-actions { display: flex; justify-content: flex-end; gap: 10px; }
.btn-cancel-prompt {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 10px 18px; font-size: 13.5px; font-weight: 600;
    border-radius: 10px; font-family: 'Inter', sans-serif; cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); background: #f1f5f9;
    color: #475569; border: 1px solid #e2e8f0;
}
.btn-cancel-prompt:hover { background: #e2e8f0; color: #0f172a; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes scaleUp { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>

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
        if (confirm && input.value.trim() !== '') promptResolve(input.value.trim());
        else promptResolve(null);
        promptResolve = null;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const dynamicSelects = document.querySelectorAll('.dynamic-select');
    
    const selDep = document.querySelector('select[name="departamento_id"]');
    const selProv = document.querySelector('select[name="provincia_id"]');
    const selDist = document.querySelector('select[name="distrito_id"]');
    
    async function loadOptions(url, selectElement, placeholder) {
        selectElement.innerHTML = `<option value="">-- Seleccionar --</option><option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>`;
        try {
            const res = await fetch(url);
            const data = await res.json();
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.nombre;
                selectElement.appendChild(opt);
            });
        } catch(e) {
            console.error('Error loading options');
        }
    }

    dynamicSelects.forEach(select => {
        let previousValue = select.value;
        select.addEventListener('focus', function() { previousValue = this.value; });

        select.addEventListener('change', async function() {
            const tipo = this.getAttribute('data-tipo');
            
            // Cascading logic (fetch data when changing selection)
            if (this.value !== 'ADD_NEW') {
                if (tipo === 'departamento') {
                    if (this.value) {
                        await loadOptions('<?= BASE_URL ?>/?page=clientes&action=getProvincias&departamento_id=' + this.value, selProv);
                    } else {
                        selProv.innerHTML = `<option value="">-- Seleccionar --</option><option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>`;
                    }
                    selDist.innerHTML = `<option value="">-- Seleccionar --</option><option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>`;
                } else if (tipo === 'provincia') {
                    if (this.value) {
                        await loadOptions('<?= BASE_URL ?>/?page=clientes&action=getDistritos&provincia_id=' + this.value, selDist);
                    } else {
                        selDist.innerHTML = `<option value="">-- Seleccionar --</option><option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>`;
                    }
                }
            }
            
            // "ADD NEW" logic
            if (this.value === 'ADD_NEW') {
                // validation for rel_id
                let rel_id = '';
                if (tipo === 'provincia') {
                    rel_id = selDep.value;
                    if (!rel_id || rel_id === 'ADD_NEW') {
                        alert('Primero debes seleccionar un Departamento válido.');
                        this.value = previousValue;
                        return;
                    }
                } else if (tipo === 'distrito') {
                    rel_id = selProv.value;
                    if (!rel_id || rel_id === 'ADD_NEW') {
                        alert('Primero debes seleccionar una Provincia válida.');
                        this.value = previousValue;
                        return;
                    }
                }
                
                const nombreNuevo = await openCustomPrompt(tipo);
                
                if (nombreNuevo && nombreNuevo !== '') {
                    const formData = new URLSearchParams();
                    formData.append('tipo', tipo);
                    formData.append('nombre', nombreNuevo);
                    formData.append('rel_id', rel_id);
                    
                    try {
                        const response = await fetch('<?= BASE_URL ?>/?page=clientes&action=addCatalogItem', {
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
                            
                            // trigger cascade manually
                            if (tipo === 'departamento') {
                                selProv.innerHTML = `<option value="">-- Seleccionar --</option><option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>`;
                                selDist.innerHTML = `<option value="">-- Seleccionar --</option><option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>`;
                            } else if (tipo === 'provincia') {
                                selDist.innerHTML = `<option value="">-- Seleccionar --</option><option value="ADD_NEW" style="color: #4f46e5; font-weight: 700;">+ Añadir nuevo...</option>`;
                            }
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



