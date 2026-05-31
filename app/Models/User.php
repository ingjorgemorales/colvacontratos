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
}