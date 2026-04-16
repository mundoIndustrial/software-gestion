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
        Schema::create('observaciones_recibos_procesos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->string('tipo_proceso', 100);
            $table->text('observacion')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            $table->unique(
                ['pedido_produccion_id', 'prenda_pedido_id', 'tipo_proceso'],
                'obs_rec_proc_unq'
            );
            $table->index(['pedido_produccion_id', 'prenda_pedido_id'], 'obs_rec_proc_pedido_prenda_idx');

            $table->foreign('pedido_produccion_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');

            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');

            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observaciones_recibos_procesos');
    }
};
