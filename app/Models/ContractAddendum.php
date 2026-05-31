<?php
namespace App\Models;
use App\Core\Database;
final class ContractAddendum {
    private static function db(){ return Database::pdo(); }
    public static function byContract(int $contractId): array {
        $st=self::db()->prepare("SELECT *, COALESCE(description, observations) AS description_resolved FROM contract_addendums WHERE contract_id=? ORDER BY id DESC");
        $st->execute([$contractId]); return $st->fetchAll();
    }
    public static function create(array $d): void {
        $desc = $d['description'] ?? $d['observations'] ?? '';
        $st=self::db()->prepare("INSERT INTO contract_addendums(contract_id,start_date,end_date,value,observations,description,created_at) VALUES(?,?,?,?,?,?,NOW())");
        $st->execute([$d['contract_id'],$d['start_date'] ?: null,$d['end_date'] ?: null,str_replace(',','',(string)$d['value']),$desc,$desc]);
    }
}
