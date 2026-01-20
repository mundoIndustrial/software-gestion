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
        Schema::table('prenda_fotos_tela_pedido', function (Blueprint $table) {
            $table->unsignedBigInteger('prenda_pedido_colores_telas_id')->nullable()->after('prenda_pedido_id');
            
            $table->foreign('prenda_pedido_colores_telas_id')
                ->references('id')
                ->on('prenda_pedido_colores_telas')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_fotos_tela_pedido', function (Blueprint $table) {
            $table->dropForeign(['prenda_pedido_colores_telas_id']);
            $table->dropColumn('prenda_pedido_colores_telas_id');
        });
    }
};
