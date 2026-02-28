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
        // Agregar estado 'Pendiente_Insumos' si la columna estado en consecutivos_recibos_pedidos es un ENUM
        // Si es VARCHAR, no es necesario pero se ejecuta sin problema
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            // El campo estado es varchar(50), así que cualquier valor es válido
            // Esta migración documenta que "Pendiente_Insumos" es un estado válido
            // Cuando área = "Insumos", el estado cambia a "Pendiente_Insumos"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            // No revertir nada, el estado Pendiente_Insumos puede permanecer en registros existentes
        });
    }
};
