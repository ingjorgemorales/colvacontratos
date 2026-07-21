<?php
namespace App\Models;

use App\Core\Database;

/**
 * Lectura de las tablas del Agente de Pólizas (unificadas en colvacontratos:
 * documentos, manual_contratacion, motores_ia, proveedores_ia, api_keys).
 *
 * Solo lee columnas NO cifradas para pintar las páginas (histórico, manual,
 * config). Todo lo que requiere descifrado, IA o PDF lo hace el motor Flask
 * a través del proxy.
 */
final class AgentePoliza
{
    /** Motores de IA activos (botones del selector). */
    public static function motores(bool $soloActivos = true): array
    {
        $pdo = Database::pdo();
        $where = $soloActivos ? 'WHERE activo=1' : '';
        return $pdo->query(
            "SELECT id, clave, etiqueta, chip, descripcion, proveedor, model_id, max_tokens, activo
             FROM motores_ia $where ORDER BY id"
        )->fetchAll();
    }

    public static function proveedores(): array
    {
        return Database::pdo()->query(
            "SELECT id, clave, etiqueta, tipo, base_url, api_key_nombre FROM proveedores_ia ORDER BY id"
        )->fetchAll();
    }

    /** Nombres de API keys guardadas (sin el valor cifrado). */
    public static function apiKeys(): array
    {
        return Database::pdo()->query(
            "SELECT nombre, actualizado FROM api_keys ORDER BY nombre"
        )->fetchAll();
    }

    /** Histórico de análisis (columnas planas, sin descifrar). */
    public static function historico(int $limit = 200): array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare(
            "SELECT id, fecha, modelo, archivo_contrato, archivo_poliza, num_polizas,
                    num_contrato, contratista, nit_contratista, tipo_contrato,
                    valor_sin_iva, valor_total, resultado
             FROM documentos ORDER BY fecha DESC LIMIT ?"
        );
        $st->bindValue(1, $limit, \PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $ap = trim((string)($r['archivo_poliza'] ?? ''));
            $nombres = array_values(array_filter(array_map('trim', explode('|', $ap)), fn($x) => $x !== ''));
            $r['poliza_nombres'] = $nombres;
            $r['poliza_es_multi'] = count($nombres) > 1;
        }
        return $rows;
    }

    /** Manuales de contratación subidos (metadata, sin el texto completo). */
    public static function manuales(int $limit = 50): array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare(
            "SELECT id, nombre_archivo, activo, fecha_subida, subido_por,
                    CASE WHEN parametros_json IS NOT NULL AND parametros_json <> '' THEN 1 ELSE 0 END AS tiene_params
             FROM manual_contratacion ORDER BY fecha_subida DESC LIMIT ?"
        );
        $st->bindValue(1, $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function manualActivo(): ?array
    {
        $row = Database::pdo()->query(
            "SELECT id, nombre_archivo, fecha_subida, subido_por FROM manual_contratacion WHERE activo=1 LIMIT 1"
        )->fetch();
        return $row ?: null;
    }
}
