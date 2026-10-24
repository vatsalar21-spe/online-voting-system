<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Election;
use App\Models\User;
use Core\Auth;
use Core\Csrf;
use Core\Database;
use Core\Router;
use Core\Validator;
use Core\View;

class AdminElectionController
{
    public function dashboard(): void
    {
        Auth::requireRole('admin');

        $elections = Election::all();
        foreach ($elections as &$election) {
            $election['status'] = Election::status($election);
        }
        unset($election);

        $stats = [
            'total_elections' => count($elections),
            'open_elections' => count(array_filter($elections, fn($e) => $e['status'] === 'open')),
            'pending_voters' => count(User::pending()),
            'approved_voters' => Database::connection()
                ->query("SELECT COUNT(*) c FROM users WHERE role = 'voter' AND status = 'approved'")
                ->fetch()['c'],
        ];

        View::render('admin/dashboard', ['elections' => $elections, 'stats' => $stats]);
    }

    public function index(): void
    {
        Auth::requireRole('admin');

        $elections = Election::all();
        foreach ($elections as &$election) {
            $election['status'] = Election::status($election);
        }
        unset($election);

        View::render('admin/elections/index', ['elections' => $elections]);
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        View::render('admin/elections/form', ['election' => null]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect('/admin/elections/create');
        }

        [$data, $ok] = $this->validated();
        if (!$ok) {
            Router::redirect('/admin/elections/create');
        }

        $data['created_by'] = Auth::id();
        $id = Election::create($data);
        AuditLog::record(Auth::id(), 'election_created', "Election #{$id}: {$data['title']}");

        View::flash('success', 'Election created.');
        Router::redirect('/admin/elections');
    }

    public function edit(string $id): void
    {
        Auth::requireRole('admin');
        $election = Election::find((int) $id);
        if (!$election) {
            Router::redirect('/admin/elections');
        }
        if (Election::status($election) !== 'upcoming') {
            View::flash('error', 'This election has already started (or closed) — it can no longer be edited.');
            Router::redirect('/admin/elections');
        }
        View::render('admin/elections/form', ['election' => $election]);
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect("/admin/elections/{$id}/edit");
        }

        $election = Election::find((int) $id);
        if (!$election) {
            Router::redirect('/admin/elections');
        }
        if (Election::status($election) !== 'upcoming') {
            View::flash('error', 'This election has already started (or closed) — it can no longer be edited.');
            Router::redirect('/admin/elections');
        }

        [$data, $ok] = $this->validated();
        if (!$ok) {
            Router::redirect("/admin/elections/{$id}/edit");
        }

        Election::update((int) $id, $data);
        AuditLog::record(Auth::id(), 'election_updated', "Election #{$id}");

        View::flash('success', 'Election updated.');
        Router::redirect('/admin/elections');
    }

    public function close(string $id): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect('/admin/elections');
        }

        Election::closeManually((int) $id);
        AuditLog::record(Auth::id(), 'election_closed', "Election #{$id}");

        View::flash('success', 'Election closed.');
        Router::redirect('/admin/elections');
    }

    public function publishResults(string $id): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect('/admin/elections');
        }

        Election::publishResults((int) $id);
        AuditLog::record(Auth::id(), 'results_published', "Election #{$id}");

        View::flash('success', 'Results published to voters.');
        Router::redirect('/admin/elections');
    }

    /**
     * @return array{0: array, 1: bool}
     */
    private function validated(): array
    {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'format' => $_POST['format'] ?? 'fptp',
            'select_n' => $_POST['select_n'] ?? '',
            'starts_at' => $_POST['starts_at'] ?? '',
            'ends_at' => $_POST['ends_at'] ?? '',
        ];

        $validator = new Validator($data);
        $validator->required('title')
            ->required('starts_at')
            ->required('ends_at');

        if (!in_array($data['format'], ['fptp', 'select_n'], true)) {
            $data['format'] = 'fptp';
        }
        if ($data['format'] === 'select_n') {
            $validator->required('select_n')->integer('select_n');
        }

        if ($validator->fails()) {
            $_SESSION['errors'] = $validator->firstErrors();
            $_SESSION['old'] = $data;
            return [$data, false];
        }

        $data['select_n'] = $data['select_n'] !== '' ? (int) $data['select_n'] : null;
        return [$data, true];
    }
}
