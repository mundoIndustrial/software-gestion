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
        Schema::table('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            // Cambiar tallas a un JSON que contenga tallas y cantidades
            // Se renombrará a un nombre más descriptivo
            $table->json('tallas')->change(); // Guardará: {"talla": "M", "cantidad": 5}, etc
            
            // Eliminar la columna cantidad separada
            $table->dropColumn('cantidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            $table->integer('cantidad')->default(1);
        });
    }
};
