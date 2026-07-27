<?php
namespace App\Core;

/**
 * Paginador reutilizable para listados.
 *
 * Calcula offset/limit a partir de la página actual y el total, y arma la
 * barra de enlaces. La vista solo tiene que:
 *   $pg = new Paginator($total, $porPagina, $paginaActual);
 *   ... usar $pg->offset() y $pg->perPage() en el SELECT ...
 *   echo $pg->links($urlBase);   // barra de paginación
 */
final class Paginator
{
    public int $total;
    public int $perPage;
    public int $page;
    public int $pages;

    public function __construct(int $total, int $perPage = 20, int $page = 1)
    {
        $this->total   = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->pages   = (int) max(1, ceil($this->total / $this->perPage));
        $this->page    = min($this->pages, max(1, $page));
    }

    /** Lee el número de página de la query string (?page=N). */
    public static function pageFromRequest(string $param = 'page'): int
    {
        return max(1, (int) ($_GET[$param] ?? 1));
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    /** Rango mostrado, p. ej. "1–20 de 137". */
    public function summary(): string
    {
        if ($this->total === 0) {
            return '0 registros';
        }
        $desde = $this->offset() + 1;
        $hasta = min($this->total, $this->offset() + $this->perPage);
        return number_format($desde, 0, ',', '.') . '–' . number_format($hasta, 0, ',', '.')
            . ' de ' . number_format($this->total, 0, ',', '.');
    }

    /**
     * Barra de paginación (Bootstrap). $baseParams son los parámetros actuales
     * (filtros, r=..., etc.) que se conservan al cambiar de página.
     */
    public function links(array $baseParams, string $param = 'page'): string
    {
        if ($this->pages <= 1) {
            return '';
        }
        $url = function (int $p) use ($baseParams, $param): string {
            $baseParams[$param] = $p;
            return 'index.php?' . http_build_query($baseParams);
        };
        $item = function (int $p, string $txt, bool $disabled = false, bool $active = false) use ($url): string {
            $cls = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');
            $href = $disabled ? '#' : $url($p);
            return '<li class="' . $cls . '"><a class="page-link" href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . $txt . '</a></li>';
        };

        // Ventana de páginas alrededor de la actual.
        $ini = max(1, $this->page - 2);
        $fin = min($this->pages, $this->page + 2);

        $html  = '<nav class="cc-pager"><ul class="pagination pagination-sm mb-0">';
        $html .= $item(max(1, $this->page - 1), '<i class="bi bi-chevron-left"></i>', $this->page <= 1);
        if ($ini > 1) {
            $html .= $item(1, '1');
            if ($ini > 2) { $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
        }
        for ($p = $ini; $p <= $fin; $p++) {
            $html .= $item($p, (string) $p, false, $p === $this->page);
        }
        if ($fin < $this->pages) {
            if ($fin < $this->pages - 1) { $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
            $html .= $item($this->pages, (string) $this->pages);
        }
        $html .= $item(min($this->pages, $this->page + 1), '<i class="bi bi-chevron-right"></i>', $this->page >= $this->pages);
        $html .= '</ul></nav>';
        return $html;
    }
}
