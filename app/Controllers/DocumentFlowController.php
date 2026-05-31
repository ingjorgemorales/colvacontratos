<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Flash;
use App\Models\Contract;
use App\Models\DocumentFlow;

final class DocumentFlowController {
    public function viewer(): void {
        Auth::requireLogin();
        $documentId = (int)($_GET['document_id'] ?? 0);
        $doc = DocumentFlow::document($documentId);
        if (!$doc) { http_response_code(404); exit('Documento no encontrado'); }
        $contract = Contract::find((int)$doc['contract_id']);
        View::render('documentflow/viewer', ['doc'=>$doc, 'contract'=>$contract, 'versions'=>DocumentFlow::versions($documentId), 'fields'=>DocumentFlow::fields((int)$doc['contract_id'], $documentId), 'checklist'=>DocumentFlow::checklist((int)$doc['contract_id'])]);
    }
    public function addField(): void {
        Auth::requireLogin();
        DocumentFlow::addField($_POST);
        Flash::set('success','Campo de firma agregado.');
        header('Location: index.php?r=documentflow.viewer&document_id='.(int)($_POST['document_id'] ?? 0)); exit;
    }
    public function markSigned(): void {
        Auth::requireLogin();
        DocumentFlow::markSigned((int)($_GET['id'] ?? 0));
        Flash::set('success','Campo marcado como firmado.');
        header('Location: index.php?r=documentflow.viewer&document_id='.(int)($_GET['document_id'] ?? 0)); exit;
    }
    public function checklistUpdate(): void {
        Auth::requireLogin();
        DocumentFlow::updateChecklist((int)($_POST['id'] ?? 0), $_POST['status'] ?? 'pendiente', $_POST['notes'] ?? '');
        Flash::set('success','Checklist documental actualizado.');
        header('Location: index.php?r=documents&contract_id='.(int)($_POST['contract_id'] ?? 0)); exit;
    }
}
