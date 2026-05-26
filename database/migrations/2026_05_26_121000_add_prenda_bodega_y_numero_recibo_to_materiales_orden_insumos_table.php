<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (!Schema::hasColumn('materiales_orden_insumos', 'prenda_bodega_id')) {
                $table->unsignedBigInteger('prenda_bodega_id')
                    ->nullable()
                    ->after('prenda_id');
                $table->index('prenda_bodega_id', 'moi_prenda_bodega_id_idx');
            }

            if (!Schema::hasColumn('materiales_orden_insumos', 'numero_recibo')) {
                $table->unsignedBigInteger('numero_recibo')
                    ->nullable()
                    ->after('numero_pedido');
                $table->index('numero_recibo', 'moi_numero_recibo_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (Schema::hasColumn('materiales_orden_insumos', 'prenda_bodega_id')) {
                $table->dropIndex('moi_prenda_bodega_id_idx');
                $table->dropColumn('prenda_bodega_id');
            }

            if (Schema::hasColumn('materiales_orden_insumos', 'numero_recibo')) {
                $table->dropIndex('moi_numero_recibo_idx');
                $table->dropColumn('numero_recibo');
            }
        });
    }
};

