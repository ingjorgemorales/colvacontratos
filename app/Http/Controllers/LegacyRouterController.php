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

        // ── Cambio de contraseña obligatorio en el primer ingreso ──
        // Si el usuario tiene la marca must_change_password, se le retiene en la
        // pantalla de cambio inicial hasta que defina una contraseña nueva.
        if (\App\Core\Auth::check() && (int) (\App\Core\Auth::user()['must_change_password'] ?? 0) === 1) {
            $permitidas = ['perfil.cambio_inicial', 'perfil.cambio_inicial.save', 'logout'];
            if (!in_array($route, $permitidas, true)) {
                header('Location: index.php?r=perfil.cambio_inicial');
                return response('', 302);
            }
        }

        // ── Control de acceso por rol (Roles y permisos) ──
        // Chequeo central: si el usuario tiene sesión y su rol no puede entrar
        // al módulo de esta ruta, se muestra la pantalla de acceso restringido.
        // Las rutas públicas (login, logout, recuperación) nunca se bloquean.
        if (\App\Core\Auth::check() && !\App\Models\RolePermission::esRutaPublica($route)) {
            $modulo = \App\Models\RolePermission::moduloDeRuta($route);
            if ($modulo !== null && !\App\Core\Auth::can($modulo)) {
                ob_start();
                \App\Core\View::render('errors/403', ['modulo' => $modulo]);
                return response(ob_get_clean(), 403);
            }
        }

        ob_start();

        $controller = new $class();
        $controller->{$method}();

        $content = ob_get_clean();

        return response($content);
    }
}
