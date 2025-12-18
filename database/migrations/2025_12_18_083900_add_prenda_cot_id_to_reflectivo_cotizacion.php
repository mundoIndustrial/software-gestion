<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agregar prenda_cot_id a reflectivo_cotizacion para que cada prenda
     * tenga su propia información de reflectivo (ubicaciones e imágenes separadas)
     */
    public function up(): void
    {
        Schema::table('reflectivo_cotizacion', function (Blueprint $table) {
            // Agregar columna prenda_cot_id después de cotizacion_id
            $table->unsignedBigInteger('prenda_cot_id')->nullable()->after('cotizacion_id');
            
            // Agregar foreign key
            $table->foreign('prenda_cot_id')
                ->references('id')
                ->on('prendas_cot')
                ->onDelete('cascade');
            
            // Agregar índice para mejorar performance
            $table->index('prenda_cot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reflectivo_cotizacion', function (Blueprint $table) {
            // Eliminar foreign key primero
            $table->dropForeign(['prenda_cot_id']);
            
            // Eliminar índice
            $table->dropIndex(['prenda_cot_id']);
            
            // Eliminar columna
            $table->dropColumn('prenda_cot_id');
        });
    }
};
