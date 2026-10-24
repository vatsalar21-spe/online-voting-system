<?php
use Core\Router;

$stored = $change['stored_hash'];
$recomputed = $change['recomputed_hash'];
$commonLen = 0;
while ($commonLen < strlen($stored) && $commonLen < strlen($recomputed) && $stored[$commonLen] === $recomputed[$commonLen]) {
    $commonLen++;
}
$storedCommon = htmlspecialchars(substr($stored, 0, $commonLen), ENT_QUOTES);
$storedTail = htmlspecialchars(substr($stored, $commonLen), ENT_QUOTES);
$recomputedCommon = htmlspecialchars(substr($recomputed, 0, $commonLen), ENT_QUOTES);
$recomputedTail = htmlspecialchars(substr($recomputed, $commonLen), ENT_QUOTES);
?>
<p class="eyebrow mb-1">Simulated Attack — Demo</p>
<h1 class="h3 mb-4">Tamper Simulation Result</h1>

<div class="alert alert-danger">
    <strong>Block #<?= $change['block_id'] ?> was just modified directly in the database</strong> &mdash; the way a 3rd party with raw SQL access would, bypassing this app entirely. Its stored hash was left untouched, because a real attacker wouldn't know the <code>ledger_secret</code> needed to recompute one that would pass.
</div>

<div class="card card-ovs mb-4">
    <div class="card-header">What changed &mdash; <?= htmlspecialchars($election['title'], ENT_QUOTES) ?></div>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead>
            <tbody>
                <tr>
                    <td>Vote</td>
                    <td><?= htmlspecialchars($beforeLabel, ENT_QUOTES) ?></td>
                    <td class="text-danger fw-bold"><?= htmlspecialchars($afterLabel, ENT_QUOTES) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card card-ovs mb-4">
    <div class="card-header">Hash comparison</div>
    <div class="card-body">
        <p class="small text-muted">A block's hash is derived from its data. Change the data without recomputing the hash, and the two stop matching &mdash; that mismatch is exactly what "Verify Ledger Integrity" checks for.</p>
        <dl class="mb-0">
            <div class="receipt-field">
                <dt>Hash stored in this block<br><span class="text-muted fw-normal">(the attacker didn't touch this)</span></dt>
                <dd><?= $storedCommon ?><strong class="text-danger"><?= $storedTail ?></strong></dd>
            </div>
            <div class="receipt-field">
                <dt>Hash recomputed from the edited data<br><span class="text-muted fw-normal">(what integrity verification calculates now)</span></dt>
                <dd><?= $recomputedCommon ?><strong class="text-danger"><?= $recomputedTail ?></strong></dd>
            </div>
        </dl>
        <p class="small text-muted mt-3 mb-0">Notice how little the two hashes have in common, even though only one field changed &mdash; that's the avalanche effect: a cryptographic hash is designed so any change to its input scrambles the output almost completely, so a tampered block can't be disguised as "close enough."</p>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap">
    <a href="<?= Router::url('/elections/' . $change['election_id'] . '/results') ?>" class="btn btn-ovs">See the effect on results &rarr;</a>
    <a href="<?= Router::url('/admin/ledger') ?>" class="btn btn-outline-secondary">Run Verify Ledger Integrity &rarr;</a>
</div>
