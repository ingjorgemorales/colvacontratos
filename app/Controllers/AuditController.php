<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AuditLog;

final class AuditController {
    public function index(): void {
        Auth::requireLogin();
        $logs = AuditLog::latest(150);
        View::render('audit/index', compact('logs'));
    }
}
