<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acceso al Sistema de Gestión Comercial - Oportunidades y Contratos del Estado">
    <title>Iniciar Sesión | Gestión de Proyectos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body, html {
            height: 100vh;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            /* Fondo gris claro perlado */
            background: #eef0f5;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
            position: relative;
        }

        /* ── Fondo animado con esferas borrosas ── */
        .bg-layer {
            position: fixed; inset: 0;
            pointer-events: none; z-index: 0;
            overflow: hidden;
        }
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.35;
        }
        .blob-1 {
            width: 600px; height: 600px;
            background: radial-gradient(circle, #c7d2fe, transparent 70%);
            top: -150px; left: -100px;
            animation: driftBlob 16s ease-in-out infinite;
        }
        .blob-2 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #a5f3fc, transparent 70%);
            bottom: -120px; right: -80px;
            animation: driftBlob 20s ease-in-out infinite reverse;
        }
        .blob-3 {
            width: 380px; height: 380px;
            background: radial-gradient(circle, #ddd6fe, transparent 70%);
            top: 40%; left: 55%;
            animation: driftBlob 13s ease-in-out infinite 4s;
            opacity: 0.25;
        }
        @keyframes driftBlob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%  { transform: translate(25px, -30px) scale(1.06); }
            66%  { transform: translate(-20px, 20px) scale(0.95); }
        }

        /* Grilla sutil de fondo */
        .bg-grid {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(99,102,241,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,0.06) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        /* Partículas */
        .particles { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
        .dot {
            position: absolute;
            border-radius: 50%;
            background: rgba(99,102,241,0.25);
            animation: riseUp linear infinite;
        }
        @keyframes riseUp {
            0%   { transform: translateY(105vh); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { transform: translateY(-5vh); opacity: 0; }
        }

        /* ── Card principal ── */
        .login-card {
            position: relative; z-index: 10;
            display: flex;
            width: min(94vw, 1160px);
            min-height: 620px;
            margin: auto;
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-radius: 32px;
            border: 1px solid rgba(255,255,255,0.9);
            box-shadow:
                0 4px 6px rgba(99,102,241,0.04),
                0 12px 32px rgba(99,102,241,0.08),
                0 40px 80px rgba(99,102,241,0.14),
                inset 0 1px 0 rgba(255,255,255,1);
            overflow: hidden;
            animation: cardIn 0.85s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ── Panel izquierdo (imagen) ── */
        .image-side {
            flex: 1.25;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 52px 48px;
            min-height: 440px;
        }
        .image-side img.bg-photo {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 8s ease;
        }
        .image-side:hover img.bg-photo { transform: scale(1.04); }

        /* Gradiente sobre la imagen */
        .image-side::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(
                160deg,
                rgba(30,17,69,0.35) 0%,
                rgba(79,70,229,0.55) 50%,
                rgba(15,10,40,0.80) 100%
            );
            z-index: 1;
        }

        /* Borde decorativo animado en la esquina */
        .img-deco {
            position: absolute;
            width: 280px; height: 280px;
            border: 1px solid rgba(167,139,250,0.25);
            border-radius: 50%;
            bottom: -60px; right: -60px;
            z-index: 2;
            animation: rotateSlow 30s linear infinite;
        }
        .img-deco-inner {
            position: absolute;
            width: 180px; height: 180px;
            border: 1px solid rgba(167,139,250,0.18);
            border-radius: 50%;
            bottom: -10px; right: -10px;
            z-index: 2;
            animation: rotateSlow 20s linear infinite reverse;
        }
        @keyframes rotateSlow { to { transform: rotate(360deg); } }

        .image-content { position: relative; z-index: 3; color: white; }
        .brand-pill {
            display: inline-flex; align-items: center; gap: 9px;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px; padding: 7px 16px;
            font-size: 12px; font-weight: 700; letter-spacing: 0.5px;
            margin-bottom: 20px;
        }
        .brand-pill i { color: #a78bfa; }
        .image-content h2 {
            font-size: 34px; font-weight: 800;
            line-height: 1.2; margin-bottom: 14px;
            letter-spacing: -0.4px;
        }
        .image-content h2 em {
            font-style: normal;
            background: linear-gradient(90deg, #c4b5fd, #5eead4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .image-content p {
            font-size: 14px; opacity: 0.88; line-height: 1.7; max-width: 380px; margin-bottom: 28px;
        }
        .feature-chips { display: flex; flex-wrap: wrap; gap: 10px; }
        .chip {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 20px; padding: 6px 14px;
            font-size: 12px; font-weight: 600;
            opacity: 0; animation: chipIn 0.5s ease-out forwards;
        }
        .chip:nth-child(1) { animation-delay: 0.4s; }
        .chip:nth-child(2) { animation-delay: 0.6s; }
        .chip:nth-child(3) { animation-delay: 0.8s; }
        .chip:nth-child(4) { animation-delay: 1.0s; }
        @keyframes chipIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Panel derecho (formulario) ── */
        .form-side {
            flex: 0.9;
            padding: 60px 56px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }

        .form-eyebrow {
            font-size: 11px; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            color: #7c3aed;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 14px;
        }
        .form-eyebrow::after {
            content: ''; width: 36px; height: 2px;
            background: linear-gradient(90deg, #7c3aed, transparent);
            border-radius: 1px;
        }

        .form-title {
            font-size: 34px; font-weight: 800;
            color: #1e1145; letter-spacing: -0.7px;
            line-height: 1.15; margin-bottom: 12px;
        }
        .form-subtitle {
            font-size: 14px; color: #64748b;
            line-height: 1.65; margin-bottom: 40px;
        }

        /* Alerta error */
        .alert-error {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 14px 16px; border-radius: 12px;
            background: #fef2f2; color: #991b1b;
            border: 1px solid #fecaca;
            font-size: 13px; margin-bottom: 24px;
            animation: shakeAlert 0.5s ease-out;
        }
        @keyframes shakeAlert {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* ── Botón Google ── */
        .btn-google {
            display: flex; align-items: center; justify-content: center; gap: 14px;
            width: 100%; padding: 17px 28px;
            border-radius: 14px;
            border: 1.5px solid #e4e4e7;
            background: white;
            color: #18181b;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-size: 15px; font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            position: relative; overflow: hidden;
        }
        .btn-google::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(124,58,237,0.06), rgba(79,70,229,0.06));
            opacity: 0; transition: opacity 0.3s;
        }
        .btn-google:hover {
            border-color: #7c3aed;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(124,58,237,0.2), 0 4px 8px rgba(124,58,237,0.1);
        }
        .btn-google:hover::before { opacity: 1; }
        .btn-google:active { transform: translateY(-1px); }

        .google-logo { width: 22px; height: 22px; flex-shrink: 0; }

        /* Separador */
        .separator {
            display: flex; align-items: center; gap: 12px;
            margin: 28px 0 0;
            color: #a1a1aa; font-size: 11px; font-weight: 600; letter-spacing: 0.5px;
        }
        .separator::before, .separator::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(90deg, transparent, #e4e4e7, transparent);
        }

        /* Footer */
        .form-footer {
            margin-top: 30px;
            padding-top: 24px;
            border-top: 1px solid #f4f4f5;
            text-align: center;
        }
        .form-footer p { font-size: 11px; color: #a1a1aa; line-height: 1.7; margin-bottom: 10px; }
        .security-row {
            display: inline-flex; align-items: center; gap: 16px;
        }
        .security-badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 600; color: #71717a;
            background: #f8f8f8; border-radius: 20px; padding: 5px 12px;
        }
        .security-badge i { color: #0d9488; font-size: 10px; }

        /* Loading state en botón */
        .btn-google.loading { pointer-events: none; opacity: 0.75; }
        .btn-google.loading .google-logo { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 800px) {
            .login-card { flex-direction: column; }
            .image-side { min-height: 280px; padding: 32px 28px; }
            .form-side { padding: 36px 28px; }
            .form-title { font-size: 24px; }
        }
    </style>
</head>
<body>
    <!-- Fondo animado -->
    <div class="bg-layer">
        <div class="bg-blob blob-1"></div>
        <div class="bg-blob blob-2"></div>
        <div class="bg-blob blob-3"></div>
    </div>
    <div class="bg-grid"></div>
    <div class="particles" id="particles"></div>

    <div class="login-card">

        <!-- ── Panel Izquierdo ── -->
        <div class="image-side">
            <!-- Imagen de fondo: sala de reuniones/contratos gubernamentales -->
            <img
                class="bg-photo"
                src="https://images.unsplash.com/photo-1521791136064-7986c2920216?q=80&w=1000&auto=format&fit=crop"
                alt="Equipo de contratos y licitaciones"
            >
            <div class="img-deco"></div>
            <div class="img-deco-inner"></div>

            <div class="image-content">
                <div class="brand-pill">
                    <i class="fas fa-gem"></i>
                    Gestión de Proyectos
                </div>
                <h2>Sistema de<br><em>Oportunidades</em><br>y Contratos</h2>
                <p>Centralice licitaciones, propuestas y contratos del Estado peruano con integración directa a la API OECE/SEACE.</p>
                <div class="feature-chips">
                    <div class="chip"><i class="fas fa-gavel" style="font-size:10px;color:#a78bfa"></i> Licitaciones OECE</div>
                    <div class="chip"><i class="fas fa-file-signature" style="font-size:10px;color:#5eead4"></i> Contratos</div>
                    <div class="chip"><i class="fas fa-chart-line" style="font-size:10px;color:#fbbf24"></i> Dashboard KPIs</div>
                    <div class="chip"><i class="fas fa-building" style="font-size:10px;color:#86efac"></i> Multi-empresa</div>
                </div>
            </div>
        </div>

        <!-- ── Panel Derecho ── -->
        <div class="form-side">

            <div class="form-eyebrow">Acceso seguro</div>
            <h1 class="form-title">Bienvenido<br>al sistema</h1>
            <p class="form-subtitle">
                Inicia sesión con tu cuenta de Google corporativa para acceder a la plataforma de gestión comercial.
            </p>

            <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fas fa-circle-exclamation" style="margin-top:2px;flex-shrink:0"></i>
                <div><strong>Acceso denegado</strong><br><?= htmlspecialchars($error) ?></div>
            </div>
            <?php endif; ?>

            <!-- Único botón de acceso: Google -->
            <a href="<?= BASE_URL ?>/auth/google" class="btn-google" id="btn-google" onclick="this.classList.add('loading')">
                <svg class="google-logo" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continuar con Google
            </a>

            <div class="separator">Acceso exclusivo para administradores</div>

            <!-- Link al login de colaborador -->
            <a href="<?= BASE_URL ?>/auth/loginColaborador" style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:18px;font-size:13px;color:#64748b;text-decoration:none;font-weight:500;transition:all 0.3s;padding:10px;border-radius:10px;" onmouseover="this.style.color='#10b981';this.style.background='rgba(16,185,129,0.05)'" onmouseout="this.style.color='#64748b';this.style.background='transparent'">
                <i class="fas fa-users" style="font-size:12px;"></i>
                ¿Eres colaborador? Accede aquí
                <i class="fas fa-arrow-right" style="font-size:11px;transition:transform 0.3s;"></i>
            </a>

            <!-- Footer -->
            <div class="form-footer">
                <p>Al ingresar confirmas que eres un usuario autorizado de esta plataforma y aceptas los términos de uso internos del sistema.</p>
                <div class="security-row">
                    <div class="security-badge"><i class="fas fa-lock"></i> OAuth 2.0</div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Generar partículas flotantes
        const pc = document.getElementById('particles');
        for (let i = 0; i < 22; i++) {
            const d = document.createElement('div');
            d.className = 'dot';
            const size = 2 + Math.random() * 4;
            d.style.cssText = `
                width: ${size}px; height: ${size}px;
                left: ${Math.random() * 100}vw;
                animation-duration: ${10 + Math.random() * 18}s;
                animation-delay: ${Math.random() * 14}s;
                opacity: ${0.15 + Math.random() * 0.3};
            `;
            pc.appendChild(d);
        }
    </script>
    <script>
        window.addEventListener('pageshow', function() {
            document.querySelectorAll('.btn-google').forEach(function(btn) {
                btn.classList.remove('loading');
            });
        });
    </script>
</body>
</html>
