<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Report;
use App\Models\Area;
use App\Models\Provider;
use App\Models\Catalog;
use App\Core\Database;

final class ReportController {
    public function index(): void {
        Auth::requireLogin();
        $filters = $_GET;
        $contracts = Report::contracts($filters);
        $kpis = Report::reportKpis($filters);
        $providersReport = Report::providers($filters);
        $paymentsReport = Report::payments($filters);
        $documentsReport = Report::documents($filters);
        $areas = Area::all();
        $providers = Provider::all();
        $statuses = Catalog::statuses();
        $expenseTypes = Catalog::group('tipo_gasto');
        $selectionModalities = Catalog::group('modalidad_seleccion');
        $catalogosProveedor = $this->providerCatalogs();
        $analytics = Report::analytics($filters);
        $intelligence = Report::intelligence($filters);
        View::render('reports/index', compact('contracts','providersReport','paymentsReport','documentsReport','kpis','analytics','intelligence','areas','providers','statuses','expenseTypes','selectionModalities','catalogosProveedor','filters'));
    }

    private function providerCatalogs(): array {
        $pdo = Database::pdo();
        $get = function(string $table) use ($pdo): array {
            try { return $pdo->query("SELECT id, name AS nombre FROM $table ORDER BY name ASC")->fetchAll(); }
            catch (\Throwable $e) { return []; }
        };
        return [
            'tipo_contratista' => $get('catalog_tipo_contratista'),
            'tipo_persona' => $get('catalog_tipo_persona'),
            'naturaleza' => $get('catalog_naturaleza'),
            'clasificacion' => $get('catalog_clasificacion'),
            'nacionalidad' => $get('catalog_nacionalidad_contratista'),
        ];
    }

    public function exportCsv(): void {
        Auth::requireLogin();
        $rows = Report::contracts($_GET);
        $this->downloadCsv('reporte_contratos', ['Numero','Contrato','Proveedor','Area','Estado','Tipo gasto','Modalidad','Inicio','Fin','Valor','Ejecutado','% Ejecucion','Dias','Riesgo'], array_map(fn($r)=>[
            $r['number'] ?? '', $r['name'] ?? '', $r['provider_name'] ?? '', $r['area_name'] ?? '', $r['status_name'] ?? '',
            $r['expense_type_name'] ?? '', $r['selection_modality_name'] ?? '', $r['start_date'] ?? '', $r['extension_end_date'] ?: ($r['end_date'] ?? ''),
            $r['total_value'] ?? 0, $r['executed_value'] ?? 0, $r['execution_percent'] ?? 0, $r['days_left'] ?? '', $r['risk_level'] ?? ''
        ], $rows));
    }

    public function exportExcel(): void { $this->exportContractsExcel(); }

    public function exportContractsExcel(): void {
        Auth::requireLogin();
        $rows = Report::contracts($_GET);
        $headers = ['Numero','Contrato','Objeto','Proveedor','Area','Supervisor','Estado','Tipo gasto','Modalidad','Inicio','Fin','Valor total','Ejecutado','Saldo','% Ejecucion','Dias','Riesgo'];
        $data = [];
        foreach ($rows as $r) {
            $value=(float)($r['total_value'] ?? 0); $executed=(float)($r['executed_value'] ?? 0);
            $data[] = [$r['number'] ?? '',$r['name'] ?? '',$r['object'] ?? '',$r['provider_name'] ?? '',$r['area_name'] ?? '',$r['supervisor_name'] ?? '',$r['status_name'] ?? '',$r['expense_type_name'] ?? '',$r['selection_modality_name'] ?? '',$r['start_date'] ?? '',$r['extension_end_date'] ?: ($r['end_date'] ?? ''),$value,$executed,$value-$executed,$r['execution_percent'] ?? 0,$r['days_left'] ?? '',$r['risk_level'] ?? ''];
        }
        $this->downloadExcel('reporte_contratos', 'Contratos', $headers, $data);
    }

    public function exportProvidersExcel(): void {
        Auth::requireLogin();
        $rows = Report::providers($_GET);
        $headers = ['NIT/ID','DV','Proveedor','Tipo proveedor','Tipo contratista','Tipo persona','Naturaleza','Clasificacion','Nacionalidad','Clase contratista','Ciudad','Telefono','Contacto','Correo','Estado','Contratos','Valor contratos'];
        $data=[];
        foreach ($rows as $r) {
            $data[] = [$r['document_number'] ?? '',$r['verification_digit'] ?? '',$r['name'] ?? '',$r['tipo_proveedor'] ?? '',$r['tipo_contratista'] ?? '',$r['tipo_persona'] ?? '',$r['naturaleza'] ?? '',$r['clasificacion'] ?? '',$r['nacionalidad'] ?? '',$r['clase_contratista'] ?? '',$r['city'] ?? '',$r['phone'] ?? '',$r['contact_name'] ?? '',$r['email'] ?? '',!empty($r['active'])?'Activo':'Inactivo',$r['contratos_total'] ?? 0,$r['contratos_valor'] ?? 0];
        }
        $this->downloadExcel('reporte_proveedores', 'Proveedores', $headers, $data);
    }

    public function exportFinancialExcel(): void {
        Auth::requireLogin();
        $rows = Report::payments($_GET);
        $headers = ['Contrato','Nombre contrato','Proveedor','Area','Fecha factura','Factura','Valor registrado','IVA','Total factura','Orden pago','Valor pagado','Acumulado ejecutado','Saldo','Avance fisico %'];
        $data=[];
        foreach ($rows as $r) {
            $data[] = [$r['number'] ?? '',$r['contract_name'] ?? '',$r['provider_name'] ?? '',$r['area_name'] ?? '',$r['invoice_date'] ?? '',$r['invoice_number'] ?? '',$r['recorded_value'] ?? 0,$r['vat'] ?? 0,$r['invoice_total'] ?? 0,$r['payment_order'] ?? '',$r['paid_value'] ?? 0,$r['accumulated_executed_value'] ?? 0,$r['balance'] ?? 0,$r['physical_progress'] ?? 0];
        }
        $this->downloadExcel('reporte_financiero', 'Financiero', $headers, $data);
    }

