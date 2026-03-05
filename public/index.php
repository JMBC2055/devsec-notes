<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/public/index.php
// DESCRIPCIÓN: Punto de entrada principal del sistema
// ============================================================================

require_once __DIR__ . '/../helpers/Session.php';
Session::start();

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/NoteController.php';

$page = $_GET['page'] ?? 'login';

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
        $controller = new AuthController();
        $controller->showForgotPassword();
        break;

    case 'forgot-password-process':
        $controller = new AuthController();
        $controller->processForgotPassword();
        break;

    case 'reset-password':
        $controller = new AuthController();
        $controller->showResetPassword();
        break;

    case 'reset-password-process':
        $controller = new AuthController();
        $controller->processResetPassword();
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

    // ==================== TAGS ====================
    case 'manage-tags':
        $controller = new NoteController();
        $controller->manageTags();
        break;

    case 'store-tag':
        $controller = new NoteController();
        $controller->storeTag();
        break;

    case 'delete-tag':
        $controller = new NoteController();
        $controller->deleteTag();
        break;

    // ==================== DEFAULT ====================
    default:
        if (!Session::isAuthenticated()) {
            $controller = new AuthController();
            $controller->showLogin();
        } else {
            $controller = new NoteController();
            $controller->index();
        }
        break;
}
?>
