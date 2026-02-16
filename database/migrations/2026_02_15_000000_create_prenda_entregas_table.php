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
        Schema::create('prenda_entregas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->boolean('entregado')->default(false);
            $table->timestamp('fecha_entrega')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable(); // Usuario que marcó como entregado
            $table->timestamps();

            // Foreign keys
            $table->foreign('prenda_pedido_id')
                  ->references('id')
                  ->on('prendas_pedido')
                  ->onDelete('cascade');
                  
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Índices para mejorar rendimiento
            $table->index('prenda_pedido_id');
            $table->index('entregado');
            $table->index('fecha_entrega');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_entregas');
    }
};
