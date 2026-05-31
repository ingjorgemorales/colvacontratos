<?php
namespace App\Models;

use App\Core\Database;
use PDO;

final class Payment {
    public static function byContract(int $contractId): array {
        $st = Database::pdo()->prepare("SELECT * FROM contract_payments WHERE contract_id=? ORDER BY invoice_date DESC, id DESC");
        $st->execute([$contractId]);
        return $st->fetchAll();
    }

    public static function summaryByContract(int $contractId): array {
        $st = Database::pdo()->prepare("SELECT COALESCE(SUM(recorded_value),0) recorded, COALESCE(SUM(vat),0) vat, COALESCE(SUM(invoice_total),0) invoiced, COALESCE(SUM(paid_value),0) paid, COALESCE(MAX(accumulated_executed_value),0) executed, COALESCE(MAX(physical_progress),0) progress, COUNT(*) total_invoices FROM contract_payments WHERE contract_id=?");
        $st->execute([$contractId]);
        return $st->fetch() ?: ['recorded'=>0,'vat'=>0,'invoiced'=>0,'paid'=>0,'executed'=>0,'progress'=>0,'total_invoices'=>0];
    }

    public static function overview(): array {
        $sql = "SELECT c.id,c.number,c.name,c.total_value,c.executed_value,c.execution_percent,p.name provider_name,a.name area_name,
                       COALESCE(SUM(cp.invoice_total),0) invoiced_total,
                       COALESCE(SUM(cp.paid_value),0) paid_total,
                       COUNT(cp.id) invoice_count
                FROM contracts c
                LEFT JOIN providers p ON p.id=c.provider_id
                LEFT JOIN areas a ON a.id=c.area_id
                LEFT JOIN contract_payments cp ON cp.contract_id=c.id
                GROUP BY c.id,c.number,c.name,c.total_value,c.executed_value,c.execution_percent,p.name,a.name
                ORDER BY c.id DESC";
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function create(int $contractId, array $d): void {
        $recorded = self::money($d['recorded_value'] ?? 0);
        $vat = self::money($d['vat'] ?? 0);
        $invoiceTotal = self::money($d['invoice_total'] ?? ($recorded + $vat));
        $paid = self::money($d['paid_value'] ?? 0);
        $accumulated = self::money($d['accumulated_executed_value'] ?? 0);
        $progress = self::money($d['physical_progress'] ?? 0);

        $sql = "INSERT INTO contract_payments (contract_id, invoice_date, invoice_number, recorded_value, vat, invoice_total, payment_order, paid_value, accumulated_executed_value, balance, physical_progress)
                VALUES (:contract_id,:invoice_date,:invoice_number,:recorded_value,:vat,:invoice_total,:payment_order,:paid_value,:accumulated_executed_value,:balance,:physical_progress)";
        $pdo = Database::pdo();
        $st = $pdo->prepare($sql);
        $st->execute([
            'contract_id'=>$contractId,
            'invoice_date'=>self::dateOrNull($d['invoice_date'] ?? null),
            'invoice_number'=>trim((string)($d['invoice_number'] ?? '')),
            'recorded_value'=>$recorded,
            'vat'=>$vat,
            'invoice_total'=>$invoiceTotal,
            'payment_order'=>trim((string)($d['payment_order'] ?? '')),
            'paid_value'=>$paid,
            'accumulated_executed_value'=>$accumulated,
            'balance'=>0,
            'physical_progress'=>$progress,
        ]);
        self::recalculateContract($contractId);
    }

    public static function delete(int $id): void {
        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT contract_id FROM contract_payments WHERE id=?");
        $st->execute([$id]);
        $contractId = (int)$st->fetchColumn();
        $pdo->prepare("DELETE FROM contract_payments WHERE id=?")->execute([$id]);
        if ($contractId > 0) self::recalculateContract($contractId);
    }

    public static function recalculateContract(int $contractId): void {
        $pdo = Database::pdo();
        $st = $pdo->prepare("SELECT total_value FROM contracts WHERE id=?");
        $st->execute([$contractId]);
        $totalValue = (float)$st->fetchColumn();

        $summary = self::summaryByContract($contractId);
        $executed = (float)$summary['executed'];
        if ($executed <= 0) $executed = (float)$summary['paid'];
        if ($executed <= 0) $executed = (float)$summary['invoiced'];
        $percent = $totalValue > 0 ? round(($executed / $totalValue) * 100, 2) : 0;

        $up = $pdo->prepare("UPDATE contracts SET executed_value=?, execution_percent=?, updated_at=NOW() WHERE id=?");
        $up->execute([$executed, $percent, $contractId]);
    }

    private static function money($v): float { return (float)str_replace([',','$',' '], '', (string)$v); }
    private static function dateOrNull($v): ?string { $v=trim((string)$v); return $v === '' ? null : $v; }
}
