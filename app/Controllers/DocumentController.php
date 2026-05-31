<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Contract;
use App\Models\ContractDocument;
use App\Models\ContractAddendum;
use App\Models\ContractPolicy;
use App\Models\DocumentFlow;

final class DocumentController {
    public function index(): void {
        Auth::requireLogin();
        $contractId = (int)($_GET['contract_id'] ?? 0);
        if ($contractId <= 0) {
            $contracts = Contract::paginate([], 200, 0);
            View::render('documents/list', compact('contracts'));
            return;
        }
        $contract = Contract::find($contractId);
        if (!$contract) { header('Location: index.php?r=contracts'); exit; }

        View::render('documents/index', [
            'contract' => $contract,
            'documents' => ContractDocument::byContract($contractId),
            'addendums' => ContractAddendum::byContract($contractId),
            'policies' => ContractPolicy::byContract($contractId),
            'checklist' => DocumentFlow::checklist($contractId),
        ]);
    }

    public function upload(): void {
        Auth::requireLogin();
        $contractId = (int)($_POST['contract_id'] ?? 0);
        $type = trim($_POST['document_type'] ?? 'general');
        $notes = trim($_POST['notes'] ?? '');
        if ($contractId <= 0 || empty($_FILES['file']['name'])) { header('Location: index.php?r=contracts'); exit; }
        $allowed = ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx'];
        $original = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) { header('Location: index.php?r=documents&contract_id='.$contractId.'&err=tipo'); exit; }
        $root = dirname(__DIR__, 2);
        $dir = $root . '/storage/documentos/' . $contractId;
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }
        $safeName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $original);
        $dest = $dir . '/' . $safeName;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) { ContractDocument::create($contractId, $type, $original, 'storage/documentos/'.$contractId.'/'.$safeName, $notes); }
        header('Location: index.php?r=documents&contract_id='.$contractId); exit;
    }

    public function download(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $doc = ContractDocument::find($id);
        if (!$doc) { http_response_code(404); exit('Documento no encontrado'); }
        $root = dirname(__DIR__, 2);
        $filePath = $doc['path_resolved'] ?? $doc['file_path'] ?? $doc['stored_path'] ?? '';
        $path = $root . '/' . ltrim($filePath, '/');
        if (!is_file($path)) { http_response_code(404); exit('Archivo no encontrado'); }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($doc['original_name']).'"');
        header('Content-Length: '.filesize($path));
        readfile($path); exit;
    }

    public function delete(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $contractId = (int)($_GET['contract_id'] ?? 0);
        $doc = ContractDocument::find($id);
        if ($doc) {
            $root = dirname(__DIR__, 2);
            $filePath = $doc['path_resolved'] ?? $doc['file_path'] ?? $doc['stored_path'] ?? '';
            $path = $root . '/' . ltrim($filePath, '/');
            if (is_file($path)) @unlink($path);
            ContractDocument::delete($id);
            $contractId = $contractId ?: (int)$doc['contract_id'];
        }
        header('Location: index.php?r=documents&contract_id='.$contractId); exit;
    }

    public function addAddendum(): void {
        Auth::requireLogin();
        $contractId = (int)($_POST['contract_id'] ?? 0);
        ContractAddendum::create(['contract_id'=>$contractId,'start_date'=>$_POST['start_date'] ?? null,'end_date'=>$_POST['end_date'] ?? null,'value'=>$_POST['value'] ?? 0,'description'=>$_POST['description'] ?? '']);
        header('Location: index.php?r=documents&contract_id='.$contractId); exit;
    }

    public function addPolicy(): void {
        Auth::requireLogin();
        $contractId = (int)($_POST['contract_id'] ?? 0);
        ContractPolicy::create(['contract_id'=>$contractId,'policy_type'=>$_POST['policy_type'] ?? '','policy_number'=>$_POST['policy_number'] ?? '','provider'=>$_POST['provider'] ?? '','start_date'=>$_POST['start_date'] ?? null,'end_date'=>$_POST['end_date'] ?? null,'insured_value'=>$_POST['insured_value'] ?? 0]);
        header('Location: index.php?r=documents&contract_id='.$contractId); exit;
    }
}
