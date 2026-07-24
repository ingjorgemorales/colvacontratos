<?php
namespace App\Models;

use App\Core\Database;

/**
 * Permisos de acceso por rol (tabla role_permissions).
 *
 * Define el CATÁLOGO de vistas/módulos del sistema y qué rutas `?r=...` cubre
 * cada uno. El rol administrador puede marcar, desde /roles, a qué vistas entra
 * cada rol. El chequeo central lo hace LegacyRouterController en cada petición.
 *
 * Reglas fijas (no configurables, para no dejar el sistema sin salida):
 *   - 'siempre'    => accesible por cualquier usuario autenticado (Mi perfil).
 *   - 'solo_admin' => exclusivo del rol administrador (Roles y permisos).
 *   - El rol administrador siempre tiene acceso a todo.
 */
final class RolePermission
{
    /** Rol con máximos privilegios (por nombre; se resuelve el id en BD). */
    public const ROL_ADMIN = 'admin';

    /** Catálogo de vistas del sistema: clave => etiqueta, icono y rutas que cubre. */
    public const MODULOS = [
        'dashboard'    => ['label' => 'Indicadores',         'icono' => 'bi-speedometer2',      'rutas' => ['dashboard']],
        'contracts'    => ['label' => 'Contratos',           'icono' => 'bi-file-earmark-text', 'rutas' => ['contracts']],
        'providers'    => ['label' => 'Proveedores',         'icono' => 'bi-building',          'rutas' => ['providers']],
        'parametricas' => ['label' => 'Paramétricas',        'icono' => 'bi-gear-fill',         'rutas' => ['admin.catalogs', 'areas', 'admin.providers']],
        'documents'    => ['label' => 'Documental',          'icono' => 'bi-paperclip',         'rutas' => ['documents', 'documentflow']],
        'polizas'      => ['label' => 'Revisión de pólizas', 'icono' => 'bi-shield-check',      'rutas' => ['polizas', 'policy_reviews']],
        'agente'       => ['label' => 'Agente de Pólizas',   'icono' => 'bi-robot',             'rutas' => ['agente']],
        'finance'      => ['label' => 'Financiera',          'icono' => 'bi-cash-coin',         'rutas' => ['finance']],
        'reports'      => ['label' => 'Reportes',            'icono' => 'bi-bar-chart-line',    'rutas' => ['reports']],
        'alerts'       => ['label' => 'Alertas',             'icono' => 'bi-bell',              'rutas' => ['alerts']],
        'intelligence' => ['label' => 'Inteligencia KPI',    'icono' => 'bi-cpu',               'rutas' => ['intelligence']],
        'audit'        => ['label' => 'Auditoría',           'icono' => 'bi-clock-history',     'rutas' => ['audit']],
        'users'        => ['label' => 'Usuarios',            'icono' => 'bi-person-gear',       'rutas' => ['admin.users', 'admin.supervisors', 'admin.assign']],
        'admin'        => ['label' => 'Panel admin',         'icono' => 'bi-sliders',           'rutas' => ['admin.panel', 'config']],
        'roles'        => ['label' => 'Roles y permisos',    'icono' => 'bi-shield-lock',       'rutas' => ['roles'], 'solo_admin' => true],
        'perfil'       => ['label' => 'Mi perfil',           'icono' => 'bi-person-circle',     'rutas' => ['perfil'], 'siempre' => true],
    ];

    /** Rutas públicas (sin sesión): nunca se restringen aquí. */
    private const RUTAS_PUBLICAS = ['login', 'auth', 'logout', 'password.'];

    private static ?array $cacheMatriz = null;

    // ── Catálogo y resolución de rutas ────────────────────────
    public static function modulos(): array
    {
        return self::MODULOS;
    }

    /** Devuelve la clave del módulo al que pertenece una ruta (el prefijo más largo que coincida). */
    public static function moduloDeRuta(string $route): ?string
    {
        $mejor = null;
        $largo = -1;
        foreach (self::MODULOS as $clave => $cfg) {
            foreach ($cfg['rutas'] as $prefijo) {
                if ($route === $prefijo || str_starts_with($route, $prefijo . '.')) {
                    if (strlen($prefijo) > $largo) {
                        $largo = strlen($prefijo);
                        $mejor = $clave;
                    }
                }
            }
        }
        return $mejor;
    }

    public static function esRutaPublica(string $route): bool
    {
        foreach (self::RUTAS_PUBLICAS as $p) {
            if ($route === $p || str_starts_with($route, $p)) {
                return true;
            }
        }
        return false;
    }

    // ── Roles ─────────────────────────────────────────────────
    public static function roles(): array
    {
        return Database::pdo()->query("SELECT id, name FROM roles ORDER BY id")->fetchAll();
    }

    public static function idRolAdmin(): int
    {
        $st = Database::pdo()->prepare("SELECT id FROM roles WHERE name=? LIMIT 1");
        $st->execute([self::ROL_ADMIN]);
        $row = $st->fetch();
        return $row ? (int) $row['id'] : 1;
    }

    // ── Matriz de permisos ────────────────────────────────────
    /** [role_id][modulo] => bool, leída una vez por petición. */
    public static function matriz(): array
    {
        if (self::$cacheMatriz !== null) {
            return self::$cacheMatriz;
        }
        $m = [];
        try {
            $rows = Database::pdo()->query("SELECT role_id, modulo, permitido FROM role_permissions")->fetchAll();
            foreach ($rows as $r) {
                $m[(int) $r['role_id']][(string) $r['modulo']] = (int) $r['permitido'] === 1;
            }
        } catch (\Throwable $e) {
            $m = []; // tabla aún no creada: se aplica el comportamiento por defecto
        }
        self::$cacheMatriz = $m;
        return $m;
    }

    public static function limpiarCache(): void
    {
        self::$cacheMatriz = null;
    }

    /** ¿El rol indicado puede entrar al módulo? */
    public static function permite(int $roleId, string $modulo): bool
    {
        $cfg = self::MODULOS[$modulo] ?? null;
        if ($cfg === null) {
            return true;                       // módulo desconocido: no se restringe
        }
        if (!empty($cfg['siempre'])) {
            return true;                       // Mi perfil: siempre
        }
        if ($roleId === self::idRolAdmin()) {
            return true;                       // el admin siempre puede todo
        }
        if (!empty($cfg['solo_admin'])) {
            return false;                      // exclusivo del admin
        }
        $matriz = self::matriz();
        if (!isset($matriz[$roleId])) {
            return true;   // rol sin configuración todavía: no se bloquea nada
        }
        return (bool) ($matriz[$roleId][$modulo] ?? false);
    }

    /** Guarda los módulos permitidos de un rol (reemplaza su configuración). */
    public static function guardarRol(int $roleId, array $modulosPermitidos): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM role_permissions WHERE role_id=?");
            $del->execute([$roleId]);
            $ins = $pdo->prepare("INSERT INTO role_permissions (role_id, modulo, permitido) VALUES (?,?,?)");
            foreach (array_keys(self::MODULOS) as $modulo) {
                $ins->execute([$roleId, $modulo, in_array($modulo, $modulosPermitidos, true) ? 1 : 0]);
            }
            $pdo->commit();
            self::limpiarCache();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
