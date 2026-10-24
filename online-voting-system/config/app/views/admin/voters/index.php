<?php use Core\Csrf; use Core\Router; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Voters</h1>
    <a href="<?= Router::url('/admin/voters/create') ?>" class="btn btn-ovs">+ Add Voter</a>
</div>
<p class="text-muted small">This is the global voter roster. To decide who can vote in a specific election, go to that election's <strong>Voters</strong> page (from Elections) and enroll them there.</p>

<div class="card card-ovs mb-4">
    <div class="card-header">Pending Self-Registrations (<?= count($pending) ?>)</div>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Registered</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pending as $voter): ?>
                <tr>
                    <td><?= htmlspecialchars($voter['name'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($voter['email'], ENT_QUOTES) ?></td>
                    <td><small><?= htmlspecialchars($voter['created_at'], ENT_QUOTES) ?></small></td>
                    <td class="text-end">
                        <form method="post" action="<?= Router::url('/admin/voters/' . $voter['id'] . '/approve') ?>" class="d-inline">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-ovs">Approve</button>
                        </form>
                        <form method="post" action="<?= Router::url('/admin/voters/' . $voter['id'] . '/reject') ?>" class="d-inline">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pending): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No pending registrations.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card card-ovs mb-4">
    <div class="card-header">Approved Voters (<?= count($approved) ?>)</div>
    <?php if (array_filter($approved, fn($v) => in_array((int) $v['id'], $votedIds, true))): ?>
        <div class="card-body pb-0"><p class="text-muted small mb-3">Voters marked <span class="badge status-badge-closed">Ballot on record</span> have already voted in at least one election. That vote is permanently recorded in the ledger, so their account can no longer be deleted &mdash; removing it would leave a counted vote with no voter behind it.</p></div>
    <?php endif; ?>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Voter Code</th><th>Source</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($approved as $voter): ?>
                <tr>
                    <td><?= htmlspecialchars($voter['name'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($voter['email'], ENT_QUOTES) ?></td>
                    <td><code class="small"><?= htmlspecialchars((string) $voter['voter_code'], ENT_QUOTES) ?></code></td>
                    <td><?= htmlspecialchars($voter['source'], ENT_QUOTES) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= Router::url('/admin/voters/' . $voter['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <?php if (in_array((int) $voter['id'], $votedIds, true)): ?>
                            <span class="badge status-badge-closed" title="Their ballot is permanently recorded in the vote ledger, so the account can't be deleted without leaving a vote with no voter behind it.">Ballot on record</span>
                        <?php else: ?>
                        <form method="post" action="<?= Router::url('/admin/voters/' . $voter['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this voter? This also removes their enrollment from any elections.');">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$approved): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No approved voters yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($rejected): ?>
<div class="card card-ovs">
    <div class="card-header">Rejected (<?= count($rejected) ?>)</div>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Email</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rejected as $voter): ?>
                <tr>
                    <td><?= htmlspecialchars($voter['name'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($voter['email'], ENT_QUOTES) ?></td>
                    <td class="text-end">
                        <?php if (in_array((int) $voter['id'], $votedIds, true)): ?>
                            <span class="badge status-badge-closed" title="Their ballot is permanently recorded in the vote ledger, so the account can't be deleted without leaving a vote with no voter behind it.">Ballot on record</span>
                        <?php else: ?>
                        <form method="post" action="<?= Router::url('/admin/voters/' . $voter['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this voter permanently?');">
                            <?= Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
