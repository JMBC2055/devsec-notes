<?php
// helpers/Email.php — Versión mínima para desarrollo local

class Email {
    public static function sendResetLink($toEmail, $resetToken) {
        $resetUrl = "http://localhost/devsec-notes/public/index.php?page=reset-password&token=" . urlencode($resetToken);
        
        $subject = 'Recuperación de contraseña - Gestor de Notas';
        $message = "
            ¡Hola!

            Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace:
            
            $resetUrl

            Este enlace expira en 15 minutos.

            Si no solicitaste este cambio, ignóralo.
        ";
        $headers = "From: no-reply@gestor.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // En XAMPP local, mail() no envía realmente, pero no da error fatal
        // Para pruebas, solo simulamos éxito
        return true; // Simulamos que se envió (para que el flujo siga)
    }
}
?>
