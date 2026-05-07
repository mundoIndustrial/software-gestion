<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            if (!Schema::hasColumn('procesos_prenda', 'prenda_bodega_id')) {
                $table->unsignedBigInteger('prenda_bodega_id')->nullable()->after('prenda_pedido_id');
                $table->index('prenda_bodega_id', 'procesos_prenda_prenda_bodega_id_idx');
                $table->foreign('prenda_bodega_id', 'procesos_prenda_prenda_bodega_fk')
                    ->references('id')
                    ->on('prenda_bodega')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            if (Schema::hasColumn('procesos_prenda', 'prenda_bodega_id')) {
                $table->dropForeign('procesos_prenda_prenda_bodega_fk');
                $table->dropIndex('procesos_prenda_prenda_bodega_id_idx');
                $table->dropColumn('prenda_bodega_id');
            }
        });
    }
};

