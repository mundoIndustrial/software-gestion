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
        Schema::table('costos_prendas', function (Blueprint $table) {
            // Agregar columna prenda_cot_id si no existe
            if (!Schema::hasColumn('costos_prendas', 'prenda_cot_id')) {
                $table->unsignedBigInteger('prenda_cot_id')->nullable()->after('cotizacion_id');
                $table->foreign('prenda_cot_id')->references('id')->on('prendas_cot')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('costos_prendas', function (Blueprint $table) {
            if (Schema::hasColumn('costos_prendas', 'prenda_cot_id')) {
                $table->dropForeign(['prenda_cot_id']);
                $table->dropColumn('prenda_cot_id');
            }
        });
    }
};
