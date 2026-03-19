<?php
require_once __DIR__ . '/../../helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Nota — Gestor de Notas</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tags.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1>
                <span style="width:32px;height:32px;background:linear-gradient(135deg,#C9A84C,#E2C47A);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:1rem;">✏️</span>
                Editar Nota
            </h1>
            <div class="header-actions">
                <a href="index.php?page=dashboard" class="btn btn-sm btn-secondary">← Volver</a>
                <a href="index.php?page=logout" class="btn btn-sm btn-secondary">Salir</a>
            </div>
        </div>
    </header>

    <main class="container">

        <?php if ($error = Session::getFlash('error')): ?>
            <div class="alert alert-error" style="margin-top:1.5rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form action="index.php?page=update-note" method="POST" class="note-form">

                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="note_id" value="<?= $note['id'] ?>">

                <div class="form-group">
                    <label for="title">Título</label>
                    <input type="text" id="title" name="title" required
                           maxlength="200" value="<?= htmlspecialchars($note['title']) ?>" autofocus>
                </div>

                <div class="form-group">
                    <label for="content">Contenido</label>
                    <textarea id="content" name="content" required
                              rows="10"><?= htmlspecialchars($note['content']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        Etiquetas
                        <a href="index.php?page=manage-tags" style="font-size:0.75rem; font-weight:400; color:var(--muted); text-transform:none; margin-left:8px;">
                            + Gestionar
                        </a>
                    </label>
                    <div class="tags-selector">
                        <?php if (empty($allTags)): ?>
                            <span class="tags-selector-empty">
                                Sin etiquetas. <a href="index.php?page=manage-tags">Crear una</a>
                            </span>
                        <?php else: ?>
                            <?php foreach ($allTags as $t): ?>
                                <label>
                                    <input type="checkbox" name="tag_ids[]" value="<?= $t['id'] ?>"
                                        <?= in_array($t['id'], $noteTagIds) ? 'checked' : '' ?>>
                                    <span class="tag-badge" style="background-color:<?= htmlspecialchars($t['color']) ?>;">
                                        <?= htmlspecialchars($t['name']) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reminder_date">Recordatorio (opcional)</label>
                    <input type="datetime-local" id="reminder_date" name="reminder_date"
                           value="<?= $note['reminder_date'] ? date('Y-m-d\TH:i', strtotime($note['reminder_date'])) : '' ?>">
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_favorite" value="1"
                               <?= $note['is_favorite'] ? 'checked' : '' ?>>
                        <span>⭐ Marcar como favorita</span>
                    </label>
                </div>

                <div class="note-info" style="font-size:0.8rem;">
                    <small style="color:var(--muted);">
                        Creada: <?= date('d/m/Y H:i', strtotime($note['created_at'])) ?>
                        <?php if ($note['updated_at']): ?>
                         &nbsp;·&nbsp; Actualizada: <?= date('d/m/Y H:i', strtotime($note['updated_at'])) ?>
                        <?php endif; ?>
                    </small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Actualizar Nota</button>
                    <a href="index.php?page=dashboard" class="btn btn-secondary">Cancelar</a>
                    <a href="index.php?page=delete-note&id=<?= $note['id'] ?>"
                       class="btn btn-danger"
                       onclick="return confirm('¿Eliminar esta nota permanentemente?')">
                        🗑️ Eliminar
                    </a>
                </div>

            </form>
        </div>

    </main>

    <footer class="main-footer">
        <p>&copy; 2024 Gestor de Notas Seguro</p>
    </footer>
</body>
</html>
