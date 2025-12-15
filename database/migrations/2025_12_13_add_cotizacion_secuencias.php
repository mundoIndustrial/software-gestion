<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar secuencias para cotizaciones
        DB::table('numero_secuencias')->insertOrIgnore([
            [
                'tipo' => 'cotizaciones_prenda',
                'siguiente' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'cotizaciones_bordado',
                'siguiente' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'cotizaciones_general',
                'siguiente' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('numero_secuencias')
            ->whereIn('tipo', [
                'cotizaciones_prenda',
                'cotizaciones_bordado',
                'cotizaciones_general',
            ])
            ->delete();
    }
};
