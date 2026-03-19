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
        Schema::create('plooter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consecutivo_recibo_pedido_id');
            $table->dateTime('fecha_envio')->nullable()->comment('Fecha cuando se marcó el checkbox');
            $table->dateTime('fecha_llegada')->nullable()->comment('Fecha de llegada del recibo');
            $table->string('notas')->nullable()->comment('Notas adicionales');
            $table->timestamps();
            
            // Foreign key
            $table->foreign('consecutivo_recibo_pedido_id')
                ->references('id')
                ->on('consecutivos_recibos_pedidos')
                ->onDelete('cascade');
            
            // Índices para búsquedas rápidas
            $table->index('consecutivo_recibo_pedido_id');
            $table->index('fecha_envio');
            $table->index('fecha_llegada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plooter');
    }
};
