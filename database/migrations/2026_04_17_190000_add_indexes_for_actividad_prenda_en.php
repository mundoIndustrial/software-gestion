<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            if (!Schema::hasIndex('prenda_pedido_tallas', 'idx_ppt_prenda_updated_at')) {
                $table->index(['prenda_pedido_id', 'updated_at'], 'idx_ppt_prenda_updated_at');
            }
        });

        Schema::table('prenda_pedido_talla_colores', function (Blueprint $table) {
            if (!Schema::hasIndex('prenda_pedido_talla_colores', 'idx_pptc_talla_updated_at')) {
                $table->index(['prenda_pedido_talla_id', 'updated_at'], 'idx_pptc_talla_updated_at');
            }
        });

        Schema::table('prenda_pedido_colores_telas', function (Blueprint $table) {
            if (!Schema::hasIndex('prenda_pedido_colores_telas', 'idx_ppct_prenda_updated_at')) {
                $table->index(['prenda_pedido_id', 'updated_at'], 'idx_ppct_prenda_updated_at');
            }
        });

        Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
            if (!Schema::hasIndex('prenda_pedido_variantes', 'idx_ppv_prenda_updated_at')) {
                $table->index(['prenda_pedido_id', 'updated_at'], 'idx_ppv_prenda_updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            if (Schema::hasIndex('prenda_pedido_tallas', 'idx_ppt_prenda_updated_at')) {
                $table->dropIndex('idx_ppt_prenda_updated_at');
            }
        });

        Schema::table('prenda_pedido_talla_colores', function (Blueprint $table) {
            if (Schema::hasIndex('prenda_pedido_talla_colores', 'idx_pptc_talla_updated_at')) {
                $table->dropIndex('idx_pptc_talla_updated_at');
            }
        });

        Schema::table('prenda_pedido_colores_telas', function (Blueprint $table) {
            if (Schema::hasIndex('prenda_pedido_colores_telas', 'idx_ppct_prenda_updated_at')) {
                $table->dropIndex('idx_ppct_prenda_updated_at');
            }
        });

        Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
            if (Schema::hasIndex('prenda_pedido_variantes', 'idx_ppv_prenda_updated_at')) {
                $table->dropIndex('idx_ppv_prenda_updated_at');
            }
        });
    }
};
