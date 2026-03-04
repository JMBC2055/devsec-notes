<?php
// C:\xampp\htdocs\devsec-notes\helpers\Email.php
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Email {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = 'TU_GMAIL@gmail.com';      // ← CAMBIA ESTO
        $this->mail->Password   = 'APP_PASSWORD';           // ← CAMBIA ESTO (contraseña de app)
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;
        $this->mail->setFrom('TU_GMAIL@gmail.com', 'Gestor de Notas');
        $this>
        $this->mail->isHTML(true);
        $this->mail->CharSet = 'UTF-8';
    }
    
    public function sendResetLink($toEmail, $resetToken) {
        $resetUrl = "http://localhost/devsec-notes/public/index.php?page=reset-password&token=" . urlencode($resetToken);
        
        $this->mail->addAddress($toEmail);
        $this->mail->Subject = 'Recuperación de contraseña - Gestor de Notas';
        $this->mail->Body    = "
            <h2>¿Olvidaste tu contraseña?</h2>
            <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
            <p><a href='$resetUrl' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Restablecer contraseña</a></p>
            <p>Este enlace expira en 15 minutos.</p>
            <p>Si no solicitaste este cambio, ignora este mensaje.</p>
        ";
        
        return $this->mail->send();
    }
}
?>
