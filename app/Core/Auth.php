<?php
namespace App\Core;
final class Auth {
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function check(): bool { return isset($_SESSION['user']); }
    public static function requireLogin(): void { if (!self::check()) { header('Location: index.php?r=login'); exit; } }
    public static function login(array $user): void { $_SESSION['user'] = ['id'=>$user['id'], 'name'=>$user['name'], 'email'=>$user['email'], 'role'=>$user['role_name'] ?? '']; }
    public static function logout(): void { $_SESSION = []; session_destroy(); }

    /** Id del rol del usuario en sesión (0 si no hay sesión). */
    public static function roleId(): int { return (int)(self::user()['role_id'] ?? 0); }

    /** ¿El usuario en sesión tiene el rol de máximos privilegios? */
    public static function isAdmin(): bool {
        return self::check() && self::roleId() === \App\Models\RolePermission::idRolAdmin();
    }

    /** ¿El usuario en sesión puede entrar al módulo indicado? (para menús y vistas) */
    public static function can(string $modulo): bool {
        if (!self::check()) { return false; }
        return \App\Models\RolePermission::permite(self::roleId(), $modulo);
    }

    /** Corta la ejecución si el usuario no puede entrar al módulo. */
    public static function requireModulo(string $modulo): void {
        self::requireLogin();
        if (!self::can($modulo)) {
            \App\Core\View::render('errors/403', ['modulo' => $modulo]);
            exit;
        }
    }
}
