<?php use Core\Csrf; use Core\Router; ?>
<p class="eyebrow mb-1">Official Ballot</p>
<h1 class="h3 mb-4"><?= htmlspecialchars($election['title'], ENT_QUOTES) ?></h1>

<div class="card card-ovs">
    <div class="card-header">
        <?= $election['format'] === 'fptp' ? 'Choose one candidate' : 'Choose up to ' . $election['select_n'] . ' candidates' ?>
    </div>
    <div class="card-body">
        <form method="post" action="<?= Router::url('/voter/elections/' . $election['id'] . '/ballot') ?>" id="ballot-form">
            <?= Csrf::field() ?>
            <?php foreach ($candidates as $candidate): ?>
                <label class="ballot-row" for="c<?= $candidate['id'] ?>">
                    <?php if ($election['format'] === 'fptp'): ?>
                        <input type="radio" name="candidate_id" id="c<?= $candidate['id'] ?>" value="<?= $candidate['id'] ?>" required>
                    <?php else: ?>
                        <input type="checkbox" class="select-n-choice" name="candidate_ids[]" id="c<?= $candidate['id'] ?>" value="<?= $candidate['id'] ?>">
                    <?php endif; ?>
                    <span>
                        <strong><?= htmlspecialchars($candidate['name'], ENT_QUOTES) ?></strong>
                        <div class="small text-muted"><?= htmlspecialchars($candidate['bio'] ?? '', ENT_QUOTES) ?></div>
                    </span>
                </label>
            <?php endforeach; ?>
            <hr class="ballot-submit-rule">
            <div class="alert alert-warning">Once submitted, your vote is final and cannot be changed.</div>
            <button type="submit" class="btn btn-ovs">Submit Vote</button>
        </form>
    </div>
</div>

<?php if ($election['format'] === 'select_n'): ?>
<script>
(function () {
    const max = <?= (int) $election['select_n'] ?>;
    const boxes = document.querySelectorAll('.select-n-choice');
    boxes.forEach(box => box.addEventListener('change', () => {
        const checked = document.querySelectorAll('.select-n-choice:checked').length;
        boxes.forEach(b => { if (!b.checked) b.disabled = checked >= max; });
    }));
})();
</script>
<?php endif; ?>
