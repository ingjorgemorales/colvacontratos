<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `manual_contratacion` — versiones del Manual de Contratación.
 * Cada fila es un manual subido: texto extraído, parámetros (tipos de contrato,
 * porcentajes, vigencias) en JSON y un flag `activo` de la versión vigente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('manual_contratacion')) {
            return;
        }
        DB::unprepared(<<<'SQL'
CREATE TABLE `manual_contratacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_archivo` varchar(255) NOT NULL,
  `contenido_texto` longtext,
  `parametros_json` longtext,
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subido_por` varchar(120) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_contratacion');
    }
};
