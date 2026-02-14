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
        Schema::create('prenda_pedido_talla_colores', function (Blueprint $table) {
            $table->id();
            
            //  RELACIÓN PRINCIPAL: Talla a la que pertenece este color
            $table->unsignedBigInteger('prenda_pedido_talla_id');
            $table->foreign('prenda_pedido_talla_id')
                ->references('id')
                ->on('prenda_pedido_tallas')
                ->onDelete('cascade');
            
            //  TELA - Campo principal con respaldo
            $table->unsignedBigInteger('tela_id')->nullable()->comment('FK a tabla telas (si existe)');
            $table->string('tela_nombre', 100)->nullable()->comment('Nombre de la tela (respaldo si no existe tela_id)');
            //  Sin FK constraint a telas - puede cambiar en futuro
            
            //  COLOR - Información del color
            $table->unsignedBigInteger('color_id')->nullable()->comment('FK a tabla colores (si existe)');
            $table->string('color_nombre', 100)->nullable()->comment('Nombre del color (puede no estar en tabla colores)');
            //  Sin FK constraint a colores - guarda información con nombre como fallback
            
            //  CANTIDAD de prendas con este color
            $table->unsignedInteger('cantidad')->default(1)->comment('Cantidad de prendas con este color');
            
            //  AUDITORÍA
            $table->timestamps();
            
            //  ÍNDICES PARA QUERIES COMUNES
            $table->index('prenda_pedido_talla_id');
            $table->index('tela_id');
            $table->index('color_id');
            $table->index('color_nombre');
            $table->index('tela_nombre');
            
            //  ÍNDICE COMPUESTO PARA REPORTES
            $table->index(['color_nombre', 'tela_nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_pedido_talla_colores');
    }
};
