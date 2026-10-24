<?php

namespace App\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use Core\Auth;
use Core\FptpTally;
use Core\Ledger;
use Core\MultiChoiceTally;
use Core\Router;
use Core\View;

class ResultsController
{
    public function show(string $id): void
    {
        Auth::requireLogin();

        $election = Election::find((int) $id);
        if (!$election) {
            Router::redirect('/');
        }

        $isAdmin = Auth::role() === 'admin';
        $status = Election::status($election);

        if (!$isAdmin && !($status === 'closed' && $election['results_published'])) {
            View::flash('error', 'Results for this election have not been published yet.');
            Router::redirect('/voter/dashboard');
        }

        $candidates = Candidate::forElection((int) $id);
        $blocks = Ledger::blocksForElection((int) $id);

        $strategy = $election['format'] === 'select_n' ? new MultiChoiceTally() : new FptpTally();
        $tally = $strategy->tally($candidates, $blocks, $election['select_n'] ? (int) $election['select_n'] : null);

        $candidatesById = [];
        foreach ($candidates as $c) {
            $candidatesById[(int) $c['id']] = $c;
        }

        // Results are tallied straight from ledger_blocks — if the ledger's
        // been tampered with, the count above is silently wrong. Tallying
        // alone can't catch that; only recomputing hashes can, so surface it
        // here rather than let a corrupted-but-plausible-looking count stand.
        // Scoped to this election only — the ledger is one shared hash chain
        // across every election (see Core\Ledger), so a tampered block in a
        // different election must not flip this warning on here too.
        $ledgerIntact = Ledger::verifyElection((int) $id)['intact'];

        View::render('results/show', [
            'election' => $election,
            'candidates' => $candidates,
            'candidatesById' => $candidatesById,
            'tally' => $tally,
            'totalBallots' => count($blocks),
            'ledgerIntact' => $ledgerIntact,
            'isAdmin' => $isAdmin,
        ]);
    }
}
