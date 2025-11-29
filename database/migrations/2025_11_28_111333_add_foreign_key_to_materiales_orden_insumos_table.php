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
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            $table->foreign('tabla_original_pedido')->references('pedido')->on('tabla_original')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            $table->dropForeign(['tabla_original_pedido']);
        });
    }
};
