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
        Schema::create('entregas_bodega_costura', function (Blueprint $table) {
    $table->id(); // crea un campo id autoincremental como PK
    $table->unsignedInteger('pedido');
    $table->string('cliente', 84);
    $table->text('prenda');
    $table->text('descripcion');
    $table->string('talla', 69);
    $table->integer('cantidad_entregada');
    $table->date('fecha_entrega');
    $table->string('costurero', 61);
    $table->string('mes_ano', 65);

    // solo llave foránea, no primary
    $table->foreign('pedido')
          ->references('pedido')
          ->on('tabla_original_bodega')
          ->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregas_bodega_costura');
    }
};
