<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Flash;
use App\Models\Contract;
use App\Models\Catalog;
use App\Services\ChangeLogger;
use App\Services\NotificationService;

final class ContractController {
    private function formData(array $contract, string $action, string $title): array {
        return Catalog::contractParams() + [
            'areas' => Catalog::areas(),
            'subAreas' => Catalog::subAreas(),
            'statuses' => Catalog::statuses(),
            'providers' => Catalog::providers(),
            'supervisors' => Catalog::supervisors(),
            'contract' => $contract,
            'action' => $action,
            'title' => $title,
        ];
    }

    public function index(): void {
        Auth::requireLogin();
        $filters = [
            'area_id' => trim($_GET['area_id'] ?? ''),
            'status_id' => trim($_GET['status_id'] ?? ''),
            'contract_type_id' => trim($_GET['contract_type_id'] ?? ''),
            'q' => trim($_GET['q'] ?? ''),
        ];
        View::render('contracts/index', [
            'contracts' => Contract::paginate($filters, 200, 0),
            'filters' => $filters,
            'areas' => Catalog::areas(),
            'statuses' => Catalog::statuses(),
            'contractTypes' => Catalog::contractTypes(),
        ]);
    }

    public function create(): void {
        Auth::requireLogin();
        View::render('contracts/form', $this->formData([], 'index.php?r=contracts.store', 'Nuevo contrato'));
    }

    public function store(): void {
        Auth::requireLogin();
        if (trim($_POST['name'] ?? '') === '' || trim($_POST['object'] ?? '') === '') {
            Flash::set('danger','Nombre y objeto del contrato son obligatorios.');
            header('Location: index.php?r=contracts.create'); exit;
        }
        $id = Contract::create($_POST);
        $after = Contract::find($id);
        if (class_exists(ChangeLogger::class)) ChangeLogger::log('contract',$id,'create','Contrato creado: '.($after['name']??''),null,$after);
        if (class_exists(NotificationService::class)) NotificationService::notify('contract_change','ColvaContratos - contrato creado','Se creó el contrato: '.($after['name']??''));
        Flash::set('success','Contrato creado correctamente.');
        header('Location: index.php?r=contracts.show&id='.$id); exit;
    }

    public function show(): void {
        Auth::requireLogin();
        $contract = Contract::find((int)($_GET['id'] ?? 0));
        if (!$contract) { http_response_code(404); View::render('errors/404'); return; }
        View::render('contracts/show', ['contract'=>$contract]);
    }

    public function edit(): void {
        Auth::requireLogin();
        $contract = Contract::find((int)($_GET['id'] ?? 0));
        if (!$contract) { http_response_code(404); View::render('errors/404'); return; }
        View::render('contracts/form', $this->formData($contract, 'index.php?r=contracts.update&id='.$contract['id'], 'Editar contrato'));
    }

    public function update(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $before = Contract::find($id);
        Contract::update($id, $_POST);
        $after = Contract::find($id);
        if (class_exists(ChangeLogger::class)) ChangeLogger::log('contract',$id,'update','Contrato actualizado: '.($after['name']??''),$before,$after);
        Flash::set('success','Contrato actualizado correctamente.');
        header('Location: index.php?r=contracts.show&id='.$id); exit;
    }
}
