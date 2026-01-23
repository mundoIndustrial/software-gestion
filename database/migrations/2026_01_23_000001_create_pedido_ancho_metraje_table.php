<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla para almacenar ancho y metraje de los pedidos de insumos.
     * Se relaciona con la tabla pedidos_produccion.
     */
    public function up(): void
    {
        Schema::create('pedido_ancho_metraje', function (Blueprint $table) {
            $table->id();
            
            // Relación con pedidos_produccion
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->foreign('pedido_produccion_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');
            
            // Campos de ancho y metraje
            $table->decimal('ancho', 8, 2)->comment('Ancho en centímetros');
            $table->decimal('metraje', 8, 2)->comment('Metraje en metros');
            
            // Auditoría
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->foreign('creado_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('actualizado_por')->references('id')->on('users')->onDelete('set null');
            
            // Timestamps
            $table->timestamps();
            
            // Índices
            $table->index('pedido_produccion_id');
            $table->index('created_at');
            
            // Restricción única: solo un registro por pedido
            $table->unique('pedido_produccion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_ancho_metraje');
    }
};
