<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\Flash;
use App\Core\Auth;
use App\Models\User;
use App\Models\PasswordReset;
use App\Services\Mailer;

final class AuthController {
    public function login(): void {
        View::render('auth/login');
    }

    public function authenticate(): void {
        $email = trim($_POST['email'] ?? '');
        $pass = (string)($_POST['password'] ?? '');

        if (($_POST['terms'] ?? '') !== '1') {
            Flash::set('danger', 'Debes aceptar la politica de tratamiento de datos personales.');
            header('Location: index.php?r=login');
            exit;
        }

        $user = User::findByEmail($email);
        if ($user && (int)($user['active'] ?? 1) === 1 && password_verify($pass, (string)$user['password_hash'])) {
            $_SESSION['user'] = $user;
            header('Location: index.php?r=dashboard');
            exit;
        }

        Flash::set('danger', 'Usuario o contrasena invalidos.');
        header('Location: index.php?r=login');
        exit;
    }

    public function forgot(): void {
        View::render('auth/forgot', [
            'devResetLink' => $_SESSION['dev_reset_link'] ?? null,
        ]);
        unset($_SESSION['dev_reset_link']);
    }

    public function sendReset(): void {
        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('danger', 'Ingresa un correo valido.');
            header('Location: index.php?r=password.forgot');
            exit;
        }

        $user = User::findByEmail($email);
        if ($user) {
            $token = PasswordReset::create((int)$user['id'], (string)$user['email'], $_SERVER['REMOTE_ADDR'] ?? null);
            $link = $this->absoluteUrl('index.php?r=password.reset&token=' . urlencode($token));
            $sent = $this->sendResetEmail((string)$user['email'], $link);
            PasswordReset::logResetLink((string)$user['email'], $link, $sent);

            if (!$sent && $this->isLocalRequest()) {
                $_SESSION['dev_reset_link'] = $link;
            }
        }

        Flash::set('success', 'Si el correo existe, enviaremos instrucciones para restablecer la contrasena.');
        header('Location: index.php?r=password.forgot');
        exit;
    }

    public function reset(): void {
        $token = (string)($_GET['token'] ?? '');
        $reset = PasswordReset::findValid($token);
        if (!$reset) {
            Flash::set('danger', 'El enlace de recuperacion no es valido o ya expiro.');
            header('Location: index.php?r=password.forgot');
            exit;
        }

        View::render('auth/reset', ['token' => $token, 'email' => $reset['email']]);
    }

    public function updatePassword(): void {
        $token = (string)($_POST['token'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirmation'] ?? '');
        $reset = PasswordReset::findValid($token);

        if (!$reset) {
            Flash::set('danger', 'El enlace de recuperacion no es valido o ya expiro.');
            header('Location: index.php?r=password.forgot');
            exit;
        }

        if (strlen($password) < 8) {
            Flash::set('danger', 'La nueva contrasena debe tener minimo 8 caracteres.');
            header('Location: index.php?r=password.reset&token=' . urlencode($token));
            exit;
        }

        if ($password !== $confirm) {
            Flash::set('danger', 'La confirmacion no coincide.');
            header('Location: index.php?r=password.reset&token=' . urlencode($token));
            exit;
        }

        User::updatePassword((int)$reset['user_id'], password_hash($password, PASSWORD_DEFAULT));
        PasswordReset::markUsed((int)$reset['id']);
        Flash::set('success', 'Contrasena actualizada. Ya puedes iniciar sesion.');
        header('Location: index.php?r=login');
        exit;
    }

    public function logout(): void {
        session_destroy();
        header('Location: index.php?r=login');
        exit;
    }

    private function absoluteUrl(string $path): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/contratos/public/index.php')), '/');
        return $scheme . '://' . $host . $base . '/' . ltrim($path, '/');
    }

    private function sendResetEmail(string $email, string $link): bool {
        $subject = 'Restablecer contrasena - ColvaContratos';
        $message = "Hola,

Recibimos una solicitud para restablecer tu contrasena en ColvaContratos.

Abre este enlace durante los proximos 60 minutos:
{$link}

Si no solicitaste este cambio, ignora este mensaje.
";
        return Mailer::send($email, $subject, $message);
    }

    private function isLocalRequest(): bool {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        return str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');
    }
}
