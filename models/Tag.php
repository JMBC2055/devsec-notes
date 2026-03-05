<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/models/Tag.php
// DESCRIPCIÓN: Modelo de etiquetas (tags)
// ============================================================================

require_once __DIR__ . '/../config/database.php';

class Tag {

    private $conn;
    private $table = 'tags';
    private $pivotTable = 'note_tags';

    public $id;
    public $user_id;
    public $name;
    public $color;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtener todos los tags del usuario
     * @param int $userId
     * @return array
     */
    public function getAllByUser($userId) {
        $query = "SELECT t.*, COUNT(nt.note_id) as note_count
                  FROM " . $this->table . " t
                  LEFT JOIN " . $this->pivotTable . " nt ON t.id = nt.tag_id
                  WHERE t.user_id = :user_id
                  GROUP BY t.id
                  ORDER BY t.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener tags de una nota específica
     * @param int $noteId
     * @return array
     */
    public function getByNote($noteId) {
        $query = "SELECT t.*
                  FROM " . $this->table . " t
                  INNER JOIN " . $this->pivotTable . " nt ON t.id = nt.tag_id
                  WHERE nt.note_id = :note_id
                  ORDER BY t.name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':note_id', $noteId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo tag
     * @return int|false ID del tag creado
     */
    public function create() {
        // Verificar si ya existe un tag con el mismo nombre para este usuario
        if ($this->existsByName($this->user_id, $this->name)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . " (user_id, name, color)
                  VALUES (:user_id, :name, :color)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':color', $this->color);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Eliminar tag (y sus relaciones)
     * @param int $tagId
     * @param int $userId
     * @return bool
     */
    public function delete($tagId, $userId) {
        // Primero eliminar relaciones en note_tags
        $queryPivot = "DELETE FROM " . $this->pivotTable . " WHERE tag_id = :tag_id";
        $stmt = $this->conn->prepare($queryPivot);
        $stmt->bindParam(':tag_id', $tagId);
        $stmt->execute();

        // Luego eliminar el tag
        $query = "DELETE FROM " . $this->table . "
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $tagId);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    /**
     * Asignar tags a una nota (reemplaza los anteriores)
     * @param int $noteId
     * @param array $tagIds
     * @return bool
     */
    public function syncToNote($noteId, array $tagIds) {
        // Eliminar relaciones actuales
        $deleteQuery = "DELETE FROM " . $this->pivotTable . " WHERE note_id = :note_id";
        $stmt = $this->conn->prepare($deleteQuery);
        $stmt->bindParam(':note_id', $noteId);
        $stmt->execute();

        // Insertar las nuevas
        if (!empty($tagIds)) {
            $insertQuery = "INSERT INTO " . $this->pivotTable . " (note_id, tag_id) VALUES (:note_id, :tag_id)";
            $stmt = $this->conn->prepare($insertQuery);

            foreach ($tagIds as $tagId) {
                $tagId = (int)$tagId;
                $stmt->bindParam(':note_id', $noteId);
                $stmt->bindParam(':tag_id', $tagId);
                $stmt->execute();
            }
        }

        return true;
    }

    /**
     * Obtener notas filtradas por tag
     * @param int $userId
     * @param int $tagId
     * @return array
     */
    public function getNotesByTag($userId, $tagId) {
        $query = "SELECT n.*
                  FROM notes n
                  INNER JOIN " . $this->pivotTable . " nt ON n.id = nt.note_id
                  WHERE n.user_id = :user_id
                  AND nt.tag_id = :tag_id
                  AND n.is_archived = 0
                  ORDER BY n.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':tag_id', $tagId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si ya existe un tag con ese nombre para el usuario
     * @param int $userId
     * @param string $name
     * @return bool
     */
    public function existsByName($userId, $name) {
        $query = "SELECT id FROM " . $this->table . "
                  WHERE user_id = :user_id AND name = :name LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':name', $name);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>
