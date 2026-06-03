<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_ancho_general', 'consecutivo_recibo_id')) {
                $table->unsignedBigInteger('consecutivo_recibo_id')->nullable()->after('numero_recibo');
                $table->index('consecutivo_recibo_id', 'pag_consecutivo_recibo_id_idx');
                $table->foreign('consecutivo_recibo_id', 'pag_consecutivo_recibo_id_fk')
                    ->references('id')
                    ->on('consecutivos_recibos_pedidos')
                    ->nullOnDelete();
            }
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_metraje_color', 'consecutivo_recibo_id')) {
                $table->unsignedBigInteger('consecutivo_recibo_id')->nullable()->after('numero_recibo');
                $table->index('consecutivo_recibo_id', 'pmc_consecutivo_recibo_id_idx');
                $table->foreign('consecutivo_recibo_id', 'pmc_consecutivo_recibo_id_fk')
                    ->references('id')
                    ->on('consecutivos_recibos_pedidos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_ancho_general', 'consecutivo_recibo_id')) {
                try {
                    $table->dropForeign('pag_consecutivo_recibo_id_fk');
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex('pag_consecutivo_recibo_id_idx');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('consecutivo_recibo_id');
            }
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_metraje_color', 'consecutivo_recibo_id')) {
                try {
                    $table->dropForeign('pmc_consecutivo_recibo_id_fk');
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropIndex('pmc_consecutivo_recibo_id_idx');
                } catch (\Throwable $e) {
                }
                $table->dropColumn('consecutivo_recibo_id');
            }
        });
    }
};
