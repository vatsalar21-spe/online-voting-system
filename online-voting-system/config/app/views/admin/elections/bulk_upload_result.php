<?php use Core\Router; ?>
<h1 class="h3 mb-4">Roster Uploaded &mdash; <?= htmlspecialchars($election['title'], ENT_QUOTES) ?></h1>

<div class="alert alert-success">
    <?= count($newAccounts) ?> new account<?= count($newAccounts) === 1 ? '' : 's' ?> created &middot;
    <?= $enrolled ?> newly enrolled &middot;
    <?= $alreadyEnrolled ?> already enrolled &middot;
    <?= $invalid ?> invalid row<?= $invalid === 1 ? '' : 's' ?> skipped
</div>

<?php if ($newAccounts): ?>
<div class="card card-ovs mb-4">
    <div class="card-header">New Account Credentials</div>
    <div class="card-body">
        <p class="text-muted small">These passwords are shown once, right now &mdash; they can't be recovered later (only reset). Share them with each voter directly; an email was also attempted but may not have gone through if SMTP isn't configured.</p>
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Voter Code</th><th>Temporary Password</th></tr></thead>
            <tbody>
            <?php foreach ($newAccounts as $account): ?>
                <tr>
                    <td><?= htmlspecialchars($account['name'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($account['email'], ENT_QUOTES) ?></td>
                    <td><code class="small"><?= htmlspecialchars($account['voter_code'], ENT_QUOTES) ?></code></td>
                    <td><code><?= htmlspecialchars($account['password'], ENT_QUOTES) ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<a href="<?= Router::url('/admin/elections/' . $election['id'] . '/voters') ?>" class="btn btn-ovs">Continue &rarr;</a>
