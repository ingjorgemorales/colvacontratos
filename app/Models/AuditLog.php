<?php
namespace App\Models;

use App\Core\Database;

final class AuditLog {
    public static function latest(int $limit = 100): array {
        try {
            $limit = max(1, min(500, $limit));
            $pdo = Database::pdo();
            $queries = [];

            if (self::tableExists('audit_logs')) {
                $queries[] = "SELECT created_at, module, action, detail, ip FROM audit_logs";
            }

            if (self::tableExists('change_logs')) {
                $queries[] = "SELECT created_at, entity_type AS module, action, summary AS detail, ip FROM change_logs";
            }

            if ($queries === []) {
                return [];
            }

            $sql = "SELECT * FROM (" . implode(" UNION ALL ", $queries) . ") logs ORDER BY created_at DESC LIMIT {$limit}";
            return $pdo->query($sql)->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function tableExists(string $table): bool {
        try {
            $st = Database::pdo()->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
            );
            $st->execute([$table]);
            return (int)$st->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
