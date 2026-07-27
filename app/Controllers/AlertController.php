<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Flash;
use App\Models\Alert;
use App\Services\Mailer;

final class AlertController {
    public function index(): void {
        Auth::requireLogin();
        $days = max(1, min(365, (int)($_GET['days'] ?? 30)));
        $expiring = Alert::expiringContracts($days);
        $expired = Alert::expiredContracts();
        $missingDocs = Alert::missingDocuments();
        // Bitácora paginada (5 por página) con parámetro propio ?lpage.
        $pgLogs = new \App\Core\Paginator(Alert::countLogs(), 5, \App\Core\Paginator::pageFromRequest('lpage'));
        $logs = Alert::logs($pgLogs->perPage(), $pgLogs->offset());
        View::render('alerts/index', compact('days','expiring','expired','missingDocs','logs','pgLogs'));
    }

    public function run(): void {
        Auth::requireLogin();
        $days = max(1, min(365, (int)($_POST['days'] ?? 30)));
        $rows = Alert::expiringContracts($days);
        $sent = 0;
        foreach ($rows as $r) {
            $to = trim((string)($r['provider_email'] ?? ''));
            $subject = 'Alerta vencimiento contrato '.$r['number'];
            $body = "Contrato: {$r['number']} - {$r['name']}\nProveedor: {$r['provider_name']}\nVence: {$r['due_date']}\nDías restantes: {$r['days_left']}\n";
            if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $ok = Mailer::send($to, $subject, $body);
                Alert::log((int)$r['id'], 'vencimiento', 'email', $to, $subject, $ok ? 'sent' : 'error', $ok ? null : 'Mailer::send() retornó false');
                if ($ok) $sent++;
            } else {
                Alert::log((int)$r['id'], 'vencimiento', 'email', $to, $subject, 'skipped', 'Proveedor sin correo válido');
            }
        }
        Flash::set('success', "Proceso ejecutado. Correos enviados: {$sent}. Registros evaluados: ".count($rows));
        header('Location: index.php?r=alerts&days='.$days);
        exit;
    }
}
