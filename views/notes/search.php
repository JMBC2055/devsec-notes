<?php
require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar — Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tags.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1>
                <span style="width:32px;height:32px;background:linear-gradient(135deg,#C9A84C,#E2C47A);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;">🔍</span>
                Buscar Notas
            </h1>
            <div class="header-actions">
                <a href="index.php?page=dashboard" class="btn btn-sm btn-secondary">← Volver</a>
                <a href="index.php?page=logout" class="btn btn-sm btn-secondary">Salir</a>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="action-bar" style="margin-top:1.5rem;">
            <a href="index.php?page=create-note" class="btn btn-primary">+ Nueva Nota</a>
            <form action="index.php?page=search-notes" method="GET" class="search-form">
                <input type="hidden" name="page" value="search-notes">
                <input type="text" name="q" value="<?= htmlspecialchars($query ?? '') ?>"
                       placeholder="Buscar notas..." class="search-input" autofocus>
                <button type="submit" class="btn btn-secondary">Buscar</button>
            </form>
        </div>

        <?php if (!empty($query)): ?>
            <div class="search-info">
                <?= count($notes) ?> resultado<?= count($notes) != 1 ? 's' : '' ?> para
                "<strong style="color:var(--cream);"><?= htmlspecialchars($query) ?></strong>"
            </div>
        <?php endif; ?>

        <div class="notes-grid">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <div style="font-size:3rem; margin-bottom:1rem; opacity:0.4;">🔍</div>
                    <p>No se encontraron notas</p>
                    <a href="index.php?page=dashboard" class="btn btn-secondary">Ver todas las notas</a>
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
                               <?= strlen($note['content']) > 150 ? '<span style="opacity:0.5;">…</span>' : '' ?>
                            </p>
                        </div>
                        <?php if (!empty($note['tags'])): ?>
                            <div class="note-tags">
                                <?php foreach ($note['tags'] as $t): ?>
                                    <span class="tag-badge" style="background-color:<?= htmlspecialchars($t['color']) ?>;">
                                        <?= htmlspecialchars($t['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="note-meta">
                            <small><?= date('d/m/Y · H:i', strtotime($note['created_at'])) ?></small>
                        </div>
                        <div class="note-actions">
                            <a href="index.php?page=edit-note&id=<?= $note['id'] ?>"
                               class="btn btn-sm btn-secondary">✏️ Editar</a>
                            <a href="index.php?page=delete-note&id=<?= $note['id'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar esta nota?')">🗑️</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro</p>
    </footer>
</body>
</html>
