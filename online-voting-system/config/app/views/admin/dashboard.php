<?php use App\Models\Election; use Core\Router; ?>
<p class="eyebrow mb-1">Office of the Election Commissioner</p>
<h1 class="h3 mb-4">Admin Dashboard</h1>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="stat-tile">
            <div class="stat-number"><?= $stats['total_elections'] ?></div>
            <div class="stat-label">Elections</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-tile">
            <div class="stat-number"><?= $stats['open_elections'] ?></div>
            <div class="stat-label">Currently Open</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-tile">
            <div class="stat-number"><?= $stats['approved_voters'] ?></div>
            <div class="stat-label">Approved Voters</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-tile">
            <div class="stat-number"><?= $stats['pending_voters'] ?></div>
            <div class="stat-label">Pending Approvals</div>
            <?php if ($stats['pending_voters'] > 0): ?>
                <div class="mt-1"><a href="<?= Router::url('/admin/voters') ?>" class="small">Review &rarr;</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card card-ovs">
    <div class="card-header d-flex justify-content-between align-items-center">
        Elections
        <a href="<?= Router::url('/admin/elections/create') ?>" class="btn btn-sm btn-ovs">+ New Election</a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Title</th><th>Voting Style</th><th>Status</th><th>Window</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($elections as $election): ?>
                <tr>
                    <td><?= htmlspecialchars($election['title'], ENT_QUOTES) ?></td>
                    <td><span title="<?= htmlspecialchars(Election::formatHint($election), ENT_QUOTES) ?>" style="cursor: help; border-bottom: 1px dotted currentColor;"><?= htmlspecialchars(Election::formatLabel($election), ENT_QUOTES) ?></span></td>
                    <td><span class="badge status-badge-<?= $election['status'] ?>"><?= ucfirst($election['status']) ?></span></td>
                    <td><small><?= htmlspecialchars($election['starts_at'], ENT_QUOTES) ?> &rarr; <?= htmlspecialchars($election['ends_at'], ENT_QUOTES) ?></small></td>
                    <td>
                        <a href="<?= Router::url('/admin/elections/' . $election['id'] . '/candidates') ?>">Candidates</a>
                        &middot;
                        <a href="<?= Router::url('/admin/elections/' . $election['id'] . '/voters') ?>">Voters</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
