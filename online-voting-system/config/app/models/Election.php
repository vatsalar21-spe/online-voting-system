<?php

namespace App\Models;

use Core\Database;

class Election
{
    public static function all(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM elections ORDER BY starts_at DESC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM elections WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO elections (title, description, format, select_n, starts_at, ends_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['format'],
            $data['format'] === 'select_n' ? $data['select_n'] : null,
            $data['starts_at'],
            $data['ends_at'],
            $data['created_by'],
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE elections SET title = ?, description = ?, format = ?, select_n = ?, starts_at = ?, ends_at = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['format'],
            $data['format'] === 'select_n' ? $data['select_n'] : null,
            $data['starts_at'],
            $data['ends_at'],
            $id,
        ]);
    }

    public static function closeManually(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE elections SET closed_manually_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function publishResults(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE elections SET results_published = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Short, voter-friendly label for the voting format (fptp | select_n).
     * Kept in one place so admin/voter/results views all say the same thing.
     */
    public static function formatLabel(array $election): string
    {
        if ($election['format'] === 'fptp') {
            return 'Single Choice';
        }
        return 'Multiple Choice (up to ' . (int) $election['select_n'] . ')';
    }

    /**
     * Plain-language explanation of the voting format, meant for a tooltip
     * (title attribute) next to formatLabel() — spells out what the label means
     * and names the formal term (FPTP) for anyone who already knows it.
     */
    public static function formatHint(array $election): string
    {
        if ($election['format'] === 'fptp') {
            return 'Also called First-Past-the-Post (FPTP): each voter picks exactly one candidate, and whoever gets the most votes wins.';
        }
        $n = (int) $election['select_n'];
        return "Each voter can pick up to {$n} candidate" . ($n === 1 ? '' : 's') . "; the top {$n} vote-getters win.";
    }

    /**
     * upcoming | open | closed, derived from the voting window and manual close.
     */
    public static function status(array $election): string
    {
        if ($election['closed_manually_at'] !== null) {
            return 'closed';
        }
        $now = time();
        if ($now < strtotime($election['starts_at'])) {
            return 'upcoming';
        }
        if ($now > strtotime($election['ends_at'])) {
            return 'closed';
        }
        return 'open';
    }
}
