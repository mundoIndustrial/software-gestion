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
        Schema::create('pedido_observaciones_despacho', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->uuid('uuid')->unique();
            $table->text('contenido');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nombre')->nullable();
            $table->string('usuario_rol')->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('estado')->default(0);
            $table->timestamps();

            $table->index(['pedido_produccion_id', 'estado'], 'pobsd_pedido_estado_idx');
            $table->index(['pedido_produccion_id', 'usuario_rol'], 'pobsd_pedido_rol_idx');
            $table->foreign('pedido_produccion_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_observaciones_despacho');
    }
};
