<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('providers') && !Schema::hasColumn('providers', 'counterparty_profile')) {
            Schema::table('providers', function (Blueprint $table): void {
                $table->string('counterparty_profile', 30)->default('proveedor')->after('provider_type_id');
            });
        }

        if (Schema::hasTable('providers') && Schema::hasColumn('providers', 'counterparty_profile')) {
            DB::table('providers')
                ->whereNull('counterparty_profile')
                ->orWhereNotIn('counterparty_profile', ['cliente', 'proveedor', 'subcontratista'])
                ->update(['counterparty_profile' => 'proveedor']);
        }

        $this->normalizeProviderTypes();
        $this->dedupeCatalog('catalog_tipo_contratista', 'tipo_contratista_id', [
            '1' => 'Contratista',
            '2' => 'Consorcio',
            '3' => 'Union Temporal',
            '4' => 'Promesa Sociedad Futura',
            '5' => 'Sociedad con Objeto Unico',
        ]);
        $this->dedupeCatalog('catalog_tipo_persona', 'tipo_persona_id', [
            '1' => 'Natural',
            '2' => 'Juridica',
            '3' => 'Juridica Extranjera',
        ]);
        $this->dedupeCatalog('catalog_naturaleza', 'naturaleza_id', [
            '2' => 'Privada',
            '3' => 'Publica',
            '4' => 'Sin Animo de Lucro',
        ]);
        $this->dedupeCatalog('catalog_clasificacion', 'clasificacion_id', [
            '3' => 'Privadas',
            '4' => 'Persona Natural',
            '5' => 'EPS',
            '6' => 'ESP',
            '7' => 'Departamentos y Municipios',
            '8' => 'ESE Hospitales',
            '9' => 'Publicos',
            '10' => 'Bomberos',
            '11' => 'Contralorias',
            '12' => 'Cajas de Compensacion',
            '13' => 'Fundaciones',
            '14' => 'Universidades',
            '15' => 'Religiosas',
            '16' => 'Institutos',
            '17' => 'Sindicatos',
            '18' => 'Corporaciones',
            '19' => 'Clubes',
            '20' => 'Cooperativas',
            '21' => 'Asociaciones',
            '22' => 'Federaciones',
            '23' => 'Juntas de Accion',
            '24' => 'Colegios/Instituciones Educativas',
            '25' => 'Cabildos',
        ]);
        $this->dedupeCatalog('catalog_nacionalidad_contratista', 'nacionalidad_contratista_id', [
            '1' => 'Nacional',
            '2' => 'Extranjero',
        ]);
        $this->dedupeCatalog('catalog_clase_contratista', 'clase_contratista_id', [
            '1' => 'Union Temporal o Consorcio',
            '2' => 'Integrante Union Temporal o Consorcio',
            '3' => 'Unico Contratista',
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('providers') && Schema::hasColumn('providers', 'counterparty_profile')) {
            Schema::table('providers', function (Blueprint $table): void {
                $table->dropColumn('counterparty_profile');
            });
        }
    }

    private function normalizeProviderTypes(): void
    {
        if (!Schema::hasTable('provider_types')) {
            return;
        }

        $naturalId = $this->providerTypeId('natural', 'Persona Natural');
        $juridicaId = $this->providerTypeId('jur', 'Persona Juridica');

        if (Schema::hasTable('providers') && Schema::hasColumn('providers', 'provider_type_id')) {
            DB::table('providers')
                ->whereNotNull('provider_type_id')
                ->whereNotIn('provider_type_id', [$naturalId, $juridicaId])
                ->update(['provider_type_id' => null]);
        }

        DB::table('provider_types')->where('id', $naturalId)->update(['name' => 'Persona Natural', 'active' => 1]);
        DB::table('provider_types')->where('id', $juridicaId)->update(['name' => 'Persona Juridica', 'active' => 1]);
        DB::table('provider_types')->whereNotIn('id', [$naturalId, $juridicaId])->delete();
    }

    private function providerTypeId(string $needle, string $name): int
    {
        $row = DB::table('provider_types')
            ->whereRaw('LOWER(name) LIKE ?', ['%' . $needle . '%'])
            ->orderBy('id')
            ->first();

        if ($row) {
            return (int) $row->id;
        }

        return (int) DB::table('provider_types')->insertGetId([
            'name' => $name,
            'active' => 1,
        ]);
    }

    private function dedupeCatalog(string $table, string $providerField, array $labels): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($labels as $code => $label) {
            $rows = DB::table($table)->where('code', (string) $code)->orderBy('id')->get();
            if ($rows->isEmpty()) {
                DB::table($table)->insert([
                    'code' => (string) $code,
                    'name' => $label,
                    'sort_order' => (int) $code,
                    'active' => 1,
                ]);
                continue;
            }

            $sorted = $rows->sortBy(function ($row): string {
                $name = (string) $row->name;
                return sprintf(
                    '%d-%d-%010d',
                    preg_match('/^\s*\d+\s+/', $name) ? 1 : 0,
                    str_contains($name, '(') ? 1 : 0,
                    (int) $row->id
                );
            })->values();

            $canonical = $sorted->first();
            $duplicateIds = $sorted->slice(1)->pluck('id')->map(fn ($id) => (int) $id)->all();

            if ($duplicateIds && Schema::hasTable('providers') && Schema::hasColumn('providers', $providerField)) {
                DB::table('providers')->whereIn($providerField, $duplicateIds)->update([$providerField => (int) $canonical->id]);
            }

            if ($duplicateIds) {
                DB::table($table)->whereIn('id', $duplicateIds)->delete();
            }

            DB::table($table)->where('id', (int) $canonical->id)->update([
                'code' => (string) $code,
                'name' => $label,
                'sort_order' => (int) $code,
                'active' => 1,
            ]);
        }
    }
};
