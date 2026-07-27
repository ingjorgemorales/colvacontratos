<?php
namespace App\Models;

use App\Core\Database;

/**
 * Bitácora de auditoría: movimientos de los usuarios en el sistema.
 *
 * Unifica dos fuentes: `audit_logs` (acciones registradas por el servicio Audit,
 * con user_id → nombre) y `change_logs` (cambios históricos, con user_name).
 * Ofrece consulta filtrada y paginada, más los catálogos para los filtros.
 */
final class AuditLog
{
    /** Subconsulta unificada de ambas fuentes con el nombre del usuario resuelto. */
    private static function baseSql(): string
    {
        $partes = [];
        if (self::tableExists('audit_logs')) {
            $partes[] = "SELECT a.created_at, a.user_id,
                                COALESCE(u.name,'Sistema') AS user_name,
                                a.module, a.action, a.detail, a.ip
                         FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id";
        }
        if (self::tableExists('change_logs')) {
            $partes[] = "SELECT c.created_at, c.user_id,
                                COALESCE(NULLIF(c.user_name,''),'Sistema') AS user_name,
                                c.entity_type AS module, c.action, c.summary AS detail, c.ip
                         FROM change_logs c";
        }
        if ($partes === []) {
            return '';
        }
        return '(' . implode(' UNION ALL ', $partes) . ') AS t';
    }

    /** Arma WHERE + params a partir de los filtros (usuario, módulo, texto). */
    private static function where(array $f): array
    {
        $w = [];
        $p = [];
        if (!empty($f['user_id'])) { $w[] = 't.user_id = ?'; $p[] = (int) $f['user_id']; }
        if (!empty($f['module']))  { $w[] = 't.module = ?';  $p[] = (string) $f['module']; }
        if (!empty($f['q'])) {
            $w[] = '(t.action LIKE ? OR t.detail LIKE ? OR t.user_name LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($p, $like, $like, $like);
        }
        $sql = $w ? (' WHERE ' . implode(' AND ', $w)) : '';
        return [$sql, $p];
    }

    public static function countFiltered(array $filters): int
    {
        $base = self::baseSql();
        if ($base === '') return 0;
        [$where, $params] = self::where($filters);
        try {
            $st = Database::pdo()->prepare("SELECT COUNT(*) FROM {$base}{$where}");
            $st->execute($params);
            return (int) $st->fetchColumn();
        } catch (\Throwable $e) { return 0; }
    }

    public static function filtered(array $filters, int $offset, int $limit): array
    {
        $base = self::baseSql();
        if ($base === '') return [];
        [$where, $params] = self::where($filters);
        $offset = max(0, $offset);
        $limit  = max(1, min(200, $limit));
        try {
            $st = Database::pdo()->prepare(
                "SELECT * FROM {$base}{$where} ORDER BY t.created_at DESC LIMIT {$offset}, {$limit}"
            );
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    /** Usuarios que aparecen en la bitácora (para el filtro). */
    public static function usuarios(): array
    {
        try {
            return Database::pdo()->query(
                "SELECT u.id, u.name FROM users u
                 WHERE u.id IN (SELECT user_id FROM audit_logs WHERE user_id IS NOT NULL
                                UNION SELECT user_id FROM change_logs WHERE user_id IS NOT NULL)
                 ORDER BY u.name"
            )->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    /** Módulos distintos presentes en la bitácora (para el filtro). */
    public static function modulos(): array
    {
        $base = self::baseSql();
        if ($base === '') return [];
        try {
            return array_column(
                Database::pdo()->query("SELECT DISTINCT t.module FROM {$base} WHERE t.module<>'' ORDER BY t.module")->fetchAll(),
                'module'
            );
        } catch (\Throwable $e) { return []; }
    }

    private static function tableExists(string $table): bool
    {
        try {
            $st = Database::pdo()->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?"
            );
            $st->execute([$table]);
            return (int) $st->fetchColumn() > 0;
        } catch (\Throwable $e) { return false; }
    }
}
