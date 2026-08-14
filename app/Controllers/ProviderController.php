<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\View;
use App\Models\Provider;
use App\Services\ChangeLogger;
use App\Services\NotificationService;

final class ProviderController {
    private function formData(array $provider, string $action, string $title): array {
        return Provider::catalogsForForm() + ['provider'=>$provider, 'action'=>$action, 'title'=>$title];
    }

    public function index(): void {
        Auth::requireLogin();
        $q = trim($_GET['q'] ?? '');
        $total = Provider::countAll($q);
        $pg = new \App\Core\Paginator($total, 20, \App\Core\Paginator::pageFromRequest());
        View::render('providers/index', ['providers'=>Provider::all($q, $pg->perPage(), $pg->offset()), 'q'=>$q, 'pg'=>$pg]);
    }

    public function create(): void {
        Auth::requireLogin();
        View::render('providers/form', $this->formData([], 'index.php?r=providers.store', 'Nueva Contraparte'));
    }

    public function store(): void {
        Auth::requireLogin();
        if (trim($_POST['document_number'] ?? '') === '' || trim($_POST['name'] ?? '') === '') {
            Flash::set('danger','Documento y nombre son obligatorios.');
            header('Location: index.php?r=providers.create');
            exit;
        }
        $id = Provider::create($_POST);
        $after = Provider::find($id);
        ChangeLogger::log('provider', $id, 'create', 'Contraparte creada: '.($after['name'] ?? ''), null, $after);
        NotificationService::notify('provider_change', 'ColvaContratos - contraparte creada', 'Se creo/actualizo la contraparte: '.($after['name'] ?? '').' NIT: '.($after['document_number'] ?? ''));
        Flash::set('success','Contraparte creada correctamente.');
        header('Location: index.php?r=providers');
        exit;
    }

    public function edit(): void {
        Auth::requireLogin();
        $p = Provider::find((int)($_GET['id'] ?? 0));
        if (!$p) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('providers/form', $this->formData($p, 'index.php?r=providers.update&id='.$p['id'], 'Editar contraparte'));
    }

    public function update(): void {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $before = Provider::find($id);
        Provider::update($id, $_POST);
        $after = Provider::find($id);
        ChangeLogger::log('provider', $id, 'update', 'Contraparte actualizada: '.($after['name'] ?? ''), $before, $after);
        NotificationService::notify('provider_change', 'ColvaContratos - contraparte actualizada', 'Se actualizo la contraparte: '.($after['name'] ?? '').' NIT: '.($after['document_number'] ?? ''));
        Flash::set('success','Contraparte actualizada correctamente.');
        header('Location: index.php?r=providers');
        exit;
    }

    public function exportExcel(): void {
        Auth::requireLogin();
        $q = trim($_GET['q'] ?? '');
        $rows = Provider::exportRows($q);
        $filename = 'directorio_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        echo "\xEF\xBB\xBF";
        echo '<table border="1">';
        echo '<thead><tr>';
        $headers = ['ID CONTRAPARTE','DIGITO VERIFICACION','NOMBRE CONTRAPARTE','PERFIL CONTRAPARTE','TIPO CONTRAPARTE','TIPO CONTRATISTA','TIPO PERSONA','NATURALEZA','CLASIFICACION','NACIONALIDAD DEL CONTRATISTA','CLASE CONTRATISTA','CIUDAD','DIRECCION','TELEFONO','CORREO NOTIFICACIONES','ESTADO','NOTAS'];
        foreach ($headers as $h) {
            echo '<th>' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $estado = ((int)($r['active'] ?? 0) === 1) ? 'Activo' : 'Inactivo';
            $profile = Provider::profileLabel((string)($r['counterparty_profile'] ?? 'proveedor'));
            $city = ($r['city_name'] ?? '') ?: ($r['city'] ?? '');
            $values = [$r['document_number'] ?? '', $r['verification_digit'] ?? '', $r['name'] ?? '', $profile, $r['type_name'] ?? '', $r['tipo_contratista_name'] ?? '', $r['tipo_persona_name'] ?? '', $r['naturaleza_name'] ?? '', $r['clasificacion_name'] ?? '', $r['nacionalidad_contratista_name'] ?? '', $r['clase_contratista_name'] ?? '', $city, $r['address'] ?? '', $r['phone'] ?? '', $r['email'] ?? '', $estado, $r['notes'] ?? ''];
            echo '<tr>';
            foreach ($values as $v) {
                echo '<td style="mso-number-format:\\@">' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        exit;
    }
}
