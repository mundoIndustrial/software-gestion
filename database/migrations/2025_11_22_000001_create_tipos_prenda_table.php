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
        Schema::create('tipos_prenda', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // JEAN, CAMISA, CAMISETA, PANTALÓN, POLO
            $table->string('codigo')->unique(); // JEANS, SHIRT, TSHIRT, PANTS, POLO
            $table->json('palabras_clave'); // ["JEAN", "JEANS", "NAPOLES", "DRILL"]
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_prenda');
    }
};
