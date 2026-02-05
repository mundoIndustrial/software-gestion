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
        // Cambiar el enum de estado_bodega para que incluya los estados que usamos
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->enum('estado_bodega', ['Pendiente', 'Entregado', 'Anulado'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->enum('estado_bodega', ['Pendiente','Parcial','Entregado','Retrasado'])->nullable()->change();
        });
    }
};
