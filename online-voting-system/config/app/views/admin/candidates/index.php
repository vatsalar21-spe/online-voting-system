<?php use App\Models\Election; use Core\Csrf; use Core\Router;
$electionStatus = Election::status($election);
$locked = $electionStatus !== 'upcoming';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Candidates &mdash; <?= htmlspecialchars($election['title'], ENT_QUOTES) ?></h1>
    <a href="<?= Router::url('/admin/elections') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Elections</a>
</div>

<?php if ($locked): ?>
    <div class="alert alert-warning">
        Voting has <?= $electionStatus === 'closed' ? 'ended' : 'started' ?> for this election — the candidate list is locked to keep results consistent with the votes already cast.
    </div>
<?php else: ?>
<div class="card card-ovs mb-4">
    <div class="card-header">Add Candidate</div>
    <div class="card-body">
        <form method="post" action="<?= Router::url('/admin/elections/' . $election['id'] . '/candidates') ?>" class="row g-2">
            <?= Csrf::field() ?>
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="Candidate name" required>
            </div>
            <div class="col-md-6">
                <input type="text" name="bio" class="form-control" placeholder="Short bio">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-ovs w-100">Add</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card card-ovs">
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Bio</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($candidates as $candidate):
                $editFormId = 'edit-candidate-' . $candidate['id'];
                $deleteFormId = 'delete-candidate-' . $candidate['id'];
            ?>
                <tr>
                    <td>
                        <input type="text" name="name" form="<?= $editFormId ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($candidate['name'], ENT_QUOTES) ?>" <?= $locked ? 'disabled' : '' ?>>
                    </td>
                    <td>
                        <input type="text" name="bio" form="<?= $editFormId ?>" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $candidate['bio'], ENT_QUOTES) ?>" <?= $locked ? 'disabled' : '' ?>>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if (!$locked): ?>
                            <button type="submit" form="<?= $editFormId ?>" class="btn btn-sm btn-outline-secondary">Save</button>
                            <button type="submit" form="<?= $deleteFormId ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this candidate?');">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!$locked): ?>
                <form id="<?= $editFormId ?>" method="post" action="<?= Router::url('/admin/candidates/' . $candidate['id'] . '/update') ?>">
                    <?= Csrf::field() ?>
                </form>
                <form id="<?= $deleteFormId ?>" method="post" action="<?= Router::url('/admin/candidates/' . $candidate['id'] . '/delete') ?>">
                    <?= Csrf::field() ?>
                </form>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$candidates): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">No candidates added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
