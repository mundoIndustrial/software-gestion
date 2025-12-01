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
        Schema::create('entrega_prenda_pedido', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido'); // Número del pedido (relación con pedidos_produccion)
            $table->string('nombre_prenda'); // Nombre de la prenda
            $table->string('talla'); // S, M, L, XL, etc.
            $table->integer('cantidad_original'); // Cantidad original de la talla
            $table->string('costurero')->nullable(); // Nombre del costurero
            $table->integer('total_producido_por_talla')->default(0); // Cantidad producida
            $table->integer('total_pendiente_por_talla'); // Cantidad pendiente (se calcula)
            $table->timestamp('fecha_completado')->nullable(); // Fecha de completado
            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas rápidas
            $table->index('numero_pedido');
            $table->index('nombre_prenda');
            $table->index('talla');
            $table->index('costurero');
            
            // Índice compuesto para búsquedas por pedido + prenda + talla
            $table->index(['numero_pedido', 'nombre_prenda', 'talla']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrega_prenda_pedido');
    }
};
