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
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            // Agregar columna consecutivo_recibo_id después de numero_recibo
            $table->unsignedBigInteger('consecutivo_recibo_id')->nullable()->after('numero_recibo');
            
            // Agregar índice para mejorar el rendimiento de las consultas
            $table->index('consecutivo_recibo_id');
            
            // Agregar foreign key constraint (opcional, pero recomendado)
            $table->foreign('consecutivo_recibo_id')
                  ->references('id')
                  ->on('consecutivos_recibos_pedidos')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            // Eliminar foreign key primero
            $table->dropForeign(['consecutivo_recibo_id']);
            
            // Eliminar índice
            $table->dropIndex(['consecutivo_recibo_id']);
            
            // Eliminar columna
            $table->dropColumn('consecutivo_recibo_id');
        });
    }
};
