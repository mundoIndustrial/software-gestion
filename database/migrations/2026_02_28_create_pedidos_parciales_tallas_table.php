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
        Schema::create('pedidos_parciales_tallas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_parcial_id');
            $table->string('talla', 50);
            $table->integer('cantidad');
            $table->enum('genero', ['DAMA', 'CABALLERO', 'UNISEX'])->nullable();
            $table->timestamps();

            $table->foreign('pedido_parcial_id')->references('id')->on('pedidos_parciales')->onDelete('cascade');
            $table->unique(['pedido_parcial_id', 'talla', 'genero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_parciales_tallas');
    }
};
