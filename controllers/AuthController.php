<?php
/**
 * AuthController - Maneja login, logout y Google OAuth
 * Soporta login de superadmin y colaborador
 */
class AuthController {

    // ─── Mostrar página de login (Superadmin/Admin) ────────
    public function login() {
        session_start();

        // Si ya está logueado, redirigir al dashboard
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
    
    // ─── Login de desarrollo local (solo localhost) ─────────
    public function devLogin() {
        session_start();
        $isLocal = (php_sapi_name() === 'cli' || (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)));
        if ($isLocal) {
            $user = dbQueryOne("SELECT * FROM usuarios WHERE id = 11 OR rol = 'admin' LIMIT 1");
            if ($user) {
                $_SESSION['user'] = [
                    'id'              => $user['id'],
                    'nombre'          => $user['nombre'],
                    'email'           => $user['email'],
                    'rol'             => $user['rol'],
                    'avatar'          => $user['avatar'] ?? null,
                    'acceso_aprobado' => 1,
                    'tipo_usuario_id' => $user['tipo_usuario_id'] ?? null,
                    'area_id'         => $user['area_id'] ?? null,
                ];
                header('Location: ' . BASE_URL . '/oportunidades');
                exit;
            }
        }
        header('Location: ' . BASE_URL . '/auth/login');
        exit;
    }

    // ─── Procesar login con usuario y contraseña ───────────
    public function loginPost() {
        session_start();

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

        // Buscar usuario en la BD
        $sql  = "SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1";
        $user = dbQueryOne($sql, [$email]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['auth_error'] = 'Correo o contraseña incorrectos.';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Guardar en sesión
        $_SESSION['user'] = [
            'id'              => $user['id'],
            'nombre'          => $user['nombre'],
            'email'           => $user['email'],
            'rol'             => $user['rol'],
            'avatar'          => $user['avatar'] ?? null,
            'acceso_aprobado' => $user['acceso_aprobado'] ?? 1,
            'tipo_usuario_id' => $user['tipo_usuario_id'] ?? null,
            'area_id'         => $user['area_id'] ?? null,
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
        $_SESSION['login_type'] = $_GET['login_type'] ?? 'admin';

        $params = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);

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

        // Buscar o crear usuario en la BD
        $user = $this->findOrCreateGoogleUser($googleUser, $loginType);

        if (!$user) {
            $_SESSION['auth_error'] = 'Tu cuenta de Google no tiene acceso a este sistema. Contacta al administrador.';
            $redirect = ($loginType === 'colaborador') ? '/auth/loginColaborador' : '/auth/login';
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }

        // Guardar sesión
        $_SESSION['user'] = [
            'id'              => $user['id'],
            'nombre'          => $user['nombre'],
            'email'           => $user['email'],
            'rol'             => $user['rol'],
            'avatar'          => $user['avatar'] ?? $googleUser['picture'] ?? null,
            'acceso_aprobado' => $user['acceso_aprobado'] ?? 0,
            'tipo_usuario_id' => $user['tipo_usuario_id'] ?? null,
            'area_id'         => $user['area_id'] ?? null,
        ];

        unset($_SESSION['login_type']);

        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    // ─── Solicitar acceso (AJAX) ──────────────────────────
    public function solicitarAcceso() {
        session_start();
        header('Content-Type: application/json');

        if (empty($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            exit;
        }

        $userId  = $_SESSION['user']['id'];
        $mensaje = trim($_POST['mensaje'] ?? 'Solicito acceso al sistema');

        // Verificar si ya tiene solicitud pendiente
        $existing = dbQueryOne(
            "SELECT id FROM solicitudes_acceso WHERE usuario_id = ? AND estado = 'pendiente' LIMIT 1",
            [$userId]
        );

        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Ya tienes una solicitud de acceso pendiente. El administrador la revisará pronto.']);
            exit;
        }

        // Crear solicitud
        dbExecute(
            "INSERT INTO solicitudes_acceso (usuario_id, mensaje, estado) VALUES (?, ?, 'pendiente')",
            [$userId, $mensaje]
        );

        echo json_encode(['success' => true, 'message' => 'Tu solicitud de acceso ha sido enviada. El administrador la revisará pronto.']);
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
                   tu.nombre as tipo_nombre, a.nombre as area_nombre
            FROM solicitudes_acceso sa
            JOIN usuarios u ON sa.usuario_id = u.id
            LEFT JOIN tipos_usuario tu ON sa.tipo_usuario_id = tu.id
            LEFT JOIN areas a ON sa.area_id = a.id
            ORDER BY sa.estado = 'pendiente' DESC, sa.created_at DESC
        ");

        $areas = dbQuery("SELECT * FROM areas WHERE activo = 1 ORDER BY nombre");
        $tipos = dbQuery("SELECT * FROM tipos_usuario ORDER BY id");

        header('Content-Type: application/json');
        echo json_encode([
            'solicitudes' => $solicitudes,
            'areas'       => $areas,
            'tipos'       => $tipos,
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
        $tipoUsuarioId = intval($_POST['tipo_usuario_id'] ?? 0);
        $areaId        = intval($_POST['area_id'] ?? 0);
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
            // Actualizar solicitud
            dbExecute(
                "UPDATE solicitudes_acceso SET estado = 'aprobada', tipo_usuario_id = ?, area_id = ?, revisado_por = ?, fecha_revision = NOW(), observaciones = ? WHERE id = ?",
                [$tipoUsuarioId ?: null, $areaId ?: null, $_SESSION['user']['id'], $observaciones, $solicitudId]
            );

            // Actualizar usuario
            dbExecute(
                "UPDATE usuarios SET acceso_aprobado = 1, tipo_usuario_id = ?, area_id = ? WHERE id = ?",
                [$tipoUsuarioId ?: null, $areaId ?: null, $solicitud['usuario_id']]
            );

            echo json_encode(['success' => true, 'message' => 'Solicitud aprobada correctamente']);
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
        session_start();
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login');
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
