<?php
// ============================================================================
// UBICACIÓN: gestor-notas/controllers/AuthController.php
// DESCRIPCIÓN: Controlador de autenticación + Recuperación de contraseña
// VERSIÓN: 4.4 - USA cURL DIRECTO (sin SDK, compatible con PHP < 8.1)
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
    // EMAIL - cURL DIRECTO A RESEND API (sin SDK, sin composer, PHP < 8.1 ok)
    // =========================================================================

    private function sendResetEmail($email, $username, $token) {
        $apiKey = getenv('RESEND_API_KEY');
        if (!$apiKey) {
            error_log("[EMAIL_ERROR] RESEND_API_KEY no configurada");
            return false;
        }

        $appUrl    = rtrim(getenv('APP_URL') ?: 'http://localhost/gestor-notas/public', '/');
        $resetLink = $appUrl . '/index.php?page=reset-password&token=' . urlencode($token);

        error_log("[EMAIL_DEBUG] Enviando con Resend (cURL directo) a: $email");

        $payload = json_encode([
            'from'    => 'Gestor de Notas <onboarding@resend.dev>',
            'to'      => [$email],
            'subject' => 'Recuperación de contraseña - Gestor de Notas',
            'html'    => $this->getEmailTemplate($username, $resetLink),
            'text'    => "Hola $username,\n\nEnlace para restablecer tu contraseña (válido 30 minutos):\n$resetLink\n\nSi no solicitaste esto, ignora este email.",
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("[EMAIL_ERROR] cURL error: $curlError");
            return false;
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 || $httpCode === 201) {
            error_log("[EMAIL_SUCCESS] Correo enviado a: $email - ID: " . ($data['id'] ?? 'desconocido'));
            return true;
        } else {
            error_log("[EMAIL_ERROR] Resend HTTP $httpCode: $response");
            return false;
        }
    }

    private function getEmailTemplate($username, $resetLink) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f7;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f7; padding: 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <!-- Header -->
                            <tr>
                                <td style="padding: 30px 30px 20px 30px; text-align: center; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border-radius: 8px 8px 0 0;">
                                    <h1 style="color: #ffffff; margin: 0; font-size: 24px;">🔐 Recuperación de Contraseña</h1>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px;">
                                    <p style="font-size: 16px; color: #374151; margin: 0 0 15px 0;">Hola <strong style="color: #4f46e5;">' . htmlspecialchars($username) . '</strong>,</p>
                                    <p style="font-size: 16px; color: #374151; margin: 0 0 20px 0;">Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <strong>Gestor de Notas Seguro</strong>.</p>
                                    <p style="font-size: 16px; color: #374151; margin: 0 0 25px 0;">Haz clic en el siguiente botón para continuar:</p>

                                    <!-- Button -->
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td align="center" style="padding: 10px 0 30px 0;">
                                                <a href="' . $resetLink . '" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; padding: 14px 34px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 16px; display: inline-block;">Restablecer Contraseña</a>
                                            </td>
                                        </tr>
                                    </table>

                                    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">

                                    <p style="font-size: 14px; color: #6b7280; margin: 20px 0 10px 0;">
                                        ⏰ Este enlace expirará en <strong>30 minutos</strong> por seguridad.
                                    </p>
                                    <p style="font-size: 14px; color: #6b7280; margin: 10px 0 20px 0;">
                                        Si el botón no funciona, copia y pega este enlace en tu navegador:
                                    </p>
                                    <p style="background-color: #f3f4f6; padding: 12px; border-radius: 4px; font-size: 12px; color: #1f2937; word-break: break-all; margin: 0;">
                                        ' . $resetLink . '
                                    </p>
                                    <p style="font-size: 14px; color: #6b7280; margin: 25px 0 0 0;">
                                        Si no solicitaste este cambio, puedes ignorar este mensaje. Tu cuenta está segura.
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="padding: 20px 30px; text-align: center; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb;">
                                    <p style="font-size: 14px; color: #9ca3af; margin: 0;">
                                        &copy; ' . date('Y') . ' Gestor de Notas Seguro. Todos los derechos reservados.
                                    </p>
                                    <p style="font-size: 12px; color: #9ca3af; margin: 10px 0 0 0;">
                                        Este es un mensaje automático, por favor no respondas a este correo.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}
?>