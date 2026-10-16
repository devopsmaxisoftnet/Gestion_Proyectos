<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configura tu Empresa | Gestión de Proyectos</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #78350f;
            --dorado: #d97706;
            --dorado-hover: #f59e0b;
            --card-bg: rgba(255,255,255,0.96);
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body, html {
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
            display: grid; place-items: center;
            padding: 24px;
            position: relative;
        }

        body::before {
            content: ''; position: fixed; width: 520px; height: 520px; border-radius: 50%; pointer-events: none;
            background: radial-gradient(circle, #fde68a 0%, transparent 70%); top: -160px; right: -120px; opacity: 0.45;
            animation: floatBg 12s ease-in-out infinite;
        }
        body::after {
            content: ''; position: fixed; width: 440px; height: 440px; border-radius: 50%; pointer-events: none;
            background: radial-gradient(circle, #cbd5e1 0%, transparent 70%); bottom: -120px; left: -90px; opacity: 0.55;
            animation: floatBg 15s ease-in-out infinite reverse;
        }
        @keyframes floatBg { 50% { transform: translateY(-25px) scale(1.05); } }

        .login-wrapper {
            display: flex; width: min(100%, 1120px); min-height: 580px; background: var(--card-bg);
            backdrop-filter: blur(24px); border-radius: 28px;
            box-shadow: 0 30px 80px rgba(30, 41, 59, 0.18), 0 0 0 1px rgba(255,255,255,0.6) inset;
            overflow: hidden; position: relative; z-index: 1;
            animation: formAppear 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes formAppear { from { opacity: 0; transform: translateY(40px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }

        .image-side {
            flex: 1.05;
            background-image: linear-gradient(135deg, rgba(120, 53, 15, 0.92) 0%, rgba(217, 119, 6, 0.82) 100%), url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&q=80');
            background-size: cover; background-position: center; padding: 50px 42px; color: white;
            display: flex; flex-direction: column; justify-content: center; position: relative;
        }
        .image-side::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M20 20.5V18H0v-2h20v-2H0v-2h20v-2H0V8h20V6H0V4h20V2H0V0h22v20h2V0h2v20h2V0h2v22H20v-1.5z' fill='%23ffffff' fill-opacity='0.04' fill-rule='evenodd'/%3E%3C/svg%3E");
            pointer-events: none;
        }

        .brand-content { position: relative; z-index: 2; }
        .brand-icon { font-size: 50px; color: #f59e0b; margin-bottom: 20px; filter: drop-shadow(0 4px 15px rgba(0,0,0,0.3)); display: block; }
        .brand-content h2 { font-size: 32px; font-weight: 800; margin: 0 0 15px 0; line-height: 1.18; text-shadow: 0 4px 15px rgba(0,0,0,0.25); }
        .brand-content p { font-size: 14px; opacity: 0.92; line-height: 1.7; font-weight: 400; margin-bottom: 24px; }

        .system-features { display: flex; flex-direction: column; gap: 12px; margin-top: 15px; }
        .feature-item { display: flex; align-items: center; gap: 12px; font-size: 13px; opacity: 0.9; font-weight: 500; }
        .feature-item i { width: 30px; height: 30px; background: rgba(255,255,255,0.18); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; color: #fef08a; }

        .form-side { flex: 1.45; padding: 42px 48px; background: white; display: flex; flex-direction: column; overflow-y: auto; max-height: 90vh; }
        .form-header { margin-bottom: 24px; flex-shrink: 0; }
        .form-header h3 { font-size: 24px; color: var(--primary); margin: 0 0 6px 0; font-weight: 800; letter-spacing: -0.5px; display: flex; align-items: center; gap: 10px; }
        .form-header p { color: var(--text-muted); font-size: 13.5px; margin: 0; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 6px; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: var(--primary); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.4px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: 'Outfit', sans-serif; font-size: 13.5px; outline: none; transition: 0.3s; box-sizing: border-box; background: #f8fafc; font-weight: 500; color: #1e293b; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--dorado); background: white; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12); }
        .input-code { font-family: 'Courier New', Courier, monospace !important; font-size: 12.5px !important; letter-spacing: 0.5px; font-weight: 600 !important; color: #78350f !important; }

        .section-title { font-size: 12px; font-weight: 800; color: var(--primary); text-transform: uppercase; margin: 24px 0 14px 0; letter-spacing: 0.6px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .section-title i { color: var(--dorado); font-size: 14px; }
        .section-title:first-child { margin-top: 0; }
        
        .nota-seguridad { background: #fffbeb; padding: 14px 16px; border-radius: 10px; font-size: 12.5px; color: #92400e; margin-bottom: 16px; line-height: 1.55; border: 1.5px solid #fde68a; display: flex; align-items: flex-start; gap: 12px; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.06); }
        .nota-seguridad i { font-size: 18px; color: #d97706; margin-top: 2px; }
        
        .btn-submit { background: linear-gradient(135deg, var(--primary), #9a3412); color: white; padding: 15px; border-radius: 10px; font-weight: 700; font-size: 14.5px; display: flex; justify-content: center; align-items: center; gap: 10px; cursor: pointer; transition: all 0.3s; border: none; box-shadow: 0 4px 16px rgba(120, 53, 15, 0.28); width: 100%; font-family: 'Outfit', sans-serif; margin-top: 24px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(120, 53, 15, 0.38); background: linear-gradient(135deg, #9a3412, var(--primary)); }
        .btn-submit.loading { opacity: 0.85; pointer-events: none; }

        .alert { padding: 14px 16px; border-radius: 10px; font-size: 13.5px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px; animation: slideUpFade 0.4s ease-out; line-height: 1.5; }
        .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
        .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        @keyframes slideUpFade { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 900px) {
            .login-wrapper { flex-direction: column; }
            .image-side, .form-side { padding: 32px 24px; max-height: none; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="image-side">
            <div class="brand-content">
                <i class="fa-solid fa-briefcase brand-icon"></i>
                <h2>Gestión de<br>Proyectos y Contratos</h2>
                <p>Centraliza tus oportunidades comerciales, licitaciones del Estado y contratos. Conecta tu Google Drive para almacenar bases, propuestas y documentos en la nube.</p>

                <div class="system-features">
                    <div class="feature-item"><i class="fa-solid fa-building-circle-check"></i><span>Entorno exclusivo para tu empresa</span></div>
                    <div class="feature-item"><i class="fa-solid fa-cloud-arrow-up"></i><span>Enlace nativo con Google Drive</span></div>
                    <div class="feature-item"><i class="fa-solid fa-file-contract"></i><span>Control integral de licitaciones y contratos</span></div>
                    <div class="feature-item"><i class="fa-solid fa-shield-halved"></i><span>Almacenamiento seguro y carpetas automáticas</span></div>
                </div>
            </div>
        </div>

        <div class="form-side">
            <div class="form-header">
                <h3><i class="fa-solid fa-flag-checkered" style="color: var(--dorado);"></i> Configuración de Empresa</h3>
                <p>
                    <?php if (!empty($_SESSION['temp_google_email'])): ?>
                        ¡Hola <strong><?= htmlspecialchars($_SESSION['temp_google_name'] ?? $_SESSION['temp_google_email']) ?></strong>! Completa los datos de tu empresa y enlaza tu carpeta de Google Drive para activar tu entorno comercial.
                    <?php else: ?>
                        Ingresa los datos fiscales de tu empresa y vincula tu carpeta en Google Drive para acceder al sistema.
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px; margin-top: 2px;"></i>
                    <div><strong>Error al procesar:</strong><br><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check" style="font-size: 18px; margin-top: 2px;"></i>
                    <div>
                        <strong>¡Registro Exitoso!</strong><br>
                        <?= htmlspecialchars($success) ?><br><br>
                        <a href="<?= BASE_URL ?>/auth/login" style="color: #065F46; text-decoration: underline; font-weight: 700;">Ir al Login del Sistema</a>
                    </div>
                </div>
            <?php else: ?>

            <form action="<?= BASE_URL ?>/auth/registro" method="POST" onsubmit="iniciarValidacion()">
                
                <div class="section-title"><i class="fa-solid fa-landmark"></i> Datos Fiscales y Contacto</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>RUC (11 Dígitos)</label>
                        <input type="text" name="ruc" required pattern="[0-9]{11}" minlength="11" maxlength="11" placeholder="Ej. 20123456789" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?= htmlspecialchars($postData['ruc'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Razón Social / Comercial</label>
                        <input type="text" name="razon_social" required placeholder="Ej. Constructora ABC S.A.C." value="<?= htmlspecialchars($postData['razon_social'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Representante Legal</label>
                        <input type="text" name="representante_legal" required placeholder="Ej. Ing. Carlos Mendoza" value="<?= htmlspecialchars($postData['representante_legal'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp Corporativo / Teléfono</label>
                        <input type="text" name="whatsapp_phone" required placeholder="Ej. 987654321" value="<?= htmlspecialchars($postData['whatsapp_phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="section-title"><i class="fa-solid fa-user-shield"></i> Administrador Responsable</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="admin_nombre" required placeholder="Ej. Carlos Mendoza" value="<?= htmlspecialchars($postData['admin_nombre'] ?? $_SESSION['temp_google_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico Corporativo</label>
                        <?php if (!empty($_SESSION['temp_google_email'])): ?>
                            <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['temp_google_email']) ?>" readonly style="background: #e2e8f0; cursor: not-allowed; color: #64748B;">
                        <?php else: ?>
                            <input type="email" name="email" required placeholder="admin@empresa.com" value="<?= htmlspecialchars($postData['email'] ?? '') ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section-title"><i class="fa-brands fa-google-drive"></i> Infraestructura Cloud Google Drive (Obligatorio)</div>
                <div class="nota-seguridad">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        Para sincronizar contratos y licitaciones, debes compartir tu carpeta de Google Drive con:<br>
                        <strong style="color: #78350f; font-size: 13px;"><?= htmlspecialchars($driveServiceEmail ?? 'devops.maxisoftnet@gmail.com') ?></strong> con rol de <u>Editor</u>.
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ID o Enlace de Carpeta Google Drive</label>
                        <input type="text" name="drive_folder_id" class="input-code" required placeholder="Ej. 16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr o enlace drive" value="<?= htmlspecialchars($postData['drive_folder_id'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Correo Propietario (Drive)</label>
                        <input type="email" name="drive_email" required placeholder="ejemplo@gmail.com" value="<?= htmlspecialchars($postData['drive_email'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btnCrear">
                    <i class="fa-brands fa-google-drive"></i> Vincular Empresa y Conectar Google Drive <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 24px;">
                <a href="<?= BASE_URL ?>/auth/login" style="color: var(--text-muted); font-size: 13px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-arrow-left"></i> Volver al login
                </a>
            </div>
        </div>
    </div>

    <script>
        function iniciarValidacion() {
            const btn = document.getElementById('btnCrear');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Validando Google Drive y Configurando...';
        }
    </script>
</body>
</html>
