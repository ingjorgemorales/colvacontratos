<?php
// Mapa de rutas heredado usado por LegacyRouterController.

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ContractController;
use App\Controllers\ProviderController;
use App\Controllers\AreaController;
use App\Controllers\FinanceController;
use App\Controllers\DocumentController;
use App\Controllers\ReportController;
use App\Controllers\AuditController;
use App\Controllers\AdminController;
use App\Controllers\AlertController;
use App\Controllers\IntelligenceController;
use App\Controllers\DocumentFlowController;

use App\Controllers\PolicyReviewController;
use App\Controllers\AgentePolizasController;

$routes = [
    // Módulo Agente de Pólizas (motor Flask colvatel-app integrado)
    'agente' => [AgentePolizasController::class, 'index'],
    'agente.historico' => [AgentePolizasController::class, 'historico'],
    'agente.manual' => [AgentePolizasController::class, 'manual'],
    'agente.apis' => [AgentePolizasController::class, 'apis'],
    'agente.proxy' => [AgentePolizasController::class, 'proxy'],

    'login' => [AuthController::class, 'login'],
    'auth' => [AuthController::class, 'authenticate'],
    'logout' => [AuthController::class, 'logout'],
    'password.forgot' => [AuthController::class, 'forgot'],
    'password.email' => [AuthController::class, 'sendReset'],
    'password.reset' => [AuthController::class, 'reset'],
    'password.update' => [AuthController::class, 'updatePassword'],
    'dashboard' => [DashboardController::class, 'index'],

    'contracts' => [ContractController::class, 'index'],
    'contracts.show' => [ContractController::class, 'show'],
    'contracts.create' => [ContractController::class, 'create'],
    'contracts.store' => [ContractController::class, 'store'],
    'contracts.edit' => [ContractController::class, 'edit'],
    'contracts.update' => [ContractController::class, 'update'],

    'providers' => [ProviderController::class, 'index'],
    'providers.create' => [ProviderController::class, 'create'],
    'providers.store' => [ProviderController::class, 'store'],
    'providers.edit' => [ProviderController::class, 'edit'],
    'providers.update' => [ProviderController::class, 'update'],
    'providers.export_excel' => [ProviderController::class, 'exportExcel'],

    'areas' => [AreaController::class, 'index'],
    'areas.store' => [AreaController::class, 'store'],
    'areas.update' => [AreaController::class, 'update'],

    'finance' => [FinanceController::class, 'index'],
    'finance.contract' => [FinanceController::class, 'contract'],
    'finance.store' => [FinanceController::class, 'store'],
    'finance.delete' => [FinanceController::class, 'delete'],

    'documents' => [DocumentController::class, 'index'],
    'documents.upload' => [DocumentController::class, 'upload'],
    'documents.download' => [DocumentController::class, 'download'],
    'documents.delete' => [DocumentController::class, 'delete'],
    'documents.addendum' => [DocumentController::class, 'addAddendum'],
    'documents.policy' => [DocumentController::class, 'addPolicy'],

    // Módulo Revisión de Pólizas
    'polizas' => [PolicyReviewController::class, 'index'],
    'polizas.create' => [PolicyReviewController::class, 'create'],
    'polizas.store' => [PolicyReviewController::class, 'store'],
    'polizas.show' => [PolicyReviewController::class, 'show'],
    'polizas.status' => [PolicyReviewController::class, 'updateStatus'],
    'polizas.download' => [PolicyReviewController::class, 'download'],
    'polizas.export' => [PolicyReviewController::class, 'exportCsv'],
    'policy_reviews' => [PolicyReviewController::class, 'index'],
    'policy_reviews.create' => [PolicyReviewController::class, 'create'],
    'policy_reviews.store' => [PolicyReviewController::class, 'store'],
    'policy_reviews.show' => [PolicyReviewController::class, 'show'],
    'policy_reviews.status' => [PolicyReviewController::class, 'updateStatus'],
    'policy_reviews.download' => [PolicyReviewController::class, 'download'],
    'policy_reviews.export' => [PolicyReviewController::class, 'exportCsv'],


    'reports' => [ReportController::class, 'index'],
    'reports.export' => [ReportController::class, 'exportCsv'],
    'reports.excel' => [ReportController::class, 'exportExcel'],
    'reports.contracts.excel' => [ReportController::class, 'exportContractsExcel'],
    'reports.providers.excel' => [ReportController::class, 'exportProvidersExcel'],
    'reports.financial.excel' => [ReportController::class, 'exportFinancialExcel'],
    'reports.documents.excel' => [ReportController::class, 'exportDocumentsExcel'],
    'reports.analytics.excel' => [ReportController::class, 'exportAnalyticsExcel'],
    'reports.intelligence.excel' => [ReportController::class, 'exportIntelligenceExcel'],
    'alerts' => [AlertController::class, 'index'],
    'alerts.run' => [AlertController::class, 'run'],
    'intelligence' => [IntelligenceController::class, 'index'],
    'intelligence.run' => [IntelligenceController::class, 'run'],
    'documentflow.viewer' => [DocumentFlowController::class, 'viewer'],
    'documentflow.field.store' => [DocumentFlowController::class, 'addField'],
    'documentflow.field.sign' => [DocumentFlowController::class, 'markSigned'],
    'documentflow.checklist.update' => [DocumentFlowController::class, 'checklistUpdate'],
    'audit' => [AuditController::class, 'index'],
    'config' => [AdminController::class, 'panel'],
    'admin.panel' => [AdminController::class, 'panel'],
    'admin.users' => [AdminController::class, 'users'],
    'admin.users.store' => [AdminController::class, 'userStore'],
    'admin.users.update' => [AdminController::class, 'userUpdate'],
    'admin.providers' => [AdminController::class, 'providers'],
    'admin.providers.store' => [AdminController::class, 'providerStore'],
    'admin.providers.update' => [AdminController::class, 'providerUpdate'],
    'admin.supervisors' => [AdminController::class, 'supervisors'],
    'admin.supervisors.store' => [AdminController::class, 'supervisorStore'],
    'admin.supervisors.update' => [AdminController::class, 'supervisorUpdate'],
    'admin.catalogs' => [AdminController::class, 'catalogs'],
    'admin.catalogs.group.store' => [AdminController::class, 'groupStore'],
    'admin.catalogs.item.store' => [AdminController::class, 'itemStore'],
    'admin.catalogs.item.update' => [AdminController::class, 'itemUpdate'],
    'admin.assign' => [AdminController::class, 'assign'],
];

return $routes;
