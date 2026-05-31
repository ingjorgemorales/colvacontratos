<?php
namespace App\Services;

use App\Core\Database;

final class Mailer
{
    public static function setting(string $key, string $default = ''): string
    {
        try {
            $st = Database::pdo()->prepare('SELECT setting_value FROM app_settings WHERE setting_key=? LIMIT 1');
            $st->execute([$key]);
            $v = $st->fetchColumn();
            return $v === false ? $default : (string)$v;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function send(string $to, string $subject, string $body): bool
    {
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $cfg = self::config();
        $driver = strtolower((string)($cfg['driver'] ?? 'mail'));

        if ($driver === 'log') {
            self::logMessage($to, $subject, $body, 'log-driver');
            return true;
        }

        if ($driver === 'smtp') {
            return self::sendSmtp($cfg, $to, $subject, $body);
        }

        $from = (string)($cfg['from_address'] ?? self::setting('alerts_from_email', self::setting('alert_email_from', 'contratos@colvatel.com')));
        $fromName = (string)($cfg['from_name'] ?? 'ColvaContratos');
        $headers = "From: {$fromName} <{$from}>\r\nReply-To: {$from}\r\nContent-Type: text/plain; charset=UTF-8";
        return @mail($to, $subject, $body, $headers);
    }

    private static function config(): array
    {
        $legacyFile = __DIR__ . '/../../config/legacy_mail.php';
        if (is_file($legacyFile)) {
            $legacyConfig = require $legacyFile;
            if (is_array($legacyConfig)) {
                return self::normalizeConfig($legacyConfig);
            }
        }

        $laravelFile = __DIR__ . '/../../config/mail.php';
        if (is_file($laravelFile)) {
            $laravelConfig = require $laravelFile;
            if (is_array($laravelConfig)) {
                return self::normalizeConfig($laravelConfig);
            }
        }

        return self::normalizeConfig([]);
    }

    private static function normalizeConfig(array $cfg): array
    {
        $defaultMailer = strtolower((string)($cfg['default'] ?? ($cfg['driver'] ?? 'mail')));
        $mailerConfig = (array)($cfg['mailers'][$defaultMailer] ?? []);
        $fromConfig = (array)($cfg['from'] ?? []);

        $transport = strtolower((string)($mailerConfig['transport'] ?? ''));
        $driver = $defaultMailer;
        if ($transport !== '') {
            $driver = $transport;
        }

        if (!in_array($driver, ['smtp', 'log', 'mail'], true)) {
            $driver = 'mail';
        }

        return [
            'driver' => $driver,
            'host' => (string)($cfg['host'] ?? $mailerConfig['host'] ?? ''),
            'port' => (int)($cfg['port'] ?? $mailerConfig['port'] ?? 587),
            'encryption' => (string)($cfg['encryption'] ?? $mailerConfig['encryption'] ?? 'tls'),
            'username' => (string)($cfg['username'] ?? $mailerConfig['username'] ?? ''),
            'password' => (string)($cfg['password'] ?? $mailerConfig['password'] ?? ''),
            'from_address' => (string)($cfg['from_address'] ?? $fromConfig['address'] ?? 'contratos@colvatel.com'),
            'from_name' => (string)($cfg['from_name'] ?? $fromConfig['name'] ?? 'ColvaContratos'),
            'timeout' => (int)($cfg['timeout'] ?? $mailerConfig['timeout'] ?? 20),
        ];
    }

    private static function sendSmtp(array $cfg, string $to, string $subject, string $body): bool
    {
        $host = (string)($cfg['host'] ?? '');
        $port = (int)($cfg['port'] ?? 587);
        $encryption = strtolower((string)($cfg['encryption'] ?? 'tls'));
        $username = (string)($cfg['username'] ?? '');
        $password = (string)($cfg['password'] ?? '');
        $from = (string)($cfg['from_address'] ?? $username);
        $fromName = (string)($cfg['from_name'] ?? 'ColvaContratos');
        $timeout = (int)($cfg['timeout'] ?? 20);

        if ($host === '' || $from === '') {
            self::logMessage($to, $subject, $body, 'smtp-missing-config');
            return false;
        }

        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($remote, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            self::logMessage($to, $subject, $body, "smtp-connect-error {$errno}: {$errstr}");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            self::expect($socket, [220]);
            self::command($socket, 'EHLO localhost', [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo activar TLS');
                }
                self::command($socket, 'EHLO localhost', [250]);
            }

            if ($username !== '' || $password !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($username), [334]);
                self::command($socket, base64_encode($password), [235]);
            }

            self::command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $headers = [
                'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>',
                'To: <' . $to . '>',
                'Subject: ' . self::encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'Date: ' . date('r'),
            ];
            $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace(["\r\n", "\r"], "\n", $body);
            $message = str_replace("\n.", "\n..", $message);
            fwrite($socket, str_replace("\n", "\r\n", $message) . "\r\n.\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            @fwrite($socket, "QUIT\r\n");
            @fclose($socket);
            self::logMessage($to, $subject, $body, 'smtp-error: ' . $e->getMessage());
            return false;
        }
    }

    private static function command($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $expectedCodes);
    }

    private static function expect($socket, array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException(trim($response) ?: 'Respuesta SMTP vacia');
        }
        return $response;
    }

    private static function encodeHeader(string $text): string
    {
        return preg_match('/[^\x20-\x7E]/', $text) ? '=?UTF-8?B?' . base64_encode($text) . '?=' : $text;
    }

    private static function logMessage(string $to, string $subject, string $body, string $status): void
    {
        $dir = self::logDirectory();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf("[%s] status=%s to=%s subject=%s\n%s\n---\n", date('c'), $status, $to, $subject, $body);
        @file_put_contents($dir . '/mail.log', $line, FILE_APPEND);
    }

    private static function logDirectory(): string
    {
        if (function_exists('storage_path')) {
            return storage_path('logs');
        }

        return dirname(__DIR__, 2) . '/storage/logs';
    }
}
