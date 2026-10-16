<?php
/**
 * AuthController - Maneja login, logout y Google OAuth
 * Soporta login de superadmin y colaborador
 */
class AuthController {

    // ─── Mostrar página de login (Superadmin/Admin) ────────
    public function login() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        // Si ya está logueado, redirigir directamente al dashboard
        if (!empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $error   = $_SESSION['auth_error']   ?? null;
        $success = $_SESSION['auth_success'] ?? null;
        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        include __DIR__ . '/../views/auth/login.php';
    }

    // ─── Mostrar página de login Colaborador ──────────────
    public function loginColaborador() {
        session_start();

        // Si ya está logueado, redirigir al dashboard
        if (!empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $error   = $_SESSION['auth_error']   ?? null;
        $success = $_SESSION['auth_success'] ?? null;
        unset($_SESSION['auth_error'], $_SESSION['auth_success']);

        include __DIR__ . '/../views/auth/login_colaborador.php';
    }

    // ─── Mostrar formulario de Registro y Configuración de Empresa/Drive ────────
    public function registro() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        // Si ya existe la empresa en la BD (o ya existen administradores), rebotar directamente al login
        $empresaExiste = (int)(dbQueryOne("SELECT COUNT(*) as t FROM empresa_software")['t'] ?? 0) > 0
                      || (int)(dbQueryOne("SELECT COUNT(*) as t FROM empresas")['t'] ?? 0) > 0;

        if ($empresaExiste) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $error    = $_SESSION['registro_error']   ?? null;
        $success  = $_SESSION['registro_success'] ?? null;
        $postData = $_SESSION['registro_data']    ?? [];
        unset($_SESSION['registro_error'], $_SESSION['registro_success'], $_SESSION['registro_data']);

        $driveServiceEmail = 'devops.maxisoftnet@gmail.com';

        include __DIR__ . '/../views/auth/registro.php';
    }

    // ─── Procesar formulario de Registro y Configuración de Empresa/Drive ────────
    public function registroPost() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        // Si ya existe la empresa en la BD, rebotar directamente al login
        $empresaExiste = (int)(dbQueryOne("SELECT COUNT(*) as t FROM empresa_software")['t'] ?? 0) > 0
                      || (int)(dbQueryOne("SELECT COUNT(*) as t FROM empresas")['t'] ?? 0) > 0;

        if ($empresaExiste) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $ruc                 = trim($_POST['ruc'] ?? '');
        $razon_social        = trim($_POST['razon_social'] ?? '');
        $representante_legal = trim($_POST['representante_legal'] ?? '');
        $whatsapp_phone      = trim($_POST['whatsapp_phone'] ?? '');
        $admin_nombre        = trim($_POST['admin_nombre'] ?? $representante_legal);
        $email               = trim($_POST['email'] ?? '');
        $drive_folder_raw    = trim($_POST['drive_folder_id'] ?? '');
        $drive_email         = trim($_POST['drive_email'] ?? '');

        $_SESSION['registro_data'] = $_POST;

        // Si proviene de flujo OAuth temporal, el email debe coincidir estrictamente
        if ($hasTempGoogle && strcasecmp($email, $_SESSION['temp_google_email']) !== 0) {
            $_SESSION['registro_error'] = 'El correo electrónico debe coincidir con la cuenta de Google autenticada (' . htmlspecialchars($_SESSION['temp_google_email']) . ').';
            header('Location: ' . BASE_URL . '/auth/registro');
            exit;
        }

        if (empty($ruc) || empty($razon_social) || empty($email)) {
            $_SESSION['registro_error'] = 'El RUC, la Razón Social y el Correo Electrónico son obligatorios.';
            header('Location: ' . BASE_URL . '/auth/registro');
            exit;
        }

        if (!preg_match('/^[0-9]{11}$/', $ruc)) {
            $_SESSION['registro_error'] = 'El RUC debe tener exactamente 11 dígitos numéricos.';
            header('Location: ' . BASE_URL . '/auth/registro');
            exit;
        }

        if (empty($drive_folder_raw)) {
            $_SESSION['registro_error'] = 'El ID o enlace de la carpeta de Google Drive es obligatorio.';
            header('Location: ' . BASE_URL . '/auth/registro');
            exit;
        }

        require_once __DIR__ . '/../helpers/GoogleDriveHelper.php';
        $drive_folder_id = GoogleDriveHelper::extractFolderId($drive_folder_raw);

        if (empty($drive_folder_id)) {
            $_SESSION['registro_error'] = 'No se pudo identificar un ID válido de carpeta de Google Drive.';
            header('Location: ' . BASE_URL . '/auth/registro');
            exit;
        }

        // Validar conexión con Google Drive y crear carpetas estándar
        try {
            $driveHelper = new GoogleDriveHelper();
            if (!$driveHelper->validateFolderAccess($drive_folder_id)) {
                $_SESSION['registro_error'] = 'No se pudo acceder a la carpeta de Google Drive. Por favor verifica que el ID sea correcto y que hayas compartido la carpeta con devops.maxisoftnet@gmail.com con rol de "Editor".';
                header('Location: ' . BASE_URL . '/auth/registro');
                exit;
            }

            // Crear subcarpetas operativas
            $subfolders = ['Contratos', 'Convocatorias', 'Propuestas', 'Oportunidades', 'Documentos'];
            foreach ($subfolders as $sfName) {
                try {
                    $driveHelper->getOrCreateSubFolder($sfName, $drive_folder_id);
                } catch (\Exception $e) {
                    // Silencioso si ya existe
                }
            }
        } catch (\Exception $e) {
            $_SESSION['registro_error'] = 'Error al comunicarse con Google Drive API: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '/auth/registro');
            exit;
        }

