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
            // Agregar campo tipo_venta: M, D, X
            if (!Schema::hasColumn('cotizaciones', 'tipo_venta')) {
                $table->enum('tipo_venta', ['M', 'D', 'X'])->nullable()->after('tipo_cotizacion_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('tipo_venta');
        });
    }
};
