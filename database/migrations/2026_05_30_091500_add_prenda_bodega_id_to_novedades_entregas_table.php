<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('novedades_entregas', function (Blueprint $table) {
            $table->unsignedBigInteger('prenda_bodega_id')->nullable()->after('prenda_pedido_id');
            $table->index('prenda_bodega_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('novedades_entregas', function (Blueprint $table) {
            $table->dropIndex('novedades_entregas_prenda_bodega_id_index');
            $table->dropColumn('prenda_bodega_id');
        });
    }
};
