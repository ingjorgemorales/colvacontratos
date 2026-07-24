<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Flash;
use App\Models\RolePermission;

/**
 * Roles y permisos — matriz editable de vistas por rol.
 * Solo accesible por el rol administrador (módulo marcado como solo_admin).
 */
final class RolePermissionController
{
    public function index(): void
    {
        Auth::requireModulo('roles');
        View::render('roles/index', [
            'roles'      => RolePermission::roles(),
            'modulos'    => RolePermission::modulos(),
            'matriz'     => RolePermission::matriz(),
            'idAdmin'    => RolePermission::idRolAdmin(),
        ]);
    }

    /** Guarda la matriz completa: por cada rol, los módulos marcados. */
    public function save(): void
    {
        Auth::requireModulo('roles');

        $permisos = $_POST['permisos'] ?? [];   // [role_id][] = modulo
        $idAdmin = RolePermission::idRolAdmin();
        $guardados = 0;

        foreach (RolePermission::roles() as $rol) {
            $rid = (int) $rol['id'];
            if ($rid === $idAdmin) {
                continue;   // el admin siempre tiene todo: no se toca
            }
            $marcados = $permisos[$rid] ?? [];
            if (!is_array($marcados)) {
                $marcados = [];
            }
            // Solo módulos válidos del catálogo
            $marcados = array_values(array_intersect($marcados, array_keys(RolePermission::modulos())));
            RolePermission::guardarRol($rid, $marcados);
            $guardados++;
        }

        Flash::set('success', "Permisos actualizados para {$guardados} rol(es).");
        header('Location: index.php?r=roles');
        exit;
    }
}
