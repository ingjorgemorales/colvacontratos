<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;

/**
 * Ayuda — entrega el Manual de Usuario en PDF.
 *
 * El PDF NO vive en public/: si estuviera ahí, cualquiera podría descargarlo
 * sin iniciar sesión conociendo la URL. Se guarda en la carpeta `manual/` de la
 * raíz del proyecto (fuera del alcance del servidor web) y se entrega desde
 * aquí solo a usuarios con sesión activa.
 *
 * La ruta `ayuda.manual` no pertenece a ningún módulo del catálogo de permisos,
 * de modo que RolePermission::moduloDeRuta() devuelve null y el manual queda
 * disponible para cualquier usuario autenticado, sea cual sea su rol.
 */
final class AyudaController
{
    /** Nombre del archivo dentro de la carpeta manual/. */
    private const ARCHIVO = 'Manual_Usuario_ColvaContratos.pdf';

    /**
     * Muestra el Manual de Usuario en el navegador.
     *
     * Se envía con Content-Disposition: inline para que se abra en el visor de
     * PDF de la pestaña en lugar de descargarse. El usuario siempre puede
     * guardarlo desde el propio visor.
     */
    public function manual(): void
    {
        Auth::requireLogin();

        $ruta = base_path('manual/' . self::ARCHIVO);

        // realpath + comprobación de prefijo: impide que un cambio futuro en la
        // constante pueda sacar la lectura fuera de la carpeta manual/.
        $real = realpath($ruta);
        $base = realpath(base_path('manual'));
        if ($real === false || $base === false || !str_starts_with($real, $base) || !is_file($real)) {
            Flash::set('warning', 'El Manual de Usuario no está disponible en este momento. Avise al administrador del sistema.');
            header('Location: index.php?r=dashboard');
            exit;
        }

        // El router envuelve la respuesta en un buffer de salida; se descarta
        // para que ningún espacio en blanco previo corrompa el binario del PDF.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . self::ARCHIVO . '"');
        header('Content-Length: ' . filesize($real));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');

        readfile($real);
        exit;
    }
}
