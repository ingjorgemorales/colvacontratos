# ColvaContratos Laravel 13

Migración inicial del proyecto ColvaContratos a Laravel 13.

## Estado de esta entrega

Esta versión usa un **puente legacy controlado**:

- Laravel 13 recibe todas las solicitudes.
- `routes/web.php` envía las rutas antiguas `?r=...` a `LegacyRouterController`.
- Los controladores, modelos y vistas heredadas siguen funcionando en `app/Controllers`, `app/Models`, `app/Core` y `app/Views`.
- Los assets actuales siguen en `public/assets`.

Esto permite arrancar en Laravel sin reescribir todo de una vez.

## Requisitos

- PHP 8.3 o superior.
- Composer.
- MySQL/MariaDB.
- Node.js solo si vas a compilar assets con Vite.

Laravel 13 requiere PHP 8.3 mínimo.

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Configura en `.env`:

```env
DB_DATABASE=colvacontratos
DB_USERNAME=root
DB_PASSWORD=
```

Luego abre:

```txt
http://127.0.0.1:8000/?r=dashboard
```

## Siguiente fase recomendada

1. Migrar autenticación a Laravel Auth.
2. Convertir `app/Views/*.php` a Blade real en `resources/views`.
3. Convertir modelos actuales a Eloquent.
4. Mover uploads a `storage/app/public`.
5. Reemplazar rutas `?r=...` por rutas limpias:
   - `/dashboard`
   - `/contracts`
   - `/providers`
   - `/reports`
