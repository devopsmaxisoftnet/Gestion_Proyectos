<?php
// Ruta: views/dashboard/colaborador_pendiente.php

/**
 * Vista: Portal del Colaborador - Cuenta en Espera de Asignación Organizacional
 * Muestra el estado del perfil mientras el Administrador asigna Cargo y Área.
 */

$userSesion = $_SESSION['user'] ?? [];
$userId = (int)($userSesion['id'] ?? 0);
$nombreUsuario = $userSesion['nombre'] ?? 'Colaborador';
$emailUsuario = $userSesion['email'] ?? '';
$avatarUsuario = $userSesion['avatar'] ?? '';
$inicial = strtoupper(substr($nombreUsuario, 0, 1));

// Consultar la última solicitud de acceso registrada
$solicitud = dbQueryOne("
    SELECT sa.*, 
           oa.nombre as area_nombre, 
           oc.nombre as cargo_nombre 
    FROM solicitudes_acceso sa
    LEFT JOIN org_areas oa ON sa.org_area_id = oa.id
    LEFT JOIN org_cargos oc ON sa.cargo_id = oc.id
    WHERE sa.usuario_id = ? 
    ORDER BY sa.id DESC 
    LIMIT 1
", [$userId]);

// Auto-crear solicitud si aún no existe para garantizar que aparezca inmediatamente en la bandeja del Admin
if (empty($solicitud)) {
    dbExecute("
        INSERT INTO solicitudes_acceso (usuario_id, mensaje, estado, created_at)
        VALUES (?, 'Solicitud de activación y asignación de puesto corporativo.', 'pendiente', NOW())
    ", [$userId]);
    $solicitud = dbQueryOne("
        SELECT sa.*, 
               oa.nombre as area_nombre, 
               oc.nombre as cargo_nombre 
        FROM solicitudes_acceso sa
        LEFT JOIN org_areas oa ON sa.org_area_id = oa.id
        LEFT JOIN org_cargos oc ON sa.cargo_id = oc.id
        WHERE sa.usuario_id = ? 
        ORDER BY sa.id DESC 
        LIMIT 1
    ", [$userId]);
}

$fechaSolicitud = !empty($solicitud['created_at']) ? date('d/m/Y h:i A', strtotime($solicitud['created_at'])) : date('d/m/Y h:i A');
$estadoSol = $solicitud['estado'] ?? 'pendiente';
$mensajeActual = $solicitud['mensaje'] ?? 'Solicitud de activación y asignación de puesto corporativo.';
?>

<div class="content-area animate__animated animate__fadeIn" style="padding: 24px 30px 50px; max-width: 1100px; margin: 0 auto;">

    <!-- Tarjeta Principal de Bienvenida -->
    <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); border-radius: 20px; padding: 32px 36px; color: #ffffff; box-shadow: 0 10px 30px rgba(49, 46, 129, 0.25); position: relative; overflow: hidden; margin-bottom: 28px;">
        <div style="position: absolute; right: -40px; top: -40px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%); border-radius: 50%;"></div>
        
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px; position: relative; z-index: 2;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <?php if (!empty($avatarUsuario)): ?>
                    <img src="<?= htmlspecialchars($avatarUsuario, ENT_QUOTES, 'UTF-8') ?>" alt="" style="width: 72px; height: 72px; border-radius: 18px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3); box-shadow: 0 4px 14px rgba(0,0,0,0.2);">
                <?php else: ?>
                    <div style="width: 72px; height: 72px; border-radius: 18px; background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%); color: #ffffff; font-weight: 800; font-size: 28px; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(255,255,255,0.3); box-shadow: 0 4px 14px rgba(0,0,0,0.2);">
                        <?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(245, 158, 11, 0.25); border: 1px solid rgba(245, 158, 11, 0.5); padding: 4px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 700; color: #fef3c7; margin-bottom: 8px;">
                        <i class="fas fa-hourglass-half"></i> Cuenta en Proceso de Activación
                    </div>
                    <h1 style="font-size: 24px; font-weight: 800; margin: 0 0 6px; letter-spacing: -0.5px;">
                        ¡Bienvenido, <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>!
                    </h1>
                    <p style="font-size: 13.5px; opacity: 0.85; margin: 0; max-width: 600px; line-height: 1.4;">
                        Tu cuenta ha sido creada exitosamente. El Administrador ya recibió tu solicitud para asignarte tu Área y Puesto oficial.
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" onclick="abrirModalMensajeColab()" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none; color: #ffffff; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                    <i class="fas fa-paper-plane"></i> Enviar Nota al Admin
                </button>
                <button type="button" onclick="comprobarEstadoManual(this)" style="background: rgba(255,255,255,0.18); border: 1.5px solid rgba(255,255,255,0.4); color: #ffffff; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; backdrop-filter: blur(8px); transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.18)'">
                    <i class="fas fa-sync-alt"></i> Comprobar
                </button>
            </div>
        </div>
    </div>

    <!-- Grid de Estado y Flujo de Activación -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 28px;">
        
        <!-- Tarjeta: Flujo de Activación Paso a Paso -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px 26px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <h3 style="font-size: 15.5px; font-weight: 800; color: #0f172a; margin: 0 0 18px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-tasks text-primary" style="color: #6366f1;"></i>
                Proceso de Habilitación de tu Cuenta
            </h3>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Paso 1 -->
                <div style="display: flex; align-items: flex-start; gap: 14px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; font-weight: 700;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 13px;">1. Registro de Usuario</div>
                        <div style="font-size: 12px; color: #64748b;">Credenciales y correo electrónico confirmados.</div>
                    </div>
                </div>

                <!-- Paso 2 -->
                <div style="display: flex; align-items: flex-start; gap: 14px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; font-weight: 700;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 13px;">2. Solicitud enviada a Administración</div>
                        <div style="font-size: 12px; color: #64748b;">Registrada el <?= htmlspecialchars($fechaSolicitud, ENT_QUOTES, 'UTF-8') ?> en el sistema.</div>
                    </div>
                </div>

                <!-- Paso 3 (Actual) -->
                <div style="display: flex; align-items: flex-start; gap: 14px;">
                    <div id="paso3Icon" style="width: 32px; height: 32px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; font-weight: 700; animation: pulse 2s infinite;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <div id="paso3Title" style="font-weight: 800; color: #d97706; font-size: 13px;">3. Asignación de Área & Cargo (En Proceso)</div>
                        <div id="paso3Desc" style="font-size: 12px; color: #475569; font-weight: 500;">El Administrador te asignará tu posición en el Organigrama oficial.</div>
                    </div>
                </div>

                <!-- Paso 4 -->
                <div style="display: flex; align-items: flex-start; gap: 14px;">
                    <div id="paso4Icon" style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; font-weight: 700;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <div id="paso4Title" style="font-weight: 700; color: #64748b; font-size: 13px;">4. Activación de Módulos de Trabajo</div>
                        <div id="paso4Desc" style="font-size: 12px; color: #94a3b8;">Tus pantallas se desplegarán según la Matriz de Permisos.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta: Ficha de la Solicitud Activa -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 24px 26px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 15.5px; font-weight: 800; color: #0f172a; margin: 0 0 16px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-id-card" style="color: #0ea5e9;"></i>
                    Datos de tu Ficha de Acceso
                </h3>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 14px;">
                    <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 3px;">Colaborador</div>
                    <div style="font-size: 14px; font-weight: 800; color: #0f172a;"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($emailUsuario, ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; text-transform: uppercase; color: #64748b; font-weight: 700;">Estado</div>
                        <div style="font-size: 12.5px; font-weight: 800; color: #d97706; margin-top: 2px;">
                            <i class="fas fa-clock" style="font-size: 11px;"></i> Pendiente
                        </div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; text-transform: uppercase; color: #64748b; font-weight: 700;">Fecha de Envío</div>
                        <div style="font-size: 12px; font-weight: 700; color: #334155; margin-top: 2px;" id="lblFechaEnvio">
                            <?= htmlspecialchars(date('d/m/Y', strtotime($solicitud['created_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>

                <!-- Mensaje enviado -->
                <div id="cajaMensajeEnviado" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: #2563eb; display: flex; align-items: center; gap: 5px;">
                            <i class="fas fa-comment-dots"></i> Mensaje para el Administrador
                        </div>
                        <span style="font-size: 10px; color: #3b82f6; font-weight: 700;">Activo</span>
                    </div>
                    <div id="textoMensajeEnviado" style="font-size: 12.5px; color: #1e40af; font-style: italic; line-height: 1.4;">
                        "<?= htmlspecialchars($mensajeActual, ENT_QUOTES, 'UTF-8') ?>"
                    </div>
                </div>

                <button type="button" onclick="abrirModalMensajeColab()" style="width: 100%; background: #ffffff; border: 1.5px dashed #cbd5e1; color: #4338ca; padding: 9px 14px; border-radius: 10px; font-size: 12.5px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;" onmouseover="this.style.borderColor='#6366f1'; this.style.background='#f5f3ff';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#ffffff';">
                    <i class="fas fa-edit"></i> Actualizar o agregar nota para el Administrador
                </button>
            </div>

            <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #f1f5f9; display: flex; align-items: center;">
                <span style="font-size: 11.5px; color: #94a3b8;">
                    <i class="fas fa-shield-alt" style="color: #10b981;"></i> Seguridad activa por Organigrama
                </span>
            </div>
        </div>
    </div>

    <!-- Mensaje Informativo Inferior -->
    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 14px;">
        <div style="width: 36px; height: 36px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
            <i class="fas fa-info-circle"></i>
        </div>
        <div style="font-size: 12.5px; color: #475569; line-height: 1.4;">
            <strong>¿Eres nuevo en la empresa?</strong> Tu solicitud ya se encuentra visible para el Administrador en la bandeja de <strong>Usuarios &gt; Solicitudes de Acceso</strong>. Puedes actualizar tu nota en cualquier momento indicando tu puesto o departamento deseado.
        </div>
    </div>
</div>

<!-- Estilos Standalone para Modales, Spinner y Animación Checkmark SVG -->
<style>
/* Modales universales sin depender de librerías externas */
.modal-standalone {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow-x: hidden;
    overflow-y: auto;
    z-index: 10600;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}
.modal-standalone.show {
    display: flex !important;
}
.modal-standalone-dialog {
    position: relative;
    width: 100%;
    max-width: 520px;
    margin: auto;
    box-sizing: border-box;
    animation: modalPopIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes modalPopIn {
    0% { opacity: 0; transform: scale(0.92) translateY(12px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
.modal-standalone-content {
    background: #ffffff;
    border-radius: 22px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 25px 60px -10px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    width: 100%;
    box-sizing: border-box;
}

/* Spinner animado dinámico con avión de papel */
.envio-spinner-wrapper {
    position: relative;
    width: 86px;
    height: 86px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.envio-spinner-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 4px solid transparent;
    border-top-color: #6366f1;
    border-right-color: #06b6d4;
    border-bottom-color: #8b5cf6;
    animation: envioSpinRing 1s linear infinite;
}
.envio-spinner-ring-inner {
    position: absolute;
    inset: 9px;
    border-radius: 50%;
    border: 3.5px solid transparent;
    border-top-color: #10b981;
    border-left-color: #3b82f6;
    animation: envioSpinRingRev 0.8s linear infinite;
}
.envio-spinner-icon {
    font-size: 26px;
    color: #4338ca;
    animation: envioPlaneFloat 1.3s ease-in-out infinite alternate;
}
@keyframes envioSpinRing {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes envioSpinRingRev {
    0% { transform: rotate(360deg); }
    100% { transform: rotate(0deg); }
}
@keyframes envioPlaneFloat {
    0% { transform: translateY(4px) scale(0.94); opacity: 0.85; }
    100% { transform: translateY(-5px) scale(1.08); opacity: 1; }
}

/* Checkmark SVG animado de Éxito */
.success-checkmark-box {
    width: 86px;
    height: 86px;
    margin: 0 auto 18px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.checkmark-svg {
    width: 86px;
    height: 86px;
    border-radius: 50%;
    display: block;
    stroke-width: 3.5;
    stroke: #10b981;
    stroke-miterlimit: 10;
    box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
    animation: fillSuccess 0.4s ease-in-out 0.4s forwards, scalePulse 0.3s ease-in-out 0.8s both;
}
.checkmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 3.5;
    stroke-miterlimit: 10;
    stroke: #10b981;
    fill: none;
    animation: strokeCircle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.checkmark-check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    stroke: #ffffff;
    animation: strokeCheck 0.35s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
}
@keyframes strokeCircle {
    100% { stroke-dashoffset: 0; }
}
@keyframes strokeCheck {
    100% { stroke-dashoffset: 0; }
}
@keyframes fillSuccess {
    100% { box-shadow: inset 0px 0px 0px 45px #10b981; }
}
@keyframes scalePulse {
    0%, 100% { transform: none; }
    50% { transform: scale3d(1.08, 1.08, 1); }
}
</style>

<!-- Modal 1: Enviar / Actualizar Mensaje al Administrador -->
<div class="modal-standalone" id="modalMensajeColab" style="z-index: 10600;">
    <div class="modal-standalone-dialog" style="max-width: 500px;">
        <div class="modal-standalone-content">
            <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #ffffff; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 17px;">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 16px; font-weight: 800; margin: 0; color: #ffffff;">Mensaje para el Administrador</h4>
                        <p style="margin: 0; font-size: 11.5px; opacity: 0.8; color: #e0e7ff;">Presenta tu perfil o indica a qué área te incorporarás</p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalMensajeColab()" style="background: transparent; border: none; color: #ffffff; font-size: 18px; cursor: pointer; opacity: 0.8; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="formMensajeColab" onsubmit="enviarMensajeColab(event)">
                <div style="padding: 22px 24px;">
                    <!-- Sugerencias Rápidas -->
                    <div style="margin-bottom: 14px;">
                        <label style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; display: block; margin-bottom: 6px;">
                            Sugerencias de presentación rápida:
                        </label>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            <button type="button" class="btn-chip" onclick="aplicarChipMensaje('Hola, me incorporo al área de Operaciones / Obras como Ingeniero.')" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 20px; font-size: 11px; padding: 5px 12px; color: #334155; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                🏗️ Área Operaciones
                            </button>
                            <button type="button" class="btn-chip" onclick="aplicarChipMensaje('Hola, soy especialista del área de Licitaciones y Propuestas.')" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 20px; font-size: 11px; padding: 5px 12px; color: #334155; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                📑 Licitaciones
                            </button>
                            <button type="button" class="btn-chip" onclick="aplicarChipMensaje('Hola, solicito habilitación para el área de Administración y Finanzas.')" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 20px; font-size: 11px; padding: 5px 12px; color: #334155; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                💼 Finanzas
                            </button>
                            <button type="button" class="btn-chip" onclick="aplicarChipMensaje('Hola, soy Asistente de Proyectos y requiero acceso general.')" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 20px; font-size: 11px; padding: 5px 12px; color: #334155; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                                📋 Asistente
                            </button>
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label for="txtMensajeColab" style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px;">
                            Tu mensaje o nota detallada:
                        </label>
                        <textarea id="txtMensajeColab" name="mensaje" rows="4" style="width: 100%; box-sizing: border-box; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 10px 14px; font-size: 13px; color: #0f172a; resize: vertical; outline: none; transition: border-color 0.2s; font-family: inherit;" placeholder="Escribe aquí tu mensaje, puesto acordado o departamento..."><?= htmlspecialchars($mensajeActual, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div id="alertaMensajeColab" style="display: none; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; font-weight: 600; margin-top: 10px;"></div>
                </div>

                <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="cerrarModalMensajeColab()" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 9px 18px; border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer;">Cancelar</button>
                    <button type="submit" id="btnGuardarMensajeColab" style="background: #4338ca; border: none; color: #ffffff; padding: 9px 20px; border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(67, 56, 202, 0.35);">
                        <i class="fas fa-check"></i> Enviar Mensaje
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Feedback Animado de Envío (Spinner & Checkmark de Confirmación) -->
<div class="modal-standalone" id="modalEnvioFeedback" style="z-index: 10700;">
    <div class="modal-standalone-dialog" style="max-width: 460px;">
        <div class="modal-standalone-content" style="border-radius: 24px; border: 1px solid rgba(226, 232, 240, 0.9); box-shadow: 0 25px 60px -10px rgba(15, 23, 42, 0.4);">
            
            <!-- Línea superior de acento degradado -->
            <div style="height: 5px; width: 100%; background: linear-gradient(90deg, #6366f1 0%, #06b6d4 50%, #10b981 100%);"></div>

            <!-- FASE 1: SPINNER ENVIANDO -->
            <div id="feedbackFaseCargando" style="padding: 38px 30px; text-align: center;">
                <div class="envio-spinner-wrapper">
                    <div class="envio-spinner-ring"></div>
                    <div class="envio-spinner-ring-inner"></div>
                    <i class="fas fa-paper-plane envio-spinner-icon"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 8px; letter-spacing: -0.3px;">
                    Enviando tu nota al Administrador...
                </h3>
                <p style="font-size: 13.5px; color: #64748b; margin: 0 0 18px; line-height: 1.45;">
                    Transmitiendo y sincronizando tu mensaje en la bandeja de aprobaciones en tiempo real.
                </p>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: #f1f5f9; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #475569;">
                    <i class="fas fa-circle-notch fa-spin text-primary" style="color: #6366f1;"></i>
                    Registrando en el sistema corporativo...
                </div>
            </div>

            <!-- FASE 2: ÉXITO ANIMADO CON CHECKMARK -->
            <div id="feedbackFaseExito" style="display: none; padding: 34px 28px; text-align: center;">
                <div class="success-checkmark-box">
                    <svg class="checkmark-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
                
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #047857; margin-bottom: 10px;">
                    <i class="fas fa-check-double"></i> Recibido correctamente por el Administrador
                </div>
                
                <h3 style="font-size: 21px; font-weight: 800; color: #0f172a; margin: 0 0 8px; letter-spacing: -0.3px;">
                    ¡Mensaje Enviado con Éxito!
                </h3>
                <p style="font-size: 13px; color: #475569; margin: 0 0 16px; line-height: 1.45;">
                    Tu nota ya está disponible para el Administrador. Recibirás una notificación automática en cuanto te asignen tu puesto en el Organigrama.
                </p>

                <!-- Previsualización del mensaje enviado -->
                <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 14px; padding: 14px 16px; margin-bottom: 22px; text-align: left;">
                    <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fas fa-comment-alt" style="color: #6366f1;"></i> Contenido Registrado</span>
                        <span style="font-size: 10px; color: #10b981; font-weight: 700;">● Entregado</span>
                    </div>
                    <div id="feedbackPreviewTexto" style="font-size: 13px; color: #1e293b; font-style: italic; line-height: 1.45; max-height: 110px; overflow-y: auto;">
                        "..."
                    </div>
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="far fa-clock"></i> <span id="feedbackPreviewHora">Hoy</span></span>
                        <span style="color: #6366f1; font-weight: 600;"><i class="fas fa-shield-alt"></i> Solicitud activa</span>
                    </div>
                </div>

                <button type="button" onclick="cerrarModalFeedbackEnvio()" style="width: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; border: none; padding: 12px 20px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35); display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                    <i class="fas fa-check"></i> Entendido, muchas gracias
                </button>
            </div>

            <!-- FASE 3: ERROR -->
            <div id="feedbackFaseError" style="display: none; padding: 34px 28px; text-align: center;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 8px;">
                    No se pudo enviar el mensaje
                </h3>
                <p id="feedbackErrorMsg" style="font-size: 13px; color: #dc2626; margin: 0 0 20px; line-height: 1.45;">
                    Ocurrió un inconveniente al comunicarse con el servidor.
                </p>
                <button type="button" onclick="cerrarModalFeedbackEnvio()" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 12px; font-weight: 700; font-size: 13px; padding: 10px 20px; cursor: pointer;">
                    Volver a intentar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Celebración de Aprobación Oficial en Vivo -->
<div class="modal-standalone" id="modalAprobadoCelebracion" style="z-index: 10800;">
    <div class="modal-standalone-dialog" style="max-width: 500px;">
        <div class="modal-standalone-content" style="border: 2px solid #86efac; box-shadow: 0 25px 60px -10px rgba(16, 185, 129, 0.4);">
            
            <div style="background: linear-gradient(135deg, #065f46 0%, #047857 50%, #059669 100%); padding: 26px 28px; text-align: center; color: #ffffff; position: relative; overflow: hidden;">
                <div style="position: absolute; right: -20px; top: -20px; width: 120px; height: 120px; background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%); border-radius: 50%;"></div>
                
                <div style="width: 68px; height: 68px; border-radius: 20px; background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); display: inline-flex; align-items: center; justify-content: center; font-size: 30px; margin-bottom: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                    🎉
                </div>
                <div style="display: inline-block; background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4); padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">
                    ¡Acceso Aprobado y Oficializado!
                </div>
                <h2 style="font-size: 23px; font-weight: 800; margin: 0 0 4px; letter-spacing: -0.5px;">
                    ¡Felicidades, <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>!
                </h2>
                <p style="font-size: 13px; opacity: 0.9; margin: 0;">
                    El Administrador ha verificado tu perfil y te ha integrado formalmente al Organigrama.
                </p>
            </div>

            <div style="padding: 24px 28px;">
                <!-- Tarjeta con los datos asignados -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 16px; padding: 16px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div style="background: #ffffff; border: 1px solid #dcfce7; border-radius: 12px; padding: 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #15803d; text-transform: uppercase;">Puesto Asignado</div>
                        <div id="celebCargoNombre" style="font-size: 14px; font-weight: 800; color: #065f46; margin-top: 3px;">
                            Colaborador Oficial
                        </div>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #dcfce7; border-radius: 12px; padding: 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #15803d; text-transform: uppercase;">Área de Trabajo</div>
                        <div id="celebAreaNombre" style="font-size: 14px; font-weight: 800; color: #065f46; margin-top: 3px;">
                            Área Asignada
                        </div>
                    </div>
                </div>

                <p style="font-size: 13px; color: #475569; line-height: 1.5; margin: 0 0 22px; text-align: center;">
                    Tus permisos y pantallas de trabajo han sido activados. Ya puedes gestionar los proyectos y tareas asignadas a tu área.
                </p>

                <button type="button" onclick="irAlEspacioTrabajo()" style="width: 100%; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; border: none; padding: 14px 24px; border-radius: 14px; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 6px 18px rgba(5, 150, 105, 0.4); display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s;">
                    <i class="fas fa-rocket"></i> Entrar a mi Espacio de Trabajo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Controlador de Modales Standalone (Zero-Dependency) ──
function toggleModal(modalId, show) {
    const el = document.getElementById(modalId);
    if (!el) return;

    let backdrop = document.getElementById('backdrop-' + modalId);
    if (show) {
        el.style.display = 'flex';
        el.classList.add('show');
        el.removeAttribute('aria-hidden');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.id = 'backdrop-' + modalId;
            backdrop.style.cssText = 'position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); z-index:' + (parseInt(el.style.zIndex || 10600) - 1) + '; opacity:0; transition:opacity 0.25s ease;';
            backdrop.onclick = function() {
                if (modalId === 'modalMensajeColab') {
                    toggleModal(modalId, false);
                }
            };
            document.body.appendChild(backdrop);
            setTimeout(() => { backdrop.style.opacity = '1'; }, 10);
        }
        document.body.style.overflow = 'hidden';
    } else {
        el.style.display = 'none';
        el.classList.remove('show');
        el.setAttribute('aria-hidden', 'true');
        if (backdrop) {
            backdrop.style.opacity = '0';
            setTimeout(() => { backdrop.remove(); }, 250);
        }
        document.body.style.overflow = '';
    }
}

function abrirModalMensajeColab() {
    const alerta = document.getElementById('alertaMensajeColab');
    if (alerta) alerta.style.display = 'none';
    toggleModal('modalMensajeColab', true);
}

function cerrarModalMensajeColab() {
    toggleModal('modalMensajeColab', false);
}

function aplicarChipMensaje(texto) {
    const txt = document.getElementById('txtMensajeColab');
    if (txt) {
        txt.value = texto;
        txt.focus();
    }
}

function cerrarModalFeedbackEnvio() {
    toggleModal('modalEnvioFeedback', false);
}

function irAlEspacioTrabajo() {
    window.location.href = '<?= BASE_URL ?>/dashboard';
}

function enviarMensajeColab(e) {
    e.preventDefault();
    const txt = document.getElementById('txtMensajeColab');
    const valor = txt ? txt.value.trim() : '';
    const alerta = document.getElementById('alertaMensajeColab');

    if (!valor) {
        if (alerta) {
            alerta.style.background = '#fef3c7';
            alerta.style.border = '1px solid #fde68a';
            alerta.style.color = '#92400e';
            alerta.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Por favor ingresa un mensaje para el Administrador.';
            alerta.style.display = 'block';
        }
        return;
    }

    // 1. Cerrar modal de redacción
    cerrarModalMensajeColab();

    // 2. Abrir modal de feedback animado en estado de carga (Spinner elegante)
    document.getElementById('feedbackFaseCargando').style.display = 'block';
    document.getElementById('feedbackFaseExito').style.display = 'none';
    document.getElementById('feedbackFaseError').style.display = 'none';
    toggleModal('modalEnvioFeedback', true);

    const formData = new FormData();
    formData.append('mensaje', valor);

    const startTime = Date.now();

    fetch('<?= BASE_URL ?>/auth/solicitarAcceso', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        // Asegurar que se aprecie la animación fluida del spinner al menos 850ms
        const elapsed = Date.now() - startTime;
        const waitTime = Math.max(0, 850 - elapsed);

        setTimeout(() => {
            if (res.success) {
                // Cambiar a Fase 2: Éxito con SVG Checkmark animado
                document.getElementById('feedbackFaseCargando').style.display = 'none';
                document.getElementById('feedbackFaseExito').style.display = 'block';

                const pText = document.getElementById('feedbackPreviewTexto');
                if (pText) pText.textContent = '"' + valor + '"';

                const pHora = document.getElementById('feedbackPreviewHora');
                if (pHora) {
                    const ahora = new Date();
                    pHora.textContent = ahora.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }

                // Actualizar la tarjeta de mensaje en el dashboard en segundo plano
                const lblTexto = document.getElementById('textoMensajeEnviado');
                if (lblTexto) {
                    lblTexto.textContent = '"' + valor + '"';
                    const caja = document.getElementById('cajaMensajeEnviado');
                    if (caja) {
                        caja.style.transition = 'all 0.5s ease';
                        caja.style.borderColor = '#10b981';
                        caja.style.background = '#f0fdf4';
                        setTimeout(() => {
                            caja.style.borderColor = '#bfdbfe';
                            caja.style.background = '#eff6ff';
                        }, 2500);
                    }
                }
            } else {
                document.getElementById('feedbackFaseCargando').style.display = 'none';
                document.getElementById('feedbackFaseError').style.display = 'block';
                const errEl = document.getElementById('feedbackErrorMsg');
                if (errEl) errEl.textContent = res.message || 'No se pudo registrar tu mensaje.';
            }
        }, waitTime);
    })
    .catch(err => {
        setTimeout(() => {
            document.getElementById('feedbackFaseCargando').style.display = 'none';
            document.getElementById('feedbackFaseError').style.display = 'block';
            const errEl = document.getElementById('feedbackErrorMsg');
            if (errEl) errEl.textContent = 'Error de conexión con el servidor. Intenta de nuevo.';
        }, 850);
    });
}

// ── Monitoreo en vivo de Aprobación por el Administrador ──
let pollerAprobacion = null;

function iniciarVigilanciaAprobacion() {
    if (pollerAprobacion) clearInterval(pollerAprobacion);
    pollerAprobacion = setInterval(verificarEstadoAprobacionSilencioso, 6500);
}

function verificarEstadoAprobacionSilencioso() {
    fetch('<?= BASE_URL ?>/auth/notificacionesColaborador')
    .then(r => r.json())
    .then(data => {
        if (!data) return;
        if (parseInt(data.acceso_aprobado) === 1) {
            clearInterval(pollerAprobacion);
            mostrarAprobacionEnVivo(data);
        }
    })
    .catch(() => {});
}

function mostrarAprobacionEnVivo(data) {
    // 1. Actualizar paso 3 en el dashboard
    const paso3Icon = document.getElementById('paso3Icon');
    const paso3Title = document.getElementById('paso3Title');
    const paso3Desc = document.getElementById('paso3Desc');
    if (paso3Icon) {
        paso3Icon.style.background = '#dcfce7';
        paso3Icon.style.color = '#15803d';
        paso3Icon.style.animation = 'none';
        paso3Icon.innerHTML = '<i class="fas fa-check"></i>';
    }
    if (paso3Title) {
        paso3Title.style.color = '#15803d';
        paso3Title.innerHTML = '3. Asignación de Área & Cargo (¡Aprobado!)';
    }
    if (paso3Desc) {
        paso3Desc.innerHTML = 'Área: <strong>' + escapeHtml(data.area_nombre || 'General') + '</strong> &mdash; Puesto: <strong>' + escapeHtml(data.cargo_nombre || 'Colaborador') + '</strong>';
    }

    // 2. Actualizar paso 4 en el dashboard
    const paso4Icon = document.getElementById('paso4Icon');
    const paso4Title = document.getElementById('paso4Title');
    const paso4Desc = document.getElementById('paso4Desc');
    if (paso4Icon) {
        paso4Icon.style.background = '#dcfce7';
        paso4Icon.style.color = '#15803d';
        paso4Icon.innerHTML = '<i class="fas fa-unlock"></i>';
    }
    if (paso4Title) {
        paso4Title.style.color = '#15803d';
        paso4Title.innerHTML = '4. Activación de Módulos (Habilitados)';
    }
    if (paso4Desc) {
        paso4Desc.innerHTML = 'Tus pantallas y permisos han sido desbloqueados exitosamente.';
    }

    // 3. Actualizar badge de la campanita en el header con icono de check
    const bellBtn = document.getElementById('btn-notificaciones-colab');
    let bellBadge = document.getElementById('badge-notif-colab');
    if (!bellBadge && bellBtn) {
        bellBadge = document.createElement('span');
        bellBadge.id = 'badge-notif-colab';
        bellBadge.className = 'badge-count';
        bellBtn.appendChild(bellBadge);
    }
    if (bellBadge) {
        bellBadge.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        bellBadge.style.width = '18px';
        bellBadge.style.height = '18px';
        bellBadge.innerHTML = '<i class="fas fa-check" style="font-size:8px;"></i>';
        bellBadge.style.display = 'flex';
    }

    // 4. Desplegar modal de celebración
    const cCargo = document.getElementById('celebCargoNombre');
    const cArea = document.getElementById('celebAreaNombre');
    if (cCargo) cCargo.textContent = data.cargo_nombre || 'Colaborador Oficial';
    if (cArea) cArea.textContent = data.area_nombre || 'Área Asignada';

    toggleModal('modalAprobadoCelebracion', true);
}

function comprobarEstadoManual(btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Comprobando...';
    }

    fetch('<?= BASE_URL ?>/auth/notificacionesColaborador')
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Comprobar';
        }

        if (data && parseInt(data.acceso_aprobado) === 1) {
            mostrarAprobacionEnVivo(data);
        } else {
            // Toast elegante indicando estado
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '24px';
            toast.style.right = '24px';
            toast.style.background = 'linear-gradient(135deg, #1e1b4b 0%, #312e81 100%)';
            toast.style.color = '#ffffff';
            toast.style.padding = '14px 22px';
            toast.style.borderRadius = '14px';
            toast.style.boxShadow = '0 12px 30px rgba(0,0,0,0.35)';
            toast.style.zIndex = '99999';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '12px';
            toast.style.fontSize = '13px';
            toast.style.fontWeight = '600';
            toast.innerHTML = '<i class="fas fa-hourglass-half" style="color:#fbbf24; font-size:16px;"></i> <span>Tu solicitud sigue en revisión por el Administrador.<br><small style="opacity:0.8; font-weight:400;">Te notificaremos aquí automáticamente al aprobarse.</small></span>';
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.transition = 'opacity 0.4s ease';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 400);
            }, 3500);
        }
    })
    .catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Comprobar';
        }
        location.reload();
    });
}

document.addEventListener('DOMContentLoaded', iniciarVigilanciaAprobacion);
</script>
