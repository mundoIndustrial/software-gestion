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
        Schema::create('entrega_bodega_corte', function (Blueprint $table) {
            $table->id();
            $table->string('pedido', 73);
            $table->string('cortador', 57);
            $table->integer('cantidad_prendas');
            $table->integer('piezas');
            $table->integer('pasadas');
            $table->integer('etiqueteadas');
            $table->string('etiquetador', 61);
            $table->text('prenda');
            $table->date('fecha_entrega');
            $table->string('mes', 65);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrega_bodega_corte');
    }
};
