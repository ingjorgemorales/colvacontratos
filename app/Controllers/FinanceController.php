<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Flash;
use App\Models\Contract;
use App\Models\Payment;

final class FinanceController {
    public function index(): void {
        Auth::requireLogin();
        View::render('finance/index', ['contracts'=>Payment::overview()]);
    }

    public function contract(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $contract = Contract::find($id);
        if (!$contract) { http_response_code(404); View::render('errors/404'); return; }
        View::render('finance/contract', [
            'contract'=>$contract,
            'payments'=>Payment::byContract($id),
            'summary'=>Payment::summaryByContract($id)
        ]);
    }

    public function store(): void {
        Auth::requireLogin();
        $contractId = (int)($_GET['id'] ?? 0);
        if ($contractId <= 0) { Flash::set('danger','Contrato no válido.'); header('Location: index.php?r=finance'); exit; }
        if (trim($_POST['invoice_number'] ?? '') === '') { Flash::set('danger','El número de factura es obligatorio.'); header('Location: index.php?r=finance.contract&id='.$contractId); exit; }
        Payment::create($contractId, $_POST);
        Flash::set('success','Registro financiero agregado y contrato recalculado.');
        header('Location: index.php?r=finance.contract&id='.$contractId); exit;
    }

    public function delete(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $contractId = (int)($_GET['contract_id'] ?? 0);
        if ($id > 0) Payment::delete($id);
        Flash::set('success','Registro financiero eliminado.');
        header('Location: index.php?r=finance.contract&id='.$contractId); exit;
    }
}
