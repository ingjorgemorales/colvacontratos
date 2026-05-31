<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class DocumentFlow {
    private static function db(): PDO { return Database::pdo(); }

    public static function document(int $id): ?array {
        try {
            $st = self::db()->prepare("SELECT *, COALESCE(file_path, stored_path) path_resolved FROM contract_documents WHERE id=?");
            $st->execute([$id]);
            return $st->fetch() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function versions(int $documentId): array {
        try {
            $st = self::db()->prepare("SELECT * FROM contract_document_versions WHERE document_id=? ORDER BY version_number DESC,id DESC");
            $st->execute([$documentId]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function fields(int $contractId, int $documentId = 0): array {
        try {
            $sql = "SELECT * FROM contract_signature_fields WHERE contract_id=?" . ($documentId > 0 ? " AND (document_id=? OR document_id IS NULL)" : "") . " ORDER BY page_number,id";
            $st = self::db()->prepare($sql);
            $documentId > 0 ? $st->execute([$contractId, $documentId]) : $st->execute([$contractId]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function addField(array $d): void {
        $st = self::db()->prepare("INSERT INTO contract_signature_fields(contract_id,document_id,signer_name,signer_email,role_name,field_type,page_number,x_pos,y_pos,width_pos,height_pos,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,'pendiente',NOW())");
        $st->execute([
            (int)$d['contract_id'],
            !empty($d['document_id']) ? (int)$d['document_id'] : null,
            trim($d['signer_name']),
            trim($d['signer_email'] ?? ''),
            trim($d['role_name'] ?? ''),
            $d['field_type'] ?: 'firma',
            max(1, (int)$d['page_number']),
            (float)$d['x_pos'],
            (float)$d['y_pos'],
            (float)$d['width_pos'],
            (float)$d['height_pos'],
        ]);
    }

    public static function markSigned(int $id): void {
        try {
            $st = self::db()->prepare("UPDATE contract_signature_fields SET status='firmado', signed_at=NOW() WHERE id=?");
            $st->execute([$id]);
        } catch (\Throwable $e) {
        }
    }

    public static function checklist(int $contractId): array {
        self::ensureChecklist($contractId);
        try {
            $st = self::db()->prepare("SELECT * FROM contract_document_checklist WHERE contract_id=? ORDER BY id");
            $st->execute([$contractId]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function ensureChecklist(int $contractId): void {
        foreach (['Contrato firmado', 'Camara de Comercio/RUT', 'Poliza o garantia', 'Certificacion bancaria', 'Soporte autorizacion supervisor'] as $doc) {
            try {
                $st = self::db()->prepare("INSERT INTO contract_document_checklist(contract_id,required_document,status,created_at) SELECT ?,?,'pendiente',NOW() WHERE NOT EXISTS(SELECT 1 FROM contract_document_checklist WHERE contract_id=? AND required_document=?)");
                $st->execute([$contractId, $doc, $contractId, $doc]);
            } catch (\Throwable $e) {
            }
        }
    }

    public static function updateChecklist(int $id, string $status, string $notes = ''): void {
        try {
            $st = self::db()->prepare("UPDATE contract_document_checklist SET status=?, notes=? WHERE id=?");
            $st->execute([$status, $notes, $id]);
        } catch (\Throwable $e) {
        }
    }
}
