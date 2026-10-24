<?php

namespace App\Models;

use Core\Database;

class ElectionVoter
{
    public static function isEnrolled(int $userId, int $electionId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM election_voters WHERE election_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$electionId, $userId]);
        return (bool) $stmt->fetch();
    }

    public static function enroll(int $electionId, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO election_voters (election_id, user_id) VALUES (?, ?)'
        );
        $stmt->execute([$electionId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function unenroll(int $electionId, int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM election_voters WHERE election_id = ? AND user_id = ?'
        );
        $stmt->execute([$electionId, $userId]);
    }

    /**
     * Enrolled voters for an election, joined with their user info.
     */
    public static function forElection(int $electionId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT users.*, election_voters.enrolled_at
             FROM election_voters
             JOIN users ON users.id = election_voters.user_id
             WHERE election_voters.election_id = ?
             ORDER BY users.name ASC'
        );
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    /**
     * Approved voters not yet enrolled in this election — the candidate pool
     * for the "enroll an individual voter" dropdown.
     */
    public static function unenrolledApprovedVoters(int $electionId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT users.* FROM users
             WHERE users.role = 'voter' AND users.status = 'approved'
               AND users.id NOT IN (
                   SELECT user_id FROM election_voters WHERE election_id = ?
               )
             ORDER BY users.name ASC"
        );
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    /**
     * Elections a given voter is enrolled in — the set they're actually
     * allowed to see/vote in on their dashboard.
     */
    public static function electionsForVoter(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT elections.* FROM elections
             JOIN election_voters ON election_voters.election_id = elections.id
             WHERE election_voters.user_id = ?
             ORDER BY elections.starts_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
