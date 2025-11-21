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
            // Agregar columna tipo_cotizacion_id si no existe
            if (!Schema::hasColumn('cotizaciones', 'tipo_cotizacion_id')) {
                $table->unsignedBigInteger('tipo_cotizacion_id')->nullable()->after('id');
                $table->foreign('tipo_cotizacion_id')->references('id')->on('tipos_cotizacion')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('cotizaciones', 'tipo_cotizacion_id')) {
                $table->dropForeign(['tipo_cotizacion_id']);
                $table->dropColumn('tipo_cotizacion_id');
            }
        });
    }
};
