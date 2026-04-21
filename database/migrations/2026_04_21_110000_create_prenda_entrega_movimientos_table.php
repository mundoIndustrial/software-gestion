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
        Schema::create('prenda_entrega_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->unsignedBigInteger('consecutivo_recibo_id');
            $table->unsignedInteger('cantidad_entregada')->default(1);
            $table->timestamp('fecha_entrega');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');

            $table->foreign('consecutivo_recibo_id')
                ->references('id')
                ->on('consecutivos_recibos_pedidos')
                ->onDelete('cascade');

            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index('prenda_pedido_id');
            $table->index('consecutivo_recibo_id');
            $table->index('fecha_entrega');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_entrega_movimientos');
    }
};

