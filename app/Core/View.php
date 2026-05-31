<?php
namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data);

        $basePath = function_exists('base_path') ? base_path() : dirname(__DIR__, 2);
        $app = require $basePath . '/config/legacy_app.php';

        require $basePath . '/app/Views/layouts/header.php';
        require $basePath . '/app/Views/' . $view . '.php';
        require $basePath . '/app/Views/layouts/footer.php';
    }
}
