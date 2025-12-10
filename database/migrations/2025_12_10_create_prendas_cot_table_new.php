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
        Schema::create('prendas_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->string('nombre_producto', 255);
            $table->text('descripcion')->nullable();
            $table->integer('cantidad')->default(1);
            
            // Campos de tipo de prenda
            $table->string('tipo_prenda')->nullable()->comment('Tipo de prenda: CAMISA, PANTALON, POLO, etc.');
            
            // Campos específicos para JEAN/PANTALÓN
            $table->boolean('es_jean_pantalon')->default(false)->comment('¿Es jean o pantalón?');
            $table->string('tipo_jean_pantalon')->nullable()->comment('JEAN, PANTALON, OTRO');
            
            // Campos de variantes principales
            $table->string('genero')->nullable()->comment('dama, caballero, niño, unisex');
            $table->string('color')->nullable()->comment('Color principal');
            
            // Campos de opciones
            $table->boolean('tiene_bolsillos')->default(false)->comment('¿Tiene bolsillos?');
            $table->text('obs_bolsillos')->nullable()->comment('Observaciones de bolsillos');
            
            $table->boolean('aplica_manga')->default(false)->comment('¿Aplica manga?');
            $table->string('tipo_manga')->nullable()->comment('Tipo de manga');
            $table->text('obs_manga')->nullable()->comment('Observaciones de manga');
            
            $table->boolean('aplica_broche')->default(false)->comment('¿Aplica broche?');
            $table->unsignedBigInteger('tipo_broche_id')->nullable()->comment('ID del tipo de broche');
            $table->text('obs_broche')->nullable()->comment('Observaciones de broche');
            
            $table->boolean('tiene_reflectivo')->default(false)->comment('¿Tiene reflectivo?');
            $table->text('obs_reflectivo')->nullable()->comment('Observaciones de reflectivo');
            
            // Campo de descripción adicional
            $table->text('descripcion_adicional')->nullable()->comment('Descripción adicional de variantes');
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('cotizacion_id')
                ->references('id')
                ->on('cotizaciones')
                ->onDelete('cascade');

            // Índices
            $table->index('cotizacion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prendas_cot');
    }
};
