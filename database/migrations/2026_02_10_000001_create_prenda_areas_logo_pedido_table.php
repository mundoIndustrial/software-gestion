<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_areas_logo_pedido', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('prenda_pedido_id');
            $table->unsignedBigInteger('proceso_prenda_detalle_id');

            $table->enum('area', [
                'CREACION_DE_ORDEN',
                'PENDIENTE_DISENO',
                'DISENO',
                'PENDIENTE_CONFIRMAR',
                'CORTE_Y_APLIQUE',
                'HACIENDO_MUESTRA',
                'ESTAMPANDO',
                'BORDANDO',
                'ENTREGADO',
                'ANULADO',
                'PENDIENTE',
            ])->default('PENDIENTE');

            $table->text('novedades')->nullable();

            $table->json('fechas_areas')->nullable();

            $table->timestamps();

            $table->unique('proceso_prenda_detalle_id');

            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');

            $table->foreign('proceso_prenda_detalle_id')
                ->references('id')
                ->on('pedidos_procesos_prenda_detalles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_areas_logo_pedido');
    }
};
