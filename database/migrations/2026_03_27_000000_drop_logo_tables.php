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
        // Desabilitar verificación de foreign keys temporalmente
        Schema::disableForeignKeyConstraints();

        // Eliminar tablas en orden de dependencias
        Schema::dropIfExists('logo_observacion_prenda_cot');
        Schema::dropIfExists('logo_prenda_cot');
        Schema::dropIfExists('logo_cotizaciones_fotos');
        Schema::dropIfExists('logo_fotos_cot');

        // Reabilitar verificación de foreign keys
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recrear tablas en orden inverso
        Schema::create('logo_fotos_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('logo_cotizacion_id');
            $table->string('ruta_original', 500)->nullable();
            $table->string('ruta_webp', 500)->nullable();
            $table->string('ruta_miniatura', 500)->nullable();
            $table->integer('orden')->default(0);
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamaño')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('logo_cotizacion_id');
            $table->index('orden');
            $table->foreign('logo_cotizacion_id')
                ->references('id')
                ->on('logo_cotizaciones')
                ->onDelete('cascade');
        });

        Schema::create('logo_cotizaciones_fotos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('logo_cotizacion_id');
            $table->string('ruta_original', 255)->nullable();
            $table->string('ruta_webp', 255)->nullable();
            $table->string('ruta_miniatura', 255)->nullable();
            $table->integer('orden')->default(0);
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamaño')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('logo_cotizacion_id');
            $table->foreign('logo_cotizacion_id')
                ->references('id')
                ->on('logo_cotizaciones')
                ->onDelete('cascade');
        });

        Schema::create('logo_prenda_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('logo_cot_id');
            $table->string('nombre_producto', 255)->nullable();
            $table->longText('descripcion')->nullable();
            $table->integer('cantidad')->nullable();
            $table->timestamps();

            $table->foreign('logo_cot_id')
                ->references('id')
                ->on('logo_cotizaciones')
                ->onDelete('cascade');
        });

        Schema::create('logo_observacion_prenda_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->unsignedBigInteger('prenda_cot_id');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['cotizacion_id', 'prenda_cot_id']);
            $table->index(['cotizacion_id', 'prenda_cot_id']);
            $table->foreign('cotizacion_id')
                ->references('id')
                ->on('cotizaciones')
                ->onDelete('cascade');
            $table->foreign('prenda_cot_id')
                ->references('id')
                ->on('prendas_cot')
                ->onDelete('cascade');
        });
    }
};
