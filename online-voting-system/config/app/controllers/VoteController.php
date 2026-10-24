<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\ElectionVoter;
use Core\Auth;
use Core\Csrf;
use Core\Ledger;
use Core\Router;
use Core\View;
use PDOException;

class VoteController
{
    public function showBallot(string $id): void
    {
        Auth::requireRole('voter');

        $election = $this->openElectionOrRedirect($id);
        $candidates = Candidate::forElection((int) $id);

        View::render('voter/ballot', ['election' => $election, 'candidates' => $candidates]);
    }

    public function castVote(string $id): void
    {
        Auth::requireRole('voter');
        if (!Csrf::verifyRequest()) {
            Router::redirect("/voter/elections/{$id}/ballot");
        }

        $election = $this->openElectionOrRedirect($id);
        $candidates = Candidate::forElection((int) $id);
        $validIds = array_map(fn($c) => (int) $c['id'], $candidates);

        if ($election['format'] === 'fptp') {
            $choice = (int) ($_POST['candidate_id'] ?? 0);
            if (!in_array($choice, $validIds, true)) {
                View::flash('error', 'Choose a valid candidate.');
                Router::redirect("/voter/elections/{$id}/ballot");
            }
        } else {
            $choice = array_map('intval', $_POST['candidate_ids'] ?? []);
            $choice = array_values(array_unique(array_intersect($choice, $validIds)));
            $max = (int) $election['select_n'];
            if (count($choice) === 0 || count($choice) > $max) {
                View::flash('error', "Choose between 1 and {$max} candidates.");
                Router::redirect("/voter/elections/{$id}/ballot");
            }
        }

        try {
            Ledger::addVote(Auth::id(), (int) $id, $choice);
        } catch (PDOException $e) {
            // Unique key on (voter_hash, election_id) — already voted, most
            // likely a double-submit rather than a real race in this scope.
            View::flash('error', 'You have already voted in this election.');
            Router::redirect('/voter/dashboard');
        }

        AuditLog::record(Auth::id(), 'vote_cast', "Election #{$id}");
        Router::redirect("/voter/elections/{$id}/receipt");
    }

    public function receipt(string $id): void
    {
        Auth::requireRole('voter');

        $election = Election::find((int) $id);
        if (!$election) {
            Router::redirect('/voter/dashboard');
        }

        $block = Ledger::findBlock(Auth::id(), (int) $id);
        if (!$block) {
            View::flash('error', "You haven't voted in this election yet.");
            Router::redirect("/voter/elections/{$id}");
        }

        View::render('voter/receipt', ['election' => $election, 'block' => $block]);
    }

    private function openElectionOrRedirect(string $id): array
    {
        $election = Election::find((int) $id);
        if (!$election) {
            Router::redirect('/voter/dashboard');
        }

        if (!ElectionVoter::isEnrolled(Auth::id(), (int) $id)) {
            View::flash('error', 'You are not enrolled to vote in this election.');
            Router::redirect('/voter/dashboard');
        }

        if (Election::status($election) !== 'open') {
            View::flash('error', 'This election is not currently open for voting.');
            Router::redirect('/voter/dashboard');
        }

        if (Ledger::hasVoted(Auth::id(), (int) $id)) {
            View::flash('error', 'You have already voted in this election.');
            Router::redirect("/voter/elections/{$id}/receipt");
        }

        return $election;
    }
}
