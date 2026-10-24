<?php
use Core\Auth;
use Core\Router;
use Core\View;
$currentUser = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Voting System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@500;600;700&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= Router::url('/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-ovs">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= Router::url($currentUser ? ($currentUser['role'] === 'admin' ? '/admin/dashboard' : '/voter/dashboard') : '/login') ?>">
            <?php $sealSize = '2.15rem'; $sealTone = 'brass'; include __DIR__ . '/seal.php'; ?>
            <span>
                Online Voting System
                <small>Office of the Election Commissioner</small>
            </span>
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= Router::url('/admin/dashboard') ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= Router::url('/admin/elections') ?>">Elections</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= Router::url('/admin/voters') ?>">Voters</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= Router::url('/admin/ledger') ?>">Ledger</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= Router::url('/admin/audit-log') ?>">Audit Log</a></li>
                <?php elseif ($currentUser): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= Router::url('/voter/dashboard') ?>">Elections</a></li>
                <?php endif; ?>
                <?php if ($currentUser): ?>
                    <li class="nav-item"><span class="nav-link text-white-50"><?= htmlspecialchars($currentUser['name'], ENT_QUOTES) ?></span></li>
                    <li class="nav-item">
                        <form method="post" action="<?= Router::url('/logout') ?>" class="d-inline">
                            <?= \Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-link nav-link" style="border:0;">Logout</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= Router::url('/login') ?>">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-4">
    <?php $flashSuccess = View::flash('success'); $flashError = View::flash('error'); ?>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES) ?></div>
    <?php endif; ?>
