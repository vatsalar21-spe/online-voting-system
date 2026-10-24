<?php
use Core\Csrf;
use Core\Router;
use Core\View;
$errors = $_SESSION['errors'] ?? []; unset($_SESSION['errors']);
$old = $_SESSION['old'] ?? []; unset($_SESSION['old']);
$isEdit = $election !== null;
$title = $old['title'] ?? ($election['title'] ?? '');
$description = $old['description'] ?? ($election['description'] ?? '');
$format = $old['format'] ?? ($election['format'] ?? 'fptp');
$selectN = $old['select_n'] ?? ($election['select_n'] ?? '');
$startsAt = $old['starts_at'] ?? ($election ? str_replace(' ', 'T', substr($election['starts_at'], 0, 16)) : '');
$endsAt = $old['ends_at'] ?? ($election ? str_replace(' ', 'T', substr($election['ends_at'], 0, 16)) : '');
$action = $isEdit ? "/admin/elections/{$election['id']}" : '/admin/elections';
?>
<h1 class="h3 mb-4"><?= $isEdit ? 'Edit Election' : 'New Election' ?></h1>

<div class="card card-ovs">
    <div class="card-body">
        <form method="post" action="<?= Router::url($action) ?>">
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title, ENT_QUOTES) ?>" required>
                <?php if (!empty($errors['title'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['title'], ENT_QUOTES) ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($description, ENT_QUOTES) ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Voting Style
                        <span title="Single Choice: each voter picks exactly one candidate; whoever gets the most votes wins. Also called First-Past-the-Post (FPTP).&#10;&#10;Multiple Choice: each voter can pick more than one candidate, up to the N you set below; the top N vote-getters win." style="cursor: help; border-bottom: 1px dotted currentColor;">(what's this?)</span>
                    </label>
                    <select name="format" id="format" class="form-select" onchange="document.getElementById('select_n_wrap').style.display = this.value === 'select_n' ? 'block' : 'none';">
                        <option value="fptp" <?= $format === 'fptp' ? 'selected' : '' ?>>Single Choice &mdash; one vote per voter (FPTP)</option>
                        <option value="select_n" <?= $format === 'select_n' ? 'selected' : '' ?>>Multiple Choice &mdash; voter picks several candidates</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="select_n_wrap" style="display: <?= $format === 'select_n' ? 'block' : 'none' ?>;">
                    <label class="form-label">N (seats/choices allowed)</label>
                    <input type="number" min="1" name="select_n" class="form-control" value="<?= htmlspecialchars((string) $selectN, ENT_QUOTES) ?>">
                    <?php if (!empty($errors['select_n'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['select_n'], ENT_QUOTES) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Voting starts</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="<?= htmlspecialchars($startsAt, ENT_QUOTES) ?>" required>
                    <?php if (!empty($errors['starts_at'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['starts_at'], ENT_QUOTES) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Voting ends</label>
                    <input type="datetime-local" name="ends_at" class="form-control" value="<?= htmlspecialchars($endsAt, ENT_QUOTES) ?>" required>
                    <?php if (!empty($errors['ends_at'])): ?><div class="text-danger small"><?= htmlspecialchars($errors['ends_at'], ENT_QUOTES) ?></div><?php endif; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-ovs"><?= $isEdit ? 'Save Changes' : 'Create Election' ?></button>
            <a href="<?= Router::url('/admin/elections') ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
