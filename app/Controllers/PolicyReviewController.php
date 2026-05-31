<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Flash;
use App\Models\PolicyReview;
use App\Core\Database;

final class PolicyReviewController {
    public function index(): void {
        Auth::requireLogin();
        $filters=$_GET;
        $reviews=PolicyReview::all($filters);
        $kpis=PolicyReview::kpis();
        View::render('policy_reviews/index', compact('reviews','kpis','filters'));
    }

    public function create(): void {
        Auth::requireLogin();
        $contracts=$this->contracts();
        View::render('policy_reviews/form', compact('contracts'));
    }

    public function store(): void {
        Auth::requireLogin();
        $base=dirname(__DIR__,2).'/storage/policy_reviews';
        if (!is_dir($base)) @mkdir($base,0775,true);
        $safeDir=date('Ymd_His').'_'.bin2hex(random_bytes(4));
        $dir=$base.'/'.$safeDir; @mkdir($dir,0775,true);
        $saved=[];
        foreach(['contract_file'=>'Contrato','policy_file'=>'Poliza','other_files'=>'Soporte'] as $field=>$label){
            if (empty($_FILES[$field])) continue;
            $group=$_FILES[$field];
            $names=is_array($group['name'])?$group['name']:[$group['name']];
            $tmps=is_array($group['tmp_name'])?$group['tmp_name']:[$group['tmp_name']];
            $errs=is_array($group['error'])?$group['error']:[$group['error']];
            $types=is_array($group['type'])?$group['type']:[$group['type']];
            $sizes=is_array($group['size'])?$group['size']:[$group['size']];
            foreach($names as $i=>$name){
                if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($tmps[$i])) continue;
                $ext=strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext,['pdf','doc','docx','xls','xlsx','png','jpg','jpeg'],true)) continue;
                $stored=$safeDir.'/'.preg_replace('/[^A-Za-z0-9_.-]+/','_',uniqid('',true).'_'.$name);
                if (move_uploaded_file($tmps[$i], $base.'/'.$stored)) {
                    $saved[]=['file_type'=>$label,'original_name'=>$name,'stored_path'=>$stored,'mime_type'=>$types[$i]??'', 'size_bytes'=>(int)($sizes[$i]??0)];
                }
            }
        }
        $checklist=[];
        foreach(['vigencia','asegurado','beneficiario','amparos','valor_asegurado','firmas'] as $c){ $checklist[$c]=!empty($_POST['check_'.$c]); }
        $user=Auth::user();
        $id=PolicyReview::create([
            'contract_id'=>$_POST['contract_id'] ?? null,
            'contract_number'=>trim($_POST['contract_number'] ?? ''),
            'contractor_name'=>trim($_POST['contractor_name'] ?? ''),
            'contractor_document'=>trim($_POST['contractor_document'] ?? ''),
            'policy_number'=>trim($_POST['policy_number'] ?? ''),
            'insurance_company'=>trim($_POST['insurance_company'] ?? ''),
            'policy_type'=>trim($_POST['policy_type'] ?? ''),
            'insured_value'=>$_POST['insured_value'] ?? 0,
            'start_date'=>$_POST['start_date'] ?? null,
            'end_date'=>$_POST['end_date'] ?? null,
            'status'=>$_POST['status'] ?? 'PENDIENTE',
            'observations'=>trim($_POST['observations'] ?? ''),
            'checklist'=>$checklist,
            'created_by'=>$user['name'] ?? 'Sistema',
        ], $saved);
        Flash::set('success','Revisión de póliza registrada correctamente.');
        header('Location: index.php?r=polizas.show&id='.$id); exit;
    }

    public function show(): void {
        Auth::requireLogin();
        $id=(int)($_GET['id'] ?? 0);
        $review=PolicyReview::find($id); if (!$review) { http_response_code(404); exit('Revisión no encontrada'); }
        $files=PolicyReview::files($id);
        View::render('policy_reviews/show', compact('review','files'));
    }

    public function updateStatus(): void {
        Auth::requireLogin();
        $id=(int)($_POST['id'] ?? 0);
        PolicyReview::updateStatus($id, $_POST['status'] ?? 'PENDIENTE', trim($_POST['observations'] ?? ''));
        Flash::set('success','Estado de revisión actualizado.');
        header('Location: index.php?r=polizas.show&id='.$id); exit;
    }

    public function download(): void {
        Auth::requireLogin();
        $fid=(int)($_GET['file_id'] ?? 0);
        $pdo=Database::pdo();
        $st=$pdo->prepare('SELECT * FROM policy_review_files WHERE id=?'); $st->execute([$fid]); $f=$st->fetch();
        if (!$f) { http_response_code(404); exit('Archivo no encontrado'); }
        $base=realpath(dirname(__DIR__,2).'/storage/policy_reviews');
        $path=realpath($base.'/'.$f['stored_path']);
        if (!$base || !$path || !str_starts_with($path,$base) || !is_file($path)) { http_response_code(404); exit('Archivo no disponible'); }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($f['original_name']).'"');
        readfile($path); exit;
    }

    public function exportCsv(): void {
        Auth::requireLogin();
        $rows=PolicyReview::all($_GET);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="revision_polizas_'.date('Ymd_His').'.csv"');
        $out=fopen('php://output','w');
        fputcsv($out,['ID','Contrato','Contratista','Documento','Poliza','Aseguradora','Tipo','Valor asegurado','Inicio','Fin','Estado','Observaciones','Fecha']);
        foreach($rows as $r){ fputcsv($out,[$r['id'],$r['contract_number'],$r['contractor_name'],$r['contractor_document'],$r['policy_number'],$r['insurance_company'],$r['policy_type'],$r['insured_value'],$r['start_date'],$r['end_date'],$r['status'],$r['observations'],$r['created_at']]); }
        fclose($out); exit;
    }

    private function contracts(): array {
        try { return Database::pdo()->query('SELECT c.id, c.number, c.name, p.name AS provider_name FROM contracts c LEFT JOIN providers p ON p.id=c.provider_id ORDER BY c.id DESC LIMIT 500')->fetchAll(); }
        catch (\Throwable $e) { return []; }
    }
}


