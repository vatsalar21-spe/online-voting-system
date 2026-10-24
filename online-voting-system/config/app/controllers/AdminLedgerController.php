<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Election;
use Core\Auth;
use Core\Csrf;
use Core\Ledger;
use Core\Router;
use Core\View;

class AdminLedgerController
{
    public function index(): void
    {
        Auth::requireRole('admin');

        $chainResult = Ledger::verifyChain();
        AuditLog::record(Auth::id(), 'ledger_verified', $chainResult['intact'] ? 'intact' : 'TAMPERING DETECTED');

        $elections = Election::all();
        $electionId = isset($_GET['election_id']) && $_GET['election_id'] !== ''
            ? (int) $_GET['election_id']
            : null;

        $result = $electionId !== null
            ? Ledger::verifyElection($electionId, $chainResult)
            : $chainResult;

        View::render('admin/ledger', [
            'result' => $result,
            'elections' => $elections,
            'selectedElectionId' => $electionId,
        ]);
    }

    /**
     * Demo/viva tool: simulates a 3rd party editing a vote directly in the
     * database, bypassing the app entirely. Left in place deliberately —
     * every use is CSRF-protected, admin-only, and recorded in the audit
     * log, so it's a controlled demonstration, not a real vulnerability.
     */
    public function tamper(string $blockId): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect('/admin/ledger');
        }

        $change = Ledger::simulateTamper((int) $blockId);
        if (!$change) {
            View::flash('error', "Block #{$blockId} not found, or its election has no other candidate to swap the vote to.");
            Router::redirect('/admin/ledger');
        }

        $candidatesById = [];
        foreach (Candidate::forElection($change['election_id']) as $candidate) {
            $candidatesById[(int) $candidate['id']] = $candidate['name'];
        }

        $describe = function (string $choiceJson) use ($candidatesById): string {
            $decoded = json_decode($choiceJson, true);
            $ids = is_array($decoded) ? $decoded : [$decoded];
            $names = array_map(
                fn($id) => $candidatesById[(int) $id] ?? "Unknown candidate #{$id}",
                $ids
            );
            return implode(', ', $names);
        };

        $beforeLabel = $describe($change['before_choice_data']);
        $afterLabel = $describe($change['after_choice_data']);

        AuditLog::record(
            Auth::id(),
            'ledger_tamper_simulated',
            "Block #{$blockId}: {$beforeLabel} -> {$afterLabel} (demo — direct DB edit, not via the app)"
        );

        View::render('admin/ledger_tamper_result', [
            'election' => Election::find($change['election_id']),
            'change' => $change,
            'beforeLabel' => $beforeLabel,
            'afterLabel' => $afterLabel,
        ]);
    }
}
