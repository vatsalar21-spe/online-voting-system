<?php use Core\Csrf; use Core\Router; ?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card card-ovs">
            <div class="card-header">Forgot Password</div>
            <div class="card-body">
                <form method="post" action="<?= Router::url('/forgot-password') ?>">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-ovs w-100">Send Reset Link</button>
                </form>
                <div class="mt-3"><a href="<?= Router::url('/login') ?>">Back to login</a></div>
            </div>
        </div>
    </div>
</div>
