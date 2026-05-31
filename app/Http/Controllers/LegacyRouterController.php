<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

final class LegacyRouterController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $route = (string) $request->query('r', 'dashboard');

        // Mantiene compatibilidad con formularios y código heredado que lee $_GET/$_POST.
        $_GET = array_merge($_GET, $request->query->all());
        $_POST = array_merge($_POST, $request->request->all());

        $routes = require base_path('routes/legacy.php');

        if (!isset($routes[$route])) {
            $route = 'dashboard';
        }

        [$class, $method] = $routes[$route];

        if (!class_exists($class) || !method_exists($class, $method)) {
            abort(404, "Ruta heredada no encontrada: {$route}");
        }

        ob_start();

        $controller = new $class();
        $controller->{$method}();

        $content = ob_get_clean();

        return response($content);
    }
}
