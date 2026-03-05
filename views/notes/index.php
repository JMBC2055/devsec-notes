<?php
// ============================================================================
// UBICACIÓN: C:/xampp/htdocs/devsec-notes/views/notes/index.php
// DESCRIPCIÓN: Dashboard principal - Lista de notas con filtro por etiqueta
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
                <a href="index.php?page=manage-tags" class="btn btn-tags">🏷️ Etiquetas</a>
                <a href="index.php?page=logout" class="btn btn-secondary">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <main class="container">

        <?php if ($error = Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success = Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Barra de acciones -->
        <div class="action-bar">
            <a href="index.php?page=create-note" class="btn btn-primary">➕ Nueva Nota</a>

            <form action="index.php?page=search-notes" method="GET" class="search-form">
                <input type="hidden" name="page" value="search-notes">
                <input type="text" name="q" placeholder="Buscar notas..." class="search-input">
                <button type="submit" class="btn btn-secondary">🔍 Buscar</button>
            </form>
        </div>

        <!-- Filtro por etiquetas -->
        <?php if (!empty($allTags)): ?>
            <div class="tags-filter-bar">
                <span>Filtrar:</span>
                <a href="index.php?page=dashboard"
                   class="tag-filter-btn tag-filter-all <?= !$filterTagId ? 'active' : '' ?>">
                    Todas
                </a>
                <?php foreach ($allTags as $t): ?>
                    <a href="index.php?page=dashboard&tag_id=<?= $t['id'] ?>"
                       class="tag-filter-btn <?= $filterTagId == $t['id'] ? 'active' : '' ?>"
                       style="background-color:<?= htmlspecialchars($t['color']) ?>;">
                        <?= htmlspecialchars($t['name']) ?>
                        <sup><?= $t['note_count'] ?></sup>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($filterTag): ?>
            <p style="margin-bottom:1rem; color:var(--text-muted);">
                Mostrando notas con etiqueta:
                <span class="tag-badge" style="background-color:<?= htmlspecialchars($filterTag['color']) ?>;">
                    <?= htmlspecialchars($filterTag['name']) ?>
                </span>
            </p>
        <?php endif; ?>

        <!-- Grid de notas -->
        <div class="notes-grid">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <p>📭 <?= $filterTagId ? 'No hay notas con esta etiqueta' : 'No tienes notas aún' ?></p>
                    <?php if (!$filterTagId): ?>
                        <a href="index.php?page=create-note" class="btn btn-primary">
                            Crear mi primera nota
                        </a>
                    <?php endif; ?>
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

                        <!-- Tags de la nota -->
                        <?php if (!empty($note['tags'])): ?>
                            <div class="note-tags">
                                <?php foreach ($note['tags'] as $t): ?>
                                    <a href="index.php?page=dashboard&tag_id=<?= $t['id'] ?>"
                                       class="tag-badge"
                                       style="background-color:<?= htmlspecialchars($t['color']) ?>; text-decoration:none;">
                                        <?= htmlspecialchars($t['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="note-meta">
                            <small>
                                Creada: <?= date('d/m/Y H:i', strtotime($note['created_at'])) ?>
                            </small>
                        </div>

                        <div class="note-actions">
                            <a href="index.php?page=edit-note&id=<?= $note['id'] ?>"
                               class="btn btn-sm btn-secondary">✏️ Editar</a>
                            <a href="index.php?page=archive-note&id=<?= $note['id'] ?>"
                               class="btn btn-sm btn-warning"
                               onclick="return confirm('¿Archivar esta nota?')">📦 Archivar</a>
                            <a href="index.php?page=delete-note&id=<?= $note['id'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar esta nota permanentemente?')">🗑️ Eliminar</a>
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
                    <span class="stat-label">
                        <?= $filterTagId ? 'Notas con esta etiqueta' : 'Total de notas' ?>
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">
                        <?= count(array_filter($notes, fn($n) => $n['is_favorite'])) ?>
                    </span>
                    <span class="stat-label">Favoritas</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= count($allTags) ?></span>
                    <span class="stat-label">Etiquetas</span>
                </div>
            </div>
        </div>

    </main>

    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro - DevSecOps</p>
    </footer>
</body>
</html>
