<?php

require __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Core\\' => __DIR__ . '/',
        'App\\Controllers\\' => __DIR__ . '/../app/controllers/',
        'App\\Models\\' => __DIR__ . '/../app/models/',
    ];
    foreach ($prefixes as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $dir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

$config = [
    'app' => require __DIR__ . '/../config/app.php',
    'database' => require __DIR__ . '/../config/database.php',
    'mail' => require __DIR__ . '/../config/mail.php',
];

date_default_timezone_set($config['app']['timezone']);

// A distinct session name keeps this app's session isolated from any other
// local PHP app. Cookies aren't scoped by port, so two dev servers running at
// once on localhost would otherwise share one session cookie (and, since
// PHP's default file-based handler keys purely by session ID with no per-app
// namespace, actually share session *data* too) — logging into one app would
// silently log you out of, or into, another.
session_name('online_voting_system_sess');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

Core\Config::init($config);
Core\Database::init($config['database']);
Core\Router::setBasePath($config['app']['base_path']);
