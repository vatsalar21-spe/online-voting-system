<?php

namespace App\Models;

use Core\Database;

class Candidate
{
    public static function forElection(int $electionId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM candidates WHERE election_id = ? ORDER BY id ASC');
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM candidates WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $electionId, string $name, string $bio): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO candidates (election_id, name, bio) VALUES (?, ?, ?)'
        );
        $stmt->execute([$electionId, $name, $bio]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $name, string $bio): void
    {
        $stmt = Database::connection()->prepare('UPDATE candidates SET name = ?, bio = ? WHERE id = ?');
        $stmt->execute([$name, $bio, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM candidates WHERE id = ?');
        $stmt->execute([$id]);
    }
}
