<?php use Core\Csrf; use Core\Router; ?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card card-ovs">
            <div class="card-header">Reset Password</div>
            <div class="card-body">
                <form method="post" action="<?= Router::url('/reset-password') ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password</label>
                        <input type="password" name="password_confirm" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-ovs w-100">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
