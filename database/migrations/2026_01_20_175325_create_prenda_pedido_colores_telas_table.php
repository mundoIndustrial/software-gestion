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
        Schema::create('prenda_pedido_colores_telas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('tela_id')->nullable();
            $table->timestamps();
            
            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');
            
            $table->foreign('color_id')
                ->references('id')
                ->on('colores_prenda')
                ->onDelete('set null');
            
            $table->foreign('tela_id')
                ->references('id')
                ->on('telas_prenda')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_pedido_colores_telas');
    }
};
