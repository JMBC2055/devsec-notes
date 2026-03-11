<?php
// ============================================================================
// UBICACIÓN: gestor-notas/controllers/AuthController.php
// DESCRIPCIÓN: Controlador de autenticación + Recuperación de contraseña
// VERSIÓN: 3.0 - CORREGIDO para Railway con soporte multi-puerto
// ============================================================================

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Validator.php';

// PHPMailer (instalado con Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class AuthController {

    /**
     * Constructor - Verificar regeneración de sesión pendiente
     */
    public function __construct() {
        Security::regenerateIfNeeded();
    }

    /**
     * Redireccionar de forma segura (evita headers already sent)
     * @param string $url
     * @param int $statusCode
     */
    private function safeRedirect($url, $statusCode = 302) {
        // Verificar si ya se enviaron headers
        if (!headers_sent()) {
            header("Location: " . $url, true, $statusCode);
            exit();
        } else {
            // Si ya se enviaron headers, usar JavaScript como fallback
            echo "<script>window.location.href='" . addslashes($url) . "';</script>";
            echo "<noscript>";
            echo "<meta http-equiv='refresh' content='0;url=" . addslashes($url) . "'>";
            echo "</noscript>";
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
            // Regenerar sesión de forma segura
            Security::regenerateSession(true);
            
            // Establecer datos de sesión
            Session::set('user_id',  $userData['id']);
            Session::set('username', $userData['username']);
            Session::set('email',    $userData['email']);
            
            // Mensaje flash
            Session::setFlash('success', '¡Bienvenido, ' . $userData['username'] . '!');
            
            // Redireccionar
            $this->safeRedirect('index.php?page=dashboard');
        } else {
            Session::setFlash('error', 'Credenciales incorrectas o cuenta bloqueada');
            $this->safeRedirect('index.php?page=login');
        }
    }

    public function logout() {
        // Destruir sesión de forma segura
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

        // Mensaje genérico — no revela si el email existe
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
    // EMAIL CON PHPMAILER + BREVO SMTP - VERSIÓN CORREGIDA
    // =========================================================================

    /**
     * Enviar email de recuperación usando PHPMailer + Brevo
     * Soporta auto-detección de puerto (587/TLS o 465/SSL)
     */
    private function sendResetEmail($email, $username, $token) {
        // Autoload de Composer (PHPMailer)
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log("[EMAIL] vendor/autoload.php no encontrado. ¿Ejecutaste composer install?");
            return false;
        }
        require_once $autoload;

        // Obtener configuración de variables de entorno
        $smtpHost = getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com';
        $smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
        $smtpUser = getenv('SMTP_USER') ?: '';
        $smtpPass = getenv('SMTP_PASS') ?: '';
        $smtpFrom = getenv('SMTP_FROM') ?: ($smtpUser ?: 'noreply@gestor.local');
        
        // URL base de la app
        $appUrl    = rtrim(getenv('APP_URL') ?: 'http://localhost/gestor-notas/public', '/');
        $resetLink = $appUrl . '/index.php?page=reset-password&token=' . urlencode($token);

        // Determinar encriptación según el puerto
        $encryption = PHPMailer::ENCRYPTION_STARTTLS; // TLS para puerto 587
        if ($smtpPort == 465) {
            $encryption = PHPMailer::ENCRYPTION_SMTPS; // SSL para puerto 465
        }

        // Log para depuración
        error_log("[EMAIL_DEBUG] Configuración: Host=$smtpHost, Port=$smtpPort, User=$smtpUser, Encryption=" . ($encryption == PHPMailer::ENCRYPTION_SMTPS ? 'SSL' : 'TLS'));

        try {
            $mail = new PHPMailer(true);
            
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = $encryption;
            $mail->Port       = $smtpPort;
            $mail->CharSet    = 'UTF-8';
            
            // Timeout más largo para Railway
            $mail->Timeout = 30;
            $mail->SMTPKeepAlive = true;
            
            // Opciones SSL (importante para Railway)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Remitente y destinatario
            $mail->setFrom($smtpFrom, 'Gestor de Notas Seguro');
            $mail->addAddress($email, $username);
            $mail->addReplyTo($smtpFrom, 'Gestor de Notas Seguro');

            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña - Gestor de Notas';
            
            // Cuerpo HTML mejorado
            $mail->Body    = $this->getEmailTemplate($username, $resetLink);
            
            // Versión texto plano
            $mail->AltBody = "Hola $username,\n\n" .
                "Has solicitado restablecer tu contraseña.\n\n" .
                "Haz clic en este enlace (válido por 30 minutos):\n" .
                "$resetLink\n\n" .
                "Si no solicitaste esto, ignora este mensaje.\n\n" .
                "Saludos,\nEl equipo de Gestor de Notas Seguro";

            $mail->send();
            error_log("[EMAIL_SUCCESS] Correo enviado a: $email");
            return true;

        } catch (Exception $e) {
            $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
            error_log("[EMAIL_ERROR] No se pudo enviar a $email: " . $errorMsg);
            error_log("[EMAIL_DEBUG] Host: $smtpHost, Puerto: $smtpPort");
            return false;
        }
    }

    /**
     * Template HTML para email de recuperación
     */
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
                                    
                                    <!-- Divider -->
                                    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
                                    
                                    <!-- Info -->
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