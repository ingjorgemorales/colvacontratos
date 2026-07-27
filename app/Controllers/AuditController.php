<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Paginator;
use App\Models\AuditLog;

final class AuditController {
    public function index(): void {
        Auth::requireLogin();

        $filters = [
            'user_id' => (int) ($_GET['user_id'] ?? 0),
            'module'  => trim((string) ($_GET['module'] ?? '')),
            'q'       => trim((string) ($_GET['q'] ?? '')),
        ];

        $total = AuditLog::countFiltered($filters);
        $pg    = new Paginator($total, 25, Paginator::pageFromRequest());
        $logs  = AuditLog::filtered($filters, $pg->offset(), $pg->perPage());

        View::render('audit/index', [
            'logs'     => $logs,
            'filters'  => $filters,
            'usuarios' => AuditLog::usuarios(),
            'modulos'  => AuditLog::modulos(),
            'pg'       => $pg,
        ]);
    }
}
