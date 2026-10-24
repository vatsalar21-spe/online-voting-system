<?php

namespace App\Controllers;

use App\Models\AuditLog;
use Core\Auth;
use Core\View;

class AdminAuditController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        View::render('admin/audit', ['entries' => AuditLog::all()]);
    }
}
