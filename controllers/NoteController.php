<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/controllers/NoteController.php
// DESCRIPCIÓN: Controlador de notas (CRUD completo)
// ============================================================================

require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Validator.php';

class NoteController {
    
    /**
     * Verificar autenticación
     */
    private function checkAuth() {
        if (!Session::isAuthenticated()) {
            header('Location: /devsec-notes/public/index.php?page=login');
            exit;
        }
    }
    
    /**
     * Dashboard - Mostrar todas las notas
     */
    public function index() {
        $this->checkAuth();
        
        $userId = Session::get('user_id');
        $note = new Note();
        $notes = $note->getAllByUser($userId, false);
        
        require_once __DIR__ . '/../views/notes/index.php';
    }
    
    /**
     * Mostrar formulario de crear nota
     */
    public function create() {
        $this->checkAuth();
        require_once __DIR__ . '/../views/notes/create.php';
    }
    
    /**
     * Guardar nueva nota
     */
    public function store() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
        
        // Validar CSRF
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: /devsec-notes/public/index.php?page=create-note');
            exit;
        }
        
        try {
            // Sanitizar datos
            $title = Security::sanitize($_POST['title'] ?? '');
            $content = Security::sanitize($_POST['content'] ?? '');
            $isFavorite = isset($_POST['is_favorite']) ? 1 : 0;
            $reminderDate = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : null;
            
            // Validar
            $validator = new Validator();
            $validator->required($title, 'Título')
                      ->maxLength($title, 200, 'Título')
                      ->required($content, 'Contenido')
                      ->cleanText($content, 'Contenido'); // === NUEVO: validación de texto limpio ===
            
            // Validar formato de fecha si existe
            if (!empty($reminderDate)) {
                $validator->date($reminderDate, 'Y-m-d', 'Fecha de recordatorio');
            }
            
            if ($validator->fails()) {
                Session::setFlash('error', $validator->getFirstError());
                header('Location: /devsec-notes/public/index.php?page=create-note');
                exit;
            }
            
            // Crear nota
            $note = new Note();
            $note->user_id = Session::get('user_id');
            $note->title = $title;
            $note->content = $content;
            $note->is_favorite = $isFavorite;
            $note->reminder_date = $reminderDate;
            
            if ($note->create()) {
                Session::setFlash('success', 'Nota creada exitosamente');
                header('Location: /devsec-notes/public/index.php?page=dashboard');
            } else {
                Session::setFlash('error', 'Error al crear la nota');
                header('Location: /devsec-notes/public/index.php?page=create-note');
            }
            exit;
            
        } catch (Exception $e) {
            // === NUEVO: manejo de errores controlado (punto 7 del PDF) ===
            error_log("Error en NoteController::store(): " . $e->getMessage());
            Session::setFlash('error', 'Error interno del servidor. Por favor intenta más tarde.');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
    }
    
    /**
     * Mostrar formulario de editar nota
     */
    public function edit() {
        $this->checkAuth();
        
        $noteId = $_GET['id'] ?? 0;
        $userId = Session::get('user_id');
        
        $noteModel = new Note();
        $note = $noteModel->getById($noteId, $userId);
        
        if (!$note) {
            // === NUEVO: log de intento fallido de acceso (punto 8 del PDF) ===
            error_log("Intento de acceso no autorizado a nota ID $noteId por usuario ID $userId");
            Session::setFlash('error', 'Nota no encontrada');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
        
        require_once __DIR__ . '/../views/notes/edit.php';
    }
    
    /**
     * Actualizar nota
     */
    public function update() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
        
        // Validar CSRF
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
        
        try {
            $noteId = $_POST['note_id'] ?? 0;
            $userId = Session::get('user_id');
            
            // Sanitizar datos
            $title = Security::sanitize($_POST['title'] ?? '');
            $content = Security::sanitize($_POST['content'] ?? '');
            $isFavorite = isset($_POST['is_favorite']) ? 1 : 0;
            $reminderDate = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : null;
            
            // Validar
            $validator = new Validator();
            $validator->required($title, 'Título')
                      ->maxLength($title, 200, 'Título')
                      ->required($content, 'Contenido')
                      ->cleanText($content, 'Contenido'); // === NUEVO: validación de texto limpio ===
            
            // Validar formato de fecha si existe
            if (!empty($reminderDate)) {
                $validator->date($reminderDate, 'Y-m-d', 'Fecha de recordatorio');
            }
            
            if ($validator->fails()) {
                Session::setFlash('error', $validator->getFirstError());
                header('Location: /devsec-notes/public/index.php?page=edit-note&id=' . $noteId);
                exit;
            }
            
            // Actualizar nota
            $note = new Note();
            $note->title = $title;
            $note->content = $content;
            $note->is_favorite = $isFavorite;
            $note->reminder_date = $reminderDate;
            
            if ($note->update($noteId, $userId)) {
                Session::setFlash('success', 'Nota actualizada exitosamente');
                header('Location: /devsec-notes/public/index.php?page=dashboard');
            } else {
                Session::setFlash('error', 'Error al actualizar la nota');
                header('Location: /devsec-notes/public/index.php?page=edit-note&id=' . $noteId);
            }
            exit;
            
        } catch (Exception $e) {
            // === NUEVO: manejo de errores controlado (punto 7 del PDF) ===
            error_log("Error en NoteController::update(): " . $e->getMessage());
            Session::setFlash('error', 'Error interno del servidor. Por favor intenta más tarde.');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
    }
    
    /**
     * Eliminar nota
     */
    public function delete() {
        $this->checkAuth();
        
        try {
            $noteId = $_GET['id'] ?? 0;
            $userId = Session::get('user_id');
            
            $note = new Note();
            if ($note->delete($noteId, $userId)) {
                Session::setFlash('success', 'Nota eliminada exitosamente');
            } else {
                // === NUEVO: log de intento fallido de eliminación (punto 8 del PDF) ===
                error_log("Intento fallido de eliminación de nota ID $noteId por usuario ID $userId");
                Session::setFlash('error', 'Error al eliminar la nota');
            }
            
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
            
        } catch (Exception $e) {
            // === NUEVO: manejo de errores controlado (punto 7 del PDF) ===
            error_log("Error en NoteController::delete(): " . $e->getMessage());
            Session::setFlash('error', 'Error interno del servidor. Por favor intenta más tarde.');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
    }
    
    /**
     * Archivar nota
     */
    public function archive() {
        $this->checkAuth();
        
        try {
            $noteId = $_GET['id'] ?? 0;
            $userId = Session::get('user_id');
            
            $note = new Note();
            if ($note->toggleArchive($noteId, $userId, true)) {
                Session::setFlash('success', 'Nota archivada');
            } else {
                Session::setFlash('error', 'Error al archivar la nota');
            }
            
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
            
        } catch (Exception $e) {
            // === NUEVO: manejo de errores controlado (punto 7 del PDF) ===
            error_log("Error en NoteController::archive(): " . $e->getMessage());
            Session::setFlash('error', 'Error interno del servidor. Por favor intenta más tarde.');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
    }
    
    /**
     * Buscar notas
     */
    public function search() {
        $this->checkAuth();
        
        try {
            $searchTerm = Security::sanitize($_GET['q'] ?? '');
            $userId = Session::get('user_id');
            
            $note = new Note();
            $notes = $note->search($userId, $searchTerm);
            
            require_once __DIR__ . '/../views/notes/search.php';
            
        } catch (Exception $e) {
            // === NUEVO: manejo de errores controlado (punto 7 del PDF) ===
            error_log("Error en NoteController::search(): " . $e->getMessage());
            Session::setFlash('error', 'Error interno del servidor. Por favor intenta más tarde.');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }
    }
}
?>
