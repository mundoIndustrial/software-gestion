<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agregar constraints para prevenir duplicados de borradores
     */
    public function up(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // 🔧 Índice único: Un usuario NO puede tener múltiples borradores
            // del mismo cliente sin confirmar/rechazar
            // Permite múltiples borradores del MISMO cliente si son de diferentes asesores
            $table->unique(['asesor_id', 'cliente_id', 'estado'], name: 'uk_borrador_por_asesor_cliente')
                ->where('estado', '=', 'BORRADOR')
                ->where('deleted_at', null);

            // Índice para búsquedas rápidas de borradores por asesor
            $table->index(['asesor_id', 'estado'], name: 'idx_pedidos_asesor_estado');

            // Índice para búsquedas rápidas de borradores por cliente
            $table->index(['cliente_id', 'estado'], name: 'idx_pedidos_cliente_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropUnique('uk_borrador_por_asesor_cliente');
            $table->dropIndex('idx_pedidos_asesor_estado');
            $table->dropIndex('idx_pedidos_cliente_estado');
        });
    }
};
