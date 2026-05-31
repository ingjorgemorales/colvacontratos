<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Report;

final class DashboardController {
    public function index(): void {
        Auth::requireLogin();
        $stats = Report::dashboardStats();
        $riskSummary = Report::riskSummary();
        $financialSummary = Report::financialSummary();
        $byStatus = Report::byStatus();
        $byArea = Report::byArea();
        $byExpenseType = Report::byExpenseType();
        $bySelectionModality = Report::bySelectionModality();
        $expiringRows = Report::expiring(90);
        $recentContracts = Report::recentContracts(8);
        View::render('dashboard/index', compact('stats','riskSummary','financialSummary','byStatus','byArea','byExpenseType','bySelectionModality','expiringRows','recentContracts'));
    }
}
