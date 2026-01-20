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
            // Eliminar la clave foránea primero
            $table->dropForeign(['prenda_pedido_id']);
            
            // Eliminar la columna
            $table->dropColumn('prenda_pedido_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_fotos_tela_pedido', function (Blueprint $table) {
            // Restaurar la columna
            $table->unsignedBigInteger('prenda_pedido_id')->after('id');
            
            // Restaurar la clave foránea
            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');
        });
    }
};
