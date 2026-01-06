<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta tabla vincula un LogoCotizacion con múltiples tipos de técnicas.
     * Por ejemplo, una cotización puede tener BORDADO, ESTAMPADO y SUBLIMADO
     */
    public function up(): void
    {
        Schema::create('logo_cotizacion_tecnicas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('logo_cotizacion_id', 'lct_logo_cot_fk')
                ->constrained('logo_cotizaciones')
                ->onDelete('cascade');
            $table->foreignId('tipo_logo_cotizacion_id', 'lct_tipo_fk')
                ->constrained('tipo_logo_cotizaciones')
                ->onDelete('restrict');
            
            // Datos específicos de la técnica para esta cotización
            $table->text('observaciones_tecnica')->nullable(); // Obs específicas de esta técnica
            $table->text('instrucciones_especiales')->nullable(); // Instrucciones de aplicación
            
            // Control
            $table->integer('orden')->default(0); // Orden de presentación
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índices
            $table->unique(['logo_cotizacion_id', 'tipo_logo_cotizacion_id'], 'lct_unique');
            $table->index('tipo_logo_cotizacion_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_cotizacion_tecnicas');
    }
};
