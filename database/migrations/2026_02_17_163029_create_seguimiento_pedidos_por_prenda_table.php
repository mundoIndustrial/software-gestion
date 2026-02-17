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
        Schema::create('seguimiento_pedidos_por_prenda', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->unsignedBigInteger('prenda_id');
            $table->enum('tipo_recibo', ['COSTURA', 'ESTAMPADO', 'BORDADO', 'REFLECTIVO', 'DTF', 'SUBLIMADO', 'COSTURA-BODEGA']);
            $table->integer('consecutivo_actual');
            $table->integer('consecutivo_inicial');
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            
            $table->foreign('pedido_produccion_id')->references('id')->on('pedidos_produccion')->onDelete('cascade');
            $table->foreign('prenda_id')->references('id')->on('prendas_pedido')->onDelete('cascade');
            
            $table->index(['pedido_produccion_id', 'prenda_id'], 'seguimiento_prenda_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimiento_pedidos_por_prenda');
    }
};
