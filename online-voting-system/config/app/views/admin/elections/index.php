<?php use App\Models\Election; use Core\Csrf; use Core\Router; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Elections</h1>
    <a href="<?= Router::url('/admin/elections/create') ?>" class="btn btn-ovs">+ New Election</a>
</div>

<div class="card card-ovs">
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Title</th><th>Voting Style</th><th>Status</th><th>Window</th><th>Results</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($elections as $election): ?>
                <tr>
                    <td><?= htmlspecialchars($election['title'], ENT_QUOTES) ?></td>
                    <td><span title="<?= htmlspecialchars(Election::formatHint($election), ENT_QUOTES) ?>" style="cursor: help; border-bottom: 1px dotted currentColor;"><?= htmlspecialchars(Election::formatLabel($election), ENT_QUOTES) ?></span></td>
                    <td><span class="badge status-badge-<?= $election['status'] ?>"><?= ucfirst($election['status']) ?></span></td>
                    <td><small><?= htmlspecialchars($election['starts_at'], ENT_QUOTES) ?><br>&rarr; <?= htmlspecialchars($election['ends_at'], ENT_QUOTES) ?></small></td>
                    <td><?= $election['results_published'] ? 'Published' : 'Not published' ?></td>
                    <td class="text-end">
                        <a href="<?= Router::url('/admin/elections/' . $election['id'] . '/candidates') ?>" class="btn btn-sm btn-outline-secondary">Candidates</a>
                        <a href="<?= Router::url('/admin/elections/' . $election['id'] . '/voters') ?>" class="btn btn-sm btn-outline-secondary">Voters</a>
                        <a href="<?= Router::url('/admin/ledger') ?>?election_id=<?= $election['id'] ?>" class="btn btn-sm btn-outline-secondary">Ledger</a>
                        <?php if ($election['status'] === 'upcoming'): ?>
                            <a href="<?= Router::url('/admin/elections/' . $election['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <?php endif; ?>
                        <a href="<?= Router::url('/elections/' . $election['id'] . '/results') ?>" class="btn btn-sm btn-outline-secondary">Results</a>
                        <?php if ($election['status'] !== 'closed'): ?>
                            <form method="post" action="<?= Router::url('/admin/elections/' . $election['id'] . '/close') ?>" class="d-inline" onsubmit="return confirm('Close this election now?');">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-danger">Close Now</button>
                            </form>
                        <?php elseif (!$election['results_published']): ?>
                            <form method="post" action="<?= Router::url('/admin/elections/' . $election['id'] . '/publish-results') ?>" class="d-inline">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-ovs">Publish Results</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$elections): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No elections yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
