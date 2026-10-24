<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Core\Auth;
use Core\Csrf;
use Core\Ledger;
use Core\Mailer;
use Core\Router;
use Core\Validator;
use Core\View;

class AdminVoterController
{
    public function index(): void
    {
        Auth::requireRole('admin');

        $voters = User::allVoters();
        $pending = array_values(array_filter($voters, fn($v) => $v['status'] === 'pending'));
        $approved = array_values(array_filter($voters, fn($v) => $v['status'] === 'approved'));
        $rejected = array_values(array_filter($voters, fn($v) => $v['status'] === 'rejected'));

        // Voters whose ballot is already in the ledger can't be deleted (see
        // destroy()) — the view hides their Delete button rather than letting
        // the click fail.
        $votedIds = Ledger::voterIdsWithBallots(array_map(fn($v) => (int) $v['id'], $voters));

        View::render('admin/voters/index', compact('pending', 'approved', 'rejected', 'votedIds'));
    }

    public function approve(string $id): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect('/admin/voters');
        }

        User::approve((int) $id);
        AuditLog::record(Auth::id(), 'voter_approved', "User #{$id}");

        View::flash('success', 'Voter approved.');
        Router::redirect('/admin/voters');
    }

    public function reject(string $id): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect('/admin/voters');
        }

        User::reject((int) $id);
        AuditLog::record(Auth::id(), 'voter_rejected', "User #{$id}");

        View::flash('success', 'Voter rejected.');
        Router::redirect('/admin/voters');
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        View::render('admin/voters/form', ['voter' => null]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect('/admin/voters/create');
        }

        [$data, $ok] = $this->validated(null);
        if (!$ok) {
            Router::redirect('/admin/voters/create');
        }

        $result = User::createManual($data['name'], $data['email']);
        AuditLog::record(Auth::id(), 'voter_added', "User #{$result['id']}: {$data['email']}");

        $safeName = htmlspecialchars($data['name'], ENT_QUOTES);
        Mailer::send(
            $data['email'],
            $data['name'],
            'Your voter account for the Online Voting System',
            "<p>Hello {$safeName},</p><p>An account was created for you.</p><p>Login email: {$data['email']}<br>Temporary password: {$result['password']}</p><p>Please log in and change your password.</p>"
        );

        View::flash('success', "Voter added. Login: {$data['email']} / Temp password: {$result['password']} — share this with them directly, don't rely on email having gone through. Enroll them into specific elections separately.");
        Router::redirect('/admin/voters');
    }

    public function edit(string $id): void
    {
        Auth::requireRole('admin');
        $voter = User::findById((int) $id);
        if (!$voter || $voter['role'] !== 'voter') {
            Router::redirect('/admin/voters');
        }
        View::render('admin/voters/form', ['voter' => $voter]);
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        $voter = User::findById((int) $id);
        if (!$voter || $voter['role'] !== 'voter') {
            Router::redirect('/admin/voters');
        }
        if (!Csrf::verifyRequest()) {
            Router::redirect("/admin/voters/{$id}/edit");
        }

        [$data, $ok] = $this->validated((int) $id);
        if (!$ok) {
            Router::redirect("/admin/voters/{$id}/edit");
        }

        User::updateDetails((int) $id, $data['name'], $data['email']);
        AuditLog::record(Auth::id(), 'voter_updated', "User #{$id}");

        View::flash('success', 'Voter updated.');
        Router::redirect('/admin/voters');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect('/admin/voters');
        }

        $voter = User::findById((int) $id);
        if ($voter && $voter['role'] === 'voter') {
            // Their ballot lives in the hash chain, keyed by a derived
            // voter_hash with no FK back to users — deleting the account would
            // leave the vote counting in every tally with nobody on any roll
            // behind it (and removing the block instead would break the chain).
            if (Ledger::voterIdsWithBallots([(int) $id])) {
                View::flash('error', 'This voter has already cast a ballot, so their account can no longer be deleted — their vote is permanently recorded in the ledger and would otherwise keep counting with no voter behind it.');
                Router::redirect('/admin/voters');
            }

            User::delete((int) $id);
            AuditLog::record(Auth::id(), 'voter_deleted', "User #{$id}: {$voter['email']}");
        }

        View::flash('success', 'Voter deleted.');
        Router::redirect('/admin/voters');
    }

    /**
     * @return array{0: array, 1: bool}
     */
    private function validated(?int $exceptId): array
    {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
        ];

        $validator = new Validator($data);
        $validator->required('name')
            ->required('email')->email('email')->unique('email', 'users', 'email', $exceptId);

        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->firstErrors();
            $_SESSION['old'] = $data;
            return [$data, false];
        }

        return [$data, true];
    }
}
