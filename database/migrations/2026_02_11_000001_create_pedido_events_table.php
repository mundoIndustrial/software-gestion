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
        Schema::create('pedido_events', function (Blueprint $table) {
            $table->id();
            
            // ID del aggregate (pedido)
            $table->string('aggregate_id', 50)->index();
            
            // Tipo de evento (nombre completo de la clase)
            $table->string('event_type', 255)->index();
            
            // Datos del evento serializados
            $table->json('event_data');
            
            // Versión del evento en el aggregate
            $table->integer('version')->index();
            
            // Timestamp de cuando ocurrió el evento
            $table->timestamp('occurred_at')->index();
            
            // Metadata adicional (usuario, IP, etc.)
            $table->json('metadata')->nullable();
            
            // Timestamps estándar
            $table->timestamps();
            
            // Índices compuestos para consultas optimizadas
            $table->index(['aggregate_id', 'version'], 'idx_aggregate_version');
            $table->index(['event_type', 'occurred_at'], 'idx_event_type_date');
            $table->index(['occurred_at'], 'idx_occurred_at');
        });
        
        // Crear tabla para snapshots (optimización de Event Sourcing)
        Schema::create('pedido_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_id', 50)->unique();
            $table->integer('version');
            $table->json('state'); // Estado completo del aggregate
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_snapshots');
        Schema::dropIfExists('pedido_events');
    }
};
