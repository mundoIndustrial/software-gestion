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
        Schema::create('logo_pedidos', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('pedido_id')
                ->constrained('pedidos_produccion')
                ->onDelete('cascade');
            
            $table->foreignId('logo_cotizacion_id')
                ->nullable()
                ->constrained('logo_cotizaciones')
                ->onDelete('set null');
            
            // Número de pedido LOGO (LOGO-00001, LOGO-00002, etc.)
            $table->string('numero_pedido')->unique()->index();
            
            // Datos del logo
            $table->longText('descripcion')->nullable();
            
            // Técnicas (JSON array)
            $table->json('tecnicas')->nullable();
            
            // Observaciones de técnicas
            $table->longText('observaciones_tecnicas')->nullable();
            
            // Ubicaciones (JSON array con estructura)
            // [{ubicacion: "CAMISA", opciones: ["PECHO", "ESPALDA"], observaciones: "..."}]
            $table->json('ubicaciones')->nullable();
            
            // Metadata
            $table->timestamps();
            
            // Índices para búsquedas comunes
            $table->index('pedido_id');
            $table->index('numero_pedido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_pedidos');
    }
};
