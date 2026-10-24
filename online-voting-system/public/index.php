<?php

// When running under `php -S` for local testing (not Apache/XAMPP), let the
// built-in server serve real files (assets) directly instead of routing them.
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($requested !== __DIR__ . '/' && is_file($requested)) {
        return false;
    }
}

require __DIR__ . '/../core/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\AdminElectionController;
use App\Controllers\AdminCandidateController;
use App\Controllers\AdminVoterController;
use App\Controllers\AdminElectionVoterController;
use App\Controllers\AdminLedgerController;
use App\Controllers\AdminAuditController;
use App\Controllers\VoterController;
use App\Controllers\VoteController;
use App\Controllers\ResultsController;
use Core\Router;

$router = new Router();

// Static assets are served directly by Apache from public/assets, not routed here.

// --- Auth ---
$router->get('/', [AuthController::class, 'showLogin']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// --- Admin: elections ---
$router->get('/admin/dashboard', [AdminElectionController::class, 'dashboard']);
$router->get('/admin/elections', [AdminElectionController::class, 'index']);
$router->get('/admin/elections/create', [AdminElectionController::class, 'create']);
$router->post('/admin/elections', [AdminElectionController::class, 'store']);
$router->get('/admin/elections/{id}/edit', [AdminElectionController::class, 'edit']);
$router->post('/admin/elections/{id}', [AdminElectionController::class, 'update']);
$router->post('/admin/elections/{id}/close', [AdminElectionController::class, 'close']);
$router->post('/admin/elections/{id}/publish-results', [AdminElectionController::class, 'publishResults']);

// --- Admin: candidates (nested under an election) ---
$router->get('/admin/elections/{id}/candidates', [AdminCandidateController::class, 'index']);
$router->post('/admin/elections/{id}/candidates', [AdminCandidateController::class, 'store']);
$router->post('/admin/candidates/{id}/update', [AdminCandidateController::class, 'update']);
$router->post('/admin/candidates/{id}/delete', [AdminCandidateController::class, 'delete']);

// --- Admin: voters (global roster) ---
$router->get('/admin/voters', [AdminVoterController::class, 'index']);
$router->get('/admin/voters/create', [AdminVoterController::class, 'create']);
$router->post('/admin/voters', [AdminVoterController::class, 'store']);
$router->get('/admin/voters/{id}/edit', [AdminVoterController::class, 'edit']);
$router->post('/admin/voters/{id}', [AdminVoterController::class, 'update']);
$router->post('/admin/voters/{id}/delete', [AdminVoterController::class, 'destroy']);
$router->post('/admin/voters/{id}/approve', [AdminVoterController::class, 'approve']);
$router->post('/admin/voters/{id}/reject', [AdminVoterController::class, 'reject']);

// --- Admin: election voters (per-election enrollment) ---
$router->get('/admin/elections/{id}/voters', [AdminElectionVoterController::class, 'index']);
$router->post('/admin/elections/{id}/voters/enroll', [AdminElectionVoterController::class, 'enroll']);
$router->post('/admin/elections/{id}/voters/{userId}/unenroll', [AdminElectionVoterController::class, 'unenroll']);
$router->post('/admin/elections/{id}/voters/bulk-upload', [AdminElectionVoterController::class, 'bulkUpload']);

// --- Admin: ledger + audit ---
$router->get('/admin/ledger', [AdminLedgerController::class, 'index']);
$router->post('/admin/ledger/{id}/tamper', [AdminLedgerController::class, 'tamper']);
$router->get('/admin/audit-log', [AdminAuditController::class, 'index']);

// --- Voter ---
$router->get('/voter/dashboard', [VoterController::class, 'dashboard']);
$router->get('/voter/elections/{id}', [VoterController::class, 'show']);

// --- Voting ---
$router->get('/voter/elections/{id}/ballot', [VoteController::class, 'showBallot']);
$router->post('/voter/elections/{id}/ballot', [VoteController::class, 'castVote']);
$router->get('/voter/elections/{id}/receipt', [VoteController::class, 'receipt']);

// --- Results (shared, format-aware; access-checked inside the controller) ---
$router->get('/elections/{id}/results', [ResultsController::class, 'show']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
