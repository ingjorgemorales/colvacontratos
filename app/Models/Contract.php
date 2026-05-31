<?php
namespace App\Models;
use App\Core\Database;
use PDO;

final class Contract {
    public static function paginate(array $filters, int $limit=20, int $offset=0): array {
        $where=[]; $params=[];
        if (!empty($filters['area_id'])) { $where[]='c.area_id=?'; $params[]=(int)$filters['area_id']; }
        if (!empty($filters['status_id'])) { $where[]='c.status_id=?'; $params[]=(int)$filters['status_id']; }
        if (!empty($filters['contract_type_id'])) { $where[]='c.contract_type_id=?'; $params[]=(int)$filters['contract_type_id']; }
        if (!empty($filters['q'])) { $where[]='(c.number LIKE ? OR c.name LIKE ? OR p.name LIKE ? OR c.supervisor_name LIKE ?)'; $q='%'.$filters['q'].'%'; array_push($params,$q,$q,$q,$q); }
        $w = $where ? 'WHERE '.implode(' AND ', $where) : '';
        $sql = "SELECT c.*, a.name area_name, sa.name sub_area_name, p.name provider_name, s.name status_name, ct.name contract_type_name,
                       DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) AS days_to_expire
                FROM contracts c
                LEFT JOIN areas a ON a.id=c.area_id
                LEFT JOIN area_subareas sa ON sa.id=c.sub_area_id
                LEFT JOIN providers p ON p.id=c.provider_id
                LEFT JOIN contract_statuses s ON s.id=c.status_id
                LEFT JOIN contract_types ct ON ct.id=c.contract_type_id
                $w ORDER BY c.id DESC LIMIT $limit OFFSET $offset";
        $st=Database::pdo()->prepare($sql); $st->execute($params);
        return $st->fetchAll();
    }

    public static function find(int $id): ?array {
        $sql = "SELECT c.*, a.name area_name, sa.name sub_area_name, p.name provider_name, s.name status_name,
                       sup.full_name supervisor_catalog_name, sup.document_number supervisor_catalog_document, sup.verification_digit supervisor_catalog_dv,
                       tc.name commitment_type_name, ms.name selection_modality_name, tg.name expense_topic_name,
                       up.name term_unit_name, te.name specific_typology_name, rc.name contracting_regime_name,
                       tp.name expense_type_name, op.name budget_origin_name, orr.name resource_origin_name,
                       tm.name currency_type_name, ct.name control_type_name, pr.name procedure_type_name,
                       ic.name contract_start_type_name, cty.name contract_type_name,
                       DATEDIFF(COALESCE(c.extension_end_date,c.end_date), CURDATE()) AS days_to_expire
                FROM contracts c
                LEFT JOIN areas a ON a.id=c.area_id
                LEFT JOIN area_subareas sa ON sa.id=c.sub_area_id
                LEFT JOIN providers p ON p.id=c.provider_id
                LEFT JOIN contract_statuses s ON s.id=c.status_id
                LEFT JOIN contract_supervisors sup ON sup.id=c.supervisor_id
                LEFT JOIN contract_tipo_compromiso tc ON tc.id=c.commitment_type_id
                LEFT JOIN contract_modalidad_seleccion ms ON ms.id=c.selection_modality_id
                LEFT JOIN contract_tema_gasto tg ON tg.id=c.expense_topic_id
                LEFT JOIN contract_unidad_plazo up ON up.id=c.term_unit_id
                LEFT JOIN contract_tipologia_especifica te ON te.id=c.specific_typology_id
                LEFT JOIN contract_regimen_contratacion rc ON rc.id=c.contracting_regime_id
                LEFT JOIN contract_tipo_gasto tp ON tp.id=c.expense_type_id
                LEFT JOIN contract_origen_presupuesto op ON op.id=c.budget_origin_id
                LEFT JOIN contract_origen_recursos orr ON orr.id=c.resource_origin_id
                LEFT JOIN contract_tipo_moneda tm ON tm.id=c.currency_type_id
                LEFT JOIN contract_tipo_control ct ON ct.id=c.control_type_id
                LEFT JOIN contract_procedimiento pr ON pr.id=c.procedure_type_id
                LEFT JOIN contract_inicio_contrato ic ON ic.id=c.contract_start_type_id
                LEFT JOIN contract_types cty ON cty.id=c.contract_type_id
                WHERE c.id=?";
        $st=Database::pdo()->prepare($sql); $st->execute([$id]); return $st->fetch() ?: null;
    }

    public static function create(array $d): int {
        $cols = self::columns();
        $sql = "INSERT INTO contracts (".implode(',', $cols).",updated_at) VALUES (:".implode(',:', $cols).",NOW())";
        $pdo = Database::pdo();
        $st = $pdo->prepare($sql);
        $st->execute(self::payload($d));
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $d): void {
        $cols = self::columns();
        $set = implode(',', array_map(fn($c)=>"$c=:$c", $cols));
        $sql = "UPDATE contracts SET $set, updated_at=NOW() WHERE id=:id";
        $payload = self::payload($d); $payload['id'] = $id;
        Database::pdo()->prepare($sql)->execute($payload);
    }

    private static function columns(): array {
        return ['contract_type_id','area_id','sub_area_id','provider_id','status_id','number','name','object','payment_clause','subscription_date','start_date','end_date','extension_end_date','term_months','initial_value','initial_vat','total_initial','additions_value','total_value','executed_value','execution_percent','auto_extension','supervisor_id','supervisor_name','supervisor_document','supervisor_verification_digit','commitment_type_id','selection_modality_id','expense_topic_id','term_unit_id','execution_term_value','specific_typology_id','contracting_regime_id','expense_type_id','budget_origin_id','resource_origin_id','currency_type_id','control_type_id','procedure_type_id','contract_start_type_id','contract_year','contract_month','agreed_payments_qty','agreed_other_payments_qty'];
    }

    private static function payload(array $d): array {
        $initial = self::money($d['initial_value'] ?? 0);
        $vat = self::money($d['initial_vat'] ?? 0);
        $totalInitial = self::money($d['total_initial'] ?? ($initial + $vat));
        $additions = self::money($d['additions_value'] ?? 0);
        $total = self::money($d['total_value'] ?? ($totalInitial + $additions));
        $executed = self::money($d['executed_value'] ?? 0);
        $percent = $total > 0 ? round(($executed / $total) * 100, 2) : self::money($d['execution_percent'] ?? 0);
        return [
            'contract_type_id' => self::nullableInt($d['contract_type_id'] ?? null),
            'area_id' => self::nullableInt($d['area_id'] ?? null),
            'sub_area_id' => self::nullableInt($d['sub_area_id'] ?? null),
            'provider_id' => self::nullableInt($d['provider_id'] ?? null),
            'status_id' => self::resolveStatusId($d),
            'number' => self::contractNumber($d),
            'name' => trim((string)($d['name'] ?? '')),
            'object' => trim((string)($d['object'] ?? '')),
            'payment_clause' => trim((string)($d['payment_clause'] ?? '')),
            'subscription_date' => self::dateOrNull($d['subscription_date'] ?? null),
            'start_date' => self::dateOrNull($d['start_date'] ?? null),
            'end_date' => self::dateOrNull($d['end_date'] ?? null),
            'extension_end_date' => self::dateOrNull($d['extension_end_date'] ?? null),
            'term_months' => 0,
            'initial_value' => $initial,
            'initial_vat' => $vat,
            'total_initial' => $totalInitial,
            'additions_value' => $additions,
            'total_value' => ($totalInitial + $additions),
            'executed_value' => 0,
            'execution_percent' => 0,
            'auto_extension' => trim((string)($d['auto_extension'] ?? 'NO')),
            'supervisor_id' => self::nullableInt($d['supervisor_id'] ?? null),
            'supervisor_name' => trim((string)($d['supervisor_name'] ?? '')),
            'supervisor_document' => trim((string)($d['supervisor_document'] ?? '')),
            'supervisor_verification_digit' => trim((string)($d['supervisor_verification_digit'] ?? '')),
            'commitment_type_id' => self::nullableInt($d['commitment_type_id'] ?? null),
            'selection_modality_id' => self::nullableInt($d['selection_modality_id'] ?? null),
            'expense_topic_id' => self::nullableInt($d['expense_topic_id'] ?? null),
            'term_unit_id' => self::nullableInt($d['term_unit_id'] ?? null),
            'execution_term_value' => (int)($d['execution_term_value'] ?? 0),
            'specific_typology_id' => self::nullableInt($d['specific_typology_id'] ?? null),
            'contracting_regime_id' => self::nullableInt($d['contracting_regime_id'] ?? null),
            'expense_type_id' => self::nullableInt($d['expense_type_id'] ?? null),
            'budget_origin_id' => self::nullableInt($d['budget_origin_id'] ?? null),
            'resource_origin_id' => self::nullableInt($d['resource_origin_id'] ?? null),
            'currency_type_id' => self::nullableInt($d['currency_type_id'] ?? null),
            'control_type_id' => self::nullableInt($d['control_type_id'] ?? null),
            'procedure_type_id' => self::nullableInt($d['procedure_type_id'] ?? null),
            'contract_start_type_id' => null,
            'contract_year' => (int)($d['contract_year'] ?? date('Y')),
            'contract_month' => trim((string)($d['contract_month'] ?? '')),
            'agreed_payments_qty' => (int)($d['agreed_payments_qty'] ?? 0),
            'agreed_other_payments_qty' => (int)($d['agreed_other_payments_qty'] ?? 0),
        ];
    }

    private static function contractNumber(array $d): string {
        $n=trim((string)($d['number'] ?? ''));
        if($n!=='') return $n;
        $year=(int)($d['contract_year'] ?? date('Y'));
        return 'AUTO-'.$year.'-'.date('YmdHis');
    }
    private static function resolveStatusId(array $d): ?int {
        $end=self::dateOrNull($d['extension_end_date'] ?? null) ?: self::dateOrNull($d['end_date'] ?? null);
        $code = ($end && $end < date('Y-m-d')) ? 'to_liquidate' : 'active';
        $st=Database::pdo()->prepare('SELECT id FROM contract_statuses WHERE code=? LIMIT 1'); $st->execute([$code]);
        $id=$st->fetchColumn();
        if($id) return (int)$id;
        return self::nullableInt($d['status_id'] ?? null);
    }
    private static function money($v): float { return (float)str_replace([',','$',' '], '', (string)$v); }
    private static function nullableInt($v): ?int { return ($v === '' || $v === null) ? null : (int)$v; }
    private static function dateOrNull($v): ?string { $v=trim((string)$v); return $v === '' ? null : $v; }

    public static function stats(): array {
        $pdo=Database::pdo();
        $statusCount = function(string $code) use ($pdo): int {
            $st = $pdo->prepare("SELECT COUNT(*) FROM contracts c JOIN contract_statuses s ON s.id=c.status_id WHERE s.code=?");
            $st->execute([$code]);
            return (int)$st->fetchColumn();
        };
        return [
            'total'=>(int)$pdo->query('SELECT COUNT(*) FROM contracts')->fetchColumn(),
            'active'=>$statusCount('active'),
            'liquidated'=>$statusCount('liquidated'),
            'cancelled'=>$statusCount('cancelled'),
            'expiring'=>(int)$pdo->query("SELECT COUNT(*) FROM contracts WHERE COALESCE(extension_end_date,end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn(),
            'providers'=>(int)$pdo->query('SELECT COUNT(*) FROM providers')->fetchColumn(),
            'value'=>(float)$pdo->query('SELECT COALESCE(SUM(total_value),0) FROM contracts')->fetchColumn(),
            'avg_execution'=>(float)$pdo->query('SELECT COALESCE(AVG(execution_percent),0) FROM contracts')->fetchColumn(),
        ];
    }
}
