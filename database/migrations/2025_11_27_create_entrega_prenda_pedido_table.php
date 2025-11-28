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
            $table->foreignId('prenda_pedido_id')->constrained('prendas_pedido')->onDelete('cascade');
            $table->string('talla'); // S, M, L, XL, etc.
            $table->integer('cantidad_original'); // Cantidad original de la talla
            $table->string('costurero')->nullable(); // Nombre del costurero
            $table->integer('total_producido_por_talla')->default(0); // Cantidad producida
            $table->integer('total_pendiente_por_talla'); // Cantidad pendiente (se calcula)
            $table->timestamp('fecha_completado')->nullable(); // Fecha de completado
            $table->timestamps();
            $table->softDeletes();

            // Índices para búsquedas rápidas
            $table->index('prenda_pedido_id');
            $table->index('talla');
            $table->index('costurero');
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
