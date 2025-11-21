<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Marcar migraciones que ya existen en la DB
        $migrations = [
            '2025_11_19_105042_create_costos_prenda_table',
            '2025_11_19_105043_create_formatos_cotizacion_table',
        ];

        foreach ($migrations as $migration) {
            // Verificar si ya está registrada
            $exists = DB::table('migrations')
                ->where('migration', $migration)
                ->exists();

            if (!$exists) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => DB::table('migrations')->max('batch') + 1,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