    public function exportDocumentsExcel(): void {
        Auth::requireLogin();
        $rows = Report::documents($_GET);
        $headers = ['Contrato','Nombre contrato','Proveedor','Tipo','Tipo documental','Archivo','Estado firma','Version','Fecha carga','Notas'];
        $data=[];
        foreach ($rows as $r) {
            $data[] = [$r['number'] ?? '',$r['contract_name'] ?? '',$r['provider_name'] ?? '',$r['type'] ?? '',$r['document_type'] ?? '',$r['original_name'] ?? '',$r['signed_status'] ?? '',$r['version_number'] ?? '',$r['created_at'] ?? '',$r['notes'] ?? ''];
        }
        $this->downloadExcel('reporte_documental', 'Documental', $headers, $data);
    }


    public function exportAnalyticsExcel(): void {
        Auth::requireLogin();
        $analytics = Report::analytics($_GET);
        $headers = ['Modulo','Indicador','Cantidad','Valor'];
        $data = [];
        foreach (($analytics['riesgo'] ?? []) as $r) { $data[] = ['Riesgo contractual', $r['label'] ?? '', $r['total'] ?? 0, $r['value'] ?? 0]; }
        foreach (($analytics['areas'] ?? []) as $r) { $data[] = ['Contratos por area', $r['label'] ?? '', $r['total'] ?? 0, $r['value'] ?? 0]; }
        foreach (($analytics['proveedores_clasificacion'] ?? []) as $r) { $data[] = ['Proveedores por clasificacion', $r['label'] ?? '', $r['total'] ?? 0, $r['value'] ?? 0]; }
        foreach (($analytics['proveedores_tipo'] ?? []) as $r) { $data[] = ['Proveedores por tipo contratista', $r['label'] ?? '', $r['total'] ?? 0, $r['value'] ?? 0]; }
        foreach (($analytics['financiero_proveedor'] ?? []) as $r) { $data[] = ['Top proveedores por valor', $r['label'] ?? '', $r['total'] ?? 0, $r['value'] ?? 0]; }
        foreach (($analytics['documentos_estado'] ?? []) as $r) { $data[] = ['Documentos por estado firma', $r['label'] ?? '', $r['total'] ?? 0, $r['value'] ?? 0]; }
        $this->downloadExcel('reporte_analitica_gerencial', 'Analitica Gerencial', $headers, $data);
    }
    public function exportIntelligenceExcel(): void {
        Auth::requireLogin();
        $intel = Report::intelligence($_GET);
        $headers = ['Modulo','Indicador','Detalle','Cantidad','Valor','Nivel'];
        $data = [];
        foreach (($intel['expiring'] ?? []) as $r) {
            $data[] = ['Alertas vencimiento', $r['number'] ?? '', $r['name'] ?? '', $r['days_left'] ?? '', $r['total_value'] ?? 0, $r['risk_level'] ?? ''];
        }
        foreach (($intel['provider_risk'] ?? []) as $r) {
            $data[] = ['Riesgo proveedor', $r['provider_name'] ?? '', 'Contratos: '.($r['contracts_total'] ?? 0).' | Vencidos: '.($r['expired_total'] ?? 0).' | Rojo: '.($r['red_total'] ?? 0), $r['contracts_total'] ?? 0, $r['contract_value'] ?? 0, $r['risk_score'] ?? 0];
        }
        foreach (($intel['inactive_providers'] ?? []) as $r) {
            $data[] = ['Proveedor sin actividad', $r['name'] ?? '', $r['document_number'] ?? '', 0, 0, 'SIN CONTRATOS'];
        }
        foreach (($intel['missing_docs'] ?? []) as $r) {
            $data[] = ['Documentos pendientes', $r['number'] ?? '', $r['name'] ?? '', $r['pending_docs'] ?? 0, $r['total_value'] ?? 0, 'DOCUMENTAL'];
        }
        foreach (($intel['concentration'] ?? []) as $r) {
            $data[] = ['Concentracion contractual', $r['provider_name'] ?? '', ($r['percent_value'] ?? 0).'% del valor total', $r['contracts_total'] ?? 0, $r['contract_value'] ?? 0, 'CONCENTRACION'];
        }
        $this->downloadExcel('inteligencia_contractual', 'Inteligencia Contractual', $headers, $data);
    }


    private function downloadCsv(string $name, array $headers, array $rows): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$name.'_'.date('Ymd_His').'.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) fputcsv($out, $row);
        fclose($out); exit;
    }

    private function downloadExcel(string $name, string $title, array $headers, array $rows): void {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$name.'_'.date('Ymd_His').'.xls"');
        echo "\xEF\xBB\xBF";
        echo '<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse;font-family:Arial;font-size:11px}th{background:#0b4778;color:#fff;font-weight:bold}td,th{border:1px solid #cbd5e1;padding:6px} .money{mso-number-format:"#,##0"}</style></head><body>';
        echo '<h3>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h3><table>';
        echo '<tr>'; foreach ($headers as $h) echo '<th>'.htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8').'</th>'; echo '</tr>';
        foreach ($rows as $row) { echo '<tr>'; foreach ($row as $v) echo '<td>'.htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8').'</td>'; echo '</tr>'; }
        echo '</table></body></html>'; exit;
    }
}

