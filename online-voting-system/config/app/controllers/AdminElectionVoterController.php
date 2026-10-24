<?php

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Election;
use App\Models\ElectionVoter;
use App\Models\User;
use Core\Auth;
use Core\Csrf;
use Core\Ledger;
use Core\Mailer;
use Core\Router;
use Core\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminElectionVoterController
{
    public function index(string $electionId): void
    {
        Auth::requireRole('admin');

        $election = Election::find((int) $electionId);
        if (!$election) {
            Router::redirect('/admin/elections');
        }

        $enrolled = ElectionVoter::forElection((int) $electionId);
        $available = ElectionVoter::unenrolledApprovedVoters((int) $electionId);

        // Voters who've already voted here can't be removed from the roll (see
        // unenroll()) — the view hides their Remove button rather than letting
        // the click fail.
        $votedIds = Ledger::voterIdsWithBallots(
            array_map(fn($v) => (int) $v['id'], $enrolled),
            (int) $electionId
        );

        View::render('admin/elections/voters', [
            'election' => $election,
            'enrolled' => $enrolled,
            'available' => $available,
            'votedIds' => $votedIds,
        ]);
    }

    public function enroll(string $electionId): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect("/admin/elections/{$electionId}/voters");
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $voter = User::findById($userId);

        if (!$voter || $voter['role'] !== 'voter' || $voter['status'] !== 'approved') {
            View::flash('error', 'Choose a valid, approved voter.');
            Router::redirect("/admin/elections/{$electionId}/voters");
        }

        ElectionVoter::enroll((int) $electionId, $userId);
        AuditLog::record(Auth::id(), 'voter_enrolled', "User #{$userId} in election #{$electionId}");

        View::flash('success', 'Voter enrolled.');
        Router::redirect("/admin/elections/{$electionId}/voters");
    }

    public function unenroll(string $electionId, string $userId): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect("/admin/elections/{$electionId}/voters");
        }

        // Their ballot is already in the hash chain and keeps counting toward
        // this election's tally whatever happens to the roll — pulling them off
        // it would leave the roll smaller than the number of votes cast.
        if (Ledger::hasVoted((int) $userId, (int) $electionId)) {
            View::flash('error', 'This voter has already cast their ballot in this election, so they can no longer be removed from its roll — their vote stays permanently in the ledger and would otherwise be counted with nobody enrolled behind it.');
            Router::redirect("/admin/elections/{$electionId}/voters");
        }

        ElectionVoter::unenroll((int) $electionId, (int) $userId);
        AuditLog::record(Auth::id(), 'voter_unenrolled', "User #{$userId} from election #{$electionId}");

        View::flash('success', 'Voter removed from this election.');
        Router::redirect("/admin/elections/{$electionId}/voters");
    }

    public function bulkUpload(string $electionId): void
    {
        Auth::requireRole('admin');
        if (!Csrf::verifyRequest()) {
            Router::redirect("/admin/elections/{$electionId}/voters");
        }

        $election = Election::find((int) $electionId);
        if (!$election) {
            Router::redirect('/admin/elections');
        }

        $file = $_FILES['roster'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            View::flash('error', 'Please choose a valid .xlsx file to upload.');
            Router::redirect("/admin/elections/{$electionId}/voters");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $maxBytes = 5 * 1024 * 1024;

        if ($ext !== 'xlsx' || $file['size'] > $maxBytes) {
            View::flash('error', 'Roster must be a .xlsx file under 5MB.');
            Router::redirect("/admin/elections/{$electionId}/voters");
        }

        $safeName = bin2hex(random_bytes(16)) . '.xlsx';
        $storedPath = __DIR__ . '/../../public/uploads/' . $safeName;
        move_uploaded_file($file['tmp_name'], $storedPath);

        $newAccounts = [];
        $enrolled = 0;
        $alreadyEnrolled = 0;
        $invalid = 0;

        try {
            $spreadsheet = IOFactory::load($storedPath);
            $rows = $spreadsheet->getActiveSheet()->toArray();

            // Expect header row: name, email
            foreach (array_slice($rows, 1) as $row) {
                $name = trim((string) ($row[0] ?? ''));
                $email = trim((string) ($row[1] ?? ''));

                if ($name === '' && $email === '') {
                    continue;
                }

                $result = User::bulkImportRow($name, $email);
                if ($result['status'] === 'invalid') {
                    $invalid++;
                    continue;
                }

                if ($result['status'] === 'created') {
                    $newAccounts[] = $result;
                    $emailSafeName = htmlspecialchars($result['name'], ENT_QUOTES);
                    Mailer::send(
                        $result['email'],
                        $result['name'],
                        'Your voter account for the Online Voting System',
                        "<p>Hello {$emailSafeName},</p><p>You've been enrolled for \"{$election['title']}\".</p><p>Login email: {$result['email']}<br>Temporary password: {$result['password']}</p><p>Please log in and change your password.</p>"
                    );
                }

                $wasEnrolled = ElectionVoter::enroll((int) $electionId, $result['user_id']);
                $wasEnrolled ? $enrolled++ : $alreadyEnrolled++;
            }
        } finally {
            @unlink($storedPath);
        }

        AuditLog::record(
            Auth::id(),
            'voters_bulk_enrolled',
            "election #{$electionId}: created=" . count($newAccounts) . " enrolled={$enrolled} already_enrolled={$alreadyEnrolled} invalid={$invalid}"
        );

        View::render('admin/elections/bulk_upload_result', [
            'election' => $election,
            'newAccounts' => $newAccounts,
            'enrolled' => $enrolled,
            'alreadyEnrolled' => $alreadyEnrolled,
            'invalid' => $invalid,
        ]);
    }
}
