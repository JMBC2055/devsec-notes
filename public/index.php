<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/public/index.php
// DESCRIPCIÓN: Punto de entrada principal del sistema
// ============================================================================

// Iniciar sesión
require_once __DIR__ . '/../helpers/Session.php';
Session::start();

// Cargar controladores
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/NoteController.php';
require_once __DIR__ . '/../controllers/PasswordController.php'; // === NUEVO ===

// Obtener página solicitada
$page = $_GET['page'] ?? 'login';

// Enrutamiento simple
switch ($page) {
    
    // ==================== AUTENTICACIÓN ====================
    case 'register':
        $controller = new AuthController();
        $controller->showRegister();
        break;
    
    case 'register-process':
        $controller = new AuthController();
        $controller->register();
        break;
    
    case 'login':
        $controller = new AuthController();
        $controller->showLogin();
        break;
    
    case 'login-process':
        $controller = new AuthController();
        $controller->login();
        break;
    
    case 'logout':
        $controller = new AuthController();
        $controller->logout();
        break;
    
    // ==================== RECUPERACIÓN DE CONTRASEÑA ====================
    case 'forgot-password':
        $controller = new PasswordController();
        $controller->showForgotForm();
        break;

    case 'request-reset':
        $controller = new PasswordController();
        $controller->requestReset();
        break;

    case 'reset-password':
        $controller = new PasswordController();
        $controller->showResetForm();
        break;

    case 'reset-password-process':
        $controller = new PasswordController();
        $controller->resetPassword();
        break;
    
    // ==================== NOTAS ====================
    case 'dashboard':
        $controller = new NoteController();
        $controller->index();
        break;
    
    case 'create-note':
        $controller = new NoteController();
        $controller->create();
        break;
    
    case 'store-note':
        $controller = new NoteController();
        $controller->store();
        break;
    
    case 'edit-note':
        $controller = new NoteController();
        $controller->edit();
        break;
    
    case 'update-note':
        $controller = new NoteController();
        $controller->update();
        break;
    
    case 'delete-note':
        $controller = new NoteController();
        $controller->delete();
        break;
    
    case 'archive-note':
        $controller = new NoteController();
        $controller->archive();
        break;
    
    case 'search-notes':
        $controller = new NoteController();
        $controller->search();
        break;
    
    // ==================== DEFAULT ====================
    default:
        // Si no está autenticado, mostrar login
        if (!Session::isAuthenticated()) {
            $controller = new AuthController();
            $controller->showLogin();
        } else {
            // Si está autenticado, mostrar dashboard
            $controller = new NoteController();
            $controller->index();
        }
        break;
}
?>
