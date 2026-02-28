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
        Schema::table('procesos_prenda', function (Blueprint $table) {
            // Crear índice único para evitar procesos duplicados del mismo tipo para una prenda
            // Solo se aplica a procesos no completados (estado_proceso != 'Completado')
            // Esto fuerza que solo haya UN proceso activo por área/proceso para cada prenda
            
            $table->unique(
                ['numero_pedido', 'prenda_pedido_id', 'proceso'],
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
        });
    }
};
