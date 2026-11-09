<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ContractAreasMigrationTest extends TestCase
{
    public function test_catalog_matches_hierarchy_without_deleting_history_or_duplicating_on_repeat(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('areas', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->boolean('active');
        });
        Schema::create('area_subareas', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('area_id'); $table->string('name'); $table->boolean('active');
        });
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('area_id'); $table->unsignedBigInteger('sub_area_id')->nullable();
        });
        DB::table('areas')->insert([
            ['id' => 1, 'name' => 'financiera', 'active' => 0],
            ['id' => 2, 'name' => 'Area anterior', 'active' => 1],
            ['id' => 3, 'name' => 'Presidencia', 'active' => 1],
        ]);
        DB::table('area_subareas')->insert([
            ['id' => 10, 'area_id' => 1, 'name' => 'Contabilidad', 'active' => 0],
            ['id' => 20, 'area_id' => 2, 'name' => 'Anterior', 'active' => 1],
            ['id' => 30, 'area_id' => 3, 'name' => 'NO APLICA', 'active' => 1],
        ]);
        DB::table('contracts')->insert(['area_id' => 2, 'sub_area_id' => 20]);
        $migration = require base_path('database/migrations/2026_11_09_000000_sync_contract_areas_and_subareas.php');
        $migration->up();

        $expected = [
            'PRESIDENCIA' => 0, 'GERENCIA TALENTO HUMANO' => 2, 'GERENCIA COMERCIAL' => 0,
            'SECRETARIA GENERAL Y DE ASUNTOS CORPORATIVOS' => 1, 'FINANCIERA' => 2,
            'AUDITORIA CORPORATIVA' => 0, 'VICEPRESIDENCIA DE OPERACIONES' => 2, 'GERENCIA ADMINISTRATIVA' => 5,
        ];
        $areas = DB::table('areas')->where('active', 1)->get();
        $this->assertCount(8, $areas);
        foreach ($areas as $area) {
            $this->assertArrayHasKey($area->name, $expected);
            $this->assertSame($expected[$area->name], DB::table('area_subareas')->where('area_id', $area->id)->where('active', 1)->count());
        }
        $this->assertSame(1, DB::table('areas')->where('name', 'FINANCIERA')->value('id'));
        $this->assertSame(10, DB::table('area_subareas')->where('name', 'CONTABILIDAD')->value('id'));
        $this->assertSame(0, DB::table('areas')->where('id', 2)->value('active'));
        $this->assertSame(0, DB::table('area_subareas')->where('id', 20)->value('active'));
        $this->assertSame(0, DB::table('area_subareas')->where('id', 30)->value('active'));
        $this->assertSame(2, DB::table('contracts')->value('area_id'));
        $this->assertSame(20, DB::table('contracts')->value('sub_area_id'));
        $areaCount = DB::table('areas')->count();
        $childCount = DB::table('area_subareas')->count();
        $migration->up();
        $this->assertSame($areaCount, DB::table('areas')->count());
        $this->assertSame($childCount, DB::table('area_subareas')->count());
    }
}
