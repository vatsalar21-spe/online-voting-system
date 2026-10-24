<?php use Core\Csrf; use Core\Router; ?>
<?php $electionsById = []; foreach ($elections as $e) { $electionsById[(int) $e['id']] = $e['title']; } ?>
<p class="eyebrow mb-1">Chain of Custody</p>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Verify Ledger Integrity</h1>
    <form method="get" action="<?= Router::url('/admin/ledger') ?>" class="d-flex align-items-center gap-2">
        <label for="election_id" class="form-label mb-0 small text-muted">Election</label>
        <select name="election_id" id="election_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All elections (combined chain)</option>
            <?php foreach ($elections as $election): ?>
                <option value="<?= $election['id'] ?>" <?= $selectedElectionId === (int) $election['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($election['title'], ENT_QUOTES) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($selectedElectionId !== null): ?>
    <p class="text-muted small mb-3">Showing only the ledger blocks belonging to this election. <a href="<?= Router::url('/admin/ledger') ?>">View the full combined chain &rarr;</a></p>
<?php endif; ?>

<div class="alert <?= $result['intact'] ? 'alert-success' : 'alert-danger' ?>">
    <?php if ($result['intact']): ?>
        <strong>Chain intact.</strong> All <?= $result['block_count'] ?> ledger block<?= $result['block_count'] === 1 ? '' : 's' ?> <?= $selectedElectionId !== null ? 'for this election were' : 'were' ?> recomputed and match their stored hashes. No tampering detected.
    <?php else: ?>
        <strong>Tampering detected<?= $selectedElectionId !== null ? ' in this election' : '' ?>.</strong> One or more blocks do not match their recomputed hash &mdash; see below.
    <?php endif; ?>
</div>

<div class="card card-ovs mb-4">
    <div class="card-header">Block-by-block, in sequence</div>
    <div class="card-body">
        <?php if ($result['blocks']): ?>
            <div class="chain-strip">
                <?php foreach ($result['blocks'] as $block): ?>
                    <div class="chain-block <?= $block['ok'] ? 'ok' : 'broken' ?>" title="Election #<?= $block['election_id'] ?>">
                        #<?= $block['id'] ?><br><?= $block['ok'] ? 'OK' : 'BROKEN' ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No votes cast yet<?= $selectedElectionId !== null ? ' for this election' : '' ?>.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card card-ovs mb-4">
    <div class="card-header">Block-by-block, detail</div>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Block #</th><th>Election</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($result['blocks'] as $block): $tamperFormId = 'tamper-' . $block['id']; ?>
                <tr class="<?= $block['ok'] ? '' : 'table-danger' ?>">
                    <td class="font-mono">#<?= $block['id'] ?></td>
                    <td><?= htmlspecialchars($electionsById[(int) $block['election_id']] ?? ('#' . $block['election_id']), ENT_QUOTES) ?></td>
                    <td><?= $block['ok'] ? '<span class="badge status-badge-open">OK</span>' : '<span class="badge" style="background:var(--ballot-red-bg);color:var(--ballot-red);border:1px solid var(--ballot-red)">MISMATCH</span>' ?></td>
                    <td class="text-end">
                        <button type="submit" form="<?= $tamperFormId ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Simulate a 3rd party directly editing block #<?= $block['id'] ?> in the database (bypassing this app)? Its hash will be left untouched, and the integrity check should flag it as compromised.');">Simulate Tampering</button>
                        <form id="<?= $tamperFormId ?>" method="post" action="<?= Router::url('/admin/ledger/' . $block['id'] . '/tamper') ?>">
                            <?= Csrf::field() ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['blocks']): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No votes cast yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card card-ovs">
    <div class="card-header">About this demo</div>
    <div class="card-body small text-muted">
        <p class="mb-2"><strong>Simulate Tampering</strong> changes a block's vote data directly in the database &mdash; the same access path a rogue DBA, a compromised server, or a 3rd party with raw SQL access would have &mdash; without going through this app, so its stored hash is <em>not</em> recalculated. That's the point: a legitimate vote always updates hash and choice together (see <code>Core\Ledger::addVote</code>); this button deliberately updates only the choice, the way an attacker who doesn't know the ledger secret would have to.</p>
        <p class="mb-0">Reload this page after tampering to see the check re-run: the tampered block flips to MISMATCH, because its recomputed hash no longer matches what's stored. It alone flips, not the blocks after it &mdash; this demo only edits <code>choice_data</code>, not <code>hash</code>, so later blocks' <code>prev_hash</code> links (which point at the *unchanged* hash column) still check out. A more sophisticated attacker who also recalculated that block's hash to hide the edit would then break the <em>next</em> block instead, whose <code>prev_hash</code> no longer matches &mdash; they'd have to correctly rewrite every block forward to fully cover their tracks. That's the actual value of chaining hashes together: it doesn't stop tampering, it makes tampering expensive to hide. To restore a clean chain, re-import <code>database/schema.sql</code>. Every use of this button is admin-only, CSRF-protected, and recorded in the <a href="<?= Router::url('/admin/audit-log') ?>">audit log</a>.</p>
    </div>
</div>
