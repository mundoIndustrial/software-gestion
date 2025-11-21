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
        Schema::create('historial_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            $table->string('tipo_cambio'); // 'creacion', 'actualizacion', 'envio', 'aceptacion', 'rechazo'
            $table->text('descripcion')->nullable(); // Descripción del cambio
            $table->json('datos_anteriores')->nullable(); // Datos antes del cambio
            $table->json('datos_nuevos')->nullable(); // Datos después del cambio
            $table->string('usuario_id')->nullable(); // ID del usuario que hizo el cambio
            $table->string('usuario_nombre')->nullable(); // Nombre del usuario
            $table->string('ip_address')->nullable(); // IP del usuario
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('cotizacion_id');
            $table->index('tipo_cambio');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_cotizaciones');
    }
};
