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
        View::render('intelligence/index', [
            'kpis' => IntelligencePro::kpis(),
            'insights' => IntelligencePro::insights(),
            'logs' => IntelligencePro::logs(),
            'byArea' => IntelligencePro::byArea(),
            'byRisk' => IntelligencePro::byRisk(),
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
