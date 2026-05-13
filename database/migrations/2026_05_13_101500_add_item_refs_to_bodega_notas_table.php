<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bodega_notas', function (Blueprint $table) {
            if (!Schema::hasColumn('bodega_notas', 'bodega_detalle_talla_id')) {
                $table->unsignedBigInteger('bodega_detalle_talla_id')->nullable()->after('pedido_produccion_id');
                $table->index('bodega_detalle_talla_id', 'idx_bodega_notas_detalle_talla_id');
            }

            if (!Schema::hasColumn('bodega_notas', 'pedido_epp_id')) {
                $table->unsignedBigInteger('pedido_epp_id')->nullable()->after('talla_color_id');
                $table->index('pedido_epp_id', 'idx_bodega_notas_pedido_epp_id');
            }

            if (!Schema::hasColumn('bodega_notas', 'prenda_id')) {
                $table->bigInteger('prenda_id')->nullable()->after('pedido_epp_id');
                $table->index('prenda_id', 'idx_bodega_notas_prenda_id');
            }

            if (!Schema::hasColumn('bodega_notas', 'row_hash')) {
                $table->string('row_hash', 32)->nullable()->after('prenda_id');
                $table->index('row_hash', 'idx_bodega_notas_row_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bodega_notas', function (Blueprint $table) {
            if (Schema::hasColumn('bodega_notas', 'row_hash')) {
                $table->dropIndex('idx_bodega_notas_row_hash');
                $table->dropColumn('row_hash');
            }
            if (Schema::hasColumn('bodega_notas', 'prenda_id')) {
                $table->dropIndex('idx_bodega_notas_prenda_id');
                $table->dropColumn('prenda_id');
            }
            if (Schema::hasColumn('bodega_notas', 'pedido_epp_id')) {
                $table->dropIndex('idx_bodega_notas_pedido_epp_id');
                $table->dropColumn('pedido_epp_id');
            }
            if (Schema::hasColumn('bodega_notas', 'bodega_detalle_talla_id')) {
                $table->dropIndex('idx_bodega_notas_detalle_talla_id');
                $table->dropColumn('bodega_detalle_talla_id');
            }
        });
    }
};

