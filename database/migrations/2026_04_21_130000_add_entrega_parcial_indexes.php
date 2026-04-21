<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_entrega_movimientos', function (Blueprint $table) {
            if (!Schema::hasIndex('prenda_entrega_movimientos', 'idx_pem_prenda_recibo')) {
                $table->index(['prenda_pedido_id', 'consecutivo_recibo_id'], 'idx_pem_prenda_recibo');
            }

            if (!Schema::hasIndex('prenda_entrega_movimientos', 'idx_pem_prenda_fecha')) {
                $table->index(['prenda_pedido_id', 'fecha_entrega'], 'idx_pem_prenda_fecha');
            }
        });

        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (!Schema::hasIndex('consecutivos_recibos_pedidos', 'idx_crp_prenda_activo_estado')) {
                $table->index(['prenda_id', 'activo', 'estado'], 'idx_crp_prenda_activo_estado');
            }

            if (!Schema::hasIndex('consecutivos_recibos_pedidos', 'idx_crp_pedido_prenda_activo')) {
                $table->index(['pedido_produccion_id', 'prenda_id', 'activo'], 'idx_crp_pedido_prenda_activo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_entrega_movimientos', function (Blueprint $table) {
            if (Schema::hasIndex('prenda_entrega_movimientos', 'idx_pem_prenda_recibo')) {
                $table->dropIndex('idx_pem_prenda_recibo');
            }

            if (Schema::hasIndex('prenda_entrega_movimientos', 'idx_pem_prenda_fecha')) {
                $table->dropIndex('idx_pem_prenda_fecha');
            }
        });

        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (Schema::hasIndex('consecutivos_recibos_pedidos', 'idx_crp_prenda_activo_estado')) {
                $table->dropIndex('idx_crp_prenda_activo_estado');
            }

            if (Schema::hasIndex('consecutivos_recibos_pedidos', 'idx_crp_pedido_prenda_activo')) {
                $table->dropIndex('idx_crp_pedido_prenda_activo');
            }
        });
    }
};
