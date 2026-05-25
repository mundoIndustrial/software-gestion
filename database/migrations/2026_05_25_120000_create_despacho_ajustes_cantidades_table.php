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
        Schema::create('despacho_ajustes_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->string('row_hash', 32);
            $table->enum('tipo_item', ['prenda', 'epp']);
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('talla_id');
            $table->unsignedBigInteger('genero_id')->nullable();

            // Revision secuencial por fila (pendiente_1, pendiente_2, ...)
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedInteger('cantidad_base');
            $table->unsignedInteger('cantidad_ajustada');
            $table->unsignedInteger('diferencia');

            // pendiente: propuesta activa, aplicada: impacta origen, descartada: historico
            $table->enum('estado', ['pendiente', 'aplicada', 'descartada'])->default('pendiente');
            $table->text('motivo')->nullable();

            $table->unsignedBigInteger('creado_por');
            $table->unsignedBigInteger('aplicado_por')->nullable();
            $table->timestamp('aplicado_en')->nullable();
            $table->timestamps();

            $table->index(['pedido_produccion_id', 'estado'], 'dad_pedido_estado_idx');
            $table->index(['pedido_produccion_id', 'tipo_item'], 'dad_pedido_tipo_idx');
            $table->index(['pedido_produccion_id', 'row_hash'], 'dad_pedido_row_hash_idx');
            $table->index(['pedido_produccion_id', 'tipo_item', 'item_id', 'talla_id', 'genero_id'], 'dad_fila_idx');

            $table->unique(
                ['pedido_produccion_id', 'row_hash', 'revision'],
                'dad_row_hash_revision_uq'
            );

            $table->unique(
                ['pedido_produccion_id', 'tipo_item', 'item_id', 'talla_id', 'genero_id', 'revision'],
                'dad_fila_revision_uq'
            );

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
        Schema::dropIfExists('despacho_ajustes_detalles');
    }
};
