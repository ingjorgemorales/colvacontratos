<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class IntelligencePro
{
    private static function db(): PDO { return Database::pdo(); }

    public static function kpis(): array
    {
        $db = self::db();
        $k = ['total'=>0,'active'=>0,'expired'=>0,'due30'=>0,'without_docs'=>0,'pending_signatures'=>0,'risk_score'=>0,'providers'=>0,'total_value'=>0,'executed_value'=>0,'execution_pct'=>0,'critical'=>0];
        try { $k['total'] = (int)$db->query("SELECT COUNT(*) FROM contracts")->fetchColumn(); } catch (\Throwable $e) {}
        try { $k['providers'] = (int)$db->query("SELECT COUNT(*) FROM providers")->fetchColumn(); } catch (\Throwable $e) {}
        try { $k['active'] = (int)$db->query("SELECT COUNT(*) FROM contracts c LEFT JOIN contract_statuses s ON s.id=c.status_id WHERE COALESCE(s.code,s.name,'') NOT IN ('liquidated','cancelled','Cancelado','Liquidado')")->fetchColumn(); } catch (\Throwable $e) {}
        try { $k['expired'] = (int)$db->query("SELECT COUNT(*) FROM contracts WHERE COALESCE(extension_end_date,end_date) < CURDATE()")->fetchColumn(); } catch (\Throwable $e) {}
        try { $k['due30'] = (int)$db->query("SELECT COUNT(*) FROM contracts WHERE COALESCE(extension_end_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(); } catch (\Throwable $e) {}
        try { $k['without_docs'] = (int)$db->query("SELECT COUNT(*) FROM contracts c LEFT JOIN contract_documents d ON d.contract_id=c.id WHERE d.id IS NULL")->fetchColumn(); } catch (\Throwable $e) {}
        try { $k['pending_signatures'] = (int)$db->query("SELECT COUNT(*) FROM contract_signature_fields WHERE status='pendiente'")->fetchColumn(); } catch (\Throwable $e) {}
        try { $k['total_value'] = (float)$db->query("SELECT COALESCE(SUM(total_value),0) FROM contracts")->fetchColumn(); } catch (\Throwable $e) {}
        try { $k['executed_value'] = (float)$db->query("SELECT COALESCE(SUM(executed_value),0) FROM contracts")->fetchColumn(); } catch (\Throwable $e) {}
        $k['execution_pct'] = $k['total_value'] > 0 ? round(($k['executed_value'] / $k['total_value']) * 100, 2) : 0;
        $k['critical'] = $k['expired'] + $k['due30'] + $k['without_docs'];
        $k['risk_score'] = min(100, ($k['expired']*6) + ($k['due30']*3) + ($k['without_docs']*2) + ($k['pending_signatures']));
        return $k;
    }

    public static function insights(int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit)); $offset = max(0, $offset);
        try {
            return self::db()->query(
                "SELECT * FROM contract_ai_insights WHERE status='abierta'
                 ORDER BY FIELD(severity,'critica','alta','media','baja'), created_at DESC
                 LIMIT {$offset}, {$limit}"
            )->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function countInsights(): int
    {
        try { return (int) self::db()->query("SELECT COUNT(*) FROM contract_ai_insights WHERE status='abierta'")->fetchColumn(); }
        catch (\Throwable $e) { return 0; }
    }

    public static function logs(int $limit = 10, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit)); $offset = max(0, $offset);
        try { return self::db()->query("SELECT l.*, c.number FROM contract_alert_logs l LEFT JOIN contracts c ON c.id=l.contract_id ORDER BY l.id DESC LIMIT {$offset}, {$limit}")->fetchAll(); } catch (\Throwable $e) { return []; }
    }

    public static function countLogs(): int
    {
        try { return (int) self::db()->query("SELECT COUNT(*) FROM contract_alert_logs")->fetchColumn(); }
        catch (\Throwable $e) { return 0; }
    }

    public static function byArea(): array
    {
        try { return self::db()->query("SELECT COALESCE(a.name,'Sin área') label, COUNT(c.id) total FROM contracts c LEFT JOIN areas a ON a.id=c.area_id GROUP BY COALESCE(a.name,'Sin área') ORDER BY total DESC LIMIT 8")->fetchAll(); } catch (\Throwable $e) { return []; }
    }

    public static function byRisk(): array
    {
        try { return self::db()->query("SELECT COALESCE(risk_level,'normal') label, COUNT(*) total FROM contracts GROUP BY COALESCE(risk_level,'normal') ORDER BY total DESC")->fetchAll(); } catch (\Throwable $e) { return []; }
    }
}
