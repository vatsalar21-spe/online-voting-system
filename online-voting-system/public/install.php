<?php

/**
 * One-time setup wizard for a fresh machine: tests the DB connection,
 * creates the database and imports database/schema.sql, sets a real admin
 * account, writes config/database.php and config/app.php, and locks itself
 * once done. Deliberately standalone — it can't require core/bootstrap.php,
 * since bootstrap.php needs the very config this script is about to create.
 *
 * DELETE OR RENAME THIS FILE once setup is complete — a working DB-setup
 * script reachable over HTTP is a real risk on anything but a local machine.
 */

$projectRoot = dirname(__DIR__);
$lockFile = $projectRoot . '/install.lock';
$databaseConfigPath = $projectRoot . '/config/database.php';
$appConfigPath = $projectRoot . '/config/app.php';
$schemaPath = $projectRoot . '/database/schema.sql';
$dbName = 'online_voting_system'; // fixed — matches database/schema.sql

function writePhpConfigFile(string $path, array $config): void
{
    $php = "<?php\n\nreturn " . var_export($config, true) . ";\n";
    file_put_contents($path, $php);
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

$alreadyInstalled = is_file($lockFile);

// --- Environment checks -----------------------------------------------
$checks = [
    'PHP 8.0 or newer' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'pdo_mysql extension' => extension_loaded('pdo_mysql'),
    'Composer dependencies installed (vendor/autoload.php)' => is_file($projectRoot . '/vendor/autoload.php'),
];
$optionalChecks = [
    'gd extension (for bulk voter roster import)' => extension_loaded('gd'),
    'zip extension (for bulk voter roster import)' => extension_loaded('zip'),
    'mbstring extension' => extension_loaded('mbstring'),
];
$hardFailed = array_filter($checks, fn($ok) => !$ok);

$errors = [];
$success = false;
$old = [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_user' => 'root',
    'admin_name' => '',
    'admin_email' => '',
];

if (!$alreadyInstalled && $_SERVER['REQUEST_METHOD'] === 'POST' && !$hardFailed) {
    $old['db_host'] = trim($_POST['db_host'] ?? '');
    $old['db_port'] = trim($_POST['db_port'] ?? '');
    $old['db_user'] = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $old['admin_name'] = trim($_POST['admin_name'] ?? '');
    $old['admin_email'] = trim($_POST['admin_email'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';

    if ($old['db_host'] === '') $errors[] = 'Database host is required.';
    if ($old['db_port'] === '' || !ctype_digit($old['db_port'])) $errors[] = 'Database port must be a number.';
    if ($old['db_user'] === '') $errors[] = 'Database user is required.';
    if ($old['admin_name'] === '') $errors[] = 'Admin name is required.';
    if (!filter_var($old['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid admin email address.';
    if (strlen($adminPassword) < 8) $errors[] = 'Admin password must be at least 8 characters.';
    if ($adminPassword !== $adminPasswordConfirm) $errors[] = 'Admin passwords do not match.';

    $pdo = null;
    if (!$errors) {
        try {
            $dsn = "mysql:host={$old['db_host']};port={$old['db_port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $old['db_user'], $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
            ]);
        } catch (PDOException $e) {
            $errors[] = "Couldn't connect to MySQL with those details: " . $e->getMessage();
        }
    }

    if (!$errors && $pdo) {
        try {
            $pdo->exec(file_get_contents($schemaPath));
        } catch (PDOException $e) {
            $errors[] = "Database setup failed: " . $e->getMessage()
                . " If some tables were partially created, drop the `{$dbName}` database manually and try again.";
        }
    }

    if (!$errors && $pdo) {
        try {
            $pdo->exec("USE `{$dbName}`");
            $stmt = $pdo->prepare(
                "UPDATE users SET name = ?, email = ?, password_hash = ? WHERE role = 'admin' ORDER BY id ASC LIMIT 1"
            );
            $stmt->execute([
                $old['admin_name'],
                $old['admin_email'],
                password_hash($adminPassword, PASSWORD_BCRYPT),
            ]);
        } catch (PDOException $e) {
            $errors[] = "Admin account setup failed: " . $e->getMessage();
        }
    }

    if (!$errors) {
        writePhpConfigFile($databaseConfigPath, [
            'host' => $old['db_host'],
            'port' => $old['db_port'],
            'name' => $dbName,
            'user' => $old['db_user'],
            'pass' => $dbPass,
            'charset' => 'utf8mb4',
        ]);

        writePhpConfigFile($appConfigPath, [
            'timezone' => 'Asia/Kolkata',
            'base_path' => '',
            'ledger_secret' => bin2hex(random_bytes(32)),
        ]);

        file_put_contents($lockFile, "Installed " . date('Y-m-d H:i:s') . "\n");
        $success = true;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup — Online Voting System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Zilla+Slab:wght@500;600;700&family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-ovs">
    <div class="container">
        <span class="navbar-brand d-flex align-items-center">
            <?php $sealSize = '2.15rem'; $sealTone = 'brass'; include __DIR__ . '/../app/views/partials/seal.php'; ?>
            <span>
                Online Voting System
                <small>Setup Wizard</small>
            </span>
        </span>
    </div>
</nav>

<div class="container py-4" style="max-width: 640px;">

<?php if ($alreadyInstalled): ?>
    <div class="card card-ovs">
        <div class="card-header">Already Installed</div>
        <div class="card-body">
            <p>This project has already been set up (<code><?= h($lockFile) ?></code> exists).</p>
            <p class="text-muted small">To run setup again — e.g. to point this install at a different database — delete <code>install.lock</code> from the project root first. Doing so on a system already in use will not undo an existing install; it only unlocks this wizard.</p>
            <a href="login" class="btn btn-ovs">Go to Login &rarr;</a>
        </div>
    </div>

<?php elseif ($success): ?>
    <div class="alert alert-success">
        <strong>Setup complete.</strong> Database created, schema imported, and your admin account is ready.
    </div>
    <div class="card card-ovs mb-4">
        <div class="card-header">Admin Login</div>
        <div class="card-body">
            <p>Email: <code><?= h($old['admin_email']) ?></code></p>
            <a href="login" class="btn btn-ovs">Go to Login &rarr;</a>
        </div>
    </div>
    <div class="alert alert-danger">
        <strong>Delete or rename this file now</strong> (<code>public/install.php</code>). Leaving a working database-setup script reachable over HTTP is a real risk on anything other than a local machine.
    </div>

<?php else: ?>
    <p class="eyebrow mb-1">Setup Wizard</p>
    <h1 class="h3 mb-4">Set Up This Installation</h1>

    <div class="card card-ovs mb-4">
        <div class="card-header">Environment Checks</div>
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <tbody>
                <?php foreach ($checks as $label => $ok): ?>
                    <tr class="<?= $ok ? '' : 'table-danger' ?>">
                        <td><?= h($label) ?></td>
                        <td class="text-end"><?= $ok ? '<span class="badge status-badge-open">OK</span>' : '<span class="badge" style="background:var(--ballot-red-bg);color:var(--ballot-red);border:1px solid var(--ballot-red)">MISSING</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach ($optionalChecks as $label => $ok): ?>
                    <tr>
                        <td><?= h($label) ?> <span class="text-muted small">(recommended)</span></td>
                        <td class="text-end"><?= $ok ? '<span class="badge status-badge-open">OK</span>' : '<span class="badge status-badge-upcoming">MISSING</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($hardFailed): ?>
        <div class="alert alert-danger">
            Fix the failed checks above before continuing — e.g. run <code>composer install</code> in the project root if dependencies are missing.
        </div>
    <?php else: ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="card card-ovs mb-4">
                <div class="card-header">Database Connection</div>
                <div class="card-body">
                    <p class="text-muted small">Database name is fixed as <code><?= h($dbName) ?></code>, matching <code>database/schema.sql</code>. It will be created if it doesn't already exist.</p>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Host</label>
                            <input type="text" name="db_host" class="form-control" value="<?= h($old['db_host']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Port</label>
                            <input type="text" name="db_port" class="form-control" value="<?= h($old['db_port']) ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="db_user" class="form-control" value="<?= h($old['db_user']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="db_pass" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-ovs mb-4">
                <div class="card-header">Your Admin Account</div>
                <div class="card-body">
                    <p class="text-muted small">Replaces the demo admin seeded in <code>schema.sql</code>. Everything else — demo voters, elections, ledger — stays as seeded, so there's a working demo out of the box.</p>
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" name="admin_name" class="form-control" value="<?= h($old['admin_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="admin_email" class="form-control" value="<?= h($old['admin_email']) ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="admin_password" class="form-control" required minlength="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm password</label>
                            <input type="password" name="admin_password_confirm" class="form-control" required minlength="8">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-ovs">Run Setup</button>
        </form>
    <?php endif; ?>
<?php endif; ?>

</div>
</body>
</html>
