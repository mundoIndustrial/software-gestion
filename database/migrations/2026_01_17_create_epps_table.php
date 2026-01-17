<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla catálogo de EPP (Equipo de Protección Personal)
     * Almacena los productos EPP disponibles
     */
    public function up(): void
    {
        Schema::create('epps', function (Blueprint $table) {
            $table->id();
            
            // Código único del EPP
            $table->string('codigo', 50)->unique();
            
            // Nombre del EPP
            $table->string('nombre', 255);
            
            // Categoría del EPP (cabeza, manos, pies, etc.)
            $table->string('categoria', 100);
            
            // Descripción detallada
            $table->longText('descripcion')->nullable();
            
            // Estado del EPP
            $table->boolean('activo')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Soft deletes
            $table->softDeletes();
            
            // Índices
            $table->index('codigo');
            $table->index('categoria');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epps');
    }
};
