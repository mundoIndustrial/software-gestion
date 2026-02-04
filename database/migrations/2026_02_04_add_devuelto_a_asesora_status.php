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
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Cambiar el enum para agregar el nuevo estado DEVUELTO_A_ASESORA
            $table->enum('estado', [
                'Pendiente',
                'Entregado',
                'En Ejecución',
                'No iniciado',
                'Anulada',
                'PENDIENTE_SUPERVISOR',
                'pendiente_cartera',
                'RECHAZADO_CARTERA',
                'PENDIENTE_INSUMOS',
                'DEVUELTO_A_ASESORA'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Revertir el enum removiendo DEVUELTO_A_ASESORA
            $table->enum('estado', [
                'Pendiente',
                'Entregado',
                'En Ejecución',
                'No iniciado',
                'Anulada',
                'PENDIENTE_SUPERVISOR',
                'pendiente_cartera',
                'RECHAZADO_CARTERA',
                'PENDIENTE_INSUMOS'
            ])->change();
        });
    }
};
