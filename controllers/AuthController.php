<?php
/**
 * AuthController - Maneja login, logout y Google OAuth
 */
class AuthController {

    // ─── Mostrar página de login ───────────────────────────
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
            'id'     => $user['id'],
            'nombre' => $user['nombre'],
            'email'  => $user['email'],
            'rol'    => $user['rol'],
            'avatar' => $user['avatar'] ?? null,
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

        // Validar state CSRF
        $state         = $_GET['state']         ?? '';
        $expectedState = $_SESSION['oauth_state'] ?? '';
        if (!$state || $state !== $expectedState) {
            $_SESSION['auth_error'] = 'Error de seguridad en el proceso de autenticación (state inválido).';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
        unset($_SESSION['oauth_state']);

        $code = $_GET['code'] ?? '';
        if (!$code) {
            $_SESSION['auth_error'] = 'No se recibió código de autorización de Google.';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Intercambiar código por token
        $tokenData = $this->exchangeCodeForToken($code);
        if (!$tokenData || empty($tokenData['access_token'])) {
            $_SESSION['auth_error'] = 'No se pudo obtener el token de Google. Intenta de nuevo.';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Obtener perfil del usuario de Google
        $googleUser = $this->getGoogleUserInfo($tokenData['access_token']);
        if (!$googleUser || empty($googleUser['email'])) {
            $_SESSION['auth_error'] = 'No se pudo obtener la información del perfil de Google.';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Buscar o crear usuario en la BD
        $user = $this->findOrCreateGoogleUser($googleUser);

        if (!$user) {
            $_SESSION['auth_error'] = 'Tu cuenta de Google no tiene acceso a este sistema. Contacta al administrador.';
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }

        // Guardar sesión
        $_SESSION['user'] = [
            'id'     => $user['id'],
            'nombre' => $user['nombre'],
            'email'  => $user['email'],
            'rol'    => $user['rol'],
            'avatar' => $user['avatar'] ?? $googleUser['picture'] ?? null,
        ];

        header('Location: ' . BASE_URL . '/dashboard');
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

    private function findOrCreateGoogleUser(array $googleUser): ?array {
        $email = $googleUser['email'];

        // Buscar si ya existe
        $user = dbQueryOne("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1", [$email]);

        if ($user) {
            // Actualizar avatar y último acceso
            dbExecute(
                "UPDATE usuarios SET avatar = ?, ultimo_acceso = NOW() WHERE id = ?",
                [$googleUser['picture'] ?? null, $user['id']]
            );
            return $user;
        }

        // OPCIONAL: Crear automáticamente al usuario si no existe
        // Si prefieres que solo entren usuarios pre-registrados, retorna null aquí.
        $nombre = $googleUser['name'] ?? $email;
        $avatar = $googleUser['picture'] ?? null;

        $insertSql = "INSERT INTO usuarios (nombre, email, avatar, rol, activo, google_id, ultimo_acceso, created_at)
                      VALUES (?, ?, ?, 'usuario', 1, ?, NOW(), NOW())";
        $ok = dbExecute($insertSql, [$nombre, $email, $avatar, $googleUser['id'] ?? null]);

        if ($ok) {
            return dbQueryOne("SELECT * FROM usuarios WHERE email = ? LIMIT 1", [$email]);
        }

        return null;
    }
}
