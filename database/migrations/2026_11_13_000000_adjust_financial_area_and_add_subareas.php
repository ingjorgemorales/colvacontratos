<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $financial = DB::table('areas')->where('name', 'FINANCIERA')->first();
            $renamed = DB::table('areas')->where('name', 'GERENCIA FINANCIERA')->first();
            if ($financial && $renamed && $financial->id !== $renamed->id) {
                throw new RuntimeException('Existen FINANCIERA y GERENCIA FINANCIERA. Revise los registros antes de unificarlos.');
            }
            if (!$financial && !$renamed) {
                throw new RuntimeException('Falta el area FINANCIERA. Ejecute primero la migracion de areas del 2026_11_09.');
            }
            if ($financial) {
                DB::table('areas')->where('id', $financial->id)->update(['name' => 'GERENCIA FINANCIERA']);
            }

            $additions = [
                'SECRETARIA GENERAL Y DE ASUNTOS CORPORATIVOS' => 'SECRETARIA GENERAL Y ASUNTOS CORPORATIVOS',
                'VICEPRESIDENCIA DE OPERACIONES' => 'DIRECCION EMPRESAS Y GOBIERNO',
            ];
            foreach ($additions as $areaName => $childName) {
                $area = DB::table('areas')->where('name', $areaName)->first();
                if (!$area) {
                    throw new RuntimeException('No se encontro el area: ' . $areaName);
                }
                $child = DB::table('area_subareas')->where('area_id', $area->id)->where('name', $childName)->first();
                if ($child) {
                    DB::table('area_subareas')->where('id', $child->id)->update(['active' => 1]);
                } else {
                    DB::table('area_subareas')->insert(['area_id' => $area->id, 'name' => $childName, 'active' => 1]);
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Esta actualizacion de catalogos no tiene reversa automatica.');
    }
};
