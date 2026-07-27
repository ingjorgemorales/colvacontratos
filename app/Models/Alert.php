<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class Alert {
    private static function pdo(): PDO { return Database::pdo(); }

    public static function expiringContracts(int $days = 30): array {
        try {
            $st = self::pdo()->prepare("SELECT c.id, c.number, c.name, COALESCE(c.extension_end_date,c.end_date) AS due_date,
                                               DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) AS days_left,
                                               COALESCE(p.name,'') AS provider_name,
                                               COALESCE(p.email,'') AS provider_email,
                                               COALESCE(c.supervisor_name,'') AS supervisor_name
                                        FROM contracts c
                                        LEFT JOIN providers p ON p.id=c.provider_id
                                        WHERE COALESCE(c.extension_end_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                                        ORDER BY COALESCE(c.extension_end_date,c.end_date) ASC, c.id DESC");
            $st->execute([$days]);
            return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function expiredContracts(): array {
        try {
            return self::pdo()->query("SELECT c.id, c.number, c.name, COALESCE(c.extension_end_date,c.end_date) AS due_date,
                                             DATEDIFF(CURDATE(), COALESCE(c.extension_end_date,c.end_date)) AS days_expired,
                                             COALESCE(p.name,'') AS provider_name,
                                             COALESCE(c.supervisor_name,'') AS supervisor_name
                                      FROM contracts c
                                      LEFT JOIN providers p ON p.id=c.provider_id
                                      WHERE COALESCE(c.extension_end_date,c.end_date) < CURDATE()
                                      ORDER BY COALESCE(c.extension_end_date,c.end_date) ASC, c.id DESC")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function missingDocuments(): array {
        try {
            return self::pdo()->query("SELECT c.id, c.number, c.name, COALESCE(p.name,'') AS provider_name, COALESCE(c.supervisor_name,'') AS supervisor_name
                                      FROM contracts c
                                      LEFT JOIN providers p ON p.id=c.provider_id
                                      LEFT JOIN contract_documents d ON d.contract_id=c.id
                                      GROUP BY c.id, c.number, c.name, p.name, c.supervisor_name
                                      HAVING COUNT(d.id)=0
                                      ORDER BY c.id DESC")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    /**
     * Número de alertas pendientes: contratos vencidos + los que vencen en los
     * próximos 30 días. Se usa en el indicador del menú y la barra superior.
     */
    public static function pendingCount(): int {
        try {
            return (int) self::pdo()->query(
                "SELECT COUNT(*) FROM contracts
                 WHERE COALESCE(extension_end_date,end_date) IS NOT NULL
                   AND COALESCE(extension_end_date,end_date) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
            )->fetchColumn();
        } catch (\Throwable $e) { return 0; }
    }

    /** Total de registros de la bitácora (para la paginación). */
    public static function countLogs(): int {
        try { return (int) self::pdo()->query("SELECT COUNT(*) FROM contract_alert_logs")->fetchColumn(); }
        catch (\Throwable $e) { return 0; }
    }

    public static function log(int $contractId, string $type, string $channel, string $recipient, string $subject, string $status, ?string $error = null): void {
        try {
            $st = self::pdo()->prepare("INSERT INTO contract_alert_logs(contract_id, alert_type, channel, recipient, subject, status, error_message, sent_at)
                                        VALUES(?,?,?,?,?,?,?,NOW())");
            $st->execute([$contractId, $type, $channel, $recipient, $subject, $status, $error]);
        } catch (\Throwable $e) {}
    }

    public static function logs(int $limit = 100, int $offset = 0): array {
        try {
            $st = self::pdo()->prepare("SELECT l.*, c.number, c.name AS contract_name
                                      FROM contract_alert_logs l
                                      LEFT JOIN contracts c ON c.id=l.contract_id
                                      ORDER BY l.id DESC LIMIT ? OFFSET ?");
            $st->bindValue(1, $limit, PDO::PARAM_INT);
            $st->bindValue(2, max(0, $offset), PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }
}
