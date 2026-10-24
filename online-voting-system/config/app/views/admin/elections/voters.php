<?php use Core\Csrf; use Core\Router; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Voters &mdash; <?= htmlspecialchars($election['title'], ENT_QUOTES) ?></h1>
    <a href="<?= Router::url('/admin/elections') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Elections</a>
</div>
<p class="text-muted small">Only voters enrolled here can vote in this election, even if their account is approved.</p>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-ovs h-100">
            <div class="card-header">Enroll an Approved Voter</div>
            <div class="card-body">
                <?php if ($available): ?>
                <form method="post" action="<?= Router::url('/admin/elections/' . $election['id'] . '/voters/enroll') ?>" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-8">
                        <select name="user_id" class="form-select" required>
                            <option value="">Choose a voter&hellip;</option>
                            <?php foreach ($available as $voter): ?>
                                <option value="<?= $voter['id'] ?>"><?= htmlspecialchars($voter['name'], ENT_QUOTES) ?> (<?= htmlspecialchars($voter['email'], ENT_QUOTES) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-ovs w-100">Enroll</button>
                    </div>
                </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">Every approved voter is already enrolled in this election.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-ovs h-100">
            <div class="card-header">Bulk Upload Roster (.xlsx)</div>
            <div class="card-body">
                <p class="text-muted small mb-2">Columns: <code>name</code>, <code>email</code>. Creates accounts if needed (voter code and password auto-generated) and enrolls every row in this election.</p>
                <form method="post" action="<?= Router::url('/admin/elections/' . $election['id'] . '/voters/bulk-upload') ?>" enctype="multipart/form-data" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-8">
                        <input type="file" name="roster" accept=".xlsx" class="form-control" required>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-ovs w-100">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card card-ovs">
    <div class="card-header">Enrolled Voters (<?= count($enrolled) ?> &middot; <?= count($votedIds) ?> voted)</div>
    <?php if ($votedIds): ?>
        <div class="card-body pb-0"><p class="text-muted small mb-3">Voters marked <span class="badge status-badge-open">Voted</span> can no longer be removed from this roll &mdash; their ballot is permanently recorded in the ledger and keeps counting toward the result, so taking them off the roll would leave fewer voters enrolled than votes cast.</p></div>
    <?php endif; ?>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Enrolled</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($enrolled as $voter): $hasVoted = in_array((int) $voter['id'], $votedIds, true); ?>
                <tr>
                    <td><?= htmlspecialchars($voter['name'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($voter['email'], ENT_QUOTES) ?></td>
                    <td><small><?= htmlspecialchars($voter['enrolled_at'], ENT_QUOTES) ?></small></td>
                    <td class="text-end">
                        <?php if ($hasVoted): ?>
                            <span class="badge status-badge-open" title="Ballot recorded in the ledger — this voter can no longer be removed from the roll.">Voted</span>
                        <?php else: ?>
                        <form method="post" action="<?= Router::url('/admin/elections/' . $election['id'] . '/voters/' . $voter['id'] . '/unenroll') ?>" class="d-inline">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$enrolled): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No voters enrolled yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
