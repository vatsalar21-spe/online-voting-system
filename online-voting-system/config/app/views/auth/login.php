<?php use Core\Csrf; use Core\Router; use Core\View; ?>
<div class="portal-wrap">
    <div class="row justify-content-center w-100">
        <div class="col-md-5">
            <div class="portal-card">
                <div class="portal-seal">
                    <?php $sealSize = '4rem'; $sealTone = 'navy'; include __DIR__ . '/../partials/seal.php'; ?>
                </div>
                <p class="eyebrow text-center mb-2">Office of the Election Commissioner</p>
                <h1 class="portal-title">Voter &amp; Admin Login</h1>
                <p class="portal-subtitle">Ballots recorded on a tamper-evident ledger</p>

                <div class="card card-ovs">
                    <div class="card-body">
                        <form method="post" action="<?= Router::url('/login') ?>">
                            <?= Csrf::field() ?>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= View::old('email') ?>" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-ovs w-100">Log In</button>
                        </form>
                        <div class="d-flex justify-content-between mt-3 small">
                            <a href="<?= Router::url('/register') ?>">Register to vote</a>
                            <a href="<?= Router::url('/forgot-password') ?>">Forgot password?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
