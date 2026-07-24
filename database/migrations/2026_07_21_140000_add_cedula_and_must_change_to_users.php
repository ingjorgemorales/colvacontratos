<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade a `users`:
 *   - `cedula` (documento de identidad), único y no repetible. La fija el
 *     administrador al crear el usuario; el usuario NO puede editarla.
 *   - `must_change_password` para forzar el cambio de contraseña en el primer
 *     inicio de sesión (se pone en 1 al crear el usuario y en 0 tras el cambio).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'cedula')) {
            DB::statement("ALTER TABLE `users`
                ADD COLUMN `cedula` VARCHAR(32) NULL AFTER `email`,
                ADD UNIQUE KEY `uq_users_cedula` (`cedula`)");
        }
        if (!Schema::hasColumn('users', 'must_change_password')) {
            DB::statement("ALTER TABLE `users`
                ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'must_change_password')) {
            DB::statement("ALTER TABLE `users` DROP COLUMN `must_change_password`");
        }
        if (Schema::hasColumn('users', 'cedula')) {
            DB::statement("ALTER TABLE `users` DROP INDEX `uq_users_cedula`, DROP COLUMN `cedula`");
        }
    }
};
