<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de historial de anexos agregados a pedidos.
 *
 * Registra cada vez que se agrega un anexo (recibo parcial de prenda o EPP)
 * a un pedido existente. Se usa para ordenar los pedidos por "más recientemente
 * modificados con anexos" en las vistas de supervisor, bodega y despacho.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_anexos_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_produccion_id')
                  ->constrained('pedidos_produccion')
                  ->cascadeOnDelete();
            $table->enum('tipo', ['PRENDA', 'EPP'])->comment('Tipo de item anexado');
            $table->unsignedBigInteger('referencia_id')->comment('ID en pedidos_parciales o pedido_epp');
            $table->string('descripcion', 200)->comment('Descripción legible del anexo');
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            // Índices para los JOINs y ORDER BY usados en las vistas
            $table->index('pedido_produccion_id');
            $table->index(['pedido_produccion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_anexos_historial');
    }
};
