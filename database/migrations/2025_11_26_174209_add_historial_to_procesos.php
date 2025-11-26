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
        // Crear tabla de historial de procesos
        Schema::create('procesos_historial', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_pedido')->index();
            $table->string('proceso')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->string('encargado')->nullable();
            $table->enum('estado_proceso', ['En Progreso', 'Completado', 'En espera', 'Cancelado'])->default('En Progreso');
            $table->timestamps();
            
            // Índices
            $table->index(['numero_pedido', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos_historial');
    }
};
