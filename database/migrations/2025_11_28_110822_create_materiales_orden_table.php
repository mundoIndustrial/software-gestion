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
        Schema::create('materiales_orden_insumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tabla_original_pedido')->nullable();
            $table->string('nombre_material');
            $table->date('fecha_pedido')->nullable();
            $table->date('fecha_llegada')->nullable();
            $table->boolean('recibido')->default(false);
            $table->timestamps();
            
            // Índice para búsquedas rápidas
            $table->index('tabla_original_pedido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materiales_orden_insumos');
    }
};
