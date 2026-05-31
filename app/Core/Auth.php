<?php
namespace App\Core;
final class Auth {
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function check(): bool { return isset($_SESSION['user']); }
    public static function requireLogin(): void { if (!self::check()) { header('Location: index.php?r=login'); exit; } }
    public static function login(array $user): void { $_SESSION['user'] = ['id'=>$user['id'], 'name'=>$user['name'], 'email'=>$user['email'], 'role'=>$user['role_name'] ?? '']; }
    public static function logout(): void { $_SESSION = []; session_destroy(); }
}
