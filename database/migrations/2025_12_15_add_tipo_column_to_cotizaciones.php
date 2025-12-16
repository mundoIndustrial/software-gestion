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
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Agregar columna tipo si no existe
            if (!Schema::hasColumn('cotizaciones', 'tipo')) {
                $table->string('tipo')->nullable()->after('numero_cotizacion')->comment('Tipo de cotización: P, L, PL, PB, RF');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
