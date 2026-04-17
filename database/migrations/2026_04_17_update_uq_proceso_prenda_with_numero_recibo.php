<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Actualiza la restricción única en procesos_prenda para incluir numero_recibo.
     * Permite múltiples procesos de la misma área para la misma prenda,
     * siempre que sean para DIFERENTES recibos.
     */
    public function up(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            // Eliminar restricción única antigua
            $table->dropUnique('uq_proceso_prenda_activo');

            // Crear nueva restricción única que incluya numero_recibo
            $table->unique(
                ['numero_pedido', 'prenda_pedido_id', 'proceso', 'numero_recibo'],
                'uq_proceso_prenda_activo'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            $table->dropUnique('uq_proceso_prenda_activo');

            // Restaurar restricción única antigua
            $table->unique(
                ['numero_pedido', 'prenda_pedido_id', 'proceso'],
                'uq_proceso_prenda_activo'
            );
        });
    }
};
