<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/Email.php';

class PasswordController {
    
    public function showForgotForm() {
        if (Session::isAuthenticated()) {
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/auth/forgot-password.php';
    }
    
    public function requestReset() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /devsec-notes/public/index.php?page=forgot-password');
            exit;
        }
        
        $email = $_POST['email'] ?? '';
        $validator = new Validator();
        $validator->required($email, 'Email')->email($email, 'Email');
        
        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            header('Location: /devsec-notes/public/index.php?page=forgot-password');
            exit;
        }
        
        $user = new User();
        $token = $user->generateResetToken($email);
        
        if ($token) {
            $emailService = new Email();
            if ($emailService->sendResetLink($email, $token)) {
                Session::setFlash('success', 'Se ha enviado un enlace de recuperación a tu email.');
            } else {
                Session::setFlash('error', 'Error al enviar el email. Intenta más tarde.');
            }
        } else {
            // No revelar si el email existe (seguridad)
            Session::setFlash('success', 'Se ha enviado un enlace de recuperación a tu email.');
        }
        
        header('Location: /devsec-notes/public/index.php?page=forgot-password');
        exit;
    }
    
    public function showResetForm() {
        if (Session::isAuthenticated()) {
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
        
        $token = $_GET['token'] ?? '';
        $user = new User();
        $userData = $user->validateResetToken($token);
        
        if (!$userData) {
            Session::setFlash('error', 'Token inválido o expirado.');
            header('Location: /devsec-notes/public/index.php?page=login');
            exit;
        }
        
        require_once __DIR__ . '/../views/auth/reset-password.php';
    }
    
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /devsec-notes/public/index.php?page=login');
            exit;
        }
        
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $validator = new Validator();
        $validator->required($password, 'Contraseña')
                  ->strongPassword($password)
                  ->match($password, $confirmPassword, 'Las contraseñas');
        
        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            header('Location: /devsec-notes/public/index.php?page=reset-password&token=' . urlencode($token));
            exit;
        }
        
        $user = new User();
        $userData = $user->validateResetToken($token);
        
        if (!$userData) {
            Session::setFlash('error', 'Token inválido.');
            header('Location: /devsec-notes/public/index.php?page=login');
            exit;
        }
        
        if ($user->resetPassword($userData['id'], $password)) {
            Session::setFlash('success', 'Contraseña restablecida exitosamente. Ahora puedes iniciar sesión.');
            header('Location: /devsec-notes/public/index.php?page=login');
        } else {
            Session::setFlash('error', 'Error al restablecer la contraseña.');
            header('Location: /devsec-notes/public/index.php?page=reset-password&token=' . urlencode($token));
        }
        exit;
    }
}
?>
