<?php
namespace App\Models;

use App\Core\Database;

final class User {
    public static function findByEmail(string $email): ?array {
        $sql = "SELECT u.*, r.name role_name FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE u.email=? AND u.active=1 LIMIT 1";
        $st = Database::pdo()->prepare($sql);
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    public static function updatePassword(int $id, string $passwordHash): void {
        $st = Database::pdo()->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?");
        $st->execute([$passwordHash, $id]);
    }

    /** Usuario por id, con el nombre del rol (para la vista de perfil). */
    public static function findById(int $id): ?array {
        $sql = "SELECT u.*, r.name role_name FROM users u LEFT JOIN roles r ON r.id=u.role_id WHERE u.id=? LIMIT 1";
        $st = Database::pdo()->prepare($sql);
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** True si el correo ya lo usa OTRO usuario (evita duplicados al editar el perfil). */
    public static function emailTakenByOther(string $email, int $exceptId): bool {
        $st = Database::pdo()->prepare("SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
        $st->execute([$email, $exceptId]);
        return (bool) $st->fetch();
    }

    /** Actualiza los datos personales del propio usuario (la cédula NO se toca). */
    public static function updateProfile(int $id, string $name, string $email): void {
        $st = Database::pdo()->prepare("UPDATE users SET name=?, email=?, updated_at=NOW() WHERE id=?");
        $st->execute([$name, $email, $id]);
    }

    /** Fija la nueva contraseña y quita la marca de "cambio obligatorio" (primer ingreso). */
    public static function completeFirstPassword(int $id, string $passwordHash): void {
        $st = Database::pdo()->prepare("UPDATE users SET password_hash=?, must_change_password=0, updated_at=NOW() WHERE id=?");
        $st->execute([$passwordHash, $id]);
    }
}