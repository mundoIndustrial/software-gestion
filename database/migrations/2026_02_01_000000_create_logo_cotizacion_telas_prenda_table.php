<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla para almacenar la información de Color, Tela y Referencia
     * de cada prenda en una cotización de logo INDIVIDUAL
     */
    public function up(): void
    {
        Schema::create('logo_cotizacion_telas_prenda', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('logo_cotizacion_id');
            $table->unsignedBigInteger('prenda_cot_id');
            $table->string('tela')->nullable()->comment('Nombre de la tela');
            $table->string('color')->nullable()->comment('Color de la tela');
            $table->string('ref')->nullable()->comment('Referencia de la tela');
            $table->string('img')->nullable()->comment('Ruta de la imagen de la tela (ej: storage/app/public/cotizaciones/2/telas/img_prueba.webp)');
            $table->timestamps();

            // Índices para mejor rendimiento
            $table->index('logo_cotizacion_id');
            $table->index('prenda_cot_id');

            // Relaciones externas (foreign keys)
            $table->foreign('logo_cotizacion_id')
                ->references('id')
                ->on('logo_cotizaciones')
                ->onDelete('cascade');

            $table->foreign('prenda_cot_id')
                ->references('id')
                ->on('prendas_cot')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_cotizacion_telas_prenda');
    }
};