        // Guardar o actualizar empresa
        $empresaExistente = dbQueryOne("SELECT id FROM empresas WHERE ruc = ? LIMIT 1", [$ruc]);
        if ($empresaExistente) {
            $empresaId = $empresaExistente['id'];
            dbExecute("
                UPDATE empresas SET
                    razon_social = ?,
                    representante = ?,
                    contacto_administrativo = ?,
                    telefono = ?,
                    whatsapp_phone = ?,
                    email = ?,
                    drive_folder_id = ?,
                    drive_email = ?,
                    drive_vinculado = 1,
                    tipo_empresa = 'propia',
                    activo = 1
                WHERE id = ?
            ", [
                $razon_social, $representante_legal, $representante_legal, $whatsapp_phone, $whatsapp_phone,
                $email, $drive_folder_id, $drive_email, $empresaId
            ]);
        } else {
            dbExecute("
                INSERT INTO empresas (
                    razon_social, ruc, tipo_empresa, direccion, representante,
                    contacto_administrativo, telefono, whatsapp_phone, email,
                    drive_folder_id, drive_email, drive_vinculado, activo
                ) VALUES (
                    ?, ?, 'propia', '', ?,
                    ?, ?, ?, ?,
                    ?, ?, 1, 1
                )
            ", [
                $razon_social, $ruc, $representante_legal,
                $representante_legal, $whatsapp_phone, $whatsapp_phone, $email,
                $drive_folder_id, $drive_email
            ]);
            $empresaId = dbLastId();
        }

        // Guardar o actualizar datos de la empresa administradora del software (empresa_software)
        dbExecute("
            INSERT INTO empresa_software (
                id, razon_social, ruc, representante, contacto_administrativo,
                telefono, email, drive_folder_id, drive_email, drive_vinculado
            ) VALUES (
                1, ?, ?, ?, ?,
                ?, ?, ?, ?, 1
            ) ON DUPLICATE KEY UPDATE
                razon_social = VALUES(razon_social),
                ruc = VALUES(ruc),
                representante = VALUES(representante),
                contacto_administrativo = VALUES(contacto_administrativo),
                telefono = VALUES(telefono),
                email = VALUES(email),
                drive_folder_id = VALUES(drive_folder_id),
                drive_email = VALUES(drive_email),
                drive_vinculado = 1
        ", [
            $razon_social, $ruc, $representante_legal, $representante_legal,
            $whatsapp_phone, $email, $drive_folder_id, $drive_email
        ]);

        // Guardar configuraciones del sistema
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('software_empresa_id', '1') ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('drive_root_folder_id', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", [$drive_folder_id]);
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('drive_folder_id', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", [$drive_folder_id]);
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('drive_email', ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", [$drive_email]);
        dbExecute("INSERT INTO configuracion (clave, valor) VALUES ('drive_vinculado', '1') ON DUPLICATE KEY UPDATE valor = VALUES(valor)");

        // Crear o actualizar usuario administrador
        $googleId     = $_SESSION['temp_google_id'] ?? null;
        $googleAvatar = $_SESSION['temp_google_avatar'] ?? null;

        $usuarioExistente = dbQueryOne("SELECT id FROM usuarios WHERE email = ? LIMIT 1", [$email]);
        if ($usuarioExistente) {
            $userId = $usuarioExistente['id'];
            $params = [$admin_nombre, 'admin', 5, $empresaId, $drive_folder_id, $drive_email];
            $sql = "UPDATE usuarios SET nombre = ?, rol = ?, rol_id = ?, acceso_aprobado = 1, activo = 1, empresa_id = ?, drive_folder_id = ?, drive_email = ?, drive_vinculado = 1";
            if (!empty($googleId)) {
                $sql .= ", google_id = ?";
                $params[] = $googleId;
            }
            if (!empty($googleAvatar)) {
                $sql .= ", avatar = ?";
                $params[] = $googleAvatar;
            }
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            dbExecute($sql, $params);
        } else {
            $pwdHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            dbExecute("
                INSERT INTO usuarios (nombre, email, password_hash, rol, rol_id, acceso_aprobado, activo, empresa_id, google_id, avatar, drive_folder_id, drive_email, drive_vinculado)
                VALUES (?, ?, ?, 'admin', 5, 1, 1, ?, ?, ?, ?, ?, 1)
            ", [$admin_nombre, $email, $pwdHash, $empresaId, $googleId, $googleAvatar, $drive_folder_id, $drive_email]);
            $userId = dbLastId();
        }

        // Iniciar sesión inmediatamente con los datos recién configurados
        $_SESSION['user'] = [
            'id'              => $userId,
            'nombre'          => $admin_nombre,
            'email'           => $email,
            'rol'             => 'admin',
            'avatar'          => $googleAvatar ?? null,
            'acceso_aprobado' => 1,
            'tipo_usuario_id' => null,
            'area_id'         => null,
            'empresa_id'      => $empresaId,
            'drive_folder_id' => $drive_folder_id,
            'drive_email'     => $drive_email,
            'drive_vinculado' => 1,
        ];

        unset($_SESSION['temp_google_email'], $_SESSION['temp_google_name'], $_SESSION['temp_google_avatar'], $_SESSION['temp_google_id'], $_SESSION['registro_data']);

        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
    
    // ─── Login de desarrollo local (deshabilitado en producción) ──
    public function devLogin() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        global $_isLocal;
        $clientIp  = $_SERVER['REMOTE_ADDR'] ?? '';
        $isLocalIp = in_array($clientIp, ['127.0.0.1', '::1']);

        // Bloqueo estricto: Solo permitido en localhost con IP 127.0.0.1 / ::1
        if (!$_isLocal || !$isLocalIp) {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $type = $_GET['type'] ?? 'admin';
        if ($type === 'colaborador') {
            $user = dbQueryOne("SELECT * FROM usuarios WHERE email = 'guillermolima159@gmail.com' LIMIT 1");
            if (!$user) {
                $user = dbQueryOne("SELECT * FROM usuarios WHERE rol = 'usuario' LIMIT 1");
            }
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id'              => $user['id'],
                    'nombre'          => $user['nombre'],
                    'email'           => $user['email'],
                    'rol'             => $user['rol'],
                    'avatar'          => $user['avatar'] ?? null,
                    'acceso_aprobado' => (int)($user['acceso_aprobado'] ?? 0),
                    'tipo_usuario_id' => $user['tipo_usuario_id'] ?? null,
                    'area_id'         => $user['area_id'] ?? null,
                    'empresa_id'      => $user['empresa_id'] ?? null,
                ];
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }
        }

        $user = dbQueryOne("SELECT * FROM usuarios WHERE (email IN ('devops.maxisoftnet@gmail.com', 'asconsultti@gmail.com') OR rol = 'admin') AND activo = 1 ORDER BY id ASC LIMIT 1");
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'              => $user['id'],
                'nombre'          => $user['nombre'],
                'email'           => $user['email'],
                'rol'             => $user['rol'],
                'avatar'          => $user['avatar'] ?? null,
                'acceso_aprobado' => 1,
                'tipo_usuario_id' => $user['tipo_usuario_id'] ?? null,
                'area_id'         => $user['area_id'] ?? null,
                'empresa_id'      => $user['empresa_id'] ?? null,
                'drive_folder_id' => $user['drive_folder_id'] ?? '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr',
                'drive_vinculado' => 1,
            ];

            header('Location: ' . BASE_URL . '/oportunidades');
            exit;
        }
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    // ─── Procesar login con usuario y contraseña ───────────
    public function loginPost() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['auth_error'] = 'Por favor completa todos los campos.';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Protección contra ataques de fuerza bruta (Rate Limiting)
        $clientIp   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attemptKey = 'login_fails_' . md5($clientIp . '_' . strtolower($email));
        $attempts   = $_SESSION[$attemptKey] ?? ['count' => 0, 'last_time' => 0];

        if ($attempts['count'] >= 5 && (time() - $attempts['last_time']) < 900) {
            $minutosRestantes = ceil((900 - (time() - $attempts['last_time'])) / 60);
            $_SESSION['auth_error'] = "Demasiados intentos fallidos. Por seguridad, tu acceso está pausado. Intenta de nuevo en {$minutosRestantes} minuto(s).";
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Buscar usuario en la BD
        $sql  = "SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1";
        $user = dbQueryOne($sql, [$email]);

        // Soportar columna password_hash o password antigua
        $storedHash = !empty($user['password_hash']) ? $user['password_hash'] : ($user['password'] ?? '');

        if (!$user || empty($storedHash) || !password_verify($password, $storedHash)) {
            // Incrementar contador de intentos fallidos
            $attempts['count'] = ($attempts['count'] ?? 0) + 1;
            $attempts['last_time'] = time();
            $_SESSION[$attemptKey] = $attempts;

            $_SESSION['auth_error'] = 'Correo o contraseña incorrectos.';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Login exitoso: Limpiar contador de intentos fallidos
        unset($_SESSION[$attemptKey]);

        // Si la cuenta es de colaborador, no permitir ingresar por login de admin
        if (($user['rol'] ?? '') === 'usuario') {
            $_SESSION['auth_error'] = 'Esta cuenta está registrada como Colaborador. Por favor, ingresa desde el Portal de Colaboradores.';
            header('Location: ' . BASE_URL . '/auth/loginColaborador');
            exit;
        }

        // Prevenir fijación de sesión (Session Fixation)
        session_regenerate_id(true);

        // Guardar en sesión
        $_SESSION['portal_origen'] = 'admin';
        $_SESSION['user'] = [
            'id'              => $user['id'],
            'nombre'          => $user['nombre'],
            'email'           => $user['email'],
            'rol'             => $user['rol'],
            'avatar'          => $user['avatar'] ?? null,
            'acceso_aprobado' => $user['acceso_aprobado'] ?? 1,
            'tipo_usuario_id' => $user['tipo_usuario_id'] ?? null,
            'area_id'         => $user['area_id'] ?? null,
            'empresa_id'      => $user['empresa_id'] ?? null,
            'drive_folder_id' => $user['drive_folder_id'] ?? '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr',
            'drive_vinculado' => 1,
        ];

        // Actualizar último acceso
        dbExecute("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?", [$user['id']]);

        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    // ─── Redirigir a Google OAuth ──────────────────────────
    public function google() {
        session_start();

        $clientId    = GOOGLE_CLIENT_ID;
        $redirectUri = GOOGLE_REDIRECT_URI;

        // Generar state CSRF
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        // Guardar el tipo de login (admin o colaborador)
        $loginType = $_GET['login_type'] ?? 'admin';
        $_SESSION['login_type'] = $loginType;

        $paramsArray = [
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'state'         => $state,
            'prompt'        => 'select_account',
        ];

        // Parámetro 'hd' (Hosted Domain) para restringir selector de Google al dominio corporativo en login admin
        if ($loginType === 'admin' && defined('ALLOWED_ADMIN_DOMAIN') && !empty(ALLOWED_ADMIN_DOMAIN)) {
            $paramsArray['hd'] = ALLOWED_ADMIN_DOMAIN;
        }

        $params = http_build_query($paramsArray);

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    // ─── Callback de Google OAuth ──────────────────────────
    public function googleCallback() {
        session_start();

        $loginType = $_SESSION['login_type'] ?? 'admin';

        // Validar state CSRF
        $state         = $_GET['state']         ?? '';
        $expectedState = $_SESSION['oauth_state'] ?? '';
        if (!$state || $state !== $expectedState) {
            $_SESSION['auth_error'] = 'Error de seguridad en el proceso de autenticación (state inválido).';
            $redirect = ($loginType === 'colaborador') ? '/auth/loginColaborador' : '/auth/login';
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
        unset($_SESSION['oauth_state']);

        $code = $_GET['code'] ?? '';
        if (!$code) {
            $_SESSION['auth_error'] = 'No se recibió código de autorización de Google.';
            $redirect = ($loginType === 'colaborador') ? '/auth/loginColaborador' : '/auth/login';
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }

        // Intercambiar código por token
        $tokenData = $this->exchangeCodeForToken($code);
        if (!$tokenData || empty($tokenData['access_token'])) {
            $_SESSION['auth_error'] = 'No se pudo obtener el token de Google. Intenta de nuevo.';
            $redirect = ($loginType === 'colaborador') ? '/auth/loginColaborador' : '/auth/login';
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }

        // Obtener perfil del usuario de Google
        $googleUser = $this->getGoogleUserInfo($tokenData['access_token']);
        if (!$googleUser || empty($googleUser['email'])) {
            $_SESSION['auth_error'] = 'No se pudo obtener la información del perfil de Google.';
            $redirect = ($loginType === 'colaborador') ? '/auth/loginColaborador' : '/auth/login';
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }

        $email = trim($googleUser['email']);

        // 1. Verificar si el usuario ya existe
        $user = dbQueryOne("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1", [$email]);

        $empresaConfigurada = false;
        $empresaId = null;
        $driveFolderId = null;

        if ($user && !empty($user['empresa_id'])) {
            $emp = dbQueryOne("SELECT id, drive_folder_id, drive_vinculado FROM empresas WHERE id = ? AND drive_folder_id IS NOT NULL AND drive_folder_id != '' LIMIT 1", [$user['empresa_id']]);
            if ($emp && !empty($emp['drive_folder_id']) && !empty($emp['drive_vinculado'])) {
                $empresaConfigurada = true;
                $empresaId = $emp['id'];
                $driveFolderId = $emp['drive_folder_id'];
            }
        }

        // O si existe alguna empresa con su correo y Drive configurado
        if (!$empresaConfigurada) {
            $emp = dbQueryOne("SELECT id, drive_folder_id, drive_vinculado FROM empresas WHERE email = ? AND drive_folder_id IS NOT NULL AND drive_folder_id != '' LIMIT 1", [$email]);
            if ($emp && !empty($emp['drive_folder_id']) && !empty($emp['drive_vinculado'])) {
                $empresaConfigurada = true;
                $empresaId = $emp['id'];
                $driveFolderId = $emp['drive_folder_id'];
                if ($user) {
                    dbExecute("UPDATE usuarios SET empresa_id = ?, drive_folder_id = ?, drive_vinculado = 1 WHERE id = ?", [$emp['id'], $emp['drive_folder_id'], $user['id']]);
                }
            }
        }

        // O si el usuario mismo tiene Drive vinculado
        if ($user && !empty($user['drive_folder_id']) && !empty($user['drive_vinculado'])) {
            $empresaConfigurada = true;
            $driveFolderId = $user['drive_folder_id'];
            $empresaId = $user['empresa_id'];
        }

        // Si el usuario entra por el portal de COLABORADOR
        if ($loginType === 'colaborador') {
            // Obtener la carpeta y datos de la empresa del software
            $swConfig = dbQueryOne("SELECT drive_folder_id, drive_email, drive_vinculado FROM empresa_software WHERE drive_vinculado = 1 ORDER BY id DESC LIMIT 1");
            $swDriveFolderId = $swConfig['drive_folder_id'] ?? '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr';
            $swDriveEmail    = $swConfig['drive_email'] ?? 'guillermolima159@gmail.com';

            if (!$user) {
                // Registrar nuevo colaborador pendiente de aprobación
                $pwdHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                dbExecute("
                    INSERT INTO usuarios (
                        nombre, email, password_hash, rol, rol_id, acceso_aprobado, activo,
                        google_id, avatar, drive_folder_id, drive_email, drive_vinculado
                    ) VALUES (
                        ?, ?, ?, 'usuario', NULL, 0, 1,
                        ?, ?, ?, ?, 1
                    )
                ", [
                    $googleUser['name'] ?? 'Colaborador',
                    $email,
                    $pwdHash,
                    $googleUser['id'] ?? null,
                    $googleUser['picture'] ?? null,
                    $swDriveFolderId,
                    $swDriveEmail
                ]);
                $user = dbQueryOne("SELECT * FROM usuarios WHERE id = ?", [dbLastId()]);

                // Crear automáticamente solicitud de acceso para el Administrador
                dbExecute("
                    INSERT INTO solicitudes_acceso (usuario_id, mensaje, estado, created_at)
                    VALUES (?, 'Solicitud de acceso registrada automáticamente al ingresar vía Google.', 'pendiente', NOW())
                ", [$user['id']]);
            } else {
                // Actualizar credenciales Google
                dbExecute("UPDATE usuarios SET avatar = COALESCE(?, avatar), google_id = COALESCE(?, google_id), drive_folder_id = COALESCE(drive_folder_id, ?), drive_vinculado = 1, ultimo_acceso = NOW() WHERE id = ?", [
                    $googleUser['picture'] ?? null,
                    $googleUser['id'] ?? null,
                    $swDriveFolderId,
                    $user['id']
                ]);
                $user = dbQueryOne("SELECT * FROM usuarios WHERE id = ?", [$user['id']]);

                // Si no está aprobado y no tiene solicitud pendiente, crearla de inmediato
                if (empty($user['acceso_aprobado'])) {
                    $hasSol = dbQueryOne("SELECT id FROM solicitudes_acceso WHERE usuario_id = ? AND estado = 'pendiente' LIMIT 1", [$user['id']]);
                    if (!$hasSol) {
                        dbExecute("
                            INSERT INTO solicitudes_acceso (usuario_id, mensaje, estado, created_at)
                            VALUES (?, 'Solicitud de activación y asignación de puesto corporativo.', 'pendiente', NOW())
                        ", [$user['id']]);
                    }
                }
            }

            unset($_SESSION['login_type']);
            $_SESSION['portal_origen'] = 'colaborador';
            session_regenerate_id(true);

            // Iniciar sesión del colaborador (mostrará modal de solicitud de acceso si no está aprobado)
            $_SESSION['user'] = [
                'id'              => $user['id'],
                'nombre'          => $user['nombre'],
                'email'           => $user['email'],
                'rol'             => $user['rol'] ?? 'usuario',
                'avatar'          => $user['avatar'] ?? ($googleUser['picture'] ?? null),
                'acceso_aprobado' => (int)($user['acceso_aprobado'] ?? 0),
                'tipo_usuario_id' => $user['tipo_usuario_id'] ?? null,
                'area_id'         => $user['area_id'] ?? null,
                'empresa_id'      => $user['empresa_id'] ?? null,
                'drive_folder_id' => $user['drive_folder_id'] ?? $swDriveFolderId,
                'drive_vinculado' => 1,
            ];

            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        // ─── LOGIN DE ADMINISTRADOR (Restricción de Acceso) ─────────

        // 1. Si la cuenta ya existe en la BD pero tiene rol de Colaborador ('usuario'), RECHAZAR acceso como admin
        if ($user && ($user['rol'] ?? '') === 'usuario') {
            $_SESSION['auth_error'] = 'Esta cuenta (' . htmlspecialchars($email) . ') está registrada como Colaborador. No tiene permisos de Administrador. Por favor ingresa desde el Portal de Colaboradores.';
            unset($_SESSION['login_type']);
            header('Location: ' . BASE_URL . '/auth/loginColaborador');
            exit;
        }

        // 2. Si la cuenta existe pero está inactiva
        if ($user && empty($user['activo'])) {
            $_SESSION['auth_error'] = 'Tu cuenta de usuario ha sido desactivada por el administrador.';
            unset($_SESSION['login_type']);
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // 3. VALIDACIÓN ESTRICTA DE LISTA BLANCA DE ADMINISTRADORES
        $emailLower = strtolower($email);
        $allowedAdmins = defined('ALLOWED_ADMIN_EMAILS') 
            ? array_map('strtolower', ALLOWED_ADMIN_EMAILS) 
            : ['devops.maxisoftnet@gmail.com', 'asconsultti@gmail.com'];

        if (!in_array($emailLower, $allowedAdmins)) {
            $_SESSION['auth_error'] = 'Acceso restringido: El correo ' . htmlspecialchars($email) . ' no está autorizado como Administrador. Si eres colaborador, ingresa desde el Portal de Colaboradores.';
            unset($_SESSION['login_type']);
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // 4. Obtener configuración global de Google Drive de la empresa
        $swConfig = dbQueryOne("SELECT drive_folder_id, drive_email, drive_vinculado FROM empresa_software WHERE drive_vinculado = 1 ORDER BY id DESC LIMIT 1");
        $swDriveFolderId = $swConfig['drive_folder_id'] ?? '16Gne89yf9ckJFPJ0tABD31q2kZPlxHFr';
        $swDriveEmail    = $swConfig['drive_email'] ?? $email;

        // 5. Crear o actualizar Administrador autorizado directamente (sin pantallas de registro)
        if (!$user) {
            $nombreAdmin = $googleUser['name'] ?? ($emailLower === 'devops.maxisoftnet@gmail.com' ? 'Jorge Israel Palomino' : 'asconsult ti');
            $pwdHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            dbExecute("
                INSERT INTO usuarios (
                    nombre, email, password_hash, rol, rol_id, acceso_aprobado, activo,
                    google_id, avatar, drive_folder_id, drive_email, drive_vinculado
                ) VALUES (
                    ?, ?, ?, 'admin', 5, 1, 1,
                    ?, ?, ?, ?, 1
                )
            ", [
                $nombreAdmin,
                $email,
                $pwdHash,
                $googleUser['id'] ?? null,
                $googleUser['picture'] ?? null,
                $swDriveFolderId,
                $swDriveEmail
            ]);
            $user = dbQueryOne("SELECT * FROM usuarios WHERE id = ?", [dbLastId()]);
        } else {
            dbExecute("UPDATE usuarios SET rol = 'admin', acceso_aprobado = 1, activo = 1, avatar = COALESCE(?, avatar), google_id = COALESCE(?, google_id), drive_folder_id = COALESCE(drive_folder_id, ?), drive_vinculado = 1, ultimo_acceso = NOW() WHERE id = ?", [
                $googleUser['picture'] ?? null,
                $googleUser['id'] ?? null,
                $swDriveFolderId,
                $user['id']
            ]);
            $user = dbQueryOne("SELECT * FROM usuarios WHERE id = ?", [$user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['portal_origen'] = 'admin';
        $_SESSION['user'] = [
            'id'              => $user['id'],
            'nombre'          => $user['nombre'],
            'email'           => $user['email'],
            'rol'             => 'admin',
            'avatar'          => $user['avatar'] ?? ($googleUser['picture'] ?? null),
            'acceso_aprobado' => 1,
            'tipo_usuario_id' => $user['tipo_usuario_id'] ?? null,
            'area_id'         => $user['area_id'] ?? null,
            'empresa_id'      => $user['empresa_id'] ?? null,
            'drive_folder_id' => $swDriveFolderId,
            'drive_vinculado' => 1,
        ];

        unset($_SESSION['login_type']);
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    // ─── Solicitar acceso (AJAX) ──────────────────────────
    public function solicitarAcceso() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        if (empty($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            exit;
        }

        $userId  = $_SESSION['user']['id'];
        $mensaje = trim($_POST['mensaje'] ?? '');
        if (empty($mensaje)) {
            $mensaje = 'Solicito activación de cuenta y asignación de área y cargo corporativo.';
        }

        // Verificar si ya tiene solicitud pendiente
        $existing = dbQueryOne(
            "SELECT id FROM solicitudes_acceso WHERE usuario_id = ? AND estado = 'pendiente' LIMIT 1",
            [$userId]
        );

        if ($existing) {
            dbExecute(
                "UPDATE solicitudes_acceso SET mensaje = ?, updated_at = NOW() WHERE id = ?",
                [$mensaje, $existing['id']]
            );
            echo json_encode([
                'success' => true,
                'message' => 'Tu mensaje para el Administrador ha sido actualizado exitosamente.',
                'mensaje_texto' => $mensaje
            ]);
            exit;
        }

        // Crear solicitud de acceso (el Administrador asignará área y cargo desde el panel de control)
        dbExecute(
            "INSERT INTO solicitudes_acceso (usuario_id, mensaje, cargo_id, area_id, estado, created_at) VALUES (?, ?, NULL, NULL, 'pendiente', NOW())",
            [$userId, $mensaje]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Tu solicitud de acceso ha sido registrada exitosamente. El Administrador la revisará pronto.',
            'mensaje_texto' => $mensaje
        ]);
        exit;
    }

    // ─── Listar solicitudes (para el admin) ───────────────
    public function solicitudes() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['superadmin', 'admin'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $solicitudes = dbQuery("
            SELECT sa.*, u.nombre as usuario_nombre, u.email as usuario_email, u.avatar as usuario_avatar,
                   tu.nombre as tipo_nombre, 
                   COALESCE(oa.nombre, a.nombre) as area_nombre,
                   oc.nombre as cargo_nombre, oc.codigo as cargo_codigo, oc.nivel_jerarquico as cargo_nivel
            FROM solicitudes_acceso sa
            JOIN usuarios u ON sa.usuario_id = u.id
            LEFT JOIN tipos_usuario tu ON sa.tipo_usuario_id = tu.id
            LEFT JOIN org_areas oa ON sa.area_id = oa.id
            LEFT JOIN areas a ON sa.area_id = a.id
            LEFT JOIN org_cargos oc ON sa.cargo_id = oc.id
            ORDER BY sa.estado = 'pendiente' DESC, sa.created_at DESC
        ");

        $cargos = dbQuery("
            SELECT c.id, c.nombre, c.codigo, c.nivel_jerarquico, c.area_id, a.nombre as area_nombre 
            FROM org_cargos c 
            LEFT JOIN org_areas a ON c.area_id = a.id 
            WHERE c.activo = 1 
            ORDER BY c.nivel_jerarquico ASC, c.nombre ASC
        ");
        $areas = dbQuery("SELECT id, nombre, codigo, icono, color FROM org_areas WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
        $tipos = dbQuery("SELECT * FROM tipos_usuario ORDER BY id");

        header('Content-Type: application/json');
        echo json_encode([
            'solicitudes' => $solicitudes,
            'areas'       => $areas,
            'cargos'      => $cargos,
            'tipos'       => $tipos,
        ]);
        exit;
    }

    // ─── Listar notificaciones para el Colaborador ─────────
    public function notificacionesColaborador() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        if (empty($_SESSION['user'])) {
            echo json_encode(['notificaciones' => []]);
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];

        // Sincronizar en caliente el estado actual del usuario desde la BD a la sesión
        $usuarioActual = dbQueryOne("SELECT u.acceso_aprobado, u.rol, u.rol_id, u.tipo_usuario_id, u.area_id, u.cargo_id, u.org_area_id,
                                            tu.nombre as tipo_nombre, 
                                            COALESCE(oa.nombre, a.nombre) as area_nombre,
                                            oc.nombre as cargo_nombre, oc.codigo as cargo_codigo
                                      FROM usuarios u
                                      LEFT JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id
                                      LEFT JOIN org_areas oa ON COALESCE(u.org_area_id, u.area_id) = oa.id
                                      LEFT JOIN areas a ON u.area_id = a.id
                                      LEFT JOIN org_cargos oc ON u.cargo_id = oc.id
                                      WHERE u.id = ? LIMIT 1", [$userId]);

        // Consultar el historial de solicitudes del colaborador
        $solicitudes = dbQuery("
            SELECT sa.*, tu.nombre as tipo_nombre, 
                   COALESCE(oa.nombre, a.nombre) as area_nombre,
                   oc.nombre as cargo_nombre, oc.codigo as cargo_codigo,
                   admin.nombre as admin_nombre
            FROM solicitudes_acceso sa
            LEFT JOIN tipos_usuario tu ON sa.tipo_usuario_id = tu.id
            LEFT JOIN org_areas oa ON COALESCE(sa.org_area_id, sa.area_id) = oa.id
            LEFT JOIN areas a ON sa.area_id = a.id
            LEFT JOIN org_cargos oc ON sa.cargo_id = oc.id
            LEFT JOIN usuarios admin ON sa.revisado_por = admin.id
            WHERE sa.usuario_id = ?
            ORDER BY sa.updated_at DESC, sa.id DESC
        ", [$userId]);

        $esAprobado = (!empty($usuarioActual['acceso_aprobado']) && (int)$usuarioActual['acceso_aprobado'] === 1)
                   || (!empty($solicitudes[0]) && $solicitudes[0]['estado'] === 'aprobada');

        if ($usuarioActual) {
            $_SESSION['user']['acceso_aprobado'] = $esAprobado ? 1 : 0;
            if (!empty($usuarioActual['tipo_usuario_id'])) {
                $_SESSION['user']['tipo_usuario_id'] = $usuarioActual['tipo_usuario_id'];
            }
            if (!empty($usuarioActual['area_id']) || !empty($usuarioActual['org_area_id'])) {
                $_SESSION['user']['area_id'] = $usuarioActual['org_area_id'] ?? $usuarioActual['area_id'];
            }
            if (!empty($usuarioActual['cargo_id'])) {
                $_SESSION['user']['cargo_id'] = $usuarioActual['cargo_id'];
                $_SESSION['user']['cargo_nombre'] = $usuarioActual['cargo_nombre'];
            }
        }

        echo json_encode([
            'acceso_aprobado' => $esAprobado ? 1 : 0,
            'area_nombre'     => $usuarioActual['area_nombre'] ?? ($solicitudes[0]['area_nombre'] ?? 'Área Asignada'),
            'cargo_nombre'    => $usuarioActual['cargo_nombre'] ?? ($solicitudes[0]['cargo_nombre'] ?? 'Puesto Oficial'),
            'tipo_nombre'     => $usuarioActual['cargo_nombre'] ?? ($solicitudes[0]['cargo_nombre'] ?? 'Colaborador'),
            'solicitudes'     => $solicitudes
        ]);
        exit;
    }

    // ─── Aprobar/Rechazar solicitud (AJAX) ────────────────
    public function gestionarSolicitud() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        if (empty($_SESSION['user']) || !in_array($_SESSION['user']['rol'], ['superadmin', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']);
            exit;
        }

        $solicitudId   = intval($_POST['solicitud_id'] ?? 0);
        $accion        = $_POST['accion'] ?? '';
        $cargoId       = intval($_POST['cargo_id'] ?? 0);
        $areaId        = intval($_POST['area_id'] ?? 0);
        $tipoUsuarioId = intval($_POST['tipo_usuario_id'] ?? 0);
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (!$solicitudId || !in_array($accion, ['aprobar', 'rechazar'])) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }

        $solicitud = dbQueryOne("SELECT * FROM solicitudes_acceso WHERE id = ?", [$solicitudId]);
        if (!$solicitud) {
            echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada']);
            exit;
        }

        if ($accion === 'aprobar') {
            // Si tiene cargo pero no área, deducir el área del cargo
            if ($cargoId && !$areaId) {
                $cargoInfo = dbQueryOne("SELECT area_id FROM org_cargos WHERE id = ?", [$cargoId]);
                if ($cargoInfo && !empty($cargoInfo['area_id'])) {
                    $areaId = (int)$cargoInfo['area_id'];
                }
            }

            // Actualizar solicitud
            dbExecute(
                "UPDATE solicitudes_acceso SET estado = 'aprobada', cargo_id = ?, tipo_usuario_id = ?, area_id = ?, revisado_por = ?, fecha_revision = NOW(), observaciones = ? WHERE id = ?",
                [$cargoId ?: null, $tipoUsuarioId ?: null, $areaId ?: null, $_SESSION['user']['id'], $observaciones, $solicitudId]
            );

            // Determinar rol según cargo y tipo de acceso
            $cargoInfo = $cargoId ? dbQueryOne("SELECT nivel_jerarquico, codigo FROM org_cargos WHERE id = ?", [$cargoId]) : null;
            $esAdminCargo = ($tipoUsuarioId === 1 || ($cargoInfo && ((int)$cargoInfo['nivel_jerarquico'] === 1 || $cargoInfo['codigo'] === 'CEO')));
            $rolUsuario = $esAdminCargo ? 'admin' : 'usuario';
            $rolId = $esAdminCargo ? 1 : 2;

            // Actualizar usuario con su cargo corporativo y área
            dbExecute(
                "UPDATE usuarios SET acceso_aprobado = 1, activo = 1, cargo_id = ?, org_area_id = ?, area_id = ?, tipo_usuario_id = ?, rol = ?, rol_id = ? WHERE id = ?",
                [$cargoId ?: null, $areaId ?: null, $areaId ?: null, $tipoUsuarioId ?: null, $rolUsuario, $rolId, $solicitud['usuario_id']]
            );

            echo json_encode(['success' => true, 'message' => 'Solicitud aprobada y puesto corporativo asignado exitosamente']);
        } else {
            // Rechazar
            dbExecute(
                "UPDATE solicitudes_acceso SET estado = 'rechazada', revisado_por = ?, fecha_revision = NOW(), observaciones = ? WHERE id = ?",
                [$_SESSION['user']['id'], $observaciones, $solicitudId]
            );

            echo json_encode(['success' => true, 'message' => 'Solicitud rechazada']);
        }
        exit;
    }

    // ─── Logout ────────────────────────────────────────────
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $portal = $_SESSION['portal_origen'] ?? (($_SESSION['user']['rol'] ?? '') === 'usuario' ? 'colaborador' : 'admin');
        
        session_destroy();
        
        if ($portal === 'colaborador') {
            header('Location: ' . BASE_URL . '/auth/loginColaborador');
        } else {
            header('Location: ' . BASE_URL . '/auth/login');
        }
        exit;
    }

    // ── Helpers internos ───────────────────────────────────

    private function exchangeCodeForToken(string $code): ?array {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'redirect_uri'  => GOOGLE_REDIRECT_URI,
                'grant_type'    => 'authorization_code',
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    private function getGoogleUserInfo(string $accessToken): ?array {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    private function findOrCreateGoogleUser(array $googleUser, string $loginType = 'admin'): ?array {
        $email = $googleUser['email'];

        // Buscar si ya existe
        $user = dbQueryOne("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1", [$email]);

        if ($user) {
            // Actualizar avatar y último acceso
            dbExecute(
                "UPDATE usuarios SET avatar = ?, ultimo_acceso = NOW() WHERE id = ?",
                [$googleUser['picture'] ?? null, $user['id']]
            );
            
            // Si entra por el panel de admin y es usuario, lo promovemos para la demo
            if ($loginType === 'admin' && $user['rol'] === 'usuario') {
                dbExecute("UPDATE usuarios SET rol = 'admin', acceso_aprobado = 1 WHERE id = ?", [$user['id']]);
                $user['rol'] = 'admin';
                $user['acceso_aprobado'] = 1;
            }
            
            return $user;
        }

        // Crear usuario nuevo
        $nombre = $googleUser['name'] ?? $email;
        $avatar = $googleUser['picture'] ?? null;

        if ($loginType === 'colaborador') {
            // Colaborador: se crea con rol 'usuario' y acceso NO aprobado
            $insertSql = "INSERT INTO usuarios (nombre, email, avatar, rol, activo, acceso_aprobado, google_id, ultimo_acceso, created_at)
                          VALUES (?, ?, ?, 'usuario', 1, 0, ?, NOW(), NOW())";
        } else {
            // Admin: se crea como admin y acceso aprobado
            $insertSql = "INSERT INTO usuarios (nombre, email, avatar, rol, activo, acceso_aprobado, google_id, ultimo_acceso, created_at)
                          VALUES (?, ?, ?, 'admin', 1, 1, ?, NOW(), NOW())";
        }

        $ok = dbExecute($insertSql, [$nombre, $email, $avatar, $googleUser['id'] ?? null]);

        if ($ok) {
            return dbQueryOne("SELECT * FROM usuarios WHERE email = ? LIMIT 1", [$email]);
        }

        return null;
    }
}
