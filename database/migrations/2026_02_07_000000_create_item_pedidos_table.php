<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_pedidos', function (Blueprint $table) {
            $table->id();
            
            // Relación al pedido
            $table->unsignedBigInteger('pedido_id');
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedidos')
                ->onDelete('cascade');
            
            // Referencia al item (Prenda o EPP)
            $table->unsignedBigInteger('referencia_id');
            
            // Tipo de item
            $table->enum('tipo', ['prenda', 'epp']);
            
            // Orden en el pedido (1, 2, 3, ...)
            $table->unsignedInteger('orden');
            
            // Datos del item
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            
            // Datos para presentación en frontend (JSON)
            $table->json('datos_presentacion')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Índices
            $table->index(['pedido_id', 'orden']);
            $table->index(['tipo']);
            $table->unique(['pedido_id', 'referencia_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_pedidos');
    }
};
