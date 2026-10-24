<?php use App\Models\Election; use Core\Router; ?>
<h1 class="h3 mb-4">Elections</h1>

<div class="row row-cols-1 row-cols-md-2 g-3">
<?php foreach ($elections as $election): ?>
    <div class="col">
        <div class="card card-ovs h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars($election['title'], ENT_QUOTES) ?></span>
                <span class="badge status-badge-<?= $election['status'] ?>"><?= ucfirst($election['status']) ?></span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2"><?= htmlspecialchars($election['description'] ?? '', ENT_QUOTES) ?></p>
                <p class="small">
                    Voting style: <span title="<?= htmlspecialchars(Election::formatHint($election), ENT_QUOTES) ?>" style="cursor: help; border-bottom: 1px dotted currentColor;"><?= htmlspecialchars(Election::formatLabel($election), ENT_QUOTES) ?></span><br>
                    Voting window: <?= htmlspecialchars($election['starts_at'], ENT_QUOTES) ?> &rarr; <?= htmlspecialchars($election['ends_at'], ENT_QUOTES) ?>
                </p>
                <?php if ($election['has_voted']): ?>
                    <a href="<?= Router::url('/voter/elections/' . $election['id'] . '/receipt') ?>" class="btn btn-outline-secondary btn-sm">View Receipt</a>
                <?php elseif ($election['status'] === 'open'): ?>
                    <a href="<?= Router::url('/voter/elections/' . $election['id']) ?>" class="btn btn-ovs btn-sm">Vote Now</a>
                <?php else: ?>
                    <a href="<?= Router::url('/voter/elections/' . $election['id']) ?>" class="btn btn-outline-secondary btn-sm">View</a>
                <?php endif; ?>
                <?php if ($election['status'] === 'closed' && $election['results_published']): ?>
                    <a href="<?= Router::url('/elections/' . $election['id'] . '/results') ?>" class="btn btn-outline-secondary btn-sm">Results</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
