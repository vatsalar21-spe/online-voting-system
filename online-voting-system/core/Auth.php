<?php

namespace Core;

class Auth
{
    private static ?array $userCache = null;
    private static bool $userLoaded = false;

    public static function attempt(string $email, string $password): array|false
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || $user['password_hash'] === null) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        if ($user['status'] !== 'approved') {
            return false;
        }

        self::login($user);
        return $user;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$userCache = $user;
        self::$userLoaded = true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function user(): ?array
    {
        if (self::$userLoaded) {
            return self::$userCache;
        }
        self::$userLoaded = true;

        if (empty($_SESSION['user_id'])) {
            return self::$userCache = null;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;

        return self::$userCache = $user;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Router::redirect('/login');
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if (self::role() !== $role) {
            http_response_code(403);
            require __DIR__ . '/../app/views/errors/403.php';
            exit;
        }
    }
}
