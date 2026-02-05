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
        Schema::create('epp_bodega_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_produccion_id')->nullable();
            $table->unsignedBigInteger('recibo_prenda_id')->nullable();
            $table->string('numero_pedido');
            $table->string('talla');
            $table->string('prenda_nombre')->nullable();
            $table->string('asesor')->nullable();
            $table->string('empresa')->nullable();
            $table->integer('cantidad')->nullable();
            $table->string('pendientes')->nullable();
            $table->longText('observaciones_bodega')->nullable();
            $table->dateTime('fecha_entrega')->nullable();
            $table->enum('estado_bodega', ['Pendiente', 'Entregado'])->nullable();
            $table->unsignedBigInteger('usuario_bodega_id')->nullable();
            $table->string('usuario_bodega_nombre')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('numero_pedido');
            $table->index('talla');
            $table->index('estado_bodega');
            $table->unique(['numero_pedido', 'talla', 'prenda_nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epp_bodega_detalles');
    }
};
