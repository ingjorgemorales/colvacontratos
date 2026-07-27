<?php
namespace App\Services;

use App\Core\Database;
use App\Models\IntelligencePro;
use PDO;

final class SmartAlertEngine
{
    private PDO $db;
    private array $settings = [];
    private int $created = 0;
    private int $emails = 0;
    private int $evaluated = 0;
    private array $errors = [];

    public function __construct()
    {
        $this->db = Database::pdo();
        $this->loadSettings();
    }

    public static function run(): array
    {
        $engine = new self();
        $engine->ensureRuntimeTables();
        $engine->evaluateExpired();
        $engine->evaluateDueDays(30, 'alta');
        $engine->evaluateDueDays(90, 'media');
        $engine->evaluateLowExecution();
        $engine->evaluateWithoutDocuments();
        $engine->evaluateWithoutSupervisor();
        $engine->evaluateInactiveProviders();
        $engine->updateRiskLevels();
        $engine->saveKpiSnapshot();

        return [
            'evaluated' => $engine->evaluated,
            'created' => $engine->created,
            'emails' => $engine->emails,
            'errors' => $engine->errors,
            'finished_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function loadSettings(): void
    {
        try {
            $rows = $this->db->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll();
            foreach ($rows as $r) $this->settings[(string)$r['setting_key']] = (string)$r['setting_value'];
        } catch (\Throwable $e) {}
        try {
            $rows = $this->db->query("SELECT code, value FROM contract_alert_settings WHERE active=1")->fetchAll();
            foreach ($rows as $r) $this->settings[(string)$r['code']] = (string)$r['value'];
        } catch (\Throwable $e) {}
    }

    private function setting(string $key, string $default = ''): string
    {
        return $this->settings[$key] ?? $default;
    }

    private function ensureRuntimeTables(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS contract_ai_insights (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                contract_id BIGINT UNSIGNED NULL,
                insight_type VARCHAR(80) NOT NULL,
                severity ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
                title VARCHAR(220) NOT NULL,
                message TEXT NOT NULL,
                action_url VARCHAR(500) NULL,
                status ENUM('abierta','gestionada','descartada') NOT NULL DEFAULT 'abierta',
                notified_at DATETIME NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_open_insight (contract_id, insight_type, title, status),
                KEY idx_severity (severity), KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) { $this->errors[] = 'contract_ai_insights: '.$e->getMessage(); }
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS contract_alert_logs (
                id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NULL,
                alert_type VARCHAR(80) NOT NULL DEFAULT 'vencimiento',
                channel VARCHAR(40) NOT NULL DEFAULT 'email',
                recipient VARCHAR(180) NULL,
                subject VARCHAR(240) NULL,
                status VARCHAR(40) NOT NULL DEFAULT 'pending',
                error_message TEXT NULL,
                sent_at DATETIME NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_contract_id (contract_id), KEY idx_alert_type (alert_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) { $this->errors[] = 'contract_alert_logs: '.$e->getMessage(); }
    }

    private function evaluateExpired(): void
    {
        $sql = "SELECT c.id, c.number, c.name, COALESCE(c.extension_end_date,c.end_date) due_date, COALESCE(p.name,'') provider_name, COALESCE(p.email,'') provider_email
                FROM contracts c LEFT JOIN providers p ON p.id=c.provider_id
                WHERE COALESCE(c.extension_end_date,c.end_date) IS NOT NULL AND COALESCE(c.extension_end_date,c.end_date) < CURDATE() LIMIT 500";
        $this->forEachContract($sql, function($r) {
            $this->openInsight((int)$r['id'], 'vencido', 'critica', 'Contrato vencido',
                "El contrato {$r['number']} - {$r['name']} venció el {$r['due_date']}. Proveedor: {$r['provider_name']}",
                'index.php?r=contracts.show&id='.(int)$r['id'], $r['provider_email'] ?? '');
        });
    }

    private function evaluateDueDays(int $days, string $severity): void
    {
        $sql = "SELECT c.id, c.number, c.name, COALESCE(c.extension_end_date,c.end_date) due_date,
                       DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) days_left,
                       COALESCE(p.name,'') provider_name, COALESCE(p.email,'') provider_email
                FROM contracts c LEFT JOIN providers p ON p.id=c.provider_id
                WHERE COALESCE(c.extension_end_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY) LIMIT 500";
        $this->forEachContract($sql, function($r) use ($days, $severity) {
            $this->openInsight((int)$r['id'], 'vencimiento_'.$days, $severity, "Contrato por vencer en {$days} días",
                "El contrato {$r['number']} vence el {$r['due_date']} ({$r['days_left']} días). Proveedor: {$r['provider_name']}",
                'index.php?r=contracts.show&id='.(int)$r['id'], $r['provider_email'] ?? '');
        });
    }

    private function evaluateLowExecution(): void
    {
        $sql = "SELECT c.id,c.number,c.name,c.total_value,c.executed_value,c.execution_percent,COALESCE(p.name,'') provider_name,COALESCE(p.email,'') provider_email
                FROM contracts c LEFT JOIN providers p ON p.id=c.provider_id
                WHERE COALESCE(c.total_value,0) > 0 AND COALESCE(c.execution_percent,0) < 20
                  AND COALESCE(c.extension_end_date,c.end_date) <= DATE_ADD(CURDATE(), INTERVAL 120 DAY) LIMIT 500";
        $this->forEachContract($sql, function($r) {
            $this->openInsight((int)$r['id'], 'ejecucion_baja', 'alta', 'Ejecución baja vs valor contratado',
                "Contrato {$r['number']} con ejecución {$r['execution_percent']}% y valor total $".number_format((float)$r['total_value'],0,',','.').". Requiere revisión financiera.",
                'index.php?r=finance.contract&id='.(int)$r['id'], $r['provider_email'] ?? '');
        });
    }

    private function evaluateWithoutDocuments(): void
    {
        $sql = "SELECT c.id,c.number,c.name,COALESCE(p.email,'') provider_email FROM contracts c
                LEFT JOIN providers p ON p.id=c.provider_id
                LEFT JOIN contract_documents d ON d.contract_id=c.id
                GROUP BY c.id,c.number,c.name,p.email HAVING COUNT(d.id)=0 LIMIT 500";
        $this->forEachContract($sql, function($r) {
            $this->openInsight((int)$r['id'], 'sin_documentos', 'alta', 'Contrato sin documentos',
                "El contrato {$r['number']} no tiene documentos cargados en el módulo documental.",
                'index.php?r=documents&contract_id='.(int)$r['id'], $r['provider_email'] ?? '');
        });
    }

    private function evaluateWithoutSupervisor(): void
    {
        $sql = "SELECT id,number,name FROM contracts WHERE (supervisor_id IS NULL OR supervisor_id=0) AND COALESCE(supervisor_name,'')='' LIMIT 500";
        $this->forEachContract($sql, function($r) {
            $this->openInsight((int)$r['id'], 'sin_supervisor', 'media', 'Contrato sin supervisor asignado',
                "El contrato {$r['number']} no tiene supervisor asignado.",
                'index.php?r=contracts.edit&id='.(int)$r['id'], '');
        });
    }

    private function evaluateInactiveProviders(): void
    {
        try {
            $rows = $this->db->query("SELECT p.id, p.name, p.email FROM providers p LEFT JOIN contracts c ON c.provider_id=p.id GROUP BY p.id,p.name,p.email HAVING COUNT(c.id)=0 LIMIT 300")->fetchAll();
            foreach ($rows as $r) {
                $this->evaluated++;
                $this->openInsight(0, 'proveedor_inactivo_'.$r['id'], 'baja', 'Proveedor sin contratos asociados',
                    "El proveedor {$r['name']} no tiene contratos asociados. Validar si debe permanecer activo.",
                    'index.php?r=providers.edit&id='.(int)$r['id'], $r['email'] ?? '');
            }
        } catch (\Throwable $e) { $this->errors[] = 'proveedores: '.$e->getMessage(); }
    }

    private function forEachContract(string $sql, callable $fn): void
    {
        try {
            foreach ($this->db->query($sql)->fetchAll() as $row) {
                $this->evaluated++;
                $fn($row);
            }
        } catch (\Throwable $e) { $this->errors[] = $e->getMessage(); }
    }

    private function openInsight(int $contractId, string $type, string $severity, string $title, string $message, string $url, string $preferredEmail): void
    {
        try {
            // Normaliza el id: 0 significa "sin contrato" y se guarda como NULL.
            // El chequeo de existencia usa <=> (igualdad segura con NULL) para
            // que NULL coincida con NULL; antes comparaba 0 contra NULL y por eso
            // creaba un insight duplicado en cada ejecución del motor.
            $cid = $contractId > 0 ? $contractId : null;
            $exists = $this->db->prepare("SELECT id FROM contract_ai_insights WHERE contract_id <=> ? AND insight_type=? AND title=? AND status='abierta' LIMIT 1");
            $exists->execute([$cid, $type, $title]);
            $id = $exists->fetchColumn();
            if (!$id) {
                $ins = $this->db->prepare("INSERT INTO contract_ai_insights(contract_id, insight_type, severity, title, message, action_url, status, created_at) VALUES(?,?,?,?,?,?, 'abierta', NOW())");
                $ins->execute([$cid, $type, $severity, $title, $message, $url]);
                $this->created++;
                $id = (int)$this->db->lastInsertId();
            }
            if ($this->setting('notifications_enabled', $this->setting('alert_email_enabled','0')) === '1') {
                $this->notify((int)$contractId, $type, $title, $message, $preferredEmail);
                try { $this->db->prepare("UPDATE contract_ai_insights SET notified_at=NOW() WHERE id=? AND notified_at IS NULL")->execute([$id]); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) { $this->errors[] = 'insight '.$type.': '.$e->getMessage(); }
    }

    private function notify(int $contractId, string $type, string $title, string $message, string $preferredEmail): void
    {
        $recipients = $this->recipients($preferredEmail);
        foreach ($recipients as $to) {
            $subject = 'ColvaContratos - '.$title;
            $body = $message."\n\nFecha: ".date('Y-m-d H:i:s')."\nSistema: ColvaContratos\n";
            $ok = Mailer::send($to, $subject, $body);
            $this->logAlert($contractId, $type, $to, $subject, $ok ? 'sent' : 'error', $ok ? null : 'mail() retornó false o SMTP no configurado');
            if ($ok) $this->emails++;
        }
    }

    private function recipients(string $preferredEmail): array
    {
        $raw = $this->setting('notifications_recipients', $this->setting('alerts_to_email', ''));
        $parts = preg_split('/[;,\s]+/', $raw) ?: [];
        if ($preferredEmail !== '') $parts[] = $preferredEmail;
        $out = [];
        foreach ($parts as $p) {
            $p = trim((string)$p);
            if ($p !== '' && filter_var($p, FILTER_VALIDATE_EMAIL)) $out[] = $p;
        }
        return array_values(array_unique($out));
    }

    private function logAlert(int $contractId, string $type, string $to, string $subject, string $status, ?string $error): void
    {
        try {
            $st = $this->db->prepare("INSERT INTO contract_alert_logs(contract_id, alert_type, channel, recipient, subject, status, error_message, sent_at) VALUES(?,?,?,?,?,?,?,NOW())");
            $st->execute([$contractId ?: null, $type, 'email', $to, $subject, $status, $error]);
        } catch (\Throwable $e) {}
    }

    private function updateRiskLevels(): void
    {
        try { $this->db->exec("UPDATE contracts SET risk_level='critico' WHERE COALESCE(extension_end_date,end_date) < CURDATE()"); } catch (\Throwable $e) {}
        try { $this->db->exec("UPDATE contracts SET risk_level='alto' WHERE COALESCE(extension_end_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"); } catch (\Throwable $e) {}
        try { $this->db->exec("UPDATE contracts SET risk_level='medio' WHERE COALESCE(extension_end_date,end_date) BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)"); } catch (\Throwable $e) {}
        try { $this->db->exec("UPDATE contracts SET last_alert_at=NOW() WHERE risk_level IN ('critico','alto','medio')"); } catch (\Throwable $e) {}
    }

    private function saveKpiSnapshot(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS contract_kpi_snapshots (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                snapshot_date DATE NOT NULL UNIQUE,
                total_contracts INT NOT NULL DEFAULT 0,
                active_contracts INT NOT NULL DEFAULT 0,
                expired_contracts INT NOT NULL DEFAULT 0,
                due_30_contracts INT NOT NULL DEFAULT 0,
                without_documents INT NOT NULL DEFAULT 0,
                pending_signatures INT NOT NULL DEFAULT 0,
                risk_score DECIMAL(6,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $k = IntelligencePro::kpis();
            $st = $this->db->prepare("INSERT INTO contract_kpi_snapshots(snapshot_date,total_contracts,active_contracts,expired_contracts,due_30_contracts,without_documents,pending_signatures,risk_score)
                VALUES(CURDATE(),?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE total_contracts=VALUES(total_contracts),active_contracts=VALUES(active_contracts),expired_contracts=VALUES(expired_contracts),due_30_contracts=VALUES(due_30_contracts),without_documents=VALUES(without_documents),pending_signatures=VALUES(pending_signatures),risk_score=VALUES(risk_score)");
            $st->execute([$k['total'],$k['active'],$k['expired'],$k['due30'],$k['without_docs'],$k['pending_signatures'],$k['risk_score']]);
        } catch (\Throwable $e) { $this->errors[] = 'snapshot: '.$e->getMessage(); }
    }
}
