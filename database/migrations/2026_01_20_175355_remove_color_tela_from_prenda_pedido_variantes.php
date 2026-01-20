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
        Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
            // Eliminar las claves foráneas primero
            $table->dropForeign(['color_id']);
            $table->dropForeign(['tela_id']);
            
            // Eliminar las columnas
            $table->dropColumn(['color_id', 'tela_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
            // Restaurar las columnas
            $table->unsignedBigInteger('color_id')->nullable()->after('prenda_pedido_id');
            $table->unsignedBigInteger('tela_id')->nullable()->after('color_id');
            
            // Restaurar las claves foráneas
            $table->foreign('color_id')
                ->references('id')
                ->on('colores_prenda')
                ->onDelete('set null');
            
            $table->foreign('tela_id')
                ->references('id')
                ->on('telas_prenda')
                ->onDelete('set null');
        });
    }
};
