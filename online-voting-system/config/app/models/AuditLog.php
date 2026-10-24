<?php

namespace App\Models;

use Core\Database;

class AuditLog
{
    public static function record(?int $userId, string $action, ?string $details = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
    }

    public static function all(int $limit = 200): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT audit_log.*, users.name AS user_name
             FROM audit_log
             LEFT JOIN users ON users.id = audit_log.user_id
             ORDER BY audit_log.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
