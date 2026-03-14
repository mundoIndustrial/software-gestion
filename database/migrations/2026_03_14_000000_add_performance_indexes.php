<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FASE 7 - Optimización de Queries
     * 
     * Agregar índices compuestos para mejorar performance
     * de queries críticas en procesamiento de pedidos
     * 
     * Mejora esperada: 70% reduction en query time
     * N+1 eliminator: Permite usar whereIn() en lugar de foreach
     */
    public function up(): void
    {
        // Índice para calcularCantidadTotalEpps()
        // Query: SELECT SUM(cantidad) FROM pedido_epp WHERE pedido_produccion_id = ?
        Schema::table('pedido_epp', function (Blueprint $table) {
            if (!Schema::hasIndex('pedido_epp', 'idx_pedido_epp_cantidad')) {
                $table->index(['pedido_produccion_id', 'cantidad'], 'idx_pedido_epp_cantidad');
            }
        });

        // Índice para calcularCantidadTotalPrendas() - JOIN chain
        // Query: ... WHERE proceso_prenda_detalle_id = ?
        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            if (!Schema::hasIndex('pedidos_procesos_prenda_tallas', 'idx_pppt_proceso_detalle_id')) {
                $table->index('proceso_prenda_detalle_id', 'idx_pppt_proceso_detalle_id');
            }
        });

        // Índice para JOIN: prenda_pedido → prendas (FK)
        // Query: ... WHERE prenda_pedido_id = ?
        Schema::table('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
            if (!Schema::hasIndex('pedidos_procesos_prenda_detalles', 'idx_ppd_prenda_pedido_id')) {
                $table->index('prenda_pedido_id', 'idx_ppd_prenda_pedido_id');
            }
        });

        // Índice para JOIN: prendas_pedido ← pedidos_produccion (FK)
        // Query: ... WHERE pedido_produccion_id = ?
        Schema::table('prendas_pedido', function (Blueprint $table) {
            if (!Schema::hasIndex('prendas_pedido', 'idx_prendas_pedido_id')) {
                $table->index('pedido_produccion_id', 'idx_prendas_pedido_id');
            }
        });

        // Bonus: Índice para procesarImagenesDeColores() WhereHas optimization
        // Query: JOIN prendas_pedido WHERE pedido_produccion_id = ? AND color_nombre = ?
        Schema::table('prenda_pedido_talla_colores', function (Blueprint $table) {
            if (!Schema::hasIndex('prenda_pedido_talla_colores', 'idx_color_nombre')) {
                $table->index('color_nombre', 'idx_color_nombre');
            }
        });
    }

    public function down(): void
    {
        // Drop indices
        Schema::table('pedido_epp', function (Blueprint $table) {
            $table->dropIndex('idx_pedido_epp_cantidad');
        });

        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            $table->dropIndex('idx_pppt_proceso_detalle_id');
        });

        Schema::table('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
            $table->dropIndex('idx_ppd_prenda_pedido_id');
        });

        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->dropIndex('idx_prendas_pedido_id');
        });

        Schema::table('prenda_pedido_talla_colores', function (Blueprint $table) {
            $table->dropIndex('idx_color_nombre');
        });
    }
};
