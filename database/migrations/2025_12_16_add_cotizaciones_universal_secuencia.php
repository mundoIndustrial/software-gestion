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
        // Agregar secuencia universal para TODAS las cotizaciones
        DB::table('numero_secuencias')->insertOrIgnore([
            [
                'tipo' => 'cotizaciones_universal',
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
            ->where('tipo', 'cotizaciones_universal')
            ->delete();
    }
};
