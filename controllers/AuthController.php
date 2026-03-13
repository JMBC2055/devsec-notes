<?php
// ============================================================================
// UBICACIÓN: gestor-notas/controllers/AuthController.php
// DESCRIPCIÓN: Controlador de autenticación + Recuperación de contraseña
// VERSIÓN: 8.0 - Brevo API HTTP (compatible con Railway)
// ============================================================================

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Validator.php';

class AuthController {

    public function __construct() {
        Security::regenerateIfNeeded();
    }

    private function safeRedirect($url, $statusCode = 302) {
        if (!headers_sent()) {
            header("Location: " . $url, true, $statusCode);
            exit();
        } else {
            echo "<script>window.location.href='" . addslashes($url) . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . addslashes($url) . "'></noscript>";
            exit();
        }
    }

    // =========================================================================
    // REGISTRO
    // =========================================================================

    public function showRegister() {
        if (Session::isAuthenticated()) {
            $this->safeRedirect('index.php?page=dashboard');
        }
        require_once __DIR__ . '/../views/auth/register.php';
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->safeRedirect('index.php?page=register');
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            $this->safeRedirect('index.php?page=register');
        }

        $username        = Security::sanitize($_POST['username'] ?? '');
        $email           = Security::sanitize($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $validator = new Validator();
        $validator->required($username, 'Nombre de usuario')
                  ->minLength($username, 3, 'Nombre de usuario')
                  ->maxLength($username, 50, 'Nombre de usuario')
                  ->required($email, 'Email')
                  ->email($email, 'Email')
                  ->required($password, 'Contraseña')
                  ->strongPassword($password)
                  ->match($password, $confirmPassword, 'Las contraseñas');

        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            $this->safeRedirect('index.php?page=register');
        }

        $user = new User();

        if ($user->emailExists($email)) {
            Session::setFlash('error', 'El email ya está registrado');
            $this->safeRedirect('index.php?page=register');
        }

        if ($user->usernameExists($username)) {
            Session::setFlash('error', 'El nombre de usuario ya está en uso');
            $this->safeRedirect('index.php?page=register');
        }

        $user->username = $username;
        $user->email    = $email;
        $user->password = $password;

