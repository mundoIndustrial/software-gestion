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
        // Eliminar columna precios_tallas si existe
        Schema::table('prendas_cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('prendas_cotizaciones', 'precios_tallas')) {
                $table->dropColumn('precios_tallas');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en reverse
    }
};
