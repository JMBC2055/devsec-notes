<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/gestor-notas/views/notes/index.php
// DESCRIPCIÓN: Dashboard principal - Lista de notas (VISTA HTML)
// ============================================================================

require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <h1>📝 Mis Notas</h1>
            <div class="header-actions">
                <span>Hola, <strong><?= Session::get('username') ?></strong></span>
                <a href="index.php?page=logout" class="btn btn-secondary">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    
    <!-- Contenido principal -->
    <main class="container">
        
        <?php
        // Mostrar mensajes flash
        if ($error = Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success = Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <!-- Barra de acciones -->
        <div class="action-bar">
            <a href="index.php?page=create-note" class="btn btn-primary">
                ➕ Nueva Nota
            </a>
            
            <form action="index.php?page=search-notes" method="GET" class="search-form">
                <input type="hidden" name="page" value="search-notes">
                <input 
                    type="text" 
                    name="q" 
                    placeholder="Buscar notas..." 
                    class="search-input"
                >
                <button type="submit" class="btn btn-secondary">🔍 Buscar</button>
            </form>
        </div>
        
        <!-- Grid de notas -->
        <div class="notes-grid">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <p>📭 No tienes notas aún</p>
                    <a href="index.php?page=create-note" class="btn btn-primary">
                        Crear mi primera nota
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
                            <a href="index.php?page=archive-note&id=<?= $note['id'] ?>" 
                               class="btn btn-sm btn-warning"
                               onclick="return confirm('¿Archivar esta nota?')">
                                📦 Archivar
                            </a>
                            <a href="index.php?page=delete-note&id=<?= $note['id'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar esta nota permanentemente?')">
                                🗑️ Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-panel">
            <h3>📊 Estadísticas</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?= count($notes) ?></span>
                    <span class="stat-label">Total de notas</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">
                        <?= count(array_filter($notes, fn($n) => $n['is_favorite'])) ?>
                    </span>
                    <span class="stat-label">Favoritas</span>
                </div>
            </div>
        </div>
        
    </main>
    
    <!-- Footer -->
    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro - DevSecOps</p>
    </footer>
</body>
</html>