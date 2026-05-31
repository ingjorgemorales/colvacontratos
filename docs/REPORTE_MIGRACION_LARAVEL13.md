# Reporte de migración Laravel 13

## Qué se hizo

- Se creó estructura Laravel 13.
- Se agregó `composer.json` para Laravel 13.
- Se agregó `public/index.php` estándar Laravel.
- Se agregó `bootstrap/app.php` estándar moderno.
- Se agregó `routes/web.php` con puente legacy.
- Se creó `app/Http/Controllers/LegacyRouterController.php`.
- Se mantuvieron controladores/modelos/vistas existentes para compatibilidad.
- Se movió el mapa de rutas anterior a `routes/legacy.php`.
- Se copiaron assets actuales a `public/assets`.
- Se crearon `.env` y `.env.example`.
- Se copiaron archivos subidos al storage legacy: `storage/app/legacy`.

## Por qué no se reescribió todo a Eloquent/Blade en una sola pasada

La app actual usa muchos controladores y modelos con SQL manual, sesiones nativas y vistas PHP. Reescribir todo de golpe aumentaría el riesgo de romper módulos. Esta entrega deja Laravel funcionando como base profesional y permite migrar por módulo.

## Archivos legacy importantes

- `app/Controllers`
- `app/Models`
- `app/Core`
- `app/Views`
- `routes/legacy.php`

## Archivos Laravel nuevos importantes

- `app/Http/Controllers/LegacyRouterController.php`
- `routes/web.php`
- `bootstrap/app.php`
- `composer.json`
- `config/*.php`
