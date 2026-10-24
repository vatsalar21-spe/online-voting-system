<?php use App\Models\Election; use Core\Router; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= htmlspecialchars($election['title'], ENT_QUOTES) ?></h1>
    <span class="badge status-badge-<?= $election['status'] ?>"><?= ucfirst($election['status']) ?></span>
</div>
<p class="text-muted"><?= htmlspecialchars($election['description'] ?? '', ENT_QUOTES) ?></p>
<p class="small">Voting style: <span title="<?= htmlspecialchars(Election::formatHint($election), ENT_QUOTES) ?>" style="cursor: help; border-bottom: 1px dotted currentColor;"><?= htmlspecialchars(Election::formatLabel($election), ENT_QUOTES) ?></span></p>

<div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
<?php foreach ($candidates as $candidate): ?>
    <div class="col">
        <div class="card card-ovs h-100">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($candidate['name'], ENT_QUOTES) ?></h5>
                <p class="card-text small text-muted"><?= htmlspecialchars($candidate['bio'] ?? '', ENT_QUOTES) ?></p>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if ($election['has_voted']): ?>
    <a href="<?= Router::url('/voter/elections/' . $election['id'] . '/receipt') ?>" class="btn btn-outline-secondary">View My Receipt</a>
<?php elseif ($election['status'] === 'open'): ?>
    <a href="<?= Router::url('/voter/elections/' . $election['id'] . '/ballot') ?>" class="btn btn-ovs">Cast Your Vote</a>
<?php elseif ($election['status'] === 'closed' && $election['results_published']): ?>
    <a href="<?= Router::url('/elections/' . $election['id'] . '/results') ?>" class="btn btn-outline-secondary">View Results</a>
<?php else: ?>
    <p class="text-muted">Voting is not currently open for this election.</p>
<?php endif; ?>
