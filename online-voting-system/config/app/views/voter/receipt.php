<?php use Core\Router; ?>
<p class="eyebrow mb-1">Proof of Participation</p>
<h1 class="h3 mb-4">Vote Receipt</h1>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-ovs">
            <div class="card-header"><?= htmlspecialchars($election['title'], ENT_QUOTES) ?></div>
            <div class="card-body text-center">
                <div class="receipt-stamp-wrap">
                    <?php include __DIR__ . '/../partials/stamp.php'; ?>
                </div>
                <p class="text-muted small">This receipt proves you participated in this election. It does not reveal your choice &mdash; ballots are secret.</p>
                <dl class="text-start mt-3 mb-0">
                    <div class="receipt-field">
                        <dt>Ledger block</dt>
                        <dd>#<?= (int) $block['id'] ?></dd>
                    </div>
                    <div class="receipt-field">
                        <dt>Recorded at</dt>
                        <dd><?= htmlspecialchars($block['created_at'], ENT_QUOTES) ?></dd>
                    </div>
                    <div class="receipt-field">
                        <dt>Block hash</dt>
                        <dd><?= htmlspecialchars($block['hash'], ENT_QUOTES) ?></dd>
                    </div>
                </dl>
            </div>
        </div>
        <a href="<?= Router::url('/voter/dashboard') ?>" class="btn btn-outline-secondary mt-3">&larr; Back to Elections</a>
    </div>
</div>
