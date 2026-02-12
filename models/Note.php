<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/models/Note.php
// DESCRIPCIÓN: Modelo de notas
// ============================================================================

require_once __DIR__ . '/../config/database.php';

class Note {
    
    private $conn;
    private $table = 'notes';
    
    // Propiedades
    public $id;
    public $user_id;
    public $title;
    public $content;
    public $is_archived;
    public $is_favorite;
    public $reminder_date;
    public $created_at;
    public $updated_at;
    
    /**
     * Constructor
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Obtener todas las notas del usuario
     * @param int $userId
     * @param bool $includeArchived
     * @return array
     */
    public function getAllByUser($userId, $includeArchived = false) {
        $archivedFilter = $includeArchived ? '' : 'AND is_archived = 0';
        
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id $archivedFilter 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener nota por ID (solo del usuario)
     * @param int $noteId
     * @param int $userId
     * @return array|false
     */
    public function getById($noteId, $userId) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id = :id AND user_id = :user_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $noteId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear nueva nota
     * @return int|false ID de la nota creada
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, title, content, is_favorite, reminder_date) 
                  VALUES (:user_id, :title, :content, :is_favorite, :reminder_date)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':is_favorite', $this->is_favorite);
        $stmt->bindParam(':reminder_date', $this->reminder_date);
        
        if ($stmt->execute()) {
            $noteId = $this->conn->lastInsertId();
            
            // Registrar en historial
            $this->logHistory($noteId, $this->user_id, 'CREATE', null, $this->content);
            
            return $noteId;
        }
        
        return false;
    }
    
    /**
     * Actualizar nota
     * @param int $noteId
     * @param int $userId
     * @return bool
     */
    public function update($noteId, $userId) {
        // Obtener contenido anterior para historial
        $oldNote = $this->getById($noteId, $userId);
        
        $query = "UPDATE " . $this->table . " 
                  SET title = :title, 
                      content = :content, 
                      is_favorite = :is_favorite, 
                      reminder_date = :reminder_date 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':is_favorite', $this->is_favorite);
        $stmt->bindParam(':reminder_date', $this->reminder_date);
        $stmt->bindParam(':id', $noteId);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            // Registrar en historial
            $this->logHistory($noteId, $userId, 'UPDATE', $oldNote['content'], $this->content);
            return true;
        }
        
        return false;
    }
    
    /**
     * Eliminar nota
     * @param int $noteId
     * @param int $userId
     * @return bool
     */
    public function delete($noteId, $userId) {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $noteId);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            // Log de seguridad
            $this->logSecurityEvent($userId, 'NOTE_DELETED', "Nota ID $noteId eliminada");
            return true;
        }
        
        return false;
    }
    
    /**
     * Archivar/Desarchivar nota
     * @param int $noteId
     * @param int $userId
     * @param bool $archive
     * @return bool
     */
    public function toggleArchive($noteId, $userId, $archive = true) {
        $query = "UPDATE " . $this->table . " 
                  SET is_archived = :is_archived 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':is_archived', $archive);
        $stmt->bindParam(':id', $noteId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Marcar/Desmarcar favorito
     * @param int $noteId
     * @param int $userId
     * @param bool $favorite
     * @return bool
     */
    public function toggleFavorite($noteId, $userId, $favorite = true) {
        $query = "UPDATE " . $this->table . " 
                  SET is_favorite = :is_favorite 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':is_favorite', $favorite);
        $stmt->bindParam(':id', $noteId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
    
/**
 * Buscar notas
 * @param int $userId
 * @param string $search
 * @return array
 */
public function search($userId, $search) {

    if (empty(trim($search))) {
        return [];
    }

    $query = "SELECT * FROM " . $this->table . " 
              WHERE user_id = :user_id
              AND (title LIKE :search_title OR content LIKE :search_content)
              AND is_archived = 0
              ORDER BY created_at DESC";

    $stmt = $this->conn->prepare($query);

    $searchParam = "%" . trim($search) . "%";

    $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
    $stmt->bindValue(':search_title', $searchParam, PDO::PARAM_STR);
    $stmt->bindValue(':search_content', $searchParam, PDO::PARAM_STR);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    
    /**
     * Registrar en historial
     * @param int $noteId
     * @param int $userId
     * @param string $action
     * @param string|null $oldContent
     * @param string|null $newContent
     */
    private function logHistory($noteId, $userId, $action, $oldContent = null, $newContent = null) {
        $query = "INSERT INTO note_history 
                  (note_id, user_id, action_type, old_content, new_content, ip_address) 
                  VALUES (:note_id, :user_id, :action, :old_content, :new_content, :ip)";
        
        $stmt = $this->conn->prepare($query);
        
        require_once __DIR__ . '/../helpers/Security.php';
        $ip = Security::getClientIP();
        
        $stmt->bindParam(':note_id', $noteId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':old_content', $oldContent);
        $stmt->bindParam(':new_content', $newContent);
        $stmt->bindParam(':ip', $ip);
        
        $stmt->execute();
    }
    
    /**
     * Registrar evento de seguridad
     * @param int $userId
     * @param string $eventType
     * @param string $description
     */
    private function logSecurityEvent($userId, $eventType, $description) {
        $query = "INSERT INTO security_logs 
                  (user_id, event_type, event_description, ip_address, user_agent, severity_level) 
                  VALUES (:user_id, :event_type, :description, :ip, :user_agent, 'LOW')";
        
        $stmt = $this->conn->prepare($query);
        
        require_once __DIR__ . '/../helpers/Security.php';
        $ip = Security::getClientIP();
        $userAgent = Security::getUserAgent();
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':event_type', $eventType);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':user_agent', $userAgent);
        
        $stmt->execute();
    }
}
?>