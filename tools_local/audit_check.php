<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = App\Models\AuditLog::latest(10);
echo 'COUNT|' . count($rows) . PHP_EOL;

foreach ($rows as $row) {
    echo ($row['created_at'] ?? '') . '|'
        . ($row['module'] ?? '') . '|'
        . ($row['action'] ?? '') . '|'
        . substr((string)($row['detail'] ?? ''), 0, 40)
        . PHP_EOL;
}
