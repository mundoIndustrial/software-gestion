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
        Schema::create('prendas_pedido_novedades_recibo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->string('numero_recibo', 50)->nullable();
            $table->text('novedad_texto');
            $table->enum('tipo_novedad', ['observacion', 'problema', 'cambio', 'aprobacion', 'rechazo', 'correccion'])->default('observacion');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->enum('estado_novedad', ['activa', 'resuelta', 'pendiente'])->default('activa');
            $table->text('notas_adicionales')->nullable();
            $table->timestamp('fecha_resolucion')->nullable();
            $table->unsignedBigInteger('resuelto_por')->nullable();
            
            // Índices para optimizar consultas (nombres cortos para MySQL)
            $table->index(['prenda_pedido_id', 'numero_recibo'], 'pp_novedades_recibo_idx');
            $table->index(['tipo_novedad', 'estado_novedad'], 'pp_novedades_tipo_estado_idx');
            $table->index(['creado_en'], 'pp_novedades_creado_en_idx');
            $table->index(['creado_por'], 'pp_novedades_creado_por_idx');
            
            // Foreign keys
            $table->foreign('prenda_pedido_id')
                  ->references('id')
                  ->on('prendas_pedido')
                  ->onDelete('cascade');
                  
            $table->foreign('creado_por')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->foreign('resuelto_por')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prendas_pedido_novedades_recibo');
    }
};
