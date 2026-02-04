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
        // Agregar 'COSTURA-BODEGA' al enum de tipo_recibo en consecutivos_recibos
        Schema::table('consecutivos_recibos', function (Blueprint $table) {
            $table->enum('tipo_recibo', [
                'COSTURA',
                'ESTAMPADO',
                'BORDADO',
                'REFLECTIVO',
                'GENERAL',
                'DTF',
                'SUBLIMADO',
                'COSTURA-BODEGA'  // Nuevo tipo de recibo para bodeguero
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el enum sin COSTURA-BODEGA
        Schema::table('consecutivos_recibos', function (Blueprint $table) {
            $table->enum('tipo_recibo', [
                'COSTURA',
                'ESTAMPADO',
                'BORDADO',
                'REFLECTIVO',
                'GENERAL',
                'DTF',
                'SUBLIMADO'
            ])->change();
        });
    }
};
