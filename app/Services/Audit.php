<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;

/**
 * Bitácora de acciones de usuario (tabla audit_logs).
 *
 * Registra QUÉ hizo QUIÉN y CUÁNDO. Se llama desde los controladores en cada
 * acción relevante (login, crear/editar, subir archivo, analizar, cambiar
 * permisos, etc.). Nunca lanza excepción: si falla el registro, la acción del
 * usuario continúa igual.
 *
 *   Audit::log('Contratos', 'Creó contrato', 'N° SGC-005', $contratoId);
 */
final class Audit
{
    /** Módulos legibles (para mostrar una etiqueta uniforme). */
    public static function log(string $module, string $action, string $detail = '', ?int $recordId = null): void
    {
        try {
            $u = Auth::user() ?: [];
            Database::pdo()->prepare(
                "INSERT INTO audit_logs (user_id, module, action, record_id, detail, ip, created_at)
                 VALUES (?,?,?,?,?,?,NOW())"
            )->execute([
                $u['id'] ?? null,
                mb_substr($module, 0, 64),
                mb_substr($action, 0, 120),
                $recordId,
                mb_substr($detail, 0, 500),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('Audit: ' . $e->getMessage());
        }
    }
}
