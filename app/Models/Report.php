<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class Report {
    private static function pdo(): PDO { return Database::pdo(); }

    public static function scalar(string $sql, array $params = []): int|float|string|null {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    }

    private static function tableExists(string $table): bool {
        try {
            $st = self::pdo()->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
            $st->execute([$table]);
            return (int)$st->fetchColumn() > 0;
        } catch (\Throwable $e) { return false; }
    }

    public static function dashboardStats(): array {
        $pdo = self::pdo();
        $statusCount = function(string $code) use ($pdo): int {
            try { $st = $pdo->prepare("SELECT COUNT(*) FROM contracts c JOIN contract_statuses s ON s.id=c.status_id WHERE s.code=?"); $st->execute([$code]); return (int)$st->fetchColumn(); }
            catch (\Throwable $e) { return 0; }
        };
        $q = function(string $sql) use ($pdo) { try { return $pdo->query($sql)->fetchColumn(); } catch (\Throwable $e) { return 0; } };
        return [
            'total' => (int)$q('SELECT COUNT(*) FROM contracts'),
            'active' => $statusCount('active'),
            'pending_liquidation' => $statusCount('pending_liquidation'),
            'liquidated' => $statusCount('liquidated'),
            'cancelled' => $statusCount('cancelled'),
            'expiring' => (int)$q("SELECT COUNT(*) FROM contracts WHERE COALESCE(extension_end_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)"),
            'critical' => (int)$q("SELECT COUNT(*) FROM contracts WHERE COALESCE(extension_end_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"),
            'expired' => (int)$q("SELECT COUNT(*) FROM contracts WHERE COALESCE(extension_end_date,end_date) < CURDATE()"),
            'providers' => (int)$q('SELECT COUNT(*) FROM providers'),
            'documents' => (int)$q('SELECT COUNT(*) FROM contract_documents'),
            'value' => (float)$q('SELECT COALESCE(SUM(total_value),0) FROM contracts'),
            'executed' => (float)$q('SELECT COALESCE(SUM(executed_value),0) FROM contracts'),
            'avg_execution' => (float)$q('SELECT COALESCE(AVG(execution_percent),0) FROM contracts'),
        ];
    }

    public static function reportKpis(array $filters = []): array {
        $rows = self::contracts($filters);
        $total = count($rows); $value=0; $executed=0; $expired=0; $critical=0; $avg=0;
        foreach ($rows as $r) {
            $value += (float)($r['total_value'] ?? 0);
            $executed += (float)($r['executed_value'] ?? 0);
            $avg += (float)($r['execution_percent'] ?? 0);
            if (($r['risk_level'] ?? '') === 'VENCIDO') $expired++;
            if (($r['risk_level'] ?? '') === 'ROJO') $critical++;
        }
        return [
            'total' => $total,
            'valor_total' => $value,
            'ejecutado' => $executed,
            'saldo' => $value - $executed,
            'ejecucion_promedio' => $total ? $avg / $total : 0,
            'vencidos' => $expired,
            'criticos' => $critical,
        ];
    }

    public static function riskSummary(): array {
        try {
            return self::pdo()->query("SELECT
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) < CURDATE() THEN 1 ELSE 0 END) AS vencidos,
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS rojo,
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS amarillo,
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) > DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS verde,
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) IS NULL THEN 1 ELSE 0 END) AS sin_fecha
                FROM contracts")->fetch() ?: [];
        } catch (\Throwable $e) { return ['vencidos'=>0,'rojo'=>0,'amarillo'=>0,'verde'=>0,'sin_fecha'=>0]; }
    }

    public static function financialSummary(): array {
        try {
            return self::pdo()->query("SELECT
                COALESCE(SUM(initial_value),0) AS valor_inicial,
                COALESCE(SUM(initial_vat),0) AS iva,
                COALESCE(SUM(total_initial),0) AS total_inicial,
                COALESCE(SUM(additions_value),0) AS adiciones,
                COALESCE(SUM(total_value),0) AS valor_total,
                COALESCE(SUM(executed_value),0) AS ejecutado,
                COALESCE(AVG(execution_percent),0) AS ejecucion_promedio
                FROM contracts")->fetch() ?: [];
        } catch (\Throwable $e) { return []; }
    }

    public static function byStatus(): array {
        try {
            return self::pdo()->query("SELECT COALESCE(s.name,'Sin estado') AS status, COUNT(c.id) AS total, COALESCE(SUM(c.total_value),0) AS value
                    FROM contracts c LEFT JOIN contract_statuses s ON s.id = c.status_id
                    GROUP BY COALESCE(s.name,'Sin estado') ORDER BY total DESC")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function byArea(): array {
        try {
            return self::pdo()->query("SELECT COALESCE(a.name,'Sin área') AS area, COUNT(c.id) AS total, COALESCE(SUM(c.total_value),0) AS value
                    FROM contracts c LEFT JOIN areas a ON a.id=c.area_id
                    GROUP BY COALESCE(a.name,'Sin área') ORDER BY total DESC LIMIT 10")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function byExpenseType(): array {
        try {
            return self::pdo()->query("SELECT COALESCE(t.name,'Sin tipo gasto') AS label, COUNT(c.id) AS total, COALESCE(SUM(c.total_value),0) AS value
                    FROM contracts c LEFT JOIN contract_tipo_gasto t ON t.id=c.expense_type_id
                    GROUP BY COALESCE(t.name,'Sin tipo gasto') ORDER BY value DESC, total DESC")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function bySelectionModality(): array {
        try {
            return self::pdo()->query("SELECT COALESCE(t.name,'Sin modalidad') AS label, COUNT(c.id) AS total, COALESCE(SUM(c.total_value),0) AS value
                    FROM contracts c LEFT JOIN contract_modalidad_seleccion t ON t.id=c.selection_modality_id
                    GROUP BY COALESCE(t.name,'Sin modalidad') ORDER BY total DESC")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function expiring(int $days = 90): array {
        try {
            $st = self::pdo()->prepare("SELECT c.id, c.number, c.name, COALESCE(c.extension_end_date,c.end_date) AS end_date,
                    DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) AS days_left,
                    CASE
                      WHEN COALESCE(c.extension_end_date,c.end_date) < CURDATE() THEN 'VENCIDO'
                      WHEN DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) <= 30 THEN 'ROJO'
                      WHEN DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) <= 90 THEN 'AMARILLO'
                      ELSE 'VERDE'
                    END AS risk_level,
                    COALESCE(p.name,'') AS provider_name
                FROM contracts c LEFT JOIN providers p ON p.id=c.provider_id
                WHERE COALESCE(c.extension_end_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY COALESCE(c.extension_end_date,c.end_date) ASC LIMIT 20");
            $st->execute([$days]); return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function recentContracts(int $limit = 8): array {
        try {
            $st = self::pdo()->prepare("SELECT c.id, c.number, c.name, COALESCE(p.name,'') AS provider_name, COALESCE(s.name,'Sin estado') AS status,
                    c.total_value, COALESCE(c.extension_end_date,c.end_date) AS end_date
                FROM contracts c LEFT JOIN providers p ON p.id=c.provider_id LEFT JOIN contract_statuses s ON s.id=c.status_id
                ORDER BY c.updated_at DESC, c.id DESC LIMIT ?");
            $st->bindValue(1, $limit, PDO::PARAM_INT); $st->execute(); return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function contracts(array $filters = []): array {
        $where=[]; $params=[];
        if (!empty($filters['area_id'])) { $where[]='c.area_id=?'; $params[]=(int)$filters['area_id']; }
        if (!empty($filters['status_id'])) { $where[]='c.status_id=?'; $params[]=(int)$filters['status_id']; }
        if (!empty($filters['provider_id'])) { $where[]='c.provider_id=?'; $params[]=(int)$filters['provider_id']; }
        if (!empty($filters['expense_type_id'])) { $where[]='c.expense_type_id=?'; $params[]=(int)$filters['expense_type_id']; }
        if (!empty($filters['selection_modality_id'])) { $where[]='c.selection_modality_id=?'; $params[]=(int)$filters['selection_modality_id']; }
        if (!empty($filters['date_from'])) { $where[]='COALESCE(c.extension_end_date,c.end_date) >= ?'; $params[]=$filters['date_from']; }
        if (!empty($filters['date_to'])) { $where[]='COALESCE(c.extension_end_date,c.end_date) <= ?'; $params[]=$filters['date_to']; }
        if (!empty($filters['risk'])) {
            if ($filters['risk']==='vencido') $where[]='COALESCE(c.extension_end_date,c.end_date) < CURDATE()';
            if ($filters['risk']==='rojo') $where[]='COALESCE(c.extension_end_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
            if ($filters['risk']==='amarillo') $where[]='COALESCE(c.extension_end_date,c.end_date) BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
            if ($filters['risk']==='verde') $where[]='COALESCE(c.extension_end_date,c.end_date) > DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
        }
        if (!empty($filters['q'])) { $where[]='(c.number LIKE ? OR c.name LIKE ? OR p.name LIKE ? OR c.supervisor_name LIKE ?)'; $q='%'.$filters['q'].'%'; array_push($params,$q,$q,$q,$q); }
        $w=$where?'WHERE '.implode(' AND ',$where):'';
        try {
            $sql="SELECT c.id,c.number,c.name,c.object,c.supervisor_name,c.start_date,c.end_date,c.extension_end_date,c.total_value,c.executed_value,c.execution_percent,
                       COALESCE(a.name,'') area_name, COALESCE(p.name,'') provider_name, COALESCE(s.name,'Sin estado') status_name,
                       COALESCE(tg.name,'') expense_type_name, COALESCE(ms.name,'') selection_modality_name,
                       DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) days_left,
                       CASE WHEN COALESCE(c.extension_end_date,c.end_date) < CURDATE() THEN 'VENCIDO'
                            WHEN DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) <= 30 THEN 'ROJO'
                            WHEN DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) <= 90 THEN 'AMARILLO'
                            ELSE 'VERDE' END risk_level
                    FROM contracts c
                    LEFT JOIN areas a ON a.id=c.area_id
                    LEFT JOIN providers p ON p.id=c.provider_id
                    LEFT JOIN contract_statuses s ON s.id=c.status_id
                    LEFT JOIN contract_tipo_gasto tg ON tg.id=c.expense_type_id
                    LEFT JOIN contract_modalidad_seleccion ms ON ms.id=c.selection_modality_id
                    $w ORDER BY COALESCE(c.extension_end_date,c.end_date) ASC, c.id DESC";
            $st=self::pdo()->prepare($sql); $st->execute($params); return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function providers(array $filters = []): array {
        $where=[]; $params=[];
        if (!empty($filters['provider_q'])) { $where[]='(p.document_number LIKE ? OR p.name LIKE ? OR p.email LIKE ? OR p.city LIKE ? OR cc.name LIKE ?)'; $q='%'.$filters['provider_q'].'%'; array_push($params,$q,$q,$q,$q,$q); }
        if (!empty($filters['tipo_contratista_id'])) { $where[]='p.tipo_contratista_id=?'; $params[]=(int)$filters['tipo_contratista_id']; }
        if (!empty($filters['tipo_persona_id'])) { $where[]='p.tipo_persona_id=?'; $params[]=(int)$filters['tipo_persona_id']; }
        if (!empty($filters['naturaleza_id'])) { $where[]='p.naturaleza_id=?'; $params[]=(int)$filters['naturaleza_id']; }
        if (!empty($filters['clasificacion_id'])) { $where[]='p.clasificacion_id=?'; $params[]=(int)$filters['clasificacion_id']; }
        if (!empty($filters['nacionalidad_id'])) { $where[]='p.nacionalidad_contratista_id=?'; $params[]=(int)$filters['nacionalidad_id']; }
        if (isset($filters['provider_active']) && $filters['provider_active'] !== '') { $where[]='p.active=?'; $params[]=(int)$filters['provider_active']; }
        $w=$where?'WHERE '.implode(' AND ',$where):'';
        try {
            $sql="SELECT p.*, COALESCE(pt.name,'') AS tipo_proveedor,
                    COALESCE(tc.name,'') AS tipo_contratista,
                    COALESCE(tp.name,'') AS tipo_persona,
                    COALESCE(n.name,'') AS naturaleza,
                    COALESCE(cc.name,'') AS clasificacion,
                    COALESCE(na.name,'') AS nacionalidad,
                    COALESCE(cl.name,'') AS clase_contratista,
                    COUNT(c.id) AS contratos_total,
                    COALESCE(SUM(c.total_value),0) AS contratos_valor
                FROM providers p
                LEFT JOIN provider_types pt ON pt.id=p.provider_type_id
                LEFT JOIN catalog_tipo_contratista tc ON tc.id=p.tipo_contratista_id
                LEFT JOIN catalog_tipo_persona tp ON tp.id=p.tipo_persona_id
                LEFT JOIN catalog_naturaleza n ON n.id=p.naturaleza_id
                LEFT JOIN catalog_clasificacion cc ON cc.id=p.clasificacion_id
                LEFT JOIN catalog_nacionalidad_contratista na ON na.id=p.nacionalidad_contratista_id
                LEFT JOIN catalog_clase_contratista cl ON cl.id=p.clase_contratista_id
                LEFT JOIN contracts c ON c.provider_id=p.id
                $w
                GROUP BY p.id
                ORDER BY p.name ASC";
            $st=self::pdo()->prepare($sql); $st->execute($params); return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function payments(array $filters = []): array {
        $where=[]; $params=[];
        if (!empty($filters['pay_date_from'])) { $where[]='cp.invoice_date >= ?'; $params[]=$filters['pay_date_from']; }
        if (!empty($filters['pay_date_to'])) { $where[]='cp.invoice_date <= ?'; $params[]=$filters['pay_date_to']; }
        if (!empty($filters['provider_id'])) { $where[]='c.provider_id=?'; $params[]=(int)$filters['provider_id']; }
        if (!empty($filters['q'])) { $where[]='(c.number LIKE ? OR c.name LIKE ? OR p.name LIKE ? OR cp.invoice_number LIKE ? OR cp.payment_order LIKE ?)'; $q='%'.$filters['q'].'%'; array_push($params,$q,$q,$q,$q,$q); }
        $w=$where?'WHERE '.implode(' AND ',$where):'';
        try {
            $sql="SELECT cp.*, c.number, c.name AS contract_name, COALESCE(p.name,'') AS provider_name, COALESCE(a.name,'') AS area_name
                FROM contract_payments cp
                INNER JOIN contracts c ON c.id=cp.contract_id
                LEFT JOIN providers p ON p.id=c.provider_id
                LEFT JOIN areas a ON a.id=c.area_id
                $w ORDER BY cp.invoice_date DESC, cp.id DESC";
            $st=self::pdo()->prepare($sql); $st->execute($params); return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function documents(array $filters = []): array {
        $where=[]; $params=[];
        if (!empty($filters['doc_type'])) { $where[]='cd.document_type=?'; $params[]=$filters['doc_type']; }
        if (!empty($filters['doc_status'])) { $where[]='cd.signed_status=?'; $params[]=$filters['doc_status']; }
        if (!empty($filters['q'])) { $where[]='(c.number LIKE ? OR c.name LIKE ? OR p.name LIKE ? OR cd.original_name LIKE ?)'; $q='%'.$filters['q'].'%'; array_push($params,$q,$q,$q,$q); }
        $w=$where?'WHERE '.implode(' AND ',$where):'';
        try {
            $sql="SELECT cd.*, c.number, c.name AS contract_name, COALESCE(p.name,'') AS provider_name
                FROM contract_documents cd
                INNER JOIN contracts c ON c.id=cd.contract_id
                LEFT JOIN providers p ON p.id=c.provider_id
                $w ORDER BY cd.created_at DESC, cd.id DESC";
            $st=self::pdo()->prepare($sql); $st->execute($params); return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public static function analytics(array $filters = []): array {
        return [
            'riesgo' => self::analyticsRisk(),
            'areas' => self::analyticsAreas(),
            'proveedores_clasificacion' => self::analyticsProviderClassification(),
            'proveedores_tipo' => self::analyticsProviderType(),
            'financiero_proveedor' => self::analyticsFinancialProviders(),
            'documentos_estado' => self::analyticsDocumentStatus(),
            'modalidades' => self::analyticsSelectionModality(),
        ];
    }

    private static function analyticsRisk(): array {
        try {
            $row = self::pdo()->query("SELECT
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) < CURDATE() THEN 1 ELSE 0 END) AS vencidos,
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS rojo,
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS amarillo,
                SUM(CASE WHEN COALESCE(extension_end_date,end_date) > DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS verde,
                COALESCE(SUM(total_value),0) AS value
                FROM contracts")->fetch() ?: [];
            return [
                ['label'=>'Vencidos', 'total'=>(int)($row['vencidos'] ?? 0), 'value'=>0],
                ['label'=>'Rojo 0-30 dias', 'total'=>(int)($row['rojo'] ?? 0), 'value'=>0],
                ['label'=>'Amarillo 31-90 dias', 'total'=>(int)($row['amarillo'] ?? 0), 'value'=>0],
                ['label'=>'Verde +90 dias', 'total'=>(int)($row['verde'] ?? 0), 'value'=>0],
            ];
        } catch (\Throwable $e) { return []; }
    }

    private static function analyticsAreas(): array {
        try { return self::pdo()->query("SELECT COALESCE(a.name,'Sin área') label, COUNT(c.id) total, COALESCE(SUM(c.total_value),0) value
            FROM contracts c LEFT JOIN areas a ON a.id=c.area_id GROUP BY COALESCE(a.name,'Sin área') ORDER BY value DESC, total DESC LIMIT 10")->fetchAll(); }
        catch (\Throwable $e) { return []; }
    }

    private static function analyticsProviderClassification(): array {
        try { return self::pdo()->query("SELECT COALESCE(cc.name,'Sin clasificación') label, COUNT(p.id) total, COALESCE(SUM(c.total_value),0) value
            FROM providers p LEFT JOIN catalog_clasificacion cc ON cc.id=p.clasificacion_id LEFT JOIN contracts c ON c.provider_id=p.id
            GROUP BY COALESCE(cc.name,'Sin clasificación') ORDER BY total DESC, value DESC LIMIT 12")->fetchAll(); }
        catch (\Throwable $e) { return []; }
    }

    private static function analyticsProviderType(): array {
        try { return self::pdo()->query("SELECT COALESCE(tc.name,'Sin tipo contratista') label, COUNT(p.id) total, COALESCE(SUM(c.total_value),0) value
            FROM providers p LEFT JOIN catalog_tipo_contratista tc ON tc.id=p.tipo_contratista_id LEFT JOIN contracts c ON c.provider_id=p.id
            GROUP BY COALESCE(tc.name,'Sin tipo contratista') ORDER BY total DESC, value DESC")->fetchAll(); }
        catch (\Throwable $e) { return []; }
    }

    private static function analyticsFinancialProviders(): array {
        try { return self::pdo()->query("SELECT COALESCE(p.name,'Sin proveedor') label, COUNT(c.id) total, COALESCE(SUM(c.total_value),0) value
            FROM contracts c LEFT JOIN providers p ON p.id=c.provider_id GROUP BY COALESCE(p.name,'Sin proveedor') ORDER BY value DESC LIMIT 10")->fetchAll(); }
        catch (\Throwable $e) { return []; }
    }

    private static function analyticsDocumentStatus(): array {
        try { return self::pdo()->query("SELECT COALESCE(cd.signed_status,'Sin estado') label, COUNT(cd.id) total, 0 value
            FROM contract_documents cd GROUP BY COALESCE(cd.signed_status,'Sin estado') ORDER BY total DESC")->fetchAll(); }
        catch (\Throwable $e) { return []; }
    }

    private static function analyticsSelectionModality(): array {
        try { return self::pdo()->query("SELECT COALESCE(ms.name,'Sin modalidad') label, COUNT(c.id) total, COALESCE(SUM(c.total_value),0) value
            FROM contracts c LEFT JOIN contract_modalidad_seleccion ms ON ms.id=c.selection_modality_id GROUP BY COALESCE(ms.name,'Sin modalidad') ORDER BY total DESC LIMIT 10")->fetchAll(); }
        catch (\Throwable $e) { return []; }
    }


    public static function intelligence(array $filters = []): array {
        return [
            'summary' => self::intelligenceSummary(),
            'expiring' => self::intelligenceExpiringContracts(),
            'provider_risk' => self::intelligenceProviderRisk(),
            'inactive_providers' => self::intelligenceInactiveProviders(),
            'missing_docs' => self::intelligenceMissingDocuments(),
            'concentration' => self::intelligenceConcentration(),
        ];
    }

    private static function intelligenceSummary(): array {
        try {
            $contracts = (int)(self::scalar("SELECT COUNT(*) FROM contracts") ?? 0);
            $providers = (int)(self::scalar("SELECT COUNT(*) FROM providers WHERE COALESCE(active,1)=1") ?? 0);
            $expired = (int)(self::scalar("SELECT COUNT(*) FROM contracts WHERE COALESCE(extension_end_date,end_date) < CURDATE()") ?? 0);
            $red = (int)(self::scalar("SELECT COUNT(*) FROM contracts WHERE COALESCE(extension_end_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)") ?? 0);
            $value = (float)(self::scalar("SELECT COALESCE(SUM(total_value),0) FROM contracts") ?? 0);
            $pendingDocs = 0;
            if (self::tableExists('contract_document_checklist')) {
                $pendingDocs = (int)(self::scalar("SELECT COUNT(*) FROM contract_document_checklist WHERE status='pendiente'") ?? 0);
            }
            return compact('contracts','providers','expired','red','value','pendingDocs');
        } catch (\Throwable $e) { return ['contracts'=>0,'providers'=>0,'expired'=>0,'red'=>0,'value'=>0,'pendingDocs'=>0]; }
    }

    private static function intelligenceExpiringContracts(): array {
        try {
            return self::pdo()->query("SELECT c.id,c.number,c.name,COALESCE(p.name,'Sin proveedor') provider_name,COALESCE(a.name,'Sin área') area_name,
                    COALESCE(c.extension_end_date,c.end_date) end_effective,c.total_value,c.execution_percent,
                    DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) days_left,
                    CASE WHEN COALESCE(c.extension_end_date,c.end_date) < CURDATE() THEN 'VENCIDO'
                         WHEN DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) <= 30 THEN 'ROJO'
                         WHEN DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) <= 90 THEN 'AMARILLO'
                         ELSE 'VERDE' END risk_level
                FROM contracts c
                LEFT JOIN providers p ON p.id=c.provider_id
                LEFT JOIN areas a ON a.id=c.area_id
                WHERE COALESCE(c.extension_end_date,c.end_date) IS NOT NULL
                  AND COALESCE(c.extension_end_date,c.end_date) <= DATE_ADD(CURDATE(), INTERVAL 120 DAY)
                ORDER BY COALESCE(c.extension_end_date,c.end_date) ASC LIMIT 25")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    private static function intelligenceProviderRisk(): array {
        try {
            return self::pdo()->query("SELECT p.id, COALESCE(p.name,'Sin proveedor') provider_name, p.document_number,
                    COUNT(c.id) contracts_total,
                    COALESCE(SUM(c.total_value),0) contract_value,
                    SUM(CASE WHEN COALESCE(c.extension_end_date,c.end_date) < CURDATE() THEN 1 ELSE 0 END) expired_total,
                    SUM(CASE WHEN COALESCE(c.extension_end_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) red_total,
                    SUM(CASE WHEN c.execution_percent >= 90 AND COALESCE(c.extension_end_date,c.end_date) > CURDATE() THEN 1 ELSE 0 END) high_execution_total,
                    (SUM(CASE WHEN COALESCE(c.extension_end_date,c.end_date) < CURDATE() THEN 1 ELSE 0 END)*5 +
                     SUM(CASE WHEN COALESCE(c.extension_end_date,c.end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END)*3 +
                     SUM(CASE WHEN c.execution_percent >= 90 THEN 1 ELSE 0 END)*2) risk_score
                FROM providers p
                LEFT JOIN contracts c ON c.provider_id=p.id
                GROUP BY p.id
                HAVING contracts_total > 0
                ORDER BY risk_score DESC, contract_value DESC LIMIT 15")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    private static function intelligenceInactiveProviders(): array {
        try {
            return self::pdo()->query("SELECT p.id,p.document_number,p.name,p.email,p.city,p.created_at
                FROM providers p
                LEFT JOIN contracts c ON c.provider_id=p.id
                WHERE COALESCE(p.active,1)=1
                GROUP BY p.id
                HAVING COUNT(c.id)=0
                ORDER BY p.name ASC LIMIT 30")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    private static function intelligenceMissingDocuments(): array {
        try {
            if (self::tableExists('contract_document_checklist')) {
                return self::pdo()->query("SELECT c.id,c.number,c.name,COALESCE(p.name,'Sin proveedor') provider_name,c.total_value,
                        SUM(CASE WHEN chk.status='pendiente' THEN 1 ELSE 0 END) pending_docs,
                        COUNT(chk.id) checklist_total
                    FROM contracts c
                    LEFT JOIN providers p ON p.id=c.provider_id
                    LEFT JOIN contract_document_checklist chk ON chk.contract_id=c.id
                    GROUP BY c.id
                    HAVING pending_docs > 0
                    ORDER BY pending_docs DESC, c.total_value DESC LIMIT 25")->fetchAll();
            }
            return self::pdo()->query("SELECT c.id,c.number,c.name,COALESCE(p.name,'Sin proveedor') provider_name,c.total_value,
                    CASE WHEN COUNT(cd.id)=0 THEN 1 ELSE 0 END pending_docs, COUNT(cd.id) checklist_total
                FROM contracts c
                LEFT JOIN providers p ON p.id=c.provider_id
                LEFT JOIN contract_documents cd ON cd.contract_id=c.id
                GROUP BY c.id
                HAVING pending_docs > 0
                ORDER BY c.total_value DESC LIMIT 25")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    private static function intelligenceConcentration(): array {
        try {
            $total = (float)(self::scalar("SELECT COALESCE(SUM(total_value),0) FROM contracts") ?? 0);
            $rows = self::pdo()->query("SELECT COALESCE(p.name,'Sin proveedor') provider_name, COUNT(c.id) contracts_total, COALESCE(SUM(c.total_value),0) contract_value
                FROM contracts c LEFT JOIN providers p ON p.id=c.provider_id
                GROUP BY COALESCE(p.name,'Sin proveedor')
                ORDER BY contract_value DESC LIMIT 10")->fetchAll();
            foreach ($rows as &$r) { $r['percent_value'] = $total > 0 ? round(((float)$r['contract_value']/$total)*100, 2) : 0; }
            return $rows;
        } catch (\Throwable $e) { return []; }
    }
}

