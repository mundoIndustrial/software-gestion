<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disenos_logo_pedido', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proceso_prenda_detalle_id');
            $table->string('url', 2048);
            $table->timestamps();

            $table->foreign('proceso_prenda_detalle_id')
                ->references('id')
                ->on('pedidos_procesos_prenda_detalles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disenos_logo_pedido');
    }
};
