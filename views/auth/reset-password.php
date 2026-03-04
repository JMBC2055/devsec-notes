<?php require_once __DIR__ . '/../layout/header.php'; ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2>Restablecer contraseña</h2>
            <?php if (Session::has('flash_error')): ?>
                <div class="alert alert-danger"><?= Session::getFlash('error') ?></div>
            <?php endif; ?>
            
            <form method="POST" action="/devsec-notes/public/index.php?page=reset-password-process">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
                <div class="mb-3">
                    <label for="password" class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-success">Restablecer</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
