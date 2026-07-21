<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `proveedores_ia` — proveedores de API de IA.
 * Define cómo conectar cada proveedor: `tipo` (gemini u openai_compatible),
 * `base_url` y el `api_key_nombre` que apunta a la fila cifrada en `api_keys`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('proveedores_ia')) {
            return;
        }
        DB::unprepared(<<<'SQL'
CREATE TABLE `proveedores_ia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(32) NOT NULL,
  `etiqueta` varchar(64) NOT NULL,
  `tipo` enum('gemini','openai_compatible') NOT NULL DEFAULT 'openai_compatible',
  `base_url` varchar(200) NOT NULL DEFAULT '',
  `api_key_nombre` varchar(64) NOT NULL,
  `creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores_ia');
    }
};
