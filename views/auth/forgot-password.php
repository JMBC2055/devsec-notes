<?php require_once __DIR__ . '/../layout/header.php'; ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2>¿Olvidaste tu contraseña?</h2>
            <?php if (Session::has('flash_error')): ?>
                <div class="alert alert-danger"><?= Session::getFlash('error') ?></div>
            <?php endif; ?>
            <?php if (Session::has('flash_success')): ?>
                <div class="alert alert-success"><?= Session::getFlash('success') ?></div>
            <?php endif; ?>
            
            <form method="POST" action="/devsec-notes/public/index.php?page=request-reset">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary">Enviar enlace</button>
                <a href="/devsec-notes/public/index.php?page=login" class="btn btn-link">Volver al login</a>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
