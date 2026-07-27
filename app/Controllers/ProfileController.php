<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Flash;
use App\Models\User;
use App\Services\Mailer;

/**
 * Mi Perfil — datos de la cuenta del usuario en sesión y cambio de contraseña.
 *
 * Cada usuario solo puede ver y editar SU propia cuenta: el id se toma de la
 * sesión, nunca de la petición, así nadie puede modificar el perfil de otro.
 */
final class ProfileController
{
    public function index(): void
    {
        Auth::requireLogin();
        $user = User::findById($this->currentId());
        if (!$user) {
            Flash::set('danger', 'No se pudo cargar tu perfil.');
            header('Location: index.php?r=dashboard');
            exit;
        }
        View::render('profile/index', ['perfil' => $user]);
    }

    /** Actualiza nombre y correo del propio usuario. */
    public function update(): void
    {
        Auth::requireLogin();
        $id = $this->currentId();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($name === '' || mb_strlen($name) > 160) {
            $this->back('danger', 'El nombre es obligatorio (máximo 160 caracteres).');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->back('danger', 'Ingresa un correo electrónico válido.');
        }
        if (User::emailTakenByOther($email, $id)) {
            $this->back('danger', 'Ese correo ya está en uso por otro usuario.');
        }

        User::updateProfile($id, $name, $email);
        \App\Services\Audit::log('Perfil', 'Actualizó sus datos de perfil');
        $this->refreshSession($id);
        $this->back('success', 'Datos de perfil actualizados.');
    }

    /** Cambia la contraseña: exige la actual, valida la nueva y su confirmación. */
    public function password(): void
    {
        Auth::requireLogin();
        $id = $this->currentId();
        $actual  = (string) ($_POST['current_password'] ?? '');
        $nueva   = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirmation'] ?? '');

        $user = User::findById($id);
        if (!$user) {
            $this->back('danger', 'No se pudo verificar tu cuenta.');
        }
        if ($actual === '' || !password_verify($actual, (string) $user['password_hash'])) {
            $this->back('danger', 'La contraseña actual no es correcta.');
        }
        if (strlen($nueva) < 8) {
            $this->back('danger', 'La nueva contraseña debe tener mínimo 8 caracteres.');
        }
        if ($nueva !== $confirm) {
            $this->back('danger', 'La nueva contraseña y su confirmación no coinciden.');
        }
        if (password_verify($nueva, (string) $user['password_hash'])) {
            $this->back('danger', 'La nueva contraseña debe ser diferente a la actual.');
        }

        User::updatePassword($id, password_hash($nueva, PASSWORD_DEFAULT));
        \App\Services\Audit::log('Perfil', 'Cambió su contraseña');
        $this->refreshSession($id);

        // Aviso por correo (mismo criterio que el flujo de recuperación).
        $correo = (string) ($user['email'] ?? '');
        if ($correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Mailer::send(
                $correo,
                'ColvaContratos - Contrasena actualizada',
                "Hola,\n\nTu contrasena fue actualizada desde tu perfil en ColvaContratos.\n\nSi no realizaste este cambio, contacta al administrador del sistema.\n"
            );
        }

        $this->back('success', 'Contraseña actualizada correctamente.');
    }

    /** Pantalla de cambio OBLIGATORIO de contraseña (primer inicio de sesión). */
    public function firstPasswordForm(): void
    {
        Auth::requireLogin();
        if ((int) (Auth::user()['must_change_password'] ?? 0) !== 1) {
            header('Location: index.php?r=dashboard');   // ya no aplica
            exit;
        }
        View::render('profile/first_password', ['nombre' => (string) (Auth::user()['name'] ?? '')]);
    }

    /** Procesa el cambio obligatorio: solo nueva + confirmación (ya autenticado). */
    public function firstPasswordSave(): void
    {
        Auth::requireLogin();
        $id = $this->currentId();
        if ((int) (Auth::user()['must_change_password'] ?? 0) !== 1) {
            header('Location: index.php?r=dashboard');
            exit;
        }
        $nueva   = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirmation'] ?? '');

        $err = null;
        if (strlen($nueva) < 8) {
            $err = 'La nueva contraseña debe tener mínimo 8 caracteres.';
        } elseif ($nueva !== $confirm) {
            $err = 'La nueva contraseña y su confirmación no coinciden.';
        } else {
            $user = User::findById($id);
            if ($user && password_verify($nueva, (string) $user['password_hash'])) {
                $err = 'La nueva contraseña debe ser diferente a la temporal que recibiste.';
            }
        }
        if ($err !== null) {
            Flash::set('danger', $err);
            header('Location: index.php?r=perfil.cambio_inicial');
            exit;
        }

        User::completeFirstPassword($id, password_hash($nueva, PASSWORD_DEFAULT));
        \App\Services\Audit::log('Perfil', 'Definió su contraseña (primer ingreso)');
        $this->refreshSession($id);
        Flash::set('success', '¡Listo! Tu contraseña quedó configurada. Bienvenido a ColvaContratos.');
        header('Location: index.php?r=dashboard');
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────
    private function currentId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    /** Recarga en sesión los datos del usuario tras un cambio. */
    private function refreshSession(int $id): void
    {
        $fresh = User::findById($id);
        if ($fresh) {
            $_SESSION['user'] = $fresh;
        }
    }

    private function back(string $tipo, string $mensaje): never
    {
        Flash::set($tipo, $mensaje);
        header('Location: index.php?r=perfil');
        exit;
    }
}
