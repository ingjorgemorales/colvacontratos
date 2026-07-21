<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `documentos` — histórico de análisis del Agente de Pólizas.
 * Guarda cada análisis (contrato + pólizas): datos extraídos (JSON cifrado),
 * archivos originales (blob cifrado o ruta en filesystem) y el resultado.
 * La produce/consume el motor Flask (colvatel-app).
 *
 * Esquema idéntico al de producción; con guarda hasTable para poder ejecutar
 * `php artisan migrate` aunque la tabla ya exista (BD unificada).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documentos')) {
            return;
        }
        DB::unprepared(<<<'SQL'
CREATE TABLE `documentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modelo` varchar(32) NOT NULL DEFAULT '',
  `archivo_contrato` varchar(255) DEFAULT NULL,
  `archivo_poliza` text,
  `num_polizas` int NOT NULL DEFAULT '1',
  `num_contrato` varchar(120) DEFAULT NULL,
  `contratista` varchar(255) DEFAULT NULL,
  `nit_contratista` varchar(64) DEFAULT NULL,
  `tipo_contrato` varchar(64) DEFAULT NULL,
  `valor_sin_iva` decimal(18,2) DEFAULT '0.00',
  `valor_total` decimal(18,2) DEFAULT '0.00',
  `resultado` varchar(32) DEFAULT NULL,
  `datos_json` longtext,
  `contenido_contrato` longblob,
  `contenido_poliza` longblob,
  `storage_path_contrato` varchar(64) DEFAULT NULL,
  `storage_path_poliza` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
