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
            // Renombrar 'fecha' a 'fecha_envio'
            $table->renameColumn('fecha', 'fecha_envio');
            
            // Agregar 'fecha_inicio' después de 'numero_cotizacion'
            $table->datetime('fecha_inicio')->nullable()->after('numero_cotizacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Renombrar de vuelta 'fecha_envio' a 'fecha'
            $table->renameColumn('fecha_envio', 'fecha');
            
            // Eliminar 'fecha_inicio'
            $table->dropColumn('fecha_inicio');
        });
    }
};
