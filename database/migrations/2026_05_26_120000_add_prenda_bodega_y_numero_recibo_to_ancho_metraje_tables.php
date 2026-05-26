<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_ancho_general', 'prenda_bodega_id')) {
                $table->unsignedBigInteger('prenda_bodega_id')
                    ->nullable()
                    ->after('prenda_pedido_id');
                $table->index('prenda_bodega_id', 'pag_prenda_bodega_id_idx');
            }

            if (!Schema::hasColumn('pedido_ancho_general', 'numero_recibo')) {
                $table->unsignedBigInteger('numero_recibo')
                    ->nullable()
                    ->after('prenda_bodega_id');
                $table->index('numero_recibo', 'pag_numero_recibo_idx');
            }
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_metraje_color', 'prenda_bodega_id')) {
                $table->unsignedBigInteger('prenda_bodega_id')
                    ->nullable()
                    ->after('prenda_pedido_id');
                $table->index('prenda_bodega_id', 'pmc_prenda_bodega_id_idx');
            }

            if (!Schema::hasColumn('pedido_metraje_color', 'numero_recibo')) {
                $table->unsignedBigInteger('numero_recibo')
                    ->nullable()
                    ->after('prenda_bodega_id');
                $table->index('numero_recibo', 'pmc_numero_recibo_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_ancho_general', 'numero_recibo')) {
                $table->dropIndex('pag_numero_recibo_idx');
                $table->dropColumn('numero_recibo');
            }

            if (Schema::hasColumn('pedido_ancho_general', 'prenda_bodega_id')) {
                $table->dropIndex('pag_prenda_bodega_id_idx');
                $table->dropColumn('prenda_bodega_id');
            }
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_metraje_color', 'numero_recibo')) {
                $table->dropIndex('pmc_numero_recibo_idx');
                $table->dropColumn('numero_recibo');
            }

            if (Schema::hasColumn('pedido_metraje_color', 'prenda_bodega_id')) {
                $table->dropIndex('pmc_prenda_bodega_id_idx');
                $table->dropColumn('prenda_bodega_id');
            }
        });
    }
};

