<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea tablas para almacenar logos de pedidos
     * Estructura equivalente a logo_cotizaciones pero para pedidos
     * Las imágenes solo copian URLs de cotizaciones (sin duplicar archivos)
     */
    public function up(): void
    {
        // 1. Tabla logo_ped - Información principal del logo del pedido
        Schema::create('logo_ped', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->longText('descripcion')->nullable();
            $table->string('ubicacion')->nullable();
            $table->json('observaciones_generales')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pedido_produccion_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');
            $table->index('pedido_produccion_id');
        });

        // 2. Tabla logo_fotos_ped - Fotos del logo (URLs copiadas de cotizaciones)
        Schema::create('logo_fotos_ped', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('logo_ped_id');
            $table->string('ruta_original')->nullable()->comment('URL copiada de cotizacion');
            $table->string('ruta_webp')->nullable();
            $table->string('ruta_miniatura')->nullable();
            $table->integer('orden')->default(1);
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamaño')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('logo_ped_id')
                ->references('id')
                ->on('logo_ped')
                ->onDelete('cascade');
            $table->index('logo_ped_id');
            $table->index('orden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_fotos_ped');
        Schema::dropIfExists('logo_ped');
    }
};
