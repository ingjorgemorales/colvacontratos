<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reemplaza el listado de Áreas del formulario de contratos por la lista oficial.
 *
 * - Si un área ya existe con el mismo nombre (sin importar mayúsculas ni
 *   tildes) se reutiliza su id, para no romper los contratos que la usan.
 * - Las áreas que no están en la lista se DESACTIVAN (no se borran): los
 *   contratos antiguos siguen mostrando su área, pero ya no aparece para elegir.
 */
return new class extends Migration
{
    private const AREAS = [
        'ADMIN',
        'ADMINLABORATORIO',
        'ALISTAMIENTO',
        'APROVISIONAMIENTO',
        'ASEGURAMIENTO',
        'AZTECA',
        'CALIDAD',
        'CELULARES',
        'CENTRO DE GESTION',
        'CENTRO DE GESTION ASEGURAMIENTO',
        'COMERCIAL',
        'COMPENSACION',
        'COMPRAS',
        'CONTABILIDAD',
        'CONTROL INTERNO',
        'DIREECION OPERACIONES GOB. Y EMPRESA',
        'DISCIPLINARIOS',
        'FINANCIERA',
        'GERENCIA DE TALENTO HUMANO',
        'JURIDICA',
        'LABORATORIO',
        'LOGISTICA',
        'MIN_TRABAJO',
        'OPERACIONES',
        'PLANEACION, DESARROLLO CORP-CALIDAD',
        'PRESIDENCIA',
        'Proyecto Recaudo Bogotá ASG',
        'REGIONALES',
        'REGIONALES ALISTAMIENTO',
        'REGIONALES ASEGURAMIENTO',
        'SEGURIDAD',
        'SELECCION Y CONTRATACION',
        'SGSST',
        'SISO',
        'SISTEMA',
        'SUPERVISION DE CONTRATOS',
        'VICEPRECIDENCIA OPERACIONES',
        'VICEPRESIDENCIA',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('areas')) {
            return;
        }

        $existentes = [];
        foreach (DB::table('areas')->orderBy('id')->get() as $row) {
            $clave = $this->normalizar((string) $row->name);
            $existentes[$clave] ??= (int) $row->id;
        }

        $conservar = [];
        foreach (self::AREAS as $nombre) {
            $id = $existentes[$this->normalizar($nombre)] ?? null;
            if ($id !== null) {
                DB::table('areas')->where('id', $id)->update(['name' => $nombre, 'active' => 1]);
            } else {
                $id = (int) DB::table('areas')->insertGetId(['name' => $nombre, 'active' => 1]);
            }
            $conservar[] = $id;
        }

        DB::table('areas')->whereNotIn('id', $conservar)->update(['active' => 0]);
    }

    public function down(): void
    {
        // Sin reversa automática: los nombres y estados anteriores no se guardan.
        // Las áreas desactivadas pueden reactivarse desde Paramétricas > Áreas.
    }

    /** Mayúsculas y sin tildes, para comparar "Logística" con "LOGISTICA". */
    private function normalizar(string $valor): string
    {
        $valor = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $valor) ?? ''), 'UTF-8');
        return strtr($valor, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);
    }
};
