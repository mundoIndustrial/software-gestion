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
        Schema::create('costos_prenda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prenda_cotizacion_id')->constrained('prendas_cotizacion')->onDelete('cascade');
            $table->foreignId('componente_prenda_id')->constrained('componentes_prenda')->onDelete('cascade');
            $table->decimal('costo', 12, 2);
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['prenda_cotizacion_id', 'componente_prenda_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costos_prenda');
    }
};
