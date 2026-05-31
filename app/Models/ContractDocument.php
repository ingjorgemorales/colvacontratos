<?php
namespace App\Models;

use App\Core\Database;

final class ContractDocument {
    private static function db(){ return Database::pdo(); }

    public static function byContract(int $contractId): array {
        $st=self::db()->prepare("SELECT *, COALESCE(file_path, stored_path) AS path_resolved FROM contract_documents WHERE contract_id=? ORDER BY id DESC");
        $st->execute([$contractId]);
        return $st->fetchAll();
    }

    public static function find(int $id): ?array {
        $st=self::db()->prepare("SELECT *, COALESCE(file_path, stored_path) AS path_resolved FROM contract_documents WHERE id=?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function create(int $contractId,string $type,string $original,string $path,string $notes=''): void {
        $sql = "INSERT INTO contract_documents(contract_id,type,document_type,original_name,stored_path,file_path,notes,created_at)
                VALUES(?,?,?,?,?,?,?,NOW())";
        $st=self::db()->prepare($sql);
        $st->execute([$contractId,$type,$type,$original,$path,$path,$notes]);
    }

    public static function delete(int $id): void {
        $st=self::db()->prepare("DELETE FROM contract_documents WHERE id=?");
        $st->execute([$id]);
    }
}
