<?php

namespace App\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\ElectionVoter;
use Core\Auth;
use Core\Ledger;
use Core\Router;
use Core\View;

class VoterController
{
    public function dashboard(): void
    {
        Auth::requireRole('voter');

        $voterId = Auth::id();
        $elections = ElectionVoter::electionsForVoter($voterId);
        foreach ($elections as &$election) {
            $election['status'] = Election::status($election);
            $election['has_voted'] = Ledger::hasVoted($voterId, (int) $election['id']);
        }
        unset($election);

        View::render('voter/dashboard', ['elections' => $elections]);
    }

    public function show(string $id): void
    {
        Auth::requireRole('voter');

        $election = Election::find((int) $id);
        if (!$election || !ElectionVoter::isEnrolled(Auth::id(), (int) $id)) {
            View::flash('error', 'You are not enrolled in that election.');
            Router::redirect('/voter/dashboard');
        }

        $election['status'] = Election::status($election);
        $election['has_voted'] = Ledger::hasVoted(Auth::id(), (int) $id);
        $candidates = Candidate::forElection((int) $id);

        View::render('voter/election_detail', ['election' => $election, 'candidates' => $candidates]);
    }
}
