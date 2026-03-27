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
        // Drop prenda_fotos_logo_pedido table
        if (Schema::hasTable('prenda_fotos_logo_pedido')) {
            Schema::dropIfExists('prenda_fotos_logo_pedido');
        }

        // Drop prenda_logo_fotos table
        if (Schema::hasTable('prenda_logo_fotos')) {
            Schema::dropIfExists('prenda_logo_fotos');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate prenda_fotos_logo_pedido table
        Schema::create('prenda_fotos_logo_pedido', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('prenda_pedido_id')->unsigned();
            $table->string('ruta_original', 255)->nullable();
            $table->string('ruta_webp', 255)->nullable();
            $table->string('ruta_miniatura', 255)->nullable();
            $table->integer('orden')->nullable();
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamaño')->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->longText('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Recreate prenda_logo_fotos table
        Schema::create('prenda_logo_fotos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('prenda_pedido_id')->unsigned();
            $table->bigInteger('pedido_produccion_id')->unsigned();
            $table->string('ruta_original', 255)->nullable();
            $table->string('ruta_webp', 255)->nullable();
            $table->string('ruta_miniatura', 255)->nullable();
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamaño')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