        if ($user->register()) {
            Session::setFlash('success', '¡Registro exitoso! Ahora puedes iniciar sesión');
            $this->safeRedirect('index.php?page=login');
        } else {
            Session::setFlash('error', 'Error al registrar usuario. Intenta de nuevo');
            $this->safeRedirect('index.php?page=register');
        }
    }

    // =========================================================================
    // LOGIN / LOGOUT
    // =========================================================================

    public function showLogin() {
        if (Session::isAuthenticated()) {
            $this->safeRedirect('index.php?page=dashboard');
        }
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->safeRedirect('index.php?page=login');
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            $this->safeRedirect('index.php?page=login');
        }

        $email    = Security::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $validator = new Validator();
        $validator->required($email, 'Email')
                  ->email($email, 'Email')
                  ->required($password, 'Contraseña');

        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            $this->safeRedirect('index.php?page=login');
        }

        $user           = new User();
        $user->email    = $email;
        $user->password = $password;
        $userData       = $user->login();

        if ($userData) {
            Security::regenerateSession(true);

            Session::set('user_id',  $userData['id']);
            Session::set('username', $userData['username']);
            Session::set('email',    $userData['email']);

            Session::setFlash('success', '¡Bienvenido, ' . $userData['username'] . '!');
            $this->safeRedirect('index.php?page=dashboard');
        } else {
            Session::setFlash('error', 'Credenciales incorrectas o cuenta bloqueada');
            $this->safeRedirect('index.php?page=login');
        }
    }

    public function logout() {
        Security::destroySession();
        $this->safeRedirect('index.php?page=login');
    }

    // =========================================================================
    // RECUPERACIÓN DE CONTRASEÑA
    // =========================================================================

    public function showForgotPassword() {
        if (Session::isAuthenticated()) {
            $this->safeRedirect('index.php?page=dashboard');
        }
        require_once __DIR__ . '/../views/auth/forgot_password.php';
    }

    public function processForgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->safeRedirect('index.php?page=forgot-password');
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            $this->safeRedirect('index.php?page=forgot-password');
        }

        $email = Security::sanitize($_POST['email'] ?? '');

        $validator = new Validator();
        $validator->required($email, 'Email')->email($email, 'Email');

        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            $this->safeRedirect('index.php?page=forgot-password');
        }

        $passwordReset = new PasswordReset();
        $userData      = $passwordReset->getUserByEmail($email);

        if ($userData) {
            $token = $passwordReset->createToken($userData['id']);
            if ($token) {
                $sent = $this->sendResetEmail($email, $userData['username'], $token);
                if (!$sent) {
                    error_log("[PASSWORD_RESET_FAIL] No se pudo enviar email a: $email");
                }
            }
        }

        // Siempre mostrar el mismo mensaje (evita enumerar usuarios)
        Session::setFlash('success', 'Si el email está registrado, recibirás un enlace en breve. Revisa también tu carpeta de spam.');
        $this->safeRedirect('index.php?page=forgot-password');
    }

    public function showResetPassword() {
        $token = Security::sanitize($_GET['token'] ?? '');

        if (empty($token)) {
            Session::setFlash('error', 'Token inválido');
            $this->safeRedirect('index.php?page=forgot-password');
        }

        $passwordReset = new PasswordReset();
        $tokenData     = $passwordReset->validateToken($token);

        if (!$tokenData) {
            Session::setFlash('error', 'El enlace ha expirado o ya fue usado. Solicita uno nuevo.');
            $this->safeRedirect('index.php?page=forgot-password');
        }

        // Pasar variables a la vista
        $resetData = $tokenData;
        require_once __DIR__ . '/../views/auth/reset_password.php';
    }

    public function processResetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->safeRedirect('index.php?page=forgot-password');
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            $this->safeRedirect('index.php?page=forgot-password');
        }

        // La vista usa name="token" 
        $token           = Security::sanitize($_POST['token'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $validator = new Validator();
        $validator->required($password, 'Contraseña')
                  ->strongPassword($password)
                  ->match($password, $confirmPassword, 'Las contraseñas');

        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            $this->safeRedirect('index.php?page=reset-password&token=' . urlencode($token));
        }

        $passwordReset = new PasswordReset();

        if (!$passwordReset->validateToken($token)) {
            Session::setFlash('error', 'El enlace ha expirado o ya fue usado.');
            $this->safeRedirect('index.php?page=forgot-password');
        }

        if ($passwordReset->resetPassword($token, $password)) {
            Session::setFlash('success', '¡Contraseña restablecida! Ya puedes iniciar sesión.');
            $this->safeRedirect('index.php?page=login');
        } else {
            Session::setFlash('error', 'Error al restablecer la contraseña. Intenta de nuevo.');
            $this->safeRedirect('index.php?page=forgot-password');
        }
    }

    // =========================================================================
    // ENVÍO DE EMAIL CON BREVO API (compatible con Railway)
    // =========================================================================

    private function sendResetEmail($email, $username, $token) {
        $apiKey    = getenv('BREVO_API_KEY');
        $appUrl    = rtrim(getenv('APP_URL'), '/');
        $resetLink = $appUrl . '/index.php?page=reset-password&token=' . urlencode($token);

        $data = json_encode([
            'sender'      => [
                'email' => getenv('EMAIL_USER'),
                'name'  => 'Gestor de Notas'
            ],
            'to'          => [[
                'email' => $email,
                'name'  => $username
            ]],
            'subject'     => 'Recuperación de contraseña - Gestor de Notas',
            'htmlContent' => $this->getEmailTemplate($username, $resetLink),
            'textContent' => "Hola $username,\n\nRecupera tu contraseña aquí:\n$resetLink\n\nEste enlace expira en 30 minutos."
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201) {
            error_log("[EMAIL_SUCCESS] Correo enviado a: $email");
            return true;
        }

        error_log("[EMAIL_ERROR] Brevo respondió: $httpCode - $response");
        return false;
    }

    private function getEmailTemplate($username, $resetLink) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto;'>
            <h2 style='color: #333;'>🔐 Recuperación de Contraseña</h2>
            <p>Hola <strong>" . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
            <p>Haz clic en el siguiente botón para restablecer tu contraseña:</p>
            <p style='text-align: center;'>
                <a href='" . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . "'
                   style='background-color:#4F46E5;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;'>
                   Restablecer contraseña
                </a>
            </p>
            <p style='color:#666; font-size:13px;'>Este enlace expira en 30 minutos. Si no solicitaste esto, ignora este correo.</p>
        </div>
        ";
    }
}
?>