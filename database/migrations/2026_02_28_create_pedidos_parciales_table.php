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
        Schema::create('pedidos_parciales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->enum('tipo_recibo', ['COSTURA', 'ESTAMPADO', 'BORDADO', 'REFLECTIVO', 'DTF', 'SUBLIMADO', 'COSTURA-BODEGA']);
            $table->enum('estado', ['PENDIENTE', 'APROBADO', 'EN_PRODUCCION', 'COMPLETADO'])->default('PENDIENTE');
            $table->integer('consecutivo_actual')->nullable();
            $table->integer('consecutivo_inicial')->nullable();
            $table->tinyInteger('activo')->default(0);
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pedido_produccion_id')->references('id')->on('pedidos_produccion');
            $table->foreign('prenda_pedido_id')->references('id')->on('prendas_pedido');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['pedido_produccion_id', 'tipo_recibo']);
            $table->index(['prenda_pedido_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_parciales');
    }
};
