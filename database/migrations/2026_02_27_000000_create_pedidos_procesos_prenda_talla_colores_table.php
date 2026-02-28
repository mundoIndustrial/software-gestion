<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea tabla para rastrear colores en cada talla de un proceso
     * Similar a prenda_pedido_talla_colores pero para procesos
     */
    public function up(): void
    {
        Schema::create('pedidos_procesos_prenda_talla_colores', function (Blueprint $table) {
            $table->id();
            
            // RELACIÓN PRINCIPAL: Talla del proceso a la que pertenece este color
            $table->unsignedBigInteger('pedidos_procesos_prenda_talla_id');
            $table->foreign('pedidos_procesos_prenda_talla_id', 'ppptc_talla_fk')
                ->references('id')
                ->on('pedidos_procesos_prenda_tallas')
                ->onDelete('cascade');
            
            // COLOR - Nombre del color
            $table->string('color_nombre', 100)->nullable()->comment('Nombre del color');
            
            // TELA - Nombre de la tela
            $table->string('tela_nombre', 100)->nullable()->comment('Nombre de la tela');
            
            // CANTIDAD de prendas con este color que se procesan
            $table->unsignedInteger('cantidad')->default(1)->comment('Cantidad de prendas a procesar con este color');
            
            // AUDITORÍA
            $table->timestamps();
            
            // ÍNDICES PARA QUERIES COMUNES con nombres cortos
            $table->index('pedidos_procesos_prenda_talla_id', 'ppptc_talla_idx');
            $table->index('color_nombre', 'ppptc_color_idx');
            $table->index('tela_nombre', 'ppptc_tela_idx');
            
            // ÍNDICE COMPUESTO PARA REPORTES
            $table->index(['color_nombre', 'tela_nombre'], 'ppptc_color_tela_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_procesos_prenda_talla_colores');
    }
};
