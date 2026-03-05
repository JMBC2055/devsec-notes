<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/controllers/NoteController.php
// DESCRIPCIÓN: Controlador de notas (CRUD completo) + sistema de tags
// ============================================================================

require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Tag.php';
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
        $note   = new Note();
        $tag    = new Tag();

        $notes    = $note->getAllByUser($userId, false);
        $allTags  = $tag->getAllByUser($userId);

        // Adjuntar tags a cada nota
        foreach ($notes as &$n) {
            $n['tags'] = $tag->getByNote($n['id']);
        }
        unset($n);

        // Filtrar por tag si se pasa ?tag_id=X
        $filterTagId = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : null;
        $filterTag   = null;
        if ($filterTagId) {
            $notes = $tag->getNotesByTag($userId, $filterTagId);
            foreach ($notes as &$n) {
                $n['tags'] = $tag->getByNote($n['id']);
            }
            unset($n);
            // Buscar nombre del tag activo
            foreach ($allTags as $t) {
                if ($t['id'] == $filterTagId) {
                    $filterTag = $t;
                    break;
                }
            }
        }

        require_once __DIR__ . '/../views/notes/index.php';
    }

    /**
     * Mostrar formulario de crear nota
     */
    public function create() {
        $this->checkAuth();
        $userId  = Session::get('user_id');
        $tag     = new Tag();
        $allTags = $tag->getAllByUser($userId);
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

        $title        = Security::sanitize($_POST['title'] ?? '');
        $content      = Security::sanitize($_POST['content'] ?? '');
        $isFavorite   = isset($_POST['is_favorite']) ? 1 : 0;
        $reminderDate = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : null;
        $tagIds       = $_POST['tag_ids'] ?? [];

        $validator = new Validator();
        $validator->required($title, 'Título')
                  ->maxLength($title, 200, 'Título')
                  ->required($content, 'Contenido');

        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            header('Location: /devsec-notes/public/index.php?page=create-note');
            exit;
        }

        $note              = new Note();
        $note->user_id     = Session::get('user_id');
        $note->title       = $title;
        $note->content     = $content;
        $note->is_favorite = $isFavorite;
        $note->reminder_date = $reminderDate;

        $noteId = $note->create();

        if ($noteId) {
            // Guardar tags seleccionados
            if (!empty($tagIds)) {
                $tag = new Tag();
                $tag->syncToNote($noteId, $tagIds);
            }
            Session::setFlash('success', 'Nota creada exitosamente');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
        } else {
            Session::setFlash('error', 'Error al crear la nota');
            header('Location: /devsec-notes/public/index.php?page=create-note');
        }
        exit;
    }

    /**
     * Mostrar formulario de editar nota
     */
    public function edit() {
        $this->checkAuth();

        $noteId = $_GET['id'] ?? 0;
        $userId = Session::get('user_id');

        $noteModel = new Note();
        $note      = $noteModel->getById($noteId, $userId);

        if (!$note) {
            Session::setFlash('error', 'Nota no encontrada');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }

        $tag         = new Tag();
        $allTags     = $tag->getAllByUser($userId);
        $noteTags    = $tag->getByNote($noteId);
        $noteTagIds  = array_column($noteTags, 'id');

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

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }

        $noteId       = $_POST['note_id'] ?? 0;
        $userId       = Session::get('user_id');
        $title        = Security::sanitize($_POST['title'] ?? '');
        $content      = Security::sanitize($_POST['content'] ?? '');
        $isFavorite   = isset($_POST['is_favorite']) ? 1 : 0;
        $reminderDate = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : null;
        $tagIds       = $_POST['tag_ids'] ?? [];

        $validator = new Validator();
        $validator->required($title, 'Título')
                  ->maxLength($title, 200, 'Título')
                  ->required($content, 'Contenido');

        if ($validator->fails()) {
            Session::setFlash('error', $validator->getFirstError());
            header('Location: /devsec-notes/public/index.php?page=edit-note&id=' . $noteId);
            exit;
        }

        $note              = new Note();
        $note->title       = $title;
        $note->content     = $content;
        $note->is_favorite = $isFavorite;
        $note->reminder_date = $reminderDate;

        if ($note->update($noteId, $userId)) {
            // Sincronizar tags
            $tag = new Tag();
            $tag->syncToNote($noteId, $tagIds);

            Session::setFlash('success', 'Nota actualizada exitosamente');
            header('Location: /devsec-notes/public/index.php?page=dashboard');
        } else {
            Session::setFlash('error', 'Error al actualizar la nota');
            header('Location: /devsec-notes/public/index.php?page=edit-note&id=' . $noteId);
        }
        exit;
    }

    /**
     * Eliminar nota
     */
    public function delete() {
        $this->checkAuth();

        $noteId = $_GET['id'] ?? 0;
        $userId = Session::get('user_id');

        $note = new Note();
        if ($note->delete($noteId, $userId)) {
            Session::setFlash('success', 'Nota eliminada exitosamente');
        } else {
            Session::setFlash('error', 'Error al eliminar la nota');
        }

        header('Location: /devsec-notes/public/index.php?page=dashboard');
        exit;
    }

    /**
     * Archivar nota
     */
    public function archive() {
        $this->checkAuth();

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
    }

    /**
     * Buscar notas
     */
    public function search() {
        $this->checkAuth();

        $searchTerm = Security::sanitize($_GET['q'] ?? '');
        $userId     = Session::get('user_id');

        $note  = new Note();
        $tag   = new Tag();
        $notes = $note->search($userId, $searchTerm);

        foreach ($notes as &$n) {
            $n['tags'] = $tag->getByNote($n['id']);
        }
        unset($n);

        require_once __DIR__ . '/../views/notes/search.php';
    }

    // ==================== TAGS ====================

    /**
     * Crear nuevo tag (AJAX / POST)
     */
    public function storeTag() {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /devsec-notes/public/index.php?page=dashboard');
            exit;
        }

        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido');
            header('Location: /devsec-notes/public/index.php?page=manage-tags');
            exit;
        }

        $userId = Session::get('user_id');
        $name   = Security::sanitize($_POST['tag_name'] ?? '');
        $color  = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['tag_color'] ?? '')
                  ? $_POST['tag_color']
                  : '#3B82F6';

        if (empty($name) || strlen($name) > 50) {
            Session::setFlash('error', 'El nombre del tag es inválido (máx. 50 caracteres)');
            header('Location: /devsec-notes/public/index.php?page=manage-tags');
            exit;
        }

        $tag           = new Tag();
        $tag->user_id  = $userId;
        $tag->name     = $name;
        $tag->color    = $color;

        if ($tag->create()) {
            Session::setFlash('success', "Tag \"$name\" creado correctamente");
        } else {
            Session::setFlash('error', "Ya existe un tag con el nombre \"$name\"");
        }

        header('Location: /devsec-notes/public/index.php?page=manage-tags');
        exit;
    }

    /**
     * Eliminar tag
     */
    public function deleteTag() {
        $this->checkAuth();

        $tagId  = $_GET['id'] ?? 0;
        $userId = Session::get('user_id');

        $tag = new Tag();
        if ($tag->delete($tagId, $userId)) {
            Session::setFlash('success', 'Tag eliminado');
        } else {
            Session::setFlash('error', 'Error al eliminar el tag');
        }

        header('Location: /devsec-notes/public/index.php?page=manage-tags');
        exit;
    }

    /**
     * Página de gestión de tags
     */
    public function manageTags() {
        $this->checkAuth();

        $userId  = Session::get('user_id');
        $tag     = new Tag();
        $allTags = $tag->getAllByUser($userId);

        require_once __DIR__ . '/../views/tags/manage.php';
    }
}
?>
