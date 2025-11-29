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
        Schema::create('talla_metraje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prenda_metraje_id')->constrained('prendas_metraje')->onDelete('cascade');
            $table->foreignId('talla_id')->constrained('tallas')->onDelete('cascade');
            $table->decimal('metros', 8, 2)->comment('Metraje requerido para esta prenda en esta talla');
            $table->timestamps();
            
            // Índice único para evitar duplicados
            $table->unique(['prenda_metraje_id', 'talla_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talla_metraje');
    }
};
