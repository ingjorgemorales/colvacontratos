<?php
namespace App\Models;

use App\Core\Database;

final class PasswordReset {
    public static function create(int $userId, string $email, ?string $ip): string {
        self::expireOpenTokens($userId);
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $st = Database::pdo()->prepare(
            "INSERT INTO password_resets (user_id, email, token_hash, expires_at, created_ip) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), ?)"
        );
        $st->execute([$userId, $email, $hash, $ip]);
        return $token;
    }

    public static function findValid(string $token): ?array {
        if ($token === '') return null;
        $hash = hash('sha256', $token);
        $st = Database::pdo()->prepare(
            "SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at >= NOW() LIMIT 1"
        );
        $st->execute([$hash]);
        return $st->fetch() ?: null;
    }

    public static function markUsed(int $id): void {
        $st = Database::pdo()->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=?");
        $st->execute([$id]);
    }

    public static function logResetLink(string $email, string $link, bool $sent): void {
        $dir = self::logDirectory();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf("[%s] email=%s mail_sent=%s link=%s\n", date('c'), $email, $sent ? 'yes' : 'no', $link);
        @file_put_contents($dir . '/password_reset_links.log', $line, FILE_APPEND);
    }

    private static function expireOpenTokens(int $userId): void {
        $st = Database::pdo()->prepare("UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL");
        $st->execute([$userId]);
    }

    private static function logDirectory(): string {
        if (function_exists('storage_path')) {
            return storage_path('logs');
        }

        return dirname(__DIR__, 2) . '/storage/logs';
    }
}
