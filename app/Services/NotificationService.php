<?php
namespace App\Services;

use App\Core\Database;

final class NotificationService {
    public static function notify(string $eventType, string $subject, string $message): void {
        try {
            $st = Database::pdo()->prepare("SELECT email FROM notification_recipients WHERE active=1 AND (event_type='all' OR event_type=?)");
            $st->execute([$eventType]);
            $to = array_column($st->fetchAll(), 'email');
            if (!$to) { return; }
            foreach (array_unique($to) as $email) {
                Mailer::send($email, $subject, $message);
            }
        } catch (\Throwable $e) {
            error_log('NotificationService: ' . $e->getMessage());
        }
    }
}