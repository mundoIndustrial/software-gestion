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
        Schema::create('catalogo_colores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: AZUL REY, NEGRO, BLANCO
            $table->string('codigo_hex')->nullable(); // Ej: #0000FF
            $table->string('codigo_pantone')->nullable(); // Ej: PANTONE 286 C
            $table->string('imagen')->nullable(); // Muestra del color
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
        Schema::dropIfExists('catalogo_colores');
    }
};
