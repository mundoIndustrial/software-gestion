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
        Schema::create('pedidos_auditoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedidos_produccion_id');
            $table->foreign('pedidos_produccion_id')->references('id')->on('pedidos_produccion')->onDelete('cascade');
            
            // Tipo de cambio
            $table->enum('tipo_cambio', [
                'CREACION',
                'ACTUALIZACION_DATOS',
                'CAMBIO_PRENDAS',
                'CAMBIO_EPPS',
                'CAMBIO_PROCESOS',
                'CAMBIO_ESTADO',
                'CAMBIO_VARIANTES',
                'CAMBIO_TELAS',
                'CAMBIO_MANGOS_BROCHES',
                'CAMBIO_BOLSILLOS',
                'AGREGADA_IMAGEN_PRENDA',
                'ELIMINADA_IMAGEN_PRENDA',
                'REORDENADAS_IMAGENES_PRENDA',
                'AGREGADA_IMAGEN_PROCESO',
                'ELIMINADA_IMAGEN_PROCESO',
                'REORDENADAS_IMAGENES_PROCESO',
                'CAMBIO_IMAGEN_PRINCIPAL_PROCESO',
                'OTROS'
            ]);
            
            // Detalles del cambio en JSON
            $table->json('detalles')->nullable();
            
            // Información de imágenes
            $table->unsignedBigInteger('prenda_pedido_id')->nullable();
            
            $table->unsignedBigInteger('proceso_prenda_detalle_id')->nullable();
            
            $table->unsignedBigInteger('imagen_id')->nullable();  // ID de imagen agregada/eliminada
            $table->string('ruta_imagen')->nullable();
            
            // Usuario que realizó el cambio
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            
            // Campo anterior/nuevo para comparación
            $table->longText('valor_anterior')->nullable();
            $table->longText('valor_nuevo')->nullable();
            
            // Observaciones adicionales
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para búsquedas rápidas
            $table->index('pedidos_produccion_id');
            $table->index('tipo_cambio');
            $table->index('usuario_id');
            $table->index('prenda_pedido_id');
            $table->index('proceso_prenda_detalle_id');
            $table->index('created_at');
            $table->index(['pedidos_produccion_id', 'tipo_cambio']);
            $table->index(['pedidos_produccion_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_auditoria');
    }
};
