<?php
namespace App\Models;
use App\Core\Database;

final class Config {
    public static function statuses(): array { return Database::pdo()->query("SELECT * FROM contract_statuses ORDER BY id")->fetchAll(); }
    public static function addStatus(array $d): void {
        $name=trim($d['name']??''); if($name==='') return;
        $code=strtolower(preg_replace('/[^a-z0-9]+/i','_', $name));
        $st=Database::pdo()->prepare("INSERT INTO contract_statuses(code,name) VALUES(?,?)");
        $st->execute([$code,$name]);
    }
}
