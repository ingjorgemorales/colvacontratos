<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ContractPaymentMigrationTest extends TestCase
{
    public function test_payment_migration_preserves_existing_clause_and_supports_null_and_zero(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->text('payment_clause')->nullable();
        });
        $clause = 'Pago por hitos a 45 dias. Hito 1: entrega y aprobacion.';
        DB::table('contracts')->insert(['payment_clause' => $clause]);

        $migration = require base_path('database/migrations/2026_10_30_000000_add_payment_terms_to_contracts.php');
        $migration->up();
        $row = DB::table('contracts')->first();
        $this->assertSame($clause, $row->payment_clause);
        $this->assertNull($row->payment_frequency);
        $this->assertNull($row->payment_term_days);

        DB::table('contracts')->where('id', $row->id)->update(['payment_frequency' => 'hitos', 'payment_term_days' => 0]);
        $this->assertSame(0, DB::table('contracts')->value('payment_term_days'));

        $migration->down();
        $this->assertFalse(Schema::hasColumn('contracts', 'payment_frequency'));
        $this->assertSame($clause, DB::table('contracts')->value('payment_clause'));
    }
}
