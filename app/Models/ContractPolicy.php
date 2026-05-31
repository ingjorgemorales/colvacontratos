<?php
namespace App\Models;
use App\Core\Database;
final class ContractPolicy {
    private static function db(){ return Database::pdo(); }
    public static function byContract(int $contractId): array {
        $st=self::db()->prepare("SELECT * FROM contract_policies WHERE contract_id=? ORDER BY id DESC");
        $st->execute([$contractId]); return $st->fetchAll();
    }
    public static function create(array $d): void {
        $st=self::db()->prepare("INSERT INTO contract_policies(contract_id,policy_type,policy_number,provider,start_date,end_date,insured_value,created_at) VALUES(?,?,?,?,?,?,?,NOW())");
        $st->execute([$d['contract_id'],$d['policy_type'],$d['policy_number'],$d['provider'],$d['start_date'],$d['end_date'],str_replace(',','',$d['insured_value'])]);
    }
}
