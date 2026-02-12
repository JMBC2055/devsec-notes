<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/views/notes/search.php
// DESCRIPCIÓN: Resultados de búsqueda
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
$searchTerm = Security::sanitize($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <h1>🔍 Resultados de búsqueda</h1>
            <div class="header-actions">
                <a href="index.php?page=dashboard" class="btn btn-secondary">← Volver</a>
                <a href="index.php?page=logout" class="btn btn-secondary">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    
    <!-- Contenido principal -->
    <main class="container">
        
        <div class="search-info">
            <p>Buscando: <strong>"<?= htmlspecialchars($searchTerm) ?>"</strong></p>
            <p>Resultados encontrados: <strong><?= count($notes) ?></strong></p>
        </div>
        
        <!-- Grid de notas -->
        <div class="notes-grid">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <p>😕 No se encontraron notas con ese término</p>
                    <a href="index.php?page=dashboard" class="btn btn-primary">
                        Volver al dashboard
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-card <?= $note['is_favorite'] ? 'favorite' : '' ?>">
                        <div class="note-header">
                            <h3><?= htmlspecialchars($note['title']) ?></h3>
                            <?php if ($note['is_favorite']): ?>
                                <span class="badge-favorite">⭐</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="note-content">
                            <p><?= nl2br(htmlspecialchars(substr($note['content'], 0, 150))) ?>
                               <?= strlen($note['content']) > 150 ? '...' : '' ?>
                            </p>
                        </div>
                        
                        <div class="note-meta">
                            <small>
                                Creada: <?= date('d/m/Y H:i', strtotime($note['created_at'])) ?>
                            </small>
                        </div>
                        
                        <div class="note-actions">
                            <a href="index.php?page=edit-note&id=<?= $note['id'] ?>" 
                               class="btn btn-sm btn-secondary">
                                ✏️ Editar
                            </a>
                            <a href="index.php?page=delete-note&id=<?= $note['id'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar esta nota?')">
                                🗑️ Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </main>
    
    <!-- Footer -->
    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro - DevSecOps</p>
    </footer>
</body>
</html>