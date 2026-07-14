<?php
namespace App\Services;

final class Mailer
{
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
            $result = self::sendSmtp($cfg, $to, $subject, $body);
            if ($result) return true;
        }

        return self::sendMailFunction($cfg, $to, $subject, $body);
    }

    private static function sendMailFunction(array $cfg, string $to, string $subject, string $body): bool
    {
        $from = (string)($cfg['from_address'] ?? 'contratos@colvatel.com');
        $fromName = (string)($cfg['from_name'] ?? 'ColvaContratos');
        $message = self::buildMimeMessage($subject, $body);
        $headers = [
            'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $message['boundary'] . '"',
        ];

        return @mail($to, self::encodeHeader($subject), $message['body'], implode("\r\n", $headers));
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

            $message = self::buildMimeMessage($subject, $body);
            $headers = [
                'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>',
                'To: <' . $to . '>',
                'Subject: ' . self::encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $message['boundary'] . '"',
                'Date: ' . date('r'),
            ];
            $rawMessage = implode("\r\n", $headers) . "\r\n\r\n" . $message['body'];
            $rawMessage = str_replace("\n.", "\n..", str_replace(["\r\n", "\r"], "\n", $rawMessage));
            fwrite($socket, str_replace("\n", "\r\n", $rawMessage) . "\r\n.\r\n");
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

    private static function config(): array
    {
        $env = self::loadEnv();
        return [
            'driver' => $env['MAIL_MAILER'] ?? 'smtp',
            'host' => $env['MAIL_HOST'] ?? '',
            'port' => (int)($env['MAIL_PORT'] ?? 587),
            'encryption' => $env['MAIL_ENCRYPTION'] ?? 'tls',
            'username' => $env['MAIL_USERNAME'] ?? '',
            'password' => $env['MAIL_PASSWORD'] ?? '',
            'from_address' => $env['MAIL_FROM_ADDRESS'] ?? 'contratos@colvatel.com',
            'from_name' => $env['MAIL_FROM_NAME'] ?? 'ColvaContratos',
            'timeout' => 20,
        ];
    }

    private static function loadEnv(): array
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (!is_file($envFile)) return [];
        $result = [];
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            $pos = strpos($line, '=');
            if ($pos === false) continue;
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            if (strlen($val) >= 2 && (($val[0] === '"' && $val[strlen($val) - 1] === '"') || ($val[0] === "'" && $val[strlen($val) - 1] === "'"))) {
                $val = substr($val, 1, -1);
            }
            $result[$key] = $val;
        }
        return $result;
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

    private static function buildMimeMessage(string $subject, string $body): array
    {
        $boundary = 'colvacontratos_alt_' . bin2hex(random_bytes(12));
        $relatedBoundary = 'colvacontratos_related_' . bin2hex(random_bytes(12));
        $logo = self::inlineLogo();
        $text = self::normalizeBody($body);
        $html = self::renderHtmlTemplate($subject, $text, $logo['src']);

        $mimeBody = [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $text,
            '',
            '--' . $boundary,
            'Content-Type: multipart/related; boundary="' . $relatedBoundary . '"',
            '',
            '--' . $relatedBoundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $html,
            '',
        ];

        if ($logo['content'] !== '') {
            $mimeBody = array_merge($mimeBody, [
                '--' . $relatedBoundary,
                'Content-Type: image/png; name="logo-login.png"',
                'Content-Transfer-Encoding: base64',
                'Content-ID: <' . $logo['cid'] . '>',
                'Content-Disposition: inline; filename="logo-login.png"',
                '',
                chunk_split($logo['content'], 76, "\r\n"),
                '',
            ]);
        }

        $mimeBody = array_merge($mimeBody, [
            '--' . $relatedBoundary . '--',
            '',
            '--' . $boundary . '--',
            '',
        ]);

        return [
            'boundary' => $boundary,
            'body' => implode("\r\n", $mimeBody),
        ];
    }

    private static function renderHtmlTemplate(string $subject, string $body, string $logoSrc = ''): string
    {
        $escapedSubject = self::escape($subject);
        $preheader = self::escape(mb_substr(preg_replace('/\s+/', ' ', $body) ?: $subject, 0, 150));
        $content = self::bodyToHtml($body);
        $logo = $logoSrc !== ''
            ? '<img src="' . self::escape($logoSrc) . '" width="220" alt="ColvaContratos" style="display:block;width:220px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;">'
            : '<strong style="color:#0b4168;font-size:22px;line-height:28px;">ColvaContratos</strong>';
        $buttonUrl = self::firstUrl($body);
        $button = '';

        if ($buttonUrl !== '') {
            $buttonText = self::buttonText($subject, $body);
            $safeUrl = self::escape($buttonUrl);
            $button = '<tr><td style="padding:8px 34px 30px;text-align:center;">'
                . '<a href="' . $safeUrl . '" style="display:inline-block;background:#0b4168;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;line-height:20px;padding:13px 22px;border-radius:8px;">'
                . self::escape($buttonText)
                . '</a></td></tr>';
        }

        return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>' . $escapedSubject . '</title></head>'
            . '<body style="margin:0;padding:0;background:#eef4f8;font-family:Arial,Helvetica,sans-serif;color:#102a43;">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . $preheader . '</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef4f8;margin:0;padding:28px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #dbe7f0;border-radius:12px;overflow:hidden;box-shadow:0 18px 42px rgba(9,34,61,.12);">'
            . '<tr><td style="background:#ffffff;padding:24px 34px 20px;border-bottom:1px solid #e3edf4;">' . $logo . '</td></tr>'
            . '<tr><td style="background:#0b4168;padding:24px 34px;">'
            . '<div style="font-size:12px;line-height:16px;letter-spacing:.12em;text-transform:uppercase;color:#a9d7f0;font-weight:700;">Notificacion del sistema</div>'
            . '<h1 style="margin:8px 0 0;color:#ffffff;font-size:24px;line-height:31px;font-weight:800;">' . $escapedSubject . '</h1>'
            . '</td></tr>'
            . '<tr><td style="padding:30px 34px 18px;font-size:15px;line-height:23px;color:#263f57;">' . $content . '</td></tr>'
            . $button
            . '<tr><td style="background:#f7fafc;border-top:1px solid #e3edf4;padding:18px 34px;color:#60758b;font-size:12px;line-height:18px;">'
            . '<strong style="color:#0b4168;">ColvaContratos</strong><br>Gestion contractual y alertas operativas.<br>'
            . 'Este mensaje fue generado automaticamente; si tienes dudas, contacta al administrador del sistema.'
            . '</td></tr>'
            . '</table>'
            . '</td></tr>'
            . '</table>'
            . '</body></html>';
    }

    private static function bodyToHtml(string $body): string
    {
        $groups = preg_split("/\n{2,}/", trim($body)) ?: [];
        $html = [];

        foreach ($groups as $group) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $group)), static fn($line) => $line !== ''));
            if (!$lines) continue;

            if (self::isDetailBlock($lines)) {
                $rows = '';
                foreach ($lines as $line) {
                    [$label, $value] = array_map('trim', explode(':', $line, 2));
                    $rows .= '<tr>'
                        . '<td style="padding:9px 12px;border-bottom:1px solid #e7eef5;color:#60758b;font-size:13px;width:38%;font-weight:700;">' . self::escape($label) . '</td>'
                        . '<td style="padding:9px 12px;border-bottom:1px solid #e7eef5;color:#102a43;font-size:14px;">' . self::linkify(self::escape($value)) . '</td>'
                        . '</tr>';
                }
                $html[] = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e1ebf3;border-radius:8px;border-collapse:separate;border-spacing:0;margin:0 0 18px;background:#fbfdff;">' . $rows . '</table>';
                continue;
            }

            $paragraph = implode('<br>', array_map(static fn($line) => self::linkify(self::escape($line)), $lines));
            $html[] = '<p style="margin:0 0 16px;">' . $paragraph . '</p>';
        }

        return implode('', $html);
    }

    private static function isDetailBlock(array $lines): bool
    {
        if (count($lines) < 2) return false;
        foreach ($lines as $line) {
            if (!preg_match('/^[^:]{2,45}:\s*.+$/', $line)) {
                return false;
            }
        }
        return true;
    }

    private static function firstUrl(string $body): string
    {
        return preg_match('/https?:\/\/[^\s<>"\']+/i', $body, $match) ? rtrim($match[0], '.,);') : '';
    }

    private static function inlineLogo(): array
    {
        $path = dirname(__DIR__, 2) . '/public/assets/img/logo-login.png';
        $cid = 'colvacontratos-logo';

        if (!is_file($path) || !is_readable($path)) {
            return ['cid' => $cid, 'src' => '', 'content' => ''];
        }

        $content = file_get_contents($path);
        if ($content === false || $content === '') {
            return ['cid' => $cid, 'src' => '', 'content' => ''];
        }

        return [
            'cid' => $cid,
            'src' => 'cid:' . $cid,
            'content' => base64_encode($content),
        ];
    }

    private static function assetUrl(string $assetPath): string
    {
        $baseUrl = self::currentBaseUrl();
        if ($baseUrl === '') {
            $env = self::loadEnv();
            $baseUrl = trim((string)($env['APP_URL'] ?? ''));
        }

        if ($baseUrl === '') {
            return '';
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($assetPath, '/');
    }

    private static function currentBaseUrl(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') {
            return '';
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $base = rtrim(dirname($script), '/');

        return $scheme . '://' . $host . ($base === '' ? '' : $base);
    }

    private static function buttonText(string $subject, string $body): string
    {
        $text = strtolower($subject . ' ' . $body);
        if (str_contains($text, 'restablecer')) return 'Restablecer contrasena';
        if (str_contains($text, 'ingresar') || str_contains($text, 'login')) return 'Ingresar a ColvaContratos';
        if (str_contains($text, 'contrato') || str_contains($text, 'alerta')) return 'Ver detalle';
        return 'Abrir enlace';
    }

    private static function normalizeBody(string $body): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $body));
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function linkify(string $escapedText): string
    {
        return preg_replace_callback('/https?:\/\/[^\s<]+/i', static function (array $match): string {
            $url = rtrim($match[0], '.,);');
            $tail = substr($match[0], strlen($url));
            return '<a href="' . $url . '" style="color:#0b5d9d;font-weight:700;">' . $url . '</a>' . $tail;
        }, $escapedText) ?? $escapedText;
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
