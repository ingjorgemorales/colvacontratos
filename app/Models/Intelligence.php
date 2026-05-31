<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class Intelligence {
    private static function db(): PDO { return Database::pdo(); }

    public static function kpis(): array {
        $db = self::db();
        $out = ['total'=>0,'active'=>0,'expired'=>0,'due30'=>0,'without_docs'=>0,'pending_signatures'=>0,'risk_score'=>0,'providers'=>0];
        try { $out['total'] = (int)$db->query("SELECT COUNT(*) FROM contracts")->fetchColumn(); } catch (\Throwable $e) {}
        try { $out['providers'] = (int)$db->query("SELECT COUNT(*) FROM providers")->fetchColumn(); } catch (\Throwable $e) {}
        try { $out['active'] = (int)$db->query("SELECT COUNT(*) FROM contracts WHERE COALESCE(status,'') IN ('activo','Activa','ACTIVO','vigente','VIGENTE')")->fetchColumn(); } catch (\Throwable $e) {}
        try { $out['expired'] = (int)$db->query("SELECT COUNT(*) FROM contracts WHERE end_date IS NOT NULL AND end_date < CURDATE()")->fetchColumn(); } catch (\Throwable $e) {}
        try { $out['due30'] = (int)$db->query("SELECT COUNT(*) FROM contracts WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(); } catch (\Throwable $e) {}
        try { $out['without_docs'] = (int)$db->query("SELECT COUNT(*) FROM contracts c LEFT JOIN contract_documents d ON d.contract_id=c.id WHERE d.id IS NULL")->fetchColumn(); } catch (\Throwable $e) {}
        try { $out['pending_signatures'] = (int)$db->query("SELECT COUNT(*) FROM contract_signature_fields WHERE status='pendiente'")->fetchColumn(); } catch (\Throwable $e) {}
        $out['risk_score'] = min(100, ($out['expired']*5) + ($out['due30']*2) + ($out['without_docs']*3) + ($out['pending_signatures']*1));
        return $out;
    }

    public static function charts(): array {
        $db = self::db();
        $charts = ['by_status'=>[], 'by_month'=>[], 'risk'=>[]];
        try { $charts['by_status'] = $db->query("SELECT COALESCE(status,'Sin estado') label, COUNT(*) total FROM contracts GROUP BY COALESCE(status,'Sin estado') ORDER BY total DESC LIMIT 10")->fetchAll(); } catch (\Throwable $e) {}
        try { $charts['by_month'] = $db->query("SELECT DATE_FORMAT(COALESCE(start_date, created_at),'%Y-%m') label, COUNT(*) total FROM contracts GROUP BY DATE_FORMAT(COALESCE(start_date, created_at),'%Y-%m') ORDER BY label DESC LIMIT 12")->fetchAll(); } catch (\Throwable $e) {}
        try { $charts['risk'] = $db->query("SELECT COALESCE(risk_level,'normal') label, COUNT(*) total FROM contracts GROUP BY COALESCE(risk_level,'normal')")->fetchAll(); } catch (\Throwable $e) {}
        return $charts;
    }

    public static function generateInsights(): int {
        $db = self::db();
        $created = 0;
        try { $db->exec("DELETE FROM contract_ai_insights WHERE status='abierta' AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)"); } catch (\Throwable $e) {}
        $rules = [
            ["critica", "Contrato vencido", "SELECT id, COALESCE(number,code,title,CONCAT('Contrato #',id)) name, end_date FROM contracts WHERE end_date IS NOT NULL AND end_date < CURDATE() LIMIT 100"],
            ["alta", "Contrato por vencer en 30 días", "SELECT id, COALESCE(number,code,title,CONCAT('Contrato #',id)) name, end_date FROM contracts WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) LIMIT 100"],
        ];
        foreach ($rules as [$severity,$title,$sql]) {
            try {
                foreach ($db->query($sql)->fetchAll() as $row) {
                    $exists = $db->prepare("SELECT COUNT(*) FROM contract_ai_insights WHERE contract_id=? AND title=? AND status='abierta'");
                    $exists->execute([(int)$row['id'],$title]);
                    if ((int)$exists->fetchColumn() > 0) continue;
                    $msg = $row['name'].' requiere revisión. Fecha fin: '.($row['end_date'] ?? 'N/A');
                    $ins = $db->prepare("INSERT INTO contract_ai_insights(contract_id,insight_type,severity,title,message,action_url,status,created_at) VALUES(?,?,?,?,?,?, 'abierta', NOW())");
                    $ins->execute([(int)$row['id'],'vencimiento',$severity,$title,$msg,'index.php?r=contracts.show&id='.(int)$row['id']]);
                    $created++;
                }
            } catch (\Throwable $e) {}
        }
        try {
            foreach ($db->query("SELECT c.id, COALESCE(c.number,c.code,c.title,CONCAT('Contrato #',c.id)) name FROM contracts c LEFT JOIN contract_documents d ON d.contract_id=c.id WHERE d.id IS NULL LIMIT 100")->fetchAll() as $row) {
                $exists = $db->prepare("SELECT COUNT(*) FROM contract_ai_insights WHERE contract_id=? AND title='Contrato sin documentos' AND status='abierta'");
                $exists->execute([(int)$row['id']]);
                if ((int)$exists->fetchColumn() > 0) continue;
                $ins = $db->prepare("INSERT INTO contract_ai_insights(contract_id,insight_type,severity,title,message,action_url,status,created_at) VALUES(?,?,?,?,?,?, 'abierta', NOW())");
                $ins->execute([(int)$row['id'],'documental','alta','Contrato sin documentos',$row['name'].' no tiene documentos asociados.','index.php?r=documents&contract_id='.(int)$row['id']]);
                $created++;
            }
        } catch (\Throwable $e) {}
        return $created;
    }

    public static function insights(string $status='abierta'): array {
        try { $st = self::db()->prepare("SELECT * FROM contract_ai_insights WHERE status=? ORDER BY FIELD(severity,'critica','alta','media','baja'), created_at DESC LIMIT 200"); $st->execute([$status]); return $st->fetchAll(); } catch (\Throwable $e) { return []; }
    }

    public static function saveSnapshot(): void {
        $k = self::kpis();
        try {
            $st = self::db()->prepare("INSERT INTO contract_kpi_snapshots(snapshot_date,total_contracts,active_contracts,expired_contracts,due_30_contracts,without_documents,pending_signatures,risk_score) VALUES(CURDATE(),?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE total_contracts=VALUES(total_contracts),active_contracts=VALUES(active_contracts),expired_contracts=VALUES(expired_contracts),due_30_contracts=VALUES(due_30_contracts),without_documents=VALUES(without_documents),pending_signatures=VALUES(pending_signatures),risk_score=VALUES(risk_score)");
            $st->execute([$k['total'],$k['active'],$k['expired'],$k['due30'],$k['without_docs'],$k['pending_signatures'],$k['risk_score']]);
        } catch (\Throwable $e) {}
    }
}
