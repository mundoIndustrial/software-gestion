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
        Schema::create('logo_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            
            // IMÁGENES DE LOGO
            $table->json('imagenes')->nullable(); // Array de imágenes (máx 5)
            
            // TÉCNICAS
            $table->json('tecnicas')->nullable(); // Array de técnicas: BORDADO, DTF, ESTAMPADO, SUBLIMADO
            $table->longText('observaciones_tecnicas')->nullable(); // Observaciones de técnicas
            
            // UBICACIÓN
            $table->json('ubicaciones')->nullable(); // Array de ubicaciones por sección
            
            // OBSERVACIONES GENERALES
            $table->json('observaciones_generales')->nullable(); // Array de observaciones generales
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_cotizaciones');
    }
};
