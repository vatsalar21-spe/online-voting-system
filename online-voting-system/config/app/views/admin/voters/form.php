<?php
use Core\Csrf;
use Core\Router;
$errors = $_SESSION['errors'] ?? []; unset($_SESSION['errors']);
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
$isEdit = $voter !== null;
$name = $old['name'] ?? ($voter['name'] ?? '');
$email = $old['email'] ?? ($voter['email'] ?? '');
$action = $isEdit ? "/admin/voters/{$voter['id']}" : '/admin/voters';
?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Edit Voter' : 'Add Voter' ?></h1>

<div class="card card-ovs">
    <div class="card-body">
        <?php if (!$isEdit): ?>
            <p class="text-muted small">Creates an active account immediately — voter code and a temporary password are auto-generated and shown to you on the next page, same as one row of a bulk Excel upload. This only creates the account — enroll them into specific elections from that election's Voters page.</p>
        <?php else: ?>
            <p class="text-muted small">Voter code: <code><?= htmlspecialchars($voter['voter_code'] ?? '—', ENT_QUOTES) ?></code> (auto-generated, not editable)</p>
        <?php endif; ?>
        <form method="post" action="<?= Router::url($action) ?>">
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Full name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name, ENT_QUOTES) ?>" required>
                <?php if (!empty($errors['name'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['name'], ENT_QUOTES) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email, ENT_QUOTES) ?>" required>
                <?php if (!empty($errors['email'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['email'], ENT_QUOTES) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-ovs"><?= $isEdit ? 'Save Changes' : 'Add Voter' ?></button>
            <a href="<?= Router::url('/admin/voters') ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
