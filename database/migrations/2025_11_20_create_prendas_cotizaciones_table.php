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
        Schema::create('prendas_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            
            // INFORMACIÓN DE LA PRENDA
            $table->string('nombre_producto')->nullable(); // CAMISA, CAMISETA, POLO, etc.
            $table->longText('descripcion')->nullable(); // Descripción detallada
            
            // TALLAS
            $table->json('tallas')->nullable(); // Array de tallas seleccionadas: ["XS", "S", "M", "L", "DAMA 10", "CABALLERO 32"]
            
            // FOTOS
            $table->json('fotos')->nullable(); // Array de URLs de fotos de la prenda
            $table->string('imagen_tela')->nullable(); // URL de la imagen de la tela
            
            // ESTADO
            $table->string('estado')->default('Pendiente'); // Pendiente, Cotizado, Aceptado, Rechazado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prendas_cotizaciones');
    }
};
