<?php
namespace App\Services;
use App\Core\Database;
use App\Core\Auth;

final class ChangeLogger {
    public static function log(string $entityType, ?int $entityId, string $action, string $summary='', ?array $before=null, ?array $after=null): void {
        try {
            $u = Auth::user() ?: [];
            Database::pdo()->prepare('INSERT INTO change_logs (entity_type,entity_id,action,summary,before_data,after_data,user_id,user_name,ip) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$entityType,$entityId,$action,$summary,$before?json_encode($before,JSON_UNESCAPED_UNICODE):null,$after?json_encode($after,JSON_UNESCAPED_UNICODE):null,$u['id']??null,$u['name']??null,$_SERVER['REMOTE_ADDR']??null]);
        } catch (\Throwable $e) { error_log('ChangeLogger: '.$e->getMessage()); }
    }
}
