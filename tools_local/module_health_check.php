<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdo = App\Core\Database::pdo();

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$tables = [
    'users',
    'roles',
    'contracts',
    'providers',
    'areas',
    'contract_documents',
    'contract_payments',
    'policy_reviews',
    'contract_ai_insights',
    'contract_alert_logs',
    'change_logs',
    'audit_logs',
    'password_resets',
];

foreach ($tables as $table) {
    try {
        $existsStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
        );
        $existsStmt->execute([$table]);
        $exists = (int)$existsStmt->fetchColumn() > 0;
        if (!$exists) {
            echo "MISSING|{$table}\n";
            continue;
        }

        $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo "OK|{$table}|{$count}\n";
    } catch (Throwable $e) {
        echo "ERR|{$table}|{$e->getMessage()}\n";
    }
}

echo "---\n";

$routeChecks = [
    'login',
    'password.forgot',
    'dashboard',
    'contracts',
    'providers',
    'documents',
    'polizas',
    'finance',
    'reports',
    'alerts',
    'intelligence',
    'audit',
    'admin.panel',
    'admin.users',
    'admin.catalogs',
];

$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Health Check',
    'email' => 'healthcheck@local',
    'role_name' => 'admin',
    'active' => 1,
];

foreach ($routeChecks as $route) {
    try {
        $_GET = ['r' => $route];
        $_POST = [];
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/colvacontratos_laravel13/public/index.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        ob_start();
        $controller = new App\Http\Controllers\LegacyRouterController();
        $request = Illuminate\Http\Request::create('/index.php', 'GET', ['r' => $route]);
        $response = $controller($request);
        $content = (string)$response->getContent();
        ob_end_clean();

        $status = $response->getStatusCode();
        $len = strlen($content);
        echo "ROUTE|{$route}|{$status}|{$len}\n";
    } catch (Throwable $e) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo "ROUTE_ERR|{$route}|{$e->getMessage()}\n";
    }
}
