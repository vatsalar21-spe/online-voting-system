<?php use Core\Csrf; use Core\Router; use Core\View; $errors = $_SESSION['errors'] ?? []; unset($_SESSION['errors']); ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-ovs">
            <div class="card-header">Voter Registration</div>
            <div class="card-body">
                <p class="text-muted">Your account will need to be approved by an election admin before you can log in and vote.</p>
                <form method="post" action="<?= Router::url('/register') ?>">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" name="name" class="form-control" value="<?= View::old('name') ?>" required>
                        <?php if (!empty($errors['name'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['name'], ENT_QUOTES) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= View::old('email') ?>" required>
                        <?php if (!empty($errors['email'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['email'], ENT_QUOTES) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <?php if (!empty($errors['password'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['password'], ENT_QUOTES) ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password</label>
                        <input type="password" name="password_confirm" class="form-control" required minlength="8">
                        <?php if (!empty($errors['password_confirm'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['password_confirm'], ENT_QUOTES) ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-ovs w-100">Register</button>
                </form>
                <div class="mt-3"><a href="<?= Router::url('/login') ?>">Already have an account? Log in</a></div>
            </div>
        </div>
    </div>
</div>
