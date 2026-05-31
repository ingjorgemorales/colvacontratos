<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class PolicyReview {
    public static function all(array $filters = []): array {
        $pdo = Database::pdo();
        $where=[]; $params=[];
        if (!empty($filters['q'])) { $where[]='(pr.contract_number LIKE ? OR pr.contractor_name LIKE ? OR pr.policy_number LIKE ?)'; $q='%'.$filters['q'].'%'; array_push($params,$q,$q,$q); }
        if (!empty($filters['status'])) { $where[]='pr.status = ?'; $params[]=$filters['status']; }
        $sql='SELECT pr.* FROM policy_reviews pr';
        if ($where) $sql.=' WHERE '.implode(' AND ',$where);
        $sql.=' ORDER BY pr.id DESC LIMIT 500';
        $st=$pdo->prepare($sql); $st->execute($params); return $st->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo=Database::pdo();
        $st=$pdo->prepare('SELECT * FROM policy_reviews WHERE id=?'); $st->execute([$id]);
        $row=$st->fetch(); return $row ?: null;
    }

    public static function files(int $id): array {
        $pdo=Database::pdo();
        $st=$pdo->prepare('SELECT * FROM policy_review_files WHERE review_id=? ORDER BY id ASC'); $st->execute([$id]);
        return $st->fetchAll();
    }

    public static function create(array $data, array $files): int {
        $pdo=Database::pdo();
        $pdo->beginTransaction();
        try {
            $st=$pdo->prepare('INSERT INTO policy_reviews (contract_id, contract_number, contractor_name, contractor_document, policy_number, insurance_company, policy_type, insured_value, start_date, end_date, status, observations, checklist_json, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                $data['contract_id'] ?: null,
                $data['contract_number'] ?? '',
                $data['contractor_name'] ?? '',
                $data['contractor_document'] ?? '',
                $data['policy_number'] ?? '',
                $data['insurance_company'] ?? '',
                $data['policy_type'] ?? '',
                (float)($data['insured_value'] ?? 0),
                $data['start_date'] ?: null,
                $data['end_date'] ?: null,
                $data['status'] ?? 'PENDIENTE',
                $data['observations'] ?? '',
                json_encode($data['checklist'] ?? [], JSON_UNESCAPED_UNICODE),
                $data['created_by'] ?? '',
            ]);
            $id=(int)$pdo->lastInsertId();
            foreach ($files as $f) {
                $stf=$pdo->prepare('INSERT INTO policy_review_files (review_id, file_type, original_name, stored_path, mime_type, size_bytes) VALUES (?,?,?,?,?,?)');
                $stf->execute([$id,$f['file_type'],$f['original_name'],$f['stored_path'],$f['mime_type'],$f['size_bytes']]);
            }
            $pdo->commit(); return $id;
        } catch (\Throwable $e) { $pdo->rollBack(); throw $e; }
    }

    public static function updateStatus(int $id, string $status, string $observations): void {
        $pdo=Database::pdo();
        $st=$pdo->prepare('UPDATE policy_reviews SET status=?, observations=?, reviewed_at=NOW(), updated_at=NOW() WHERE id=?');
        $st->execute([$status,$observations,$id]);
    }

    public static function kpis(): array {
        $pdo=Database::pdo();
        $rows=$pdo->query('SELECT status, COUNT(*) total FROM policy_reviews GROUP BY status')->fetchAll();
        $out=['TOTAL'=>0,'PENDIENTE'=>0,'APROBADA'=>0,'OBSERVADA'=>0,'RECHAZADA'=>0];
        foreach($rows as $r){ $out[$r['status']] = (int)$r['total']; $out['TOTAL'] += (int)$r['total']; }
        return $out;
    }
}
