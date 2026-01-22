<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_pedido_tallas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->enum('genero', ['DAMA', 'CABALLERO', 'UNISEX']);
            $table->string('talla', 50);  // XS, S, M, L, XL, 28, 30, 32, etc.
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();

            // Foreign key
            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');

            // Índices para queries rápidas
            $table->index('prenda_pedido_id');
            $table->unique(['prenda_pedido_id', 'genero', 'talla']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_pedido_tallas');
    }
};
