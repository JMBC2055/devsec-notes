<?php
// ============================================================================
// UBICACIÓN: gestor-notas/controllers/AuthController.php
// DESCRIPCIÓN: Controlador de autenticación + Recuperación de contraseña
//              Email enviado con PHPMailer + Brevo SMTP (funciona en Railway)
// ============================================================================

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Validator.php';

// PHPMailer (instalado con Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController {

    // =========================================================================
    // REGISTRO
    // =========================================================================

    public function showRegister() {
        if (Session::isAuthenticated()) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/auth/register.php';
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=register');
            exit;
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: index.php?page=register');
            exit;
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
            header('Location: index.php?page=register');
            exit;
        }

        $user = new User();
        if ($user->emailExists($email)) {
            Session::setFlash('error', 'El email ya está registrado');
            header('Location: index.php?page=register');
            exit;
        }
        if ($user->usernameExists($username)) {
            Session::setFlash('error', 'El nombre de usuario ya está en uso');
            header('Location: index.php?page=register');
            exit;
        }

        $user->username = $username;
        $user->email    = $email;
        $user->password = $password;

        if ($user->register()) {
            Session::setFlash('success', '¡Registro exitoso! Ahora puedes iniciar sesión');
            header('Location: index.php?page=login');
        } else {
            Session::setFlash('error', 'Error al registrar usuario. Intenta de nuevo');
            header('Location: index.php?page=register');
        }
        exit;
    }

    // =========================================================================
    // LOGIN / LOGOUT
    // =========================================================================

    public function showLogin() {
        if (Session::isAuthenticated()) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=login');
            exit;
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: index.php?page=login');
            exit;
        }

        $email    = Security::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $validator = new Validator();
        $validator->required($email, 'Email')
                  ->email($email, 'Email')
                  ->required($password, 'Contraseña');

        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            header('Location: index.php?page=login');
            exit;
        }

        $user           = new User();
        $user->email    = $email;
        $user->password = $password;
        $userData       = $user->login();

        if ($userData) {
            Security::regenerateSession();
            Session::set('user_id',  $userData['id']);
            Session::set('username', $userData['username']);
            Session::set('email',    $userData['email']);
            Session::setFlash('success', '¡Bienvenido, ' . $userData['username'] . '!');
            header('Location: index.php?page=dashboard');
        } else {
            Session::setFlash('error', 'Credenciales incorrectas o cuenta bloqueada');
            header('Location: index.php?page=login');
        }
        exit;
    }

    public function logout() {
        Session::destroy();
        header('Location: index.php?page=login');
        exit;
    }

    // =========================================================================
    // RECUPERACIÓN DE CONTRASEÑA
    // =========================================================================

    public function showForgotPassword() {
        if (Session::isAuthenticated()) {
            header('Location: index.php?page=dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/auth/forgot_password.php';
    }

    public function processForgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=forgot-password');
            exit;
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: index.php?page=forgot-password');
            exit;
        }

        $email = Security::sanitize($_POST['email'] ?? '');

        $validator = new Validator();
        $validator->required($email, 'Email')->email($email, 'Email');

        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            header('Location: index.php?page=forgot-password');
            exit;
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

        // Mensaje genérico — no revela si el email existe
        Session::setFlash('success', 'Si el email está registrado, recibirás un enlace en breve. Revisa también tu carpeta de spam.');
        header('Location: index.php?page=forgot-password');
        exit;
    }

    public function showResetPassword() {
        $token = Security::sanitize($_GET['token'] ?? '');

        if (empty($token)) {
            Session::setFlash('error', 'Token inválido');
            header('Location: index.php?page=forgot-password');
            exit;
        }

        $passwordReset = new PasswordReset();
        $tokenData     = $passwordReset->validateToken($token);

        if (!$tokenData) {
            Session::setFlash('error', 'El enlace ha expirado o ya fue usado. Solicita uno nuevo.');
            header('Location: index.php?page=forgot-password');
            exit;
        }

        require_once __DIR__ . '/../views/auth/reset_password.php';
    }

    public function processResetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=forgot-password');
            exit;
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: index.php?page=forgot-password');
            exit;
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
            header('Location: index.php?page=reset-password&token=' . urlencode($token));
            exit;
        }

        $passwordReset = new PasswordReset();
        if (!$passwordReset->validateToken($token)) {
            Session::setFlash('error', 'El enlace ha expirado o ya fue usado.');
            header('Location: index.php?page=forgot-password');
            exit;
        }

        if ($passwordReset->resetPassword($token, $password)) {
            Session::setFlash('success', '¡Contraseña restablecida! Ya puedes iniciar sesión.');
            header('Location: index.php?page=login');
        } else {
            Session::setFlash('error', 'Error al restablecer la contraseña. Intenta de nuevo.');
            header('Location: index.php?page=forgot-password');
        }
        exit;
    }

    // =========================================================================
    // EMAIL CON PHPMAILER + BREVO SMTP
    // =========================================================================

    /**
     * Enviar email de recuperación usando PHPMailer + Brevo
     * Variables de entorno necesarias en Railway:
     *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM, APP_URL
     */
    private function sendResetEmail($email, $username, $token) {

        // Autoload de Composer (PHPMailer)
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log("[EMAIL] vendor/autoload.php no encontrado. ¿Ejecutaste composer install?");
            return false;
        }
        require_once $autoload;

        // URL base de la app (en Railway la configuras como variable APP_URL)
        $appUrl    = rtrim(getenv('APP_URL') ?: 'http://localhost/gestor-notas/public', '/');
        $resetLink = $appUrl . '/index.php?page=reset-password&token=' . urlencode($token);

        try {
            $mail = new PHPMailer(true);

            // Configuración SMTP desde variables de entorno
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER') ?: '';
            $mail->Password   = getenv('SMTP_PASS') ?: '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)(getenv('SMTP_PORT') ?: 587);
            $mail->CharSet    = 'UTF-8';

            // Remitente y destinatario
            $fromEmail = getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: 'noreply@gestor.local');
            $mail->setFrom($fromEmail, 'Gestor de Notas Seguro');
            $mail->addAddress($email, $username);

            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña - Gestor de Notas';
            $mail->Body    = "
                <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;padding:24px;'>
                    <h2 style='color:#1a1a2e;'>🔑 Recuperar contraseña</h2>
                    <p>Hola <strong>{$username}</strong>,</p>
                    <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                    <p style='margin:24px 0;'>
                        <a href='{$resetLink}'
                           style='background:#4f46e5;color:#fff;padding:12px 24px;
                                  border-radius:6px;text-decoration:none;font-weight:bold;'>
                            Restablecer contraseña
                        </a>
                    </p>
                    <p style='color:#666;font-size:0.9em;'>
                        Este enlace es válido por <strong>30 minutos</strong>.<br>
                        Si no solicitaste esto, ignora este email.
                    </p>
                    <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'>
                    <p style='color:#999;font-size:0.8em;'>
                        O copia y pega este enlace en tu navegador:<br>
                        <small>{$resetLink}</small>
                    </p>
                </div>
            ";
            $mail->AltBody = "Hola $username,\n\nEnlace para restablecer tu contraseña (válido 30 minutos):\n$resetLink\n\nSi no solicitaste esto, ignora este email.";

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("[EMAIL_ERROR] No se pudo enviar a $email: " . $e->getMessage());
            return false;
        }
    }
}
?>
