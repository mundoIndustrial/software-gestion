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
        Schema::create('prenda_variaciones_disponibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_prenda_id')->constrained('tipos_prenda')->onDelete('cascade');
            
            // Booleanos para cada variación
            $table->boolean('tiene_manga')->default(false);
            $table->boolean('tiene_bolsillos')->default(false);
            $table->boolean('tiene_broche')->default(false);
            $table->boolean('tiene_reflectivo')->default(false);
            $table->boolean('tiene_cuello')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_variaciones_disponibles');
    }
};
