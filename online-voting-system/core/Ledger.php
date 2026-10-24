<?php

namespace Core;

/**
 * Blockchain-style, tamper-evident vote storage: a single hash chain across
 * all elections, not a distributed/consensus blockchain (see FEATURES.md).
 * hash = sha256(id . created_at . voter_hash . choice_data . prev_hash) — this
 * exact formula also generated database/schema.sql's seed ledger blocks, so
 * changing it here would break integrity verification on seeded data.
 */
class Ledger
{
    private const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public static function voterHash(int $voterId, int $electionId): string
    {
        $secret = Config::get('app.ledger_secret');
        return hash('sha256', $voterId . ':' . $electionId . ':' . $secret);
    }

    public static function hasVoted(int $voterId, int $electionId): bool
    {
        return self::findBlock($voterId, $electionId) !== null;
    }

    public static function findBlock(int $voterId, int $electionId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM ledger_blocks WHERE voter_hash = ? AND election_id = ? LIMIT 1'
        );
        $stmt->execute([self::voterHash($voterId, $electionId), $electionId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param int|array $choice single candidate id (fptp) or array of candidate ids (select_n)
     * @return array the inserted block
     */
    public static function addVote(int $voterId, int $electionId, int|array $choice): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $last = $pdo->query('SELECT id, hash FROM ledger_blocks ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetch();

            $nextId = $last ? ((int) $last['id'] + 1) : 1;
            $prevHash = $last ? $last['hash'] : self::GENESIS_HASH;
            $voterHash = self::voterHash($voterId, $electionId);
            $choiceData = json_encode($choice);
            $createdAt = date('Y-m-d H:i:s');
            $hash = hash('sha256', $nextId . $createdAt . $voterHash . $choiceData . $prevHash);

            $stmt = $pdo->prepare(
                'INSERT INTO ledger_blocks (id, voter_hash, election_id, choice_data, prev_hash, hash, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$nextId, $voterHash, $electionId, $choiceData, $prevHash, $hash, $createdAt]);

            $pdo->commit();

            return [
                'id' => $nextId,
                'hash' => $hash,
                'voter_hash' => $voterHash,
                'election_id' => $electionId,
                'choice_data' => $choiceData,
                'prev_hash' => $prevHash,
                'created_at' => $createdAt,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Walks the whole chain and recomputes every hash. Returns per-block
     * results plus an overall verdict, for the admin "Verify Ledger
     * Integrity" screen.
     */
    public static function verifyChain(): array
    {
        $blocks = Database::connection()
            ->query('SELECT * FROM ledger_blocks ORDER BY id ASC')
            ->fetchAll();

        $prevHash = self::GENESIS_HASH;
        $results = [];
        $intact = true;

        foreach ($blocks as $block) {
            $expectedPrev = $prevHash;
            $recomputed = hash(
                'sha256',
                $block['id'] . $block['created_at'] . $block['voter_hash'] . $block['choice_data'] . $block['prev_hash']
            );

            $ok = $block['prev_hash'] === $expectedPrev && $recomputed === $block['hash'];
            if (!$ok) {
                $intact = false;
            }

            $results[] = [
                'id' => $block['id'],
                'election_id' => $block['election_id'],
                'ok' => $ok,
            ];

            $prevHash = $block['hash'];
        }

        return [
            'intact' => $intact,
            'block_count' => count($blocks),
            'blocks' => $results,
        ];
    }

    /**
     * Same verification as verifyChain(), scoped to one election's blocks.
     * Each block's `ok` verdict is computed independently of which election
     * it belongs to (see class doc-comment), so filtering the full chain's
     * results down to one election_id is enough — no need to re-verify.
     * Lets a results page report tampering only when *its own* votes were
     * touched, instead of flashing red for every election because some
     * unrelated block elsewhere in the shared chain failed.
     */
    public static function verifyElection(int $electionId, ?array $chainResult = null): array
    {
        $chainResult ??= self::verifyChain();
        $blocks = array_values(array_filter(
            $chainResult['blocks'],
            fn (array $b): bool => (int) $b['election_id'] === $electionId
        ));

        $intact = true;
        foreach ($blocks as $block) {
            if (!$block['ok']) {
                $intact = false;
                break;
            }
        }

        return [
            'intact' => $intact,
            'block_count' => count($blocks),
            'blocks' => $blocks,
        ];
    }

    /**
     * Of the given voter ids, which ones already have a ballot in the ledger —
     * across every election, or within one election if $electionId is given.
     * `voter_hash` is derived per (voter, election) rather than stored as a
     * plain user id (see class doc-comment), so there's no "WHERE user_id IN
     * (...)" to run: this maps every (voter, election) pair to its hash and
     * looks the whole set up in one query instead of one per voter.
     *
     * Used to stop an admin removing a voter (from the roster or from an
     * election's roll) whose ballot is already counted — deleting the person
     * doesn't and mustn't delete the block, so the ledger would otherwise keep
     * counting a vote with nobody on the roll behind it.
     *
     * @param int[] $voterIds
     * @return int[] the subset that has already voted
     */
    public static function voterIdsWithBallots(array $voterIds, ?int $electionId = null): array
    {
        if (!$voterIds) {
            return [];
        }

        if ($electionId !== null) {
            $electionIds = [$electionId];
        } else {
            $rows = Database::connection()->query('SELECT id FROM elections')->fetchAll();
            $electionIds = array_map('intval', array_column($rows, 'id'));
        }
        if (!$electionIds) {
            return [];
        }

        $hashToVoter = [];
        foreach ($voterIds as $voterId) {
            foreach ($electionIds as $eid) {
                $hashToVoter[self::voterHash((int) $voterId, (int) $eid)] = (int) $voterId;
            }
        }

        $placeholders = implode(',', array_fill(0, count($hashToVoter), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT DISTINCT voter_hash FROM ledger_blocks WHERE voter_hash IN ({$placeholders})"
        );
        $stmt->execute(array_keys($hashToVoter));

        $voted = [];
        foreach ($stmt->fetchAll() as $row) {
            $voted[$hashToVoter[$row['voter_hash']]] = true;
        }

        return array_keys($voted);
    }

    public static function blocksForElection(int $electionId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM ledger_blocks WHERE election_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    /**
     * Demo/viva tool: mutates a block's choice_data directly, the way a 3rd
     * party with raw DB access (not going through this app) would — leaving
     * hash/prev_hash untouched, since a real attacker wouldn't know the
     * ledger_secret needed to recompute a matching hash that verifyChain()
     * would accept. Swaps the vote to a genuinely different, real candidate
     * in the same election (rather than an arbitrary id) so the before/after
     * is a legible diff, not just noise.
     *
     * @return array{block_id: int, election_id: int, before_choice_data: string, after_choice_data: string, stored_hash: string, recomputed_hash: string}|null
     */
    public static function simulateTamper(int $blockId): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM ledger_blocks WHERE id = ?');
        $stmt->execute([$blockId]);
        $block = $stmt->fetch();
        if (!$block) {
            return null;
        }

        $electionId = (int) $block['election_id'];
        $candidateStmt = $pdo->prepare('SELECT id FROM candidates WHERE election_id = ? ORDER BY id');
        $candidateStmt->execute([$electionId]);
        $allCandidateIds = array_map('intval', array_column($candidateStmt->fetchAll(), 'id'));

        $before = json_decode($block['choice_data'], true);
        $beforeIds = is_array($before) ? array_map('intval', $before) : [(int) $before];

        $swapTo = array_values(array_diff($allCandidateIds, $beforeIds))[0] ?? null;
        if ($swapTo === null) {
            return null; // no other candidate to swap to
        }

        if (is_array($before)) {
            $afterIds = $beforeIds;
            $afterIds[0] = $swapTo;
            $after = json_encode($afterIds);
        } else {
            $after = json_encode($swapTo);
        }

        $update = $pdo->prepare('UPDATE ledger_blocks SET choice_data = ? WHERE id = ?');
        $update->execute([$after, $blockId]);

        $recomputedHash = hash(
            'sha256',
            $block['id'] . $block['created_at'] . $block['voter_hash'] . $after . $block['prev_hash']
        );

        return [
            'block_id' => (int) $block['id'],
            'election_id' => $electionId,
            'before_choice_data' => $block['choice_data'],
            'after_choice_data' => $after,
            'stored_hash' => $block['hash'],
            'recomputed_hash' => $recomputedHash,
        ];
    }
}
