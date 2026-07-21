<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `motores_ia` — motores/botones de IA seleccionables en el agente.
 * Cada fila es un botón (Gemini, ChatGPT, DeepSeek…) con su proveedor,
 * model_id y límite de tokens. `clave` es única.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('motores_ia')) {
            return;
        }
        DB::unprepared(<<<'SQL'
CREATE TABLE `motores_ia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(32) NOT NULL,
  `etiqueta` varchar(64) NOT NULL,
  `chip` varchar(32) NOT NULL DEFAULT '',
  `descripcion` varchar(200) NOT NULL DEFAULT '',
  `proveedor` varchar(32) NOT NULL,
  `model_id` varchar(128) NOT NULL,
  `max_tokens` int NOT NULL DEFAULT '4096',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('motores_ia');
    }
};
