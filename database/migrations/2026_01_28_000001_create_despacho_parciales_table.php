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
        Schema::create('despacho_parciales', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->unsignedBigInteger('pedido_id');
            $table->foreign('pedido_id')->references('id')->on('pedidos_produccion')->onDelete('cascade');
            
            // Datos del ítem despachado
            $table->enum('tipo_item', ['prenda', 'epp'])->comment('Tipo de ítem: prenda o epp');
            $table->unsignedBigInteger('item_id')->comment('ID del ítem (prenda_pedido_id o epp_pedido_id)');
            $table->unsignedBigInteger('talla_id')->nullable()->comment('ID de la talla (para prendas)');
            
            // Pendiente inicial
            $table->integer('pendiente_inicial')->default(0)->comment('Cantidad pendiente al inicio del despacho');
            
            // Parciales de despacho (primera entrega)
            $table->integer('parcial_1')->default(0)->comment('Cantidad despachada en primer envío');
            $table->integer('pendiente_1')->default(0)->comment('Cantidad pendiente después del primer envío');
            
            // Parciales de despacho (segunda entrega)
            $table->integer('parcial_2')->default(0)->comment('Cantidad despachada en segundo envío');
            $table->integer('pendiente_2')->default(0)->comment('Cantidad pendiente después del segundo envío');
            
            // Parciales de despacho (tercera entrega)
            $table->integer('parcial_3')->default(0)->comment('Cantidad despachada en tercer envío');
            $table->integer('pendiente_3')->default(0)->comment('Cantidad pendiente después del tercer envío');
            
            // Información del despacho
            $table->text('observaciones')->nullable()->comment('Observaciones sobre el despacho');
            $table->timestamp('fecha_despacho')->useCurrent()->comment('Fecha y hora del despacho');
            
            // Auditoría
            $table->unsignedBigInteger('usuario_id')->nullable()->comment('Usuario que registró el despacho');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['pedido_id', 'tipo_item']);
            $table->index(['item_id', 'tipo_item']);
            $table->index('fecha_despacho');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despacho_parciales');
    }
};
