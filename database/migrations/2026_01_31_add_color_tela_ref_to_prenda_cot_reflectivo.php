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
        // Agregar columna color_tela_ref a prenda_cot_reflectivo
        if (Schema::hasTable('prenda_cot_reflectivo')) {
            Schema::table('prenda_cot_reflectivo', function (Blueprint $table) {
                // Agregar columna JSON para guardar color, tela, referencia e imágenes
                $table->json('color_tela_ref')
                    ->nullable()
                    ->after('ubicaciones')
                    ->comment('Información de COLOR, TELA, REFERENCIA e imágenes de tela para reflectivo');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('prenda_cot_reflectivo')) {
            Schema::table('prenda_cot_reflectivo', function (Blueprint $table) {
                $table->dropColumn('color_tela_ref');
            });
        }
    }
};
