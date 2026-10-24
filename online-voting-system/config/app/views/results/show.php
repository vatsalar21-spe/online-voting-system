<?php use App\Models\Election; use Core\Router; ?>
<p class="eyebrow mb-1">Certified Results</p>
<h1 class="h3 mb-1"><?= htmlspecialchars($election['title'], ENT_QUOTES) ?></h1>
<p class="text-muted"><?= $totalBallots ?> ballot<?= $totalBallots === 1 ? '' : 's' ?> cast &middot; <span title="<?= htmlspecialchars(Election::formatHint($election), ENT_QUOTES) ?>" style="cursor: help; border-bottom: 1px dotted currentColor;"><?= htmlspecialchars(Election::formatLabel($election), ENT_QUOTES) ?></span></p>

<?php if (!$ledgerIntact): ?>
<div class="alert alert-danger">
    <strong>Ledger integrity check failed.</strong> The counts below are tallied directly from the vote ledger, and at least one block doesn't match its recomputed hash &mdash; meaning this count may not reflect what was actually voted. Tallying alone can't catch tampering; only hash verification can.
    <?php if ($isAdmin): ?>
        <a href="<?= Router::url('/admin/ledger') ?>" class="alert-link">Investigate on Verify Ledger Integrity &rarr;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card card-ovs mb-4">
    <div class="card-body">
        <canvas id="resultsChart" height="100"></canvas>
    </div>
</div>

<div class="card card-ovs">
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Candidate</th><th>Votes</th><th></th></tr></thead>
            <tbody>
            <?php arsort($tally['totals']); foreach ($tally['totals'] as $candidateId => $votes): $c = $candidatesById[$candidateId] ?? null; if (!$c) continue; $isWinner = in_array($candidateId, $tally['winners'], true); ?>
                <tr class="<?= $isWinner ? 'table-success' : '' ?>">
                    <td><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></td>
                    <td class="font-mono"><?= $votes ?></td>
                    <td><?php if ($isWinner): ?><span class="badge status-badge-open">Certified Winner</span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<a href="<?= Router::url('/') ?>" class="btn btn-outline-secondary mt-3">&larr; Back</a>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const winners = <?= json_encode($tally['winners']) ?>;
const labels = <?= json_encode(array_map(fn($id) => $candidatesById[$id]['name'] ?? '?', array_keys($tally['totals']))) ?>;
const ids = <?= json_encode(array_keys($tally['totals'])) ?>;
const data = <?= json_encode(array_values($tally['totals'])) ?>;

Chart.defaults.font.family = "'Public Sans', sans-serif";
Chart.defaults.color = '#3E5170';

new Chart(document.getElementById('resultsChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Votes',
            data: data,
            backgroundColor: ids.map(id => winners.includes(id) ? '#A9812E' : '#10233F'),
            borderRadius: 3
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#EFE8D6' } },
            x: { grid: { display: false } }
        },
        plugins: { legend: { display: false } }
    }
});
</script>
