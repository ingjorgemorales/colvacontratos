<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdo = App\Core\Database::pdo();

foreach (['audit_logs', 'change_logs'] as $table) {
    echo "TABLE|{$table}\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    foreach ($stmt->fetchAll() as $column) {
        echo $column['Field'] . '|' . $column['Type'] . "\n";
    }
    echo "---\n";
}
