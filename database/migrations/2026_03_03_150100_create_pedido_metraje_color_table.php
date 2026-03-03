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
        Schema::create('pedido_metraje_color', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->string('color');
            $table->decimal('metraje', 10, 2)->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('pedido_produccion_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');
            
            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');

            // Índices
            $table->unique(['pedido_produccion_id', 'prenda_pedido_id', 'color'], 'unique_metraje_color');
            $table->index('pedido_produccion_id', 'idx_pedido');
            $table->index('prenda_pedido_id', 'idx_prenda');
            $table->index('color', 'idx_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_metraje_color');
    }
};
