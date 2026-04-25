<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Índices para bodega_detalles_talla
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            // Índice más importante: usado en filtrarPedidosPorArea()
            if (!Schema::hasIndex('bodega_detalles_talla', 'idx_numero_area')) {
                $table->index(['numero_pedido', 'area'], 'idx_numero_area');
            }

            // Índice para búsquedas de estado
            if (!Schema::hasIndex('bodega_detalles_talla', 'idx_numero_estado')) {
                $table->index(['numero_pedido', 'estado_bodega'], 'idx_numero_estado');
            }

            // Índice compuesto para búsquedas complejas
            if (!Schema::hasIndex('bodega_detalles_talla', 'idx_numero_area_estado')) {
                $table->index(['numero_pedido', 'area', 'estado_bodega'], 'idx_numero_area_estado');
            }

            // Índice para ordenamiento
            if (!Schema::hasIndex('bodega_detalles_talla', 'idx_fecha_entrega')) {
                $table->index('fecha_entrega', 'idx_fecha_entrega');
            }

            // Nota: prenda_nombre es TEXT, no indexar (requiere configuración especial)
        });

        // Índices para pedidos_produccion
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Índice más importante: búsqueda por número
            if (!Schema::hasIndex('pedidos_produccion', 'idx_numero_pedido')) {
                $table->index('numero_pedido', 'idx_numero_pedido');
            }

            // Índice para filtros de estado
            if (!Schema::hasIndex('pedidos_produccion', 'idx_estado')) {
                $table->index('estado', 'idx_estado');
            }
        });

        // Índices para pedido_oculto
        if (Schema::hasTable('pedido_oculto')) {
            Schema::table('pedido_oculto', function (Blueprint $table) {
                if (!Schema::hasIndex('pedido_oculto', 'idx_user_id')) {
                    $table->index('user_id', 'idx_user_id');
                }
            });
        }

        // Índices para pedido_visto_supervisor
        if (Schema::hasTable('pedido_visto_supervisor')) {
            Schema::table('pedido_visto_supervisor', function (Blueprint $table) {
                if (!Schema::hasIndex('pedido_visto_supervisor', 'idx_user_pedido')) {
                    $table->index(['user_id', 'pedido_id'], 'idx_user_pedido');
                }
            });
        }

        // Índices para pedido_revisado
        if (Schema::hasTable('pedido_revisado')) {
            Schema::table('pedido_revisado', function (Blueprint $table) {
                if (!Schema::hasIndex('pedido_revisado', 'idx_user_pedido')) {
                    $table->index(['user_id', 'pedido_id'], 'idx_user_pedido');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_numero_area');
            $table->dropIndexIfExists('idx_numero_estado');
            $table->dropIndexIfExists('idx_numero_area_estado');
            $table->dropIndexIfExists('idx_fecha_entrega');
            $table->dropIndexIfExists('idx_prenda_nombre');
        });

        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_numero_pedido');
            $table->dropIndexIfExists('idx_estado');
        });

        Schema::table('pedido_oculto', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_user_id');
        });

        if (Schema::hasTable('pedido_visto_supervisor')) {
            Schema::table('pedido_visto_supervisor', function (Blueprint $table) {
                $table->dropIndexIfExists('idx_user_pedido');
            });
        }

        if (Schema::hasTable('pedido_revisado')) {
            Schema::table('pedido_revisado', function (Blueprint $table) {
                $table->dropIndexIfExists('idx_user_pedido');
            });
        }
    }
};
