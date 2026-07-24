<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AgentePoliza;
use App\Services\AgentClient;

/**
 * Módulo "Agente de Pólizas" dentro de ColvaContratos.
 *
 * Reproduce la app colvatel-app (analizar, histórico, manual, claves API) como
 * páginas nativas de ColvaContratos. Las páginas leen la BD unificada; las
 * acciones pesadas (analizar, IA, PDF, descargas cifradas, Excel) se reenvían
 * al motor Flask por proxy(). El navegador nunca habla con el Flask directo.
 */
final class AgentePolizasController
{
    // ── Páginas ───────────────────────────────────────────────
    public function index(): void
    {
        Auth::requireLogin();
        View::render('agente/analizar', [
            'motores'   => AgentePoliza::motores(true),
        ]);
    }

    public function historico(): void
    {
        Auth::requireLogin();
        View::render('agente/historico', [
            'registros' => AgentePoliza::historico(),
        ]);
    }

    public function manual(): void
    {
        Auth::requireLogin();
        View::render('agente/manual', [
            'manuales'      => AgentePoliza::manuales(),
            'manual_activo' => AgentePoliza::manualActivo(),
            'motores'       => AgentePoliza::motores(true),
        ]);
    }

    public function apis(): void
    {
        Auth::requireLogin();
        View::render('agente/apis', [
            'motores'     => AgentePoliza::motores(false),
            'proveedores' => AgentePoliza::proveedores(),
            'keys'        => AgentePoliza::apiKeys(),
        ]);
    }

    // ── Proxy genérico hacia el motor Flask ───────────────────
    /**
     * Reenvía la petición del navegador a  <AGENTE_URL><path>  añadiendo la
     * clave interna. Soporta GET/POST/DELETE, subida de archivos (multipart),
     * cuerpos JSON y respuestas binarias (Excel, PDF). Solo rutas /api/*.
     */
    public function proxy(): void
    {
        Auth::requireLogin();
        @set_time_limit(300);

        $path = (string) ($_GET['path'] ?? '');
        if ($path === '' || $path[0] !== '/' || strpos($path, '..') !== false || strpos($path, '/api/') !== 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Ruta del agente no válida.']);
            exit;
        }

        // Conservar query params extra (p. ej. ?idx=1), quitando r y path.
        $q = $_GET;
        unset($q['r'], $q['path']);
        $qs = http_build_query($q);
        $url = AgentClient::baseUrl() . $path . ($qs ? (strpos($path, '?') === false ? '?' : '&') . $qs : '');

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $headers = [];
        $key = AgentClient::key();
        if ($key !== '') {
            $headers[] = 'X-Internal-Key: ' . $key;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, AgentClient::timeout());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if (!empty($_FILES)) {
                [$body, $ctype] = $this->buildMultipart($_POST, $_FILES);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                $headers[] = 'Content-Type: ' . $ctype;
            } else {
                $raw = file_get_contents('php://input');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $raw);
                $headers[] = 'Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'application/json');
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $respHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $h) use (&$respHeaders) {
            $p = explode(':', $h, 2);
            if (count($p) === 2) {
                $respHeaders[strtolower(trim($p[0]))] = trim($p[1]);
            }
            return strlen($h);
        });

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            http_response_code(502);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No se pudo conectar con el motor del agente: ' . $err
                . '. Verifica que el agente Flask esté encendido.']);
            exit;
        }

        // Emite las cabeceras del motor y termina directamente: así el router
        // legacy no re-envuelve la respuesta (que convertiría los binarios en
        // text/html y perdería el nombre de archivo).
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code($status ?: 200);
        header('Content-Type: ' . ($respHeaders['content-type'] ?? 'application/octet-stream'));
        if (isset($respHeaders['content-disposition'])) {
            header('Content-Disposition: ' . $respHeaders['content-disposition']);
        }
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }

    // ── Helpers de multipart ──────────────────────────────────
    private function buildMultipart(array $post, array $files): array
    {
        $boundary = '----ColvaAgente' . bin2hex(random_bytes(12));
        $eol = "\r\n";
        $b = '';
        foreach ($post as $k => $v) {
            foreach ((is_array($v) ? $v : [$v]) as $vv) {
                $b .= '--' . $boundary . $eol
                    . 'Content-Disposition: form-data; name="' . $k . '"' . $eol . $eol
                    . $vv . $eol;
            }
        }
        foreach ($files as $field => $info) {
            if (is_array($info['name'])) {
                $n = count($info['name']);
                for ($i = 0; $i < $n; $i++) {
                    if ((int) ($info['error'][$i] ?? 4) !== 0) {
                        continue;
                    }
                    $b .= $this->filePart($boundary, $field, $info['tmp_name'][$i], $info['name'][$i]);
                }
            } else {
                if ((int) ($info['error'] ?? 4) === 0) {
                    $b .= $this->filePart($boundary, $field, $info['tmp_name'], $info['name']);
                }
            }
        }
        $b .= '--' . $boundary . '--' . $eol;
        return [$b, 'multipart/form-data; boundary=' . $boundary];
    }

    private function filePart(string $boundary, string $field, string $tmp, string $name): string
    {
        $eol = "\r\n";
        $name = str_replace(['"', "\r", "\n"], '', $name) ?: 'archivo';
        return '--' . $boundary . $eol
            . 'Content-Disposition: form-data; name="' . $field . '"; filename="' . $name . '"' . $eol
            . 'Content-Type: application/octet-stream' . $eol . $eol
            . (string) file_get_contents($tmp) . $eol;
    }
}
