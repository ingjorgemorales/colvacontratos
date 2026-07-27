<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Flash;
use App\Services\SmartAlertEngine;
use App\Models\IntelligencePro;

final class IntelligenceController
{
    public function index(): void
    {
        Auth::requireLogin();
        // Dos tablas paginadas de forma independiente: insights (?page) y logs (?lpage).
        $pg = new \App\Core\Paginator(IntelligencePro::countInsights(), 5, \App\Core\Paginator::pageFromRequest('page'));
        $pgLogs = new \App\Core\Paginator(IntelligencePro::countLogs(), 5, \App\Core\Paginator::pageFromRequest('lpage'));
        View::render('intelligence/index', [
            'kpis' => IntelligencePro::kpis(),
            'insights' => IntelligencePro::insights($pg->perPage(), $pg->offset()),
            'logs' => IntelligencePro::logs($pgLogs->perPage(), $pgLogs->offset()),
            'byArea' => IntelligencePro::byArea(),
            'byRisk' => IntelligencePro::byRisk(),
            'pg' => $pg,
            'pgLogs' => $pgLogs,
        ]);
    }

    public function run(): void
    {
        Auth::requireLogin();
        $result = SmartAlertEngine::run();
        Flash::set('success', 'Motor inteligente ejecutado. Evaluados: '.$result['evaluated'].' | Nuevos insights: '.$result['created'].' | Correos OK: '.$result['emails']);
        header('Location: index.php?r=intelligence');
        exit;
    }
}
