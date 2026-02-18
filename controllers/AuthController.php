<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/controllers/AuthController.php
// DESCRIPCIÓN: Controlador de autenticación (Login/Registro/Logout)
// ============================================================================

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Validator.php';

class AuthController {
    
    /**
     * Mostrar formulario de registro
     */
    public function showRegister() {
        // Si ya está autenticado, redirigir al dashboard
        if (Session::isAuthenticated()) {
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
        
        require_once __DIR__ . '/../views/auth/register.php';
    }
    
    /**
     * Procesar registro de usuario
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /devsec-notes/public/index.php?page=register');
            exit;
        }
        
        // Validar CSRF
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: /devsec-notes/public/index.php?page=register');
            exit;
        }
        
        // Sanitizar datos
        $username = Security::sanitize($_POST['username'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validar datos
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
            header('Location: /devsec-notes/public/index.php?page=register');
            exit;
        }
        
        // Verificar si email ya existe
        $user = new User();
        if ($user->emailExists($email)) {
            Session::setFlash('error', 'El email ya está registrado');
            header('Location: /devsec-notes/public/index.php?page=register');
            exit;
        }
        
        // Verificar si username ya existe
        if ($user->usernameExists($username)) {
            Session::setFlash('error', 'El nombre de usuario ya está en uso');
            header('Location: /devsec-notes/public/index.php?page=register');
            exit;
        }
        
        // Registrar usuario
        $user->username = $username;
        $user->email = $email;
        $user->password = $password;
        
        if ($user->register()) {
            Session::setFlash('success', '¡Registro exitoso! Ahora puedes iniciar sesión');
            header('Location: /devsec-notes/public/index.php?page=login');
        } else {
            Session::setFlash('error', 'Error al registrar usuario. Intenta de nuevo');
            header('Location: /devsec-notes/public/index.php?page=register');
        }
        exit;
    }
    
    /**
     * Mostrar formulario de login
     */
    public function showLogin() {
        // Si ya está autenticado, redirigir al dashboard
        if (Session::isAuthenticated()) {
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
        
        require_once __DIR__ . '/../views/auth/login.php';
    }
    
    /**
     * Procesar login de usuario
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /devsec-notes/public/index.php?page=login');
            exit;
        }
        
        // Validar CSRF
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: /devsec-notes/public/index.php?page=login');
            exit;
        }
        
        // Sanitizar datos
        $email = Security::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validar datos
        $validator = new Validator();
        $validator->required($email, 'Email')
                  ->email($email, 'Email')
                  ->required($password, 'Contraseña');
        
        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            header('Location: /devsec-notes/public/index.php?page=login');
            exit;
        }
        
        // Intentar login
        $user = new User();
        $user->email = $email;
        $user->password = $password;
        
        $userData = $user->login();
        
        if ($userData) {
            // Regenerar sesión para prevenir session fixation
            Security::regenerateSession();
            
            // Guardar datos en sesión
            Session::set('user_id', $userData['id']);
            Session::set('username', $userData['username']);
            Session::set('email', $userData['email']);
            Session::set('role', $userData['role']); // === NUEVO: Almacenar el rol en sesión ===
            
            // === CORRECCIÓN XSS 18/02/2026 ===
            // Sanitizar username antes de mostrar en flash message (previene XSS)
            $safeUsername = Security::sanitize($userData['username']);
            Session::setFlash('success', '¡Bienvenido, ' . $safeUsername . '!');
            // ==============================
            
            header('Location: /devsec-notes/public/index.php?page=dashboard');
        } else {
            Session::setFlash('error', 'Credenciales incorrectas o cuenta bloqueada');
            header('Location: /devsec-notes/public/index.php?page=login');
        }
        exit;
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        Session::destroy();
        header('Location: /devsec-notes/public/index.php?page=login');
        exit;
    }
}
?>
/**
 * === CAMBIO SEGURIDAD: ALMACENAMIENTO DE ROL EN SESIÓN (Punto 5 del PDF) ===
 * Fecha: 18/02/2026
 * Autor: [TU NOMBRE AQUÍ]
 * Descripción:
 *   - Agregada línea Session::set('role', $userData['role']) para almacenar el rol en sesión
 *   - Esto permite validar permisos por rol en otros controladores
 *   - Cumple punto 5 del PDF: autenticación y autorización claras (roles/permisos)
 * Reversión: Eliminar línea Session::set('role', $userData['role']) si es necesario
 */
