<?php
namespace App\Services;

/**
 * Config y utilidades para hablar con el motor Flask del Agente de Pólizas.
 *
 * La lógica de reenvío (proxy) vive en AgentePolizasController::proxy(), que
 * necesita acceso directo a $_FILES/$_POST/php://input y a la respuesta. Aquí
 * solo quedan los datos de conexión (URL, clave, timeout).
 */
final class AgentClient
{
    public static function baseUrl(): string
    {
        return rtrim((string) env('AGENTE_URL', 'http://127.0.0.1:5000'), '/');
    }

    public static function key(): string
    {
        return (string) env('AGENTE_KEY', '');
    }

    public static function timeout(): int
    {
        return (int) env('AGENTE_TIMEOUT', 200);
    }

    /** True si el motor responde (para avisar en la UI si está apagado). */
    public static function isUp(): bool
    {
        $ch = curl_init(self::baseUrl() . '/');
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 3,
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $status > 0;
    }
}
