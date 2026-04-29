<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'prenda_bodega_id')) {
                $table->unsignedBigInteger('prenda_bodega_id')->nullable()->after('prenda_id');
                $table->index('prenda_bodega_id', 'idx_crp_prenda_bodega_id');
                $table->foreign('prenda_bodega_id', 'fk_crp_prenda_bodega_id')
                    ->references('id')
                    ->on('prenda_bodega')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'prenda_bodega_id')) {
                $table->dropForeign('fk_crp_prenda_bodega_id');
                $table->dropIndex('idx_crp_prenda_bodega_id');
                $table->dropColumn('prenda_bodega_id');
            }
        });
    }
};

