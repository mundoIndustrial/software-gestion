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
            // Verificar si la columna existe antes de renombrar
            if (Schema::hasColumn('cotizaciones', 'tipo_cotizacion')) {
                // Cambiar el enum de valores a tipo_venta
                $table->renameColumn('tipo_cotizacion', 'tipo_venta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Revertir el cambio si existe
            if (Schema::hasColumn('cotizaciones', 'tipo_venta')) {
                $table->renameColumn('tipo_venta', 'tipo_cotizacion');
            }
        });
    }
};
