<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const AREAS = [
        'PRESIDENCIA' => [],
        'GERENCIA TALENTO HUMANO' => ['SELECCION Y COMPENSACION', 'SEGURIDAD Y SALUD EN EL TRABAJO'],
        'GERENCIA COMERCIAL' => [],
        'SECRETARIA GENERAL Y DE ASUNTOS CORPORATIVOS' => ['COORDINACION DE GESTION DOCUMENTAL'],
        'FINANCIERA' => ['CONTABILIDAD', 'FINANCIERA TESORERIA'],
        'AUDITORIA CORPORATIVA' => [],
        'VICEPRESIDENCIA DE OPERACIONES' => ['GERENCIA DE INTEGRACIÓN Y SOLUCIONES TICS', 'DIRECCION DE OPERACIÓN Y REDES'],
        'GERENCIA ADMINISTRATIVA' => ['INFORMATICA', 'LOGISTICA', 'ABASTECIMIENTO', 'SEGURIDAD', 'SERVICIOS ADMINISTRATIVOS'],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $areas = DB::table('areas')->orderBy('id')->get();
            $subAreas = DB::table('area_subareas')->orderBy('id')->get();
            $keepAreas = [];
            $keepChildren = [];
            foreach (self::AREAS as $name => $children) {
                $existing = $areas->first(fn($row) => $this->key($row->name) === $this->key($name));
                $areaId = $existing?->id;
                if ($areaId === null) {
                    $areaId = DB::table('areas')->insertGetId(['name' => $name, 'active' => 1]);
                } else {
                    DB::table('areas')->where('id', $areaId)->update(['name' => $name, 'active' => 1]);
                }
                $keepAreas[] = $areaId;
                foreach ($children as $childName) {
                    $child = $subAreas->first(fn($row) => (int)$row->area_id === (int)$areaId && $this->key($row->name) === $this->key($childName));
                    $childId = $child?->id;
                    if ($childId === null) {
                        $childId = DB::table('area_subareas')->insertGetId(['area_id' => $areaId, 'name' => $childName, 'active' => 1]);
                    } else {
                        DB::table('area_subareas')->where('id', $childId)->update(['name' => $childName, 'active' => 1]);
                    }
                    $keepChildren[] = $childId;
                }
            }
            // No se borran catalogos ni se reasignan contratos historicos.
            DB::table('area_subareas')->whereNotIn('id', $keepChildren)->update(['active' => 0]);
            DB::table('areas')->whereNotIn('id', $keepAreas)->update(['active' => 0]);
        });
    }

    public function down(): void
    {
        // Los estados anteriores no se pueden reconstruir sin una copia del catalogo.
        throw new RuntimeException('Esta actualizacion de catalogos no tiene reversa automatica.');
    }

    private function key(string $name): string
    {
        return \Illuminate\Support\Str::ascii(mb_strtoupper(trim(preg_replace('/\s+/', ' ', $name)), 'UTF-8'));
    }
};
