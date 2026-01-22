<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla relacional para manejar tallas de procesos de forma normalizada.
     * NO usa JSON, permitiendo:
     * - Soportar DAMA, CABALLERO, UNISEX como géneros
     * - Diferentes cantidades por talla
     * - Que una talla tenga proceso y otra no
     * - Que las cantidades difieran por talla
     */
    public function up(): void
    {
        Schema::create('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            $table->id();
            
            // Relación con el proceso de prenda
            $table->unsignedBigInteger('proceso_prenda_detalle_id');
            
            // Género: DAMA, CABALLERO, UNISEX
            $table->enum('genero', ['DAMA', 'CABALLERO', 'UNISEX']);
            
            // Talla (XS, S, M, L, XL, etc. o tallas numéricas 28, 30, 32, etc.)
            $table->string('talla', 50);
            
            // Cantidad de prendas con esa talla + género en este proceso
            $table->unsignedInteger('cantidad')->default(0);
            
            $table->timestamps();

            // Foreign key
            $table->foreign('proceso_prenda_detalle_id')
                ->references('id')
                ->on('pedidos_procesos_prenda_detalles')
                ->onDelete('cascade');

            // Índices para queries rápidas
            $table->index('proceso_prenda_detalle_id');
            $table->unique(['proceso_prenda_detalle_id', 'genero', 'talla'], 'uq_proc_prenda_genero_talla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_procesos_prenda_tallas');
    }
};
