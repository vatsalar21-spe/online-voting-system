<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Election;
use Core\Auth;
use Core\Csrf;
use Core\Router;
use Core\View;

class AdminCandidateController
{
    public function index(string $electionId): void
    {
        Auth::requireRole('admin');

        $election = Election::find((int) $electionId);
        if (!$election) {
            Router::redirect('/admin/elections');
        }

        $candidates = Candidate::forElection((int) $electionId);
        View::render('admin/candidates/index', ['election' => $election, 'candidates' => $candidates]);
    }

    public function store(string $electionId): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect("/admin/elections/{$electionId}/candidates");
        }

        $election = Election::find((int) $electionId);
        if (!$election) {
            Router::redirect('/admin/elections');
        }
        if (Election::status($election) !== 'upcoming') {
            View::flash('error', 'Voting has started (or ended) for this election — the candidate list is locked.');
            Router::redirect("/admin/elections/{$electionId}/candidates");
        }

        $name = trim($_POST['name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if ($name === '') {
            View::flash('error', 'Candidate name is required.');
            Router::redirect("/admin/elections/{$electionId}/candidates");
        }

        $id = Candidate::create((int) $electionId, $name, $bio);
        AuditLog::record(Auth::id(), 'candidate_added', "Candidate #{$id} in election #{$electionId}: {$name}");

        View::flash('success', 'Candidate added.');
        Router::redirect("/admin/elections/{$electionId}/candidates");
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        $candidate = Candidate::find((int) $id);
        if (!$candidate) {
            Router::redirect('/admin/elections');
        }
        if (!Csrf::verifyRequest()) {
            Router::redirect("/admin/elections/{$candidate['election_id']}/candidates");
        }
        $election = Election::find((int) $candidate['election_id']);
        if (!$election || Election::status($election) !== 'upcoming') {
            View::flash('error', 'Voting has started (or ended) for this election — the candidate list is locked.');
            Router::redirect("/admin/elections/{$candidate['election_id']}/candidates");
        }

        $name = trim($_POST['name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        Candidate::update((int) $id, $name, $bio);
        AuditLog::record(Auth::id(), 'candidate_updated', "Candidate #{$id}");

        View::flash('success', 'Candidate updated.');
        Router::redirect("/admin/elections/{$candidate['election_id']}/candidates");
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        $candidate = Candidate::find((int) $id);
        if (!$candidate) {
            Router::redirect('/admin/elections');
        }
        if (!Csrf::verifyRequest()) {
            Router::redirect("/admin/elections/{$candidate['election_id']}/candidates");
        }
        $election = Election::find((int) $candidate['election_id']);
        if (!$election || Election::status($election) !== 'upcoming') {
            View::flash('error', 'Voting has started (or ended) for this election — candidates can no longer be removed.');
            Router::redirect("/admin/elections/{$candidate['election_id']}/candidates");
        }

        Candidate::delete((int) $id);
        AuditLog::record(Auth::id(), 'candidate_deleted', "Candidate #{$id} from election #{$candidate['election_id']}");

        View::flash('success', 'Candidate removed.');
        Router::redirect("/admin/elections/{$candidate['election_id']}/candidates");
    }
}
