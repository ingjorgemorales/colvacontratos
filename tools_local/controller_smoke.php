<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Smoke Test',
    'email' => 'smoke@local',
    'role_name' => 'Administrador',
    'active' => 1,
];

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/colvacontratos_laravel13/public/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$checks = [
    ['module' => 'auth.login', 'controller' => App\Controllers\AuthController::class, 'method' => 'login', 'get' => ['r' => 'login']],
    ['module' => 'auth.forgot', 'controller' => App\Controllers\AuthController::class, 'method' => 'forgot', 'get' => ['r' => 'password.forgot']],
    ['module' => 'dashboard', 'controller' => App\Controllers\DashboardController::class, 'method' => 'index', 'get' => ['r' => 'dashboard']],
    ['module' => 'contracts', 'controller' => App\Controllers\ContractController::class, 'method' => 'index', 'get' => ['r' => 'contracts']],
    ['module' => 'providers', 'controller' => App\Controllers\ProviderController::class, 'method' => 'index', 'get' => ['r' => 'providers']],
    ['module' => 'documents', 'controller' => App\Controllers\DocumentController::class, 'method' => 'index', 'get' => ['r' => 'documents', 'contract_id' => '1']],
    ['module' => 'polizas', 'controller' => App\Controllers\PolicyReviewController::class, 'method' => 'index', 'get' => ['r' => 'polizas']],
    ['module' => 'finance', 'controller' => App\Controllers\FinanceController::class, 'method' => 'index', 'get' => ['r' => 'finance']],
    ['module' => 'reports', 'controller' => App\Controllers\ReportController::class, 'method' => 'index', 'get' => ['r' => 'reports']],
    ['module' => 'alerts', 'controller' => App\Controllers\AlertController::class, 'method' => 'index', 'get' => ['r' => 'alerts']],
    ['module' => 'intelligence', 'controller' => App\Controllers\IntelligenceController::class, 'method' => 'index', 'get' => ['r' => 'intelligence']],
    ['module' => 'audit', 'controller' => App\Controllers\AuditController::class, 'method' => 'index', 'get' => ['r' => 'audit']],
    ['module' => 'admin.panel', 'controller' => App\Controllers\AdminController::class, 'method' => 'panel', 'get' => ['r' => 'admin.panel']],
    ['module' => 'admin.users', 'controller' => App\Controllers\AdminController::class, 'method' => 'users', 'get' => ['r' => 'admin.users']],
    ['module' => 'admin.catalogs', 'controller' => App\Controllers\AdminController::class, 'method' => 'catalogs', 'get' => ['r' => 'admin.catalogs']],
];

foreach ($checks as $check) {
    $_GET = $check['get'];
    $_POST = [];

    try {
        ob_start();
        $controller = new $check['controller']();
        $controller->{$check['method']}();
        $content = ob_get_clean();
        echo 'OK|' . $check['module'] . '|len=' . strlen((string)$content) . PHP_EOL;
    } catch (Throwable $e) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo 'ERR|' . $check['module'] . '|' . $e->getMessage() . PHP_EOL;
    }
}
