<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `api_keys` — claves de los proveedores de IA, CIFRADAS (Fernet).
 * La clave interna es el `nombre` (p. ej. GEMINI_API_KEY). El valor viaja
 * siempre cifrado; nunca se guarda ni se muestra en claro.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_keys')) {
            return;
        }
        DB::unprepared(<<<'SQL'
CREATE TABLE `api_keys` (
  `nombre` varchar(64) NOT NULL,
  `valor_cifrado` text NOT NULL,
  `actualizado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
