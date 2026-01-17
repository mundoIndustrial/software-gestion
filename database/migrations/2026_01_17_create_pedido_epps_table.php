<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla relación entre Pedidos de Producción y EPP
     * Almacena los EPP agregados a cada pedido con sus características
     */
    public function up(): void
    {
        Schema::create('pedido_epps', function (Blueprint $table) {
            $table->id();
            
            // Relación con pedido
            $table->unsignedBigInteger('pedido_id');
            
            // Relación con EPP
            $table->unsignedBigInteger('epp_id');
            
            // Talla/medida del EPP
            $table->string('talla', 20)->nullable();
            
            // Cantidad solicitada
            $table->unsignedInteger('cantidad')->default(1);
            
            // Observaciones específicas para este EPP en el pedido
            $table->longText('observaciones')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');
            
            $table->foreign('epp_id')
                ->references('id')
                ->on('epps')
                ->onDelete('restrict');
            
            // Índices
            $table->index('pedido_id');
            $table->index('epp_id');
            $table->unique(['pedido_id', 'epp_id'], 'unique_pedido_epp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_epps');
    }
};
