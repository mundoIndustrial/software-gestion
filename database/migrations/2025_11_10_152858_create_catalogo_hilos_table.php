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
        Schema::create('catalogo_hilos', function (Blueprint $table) {
            $table->id();
            $table->string('referencia'); // Ej: REF 293, REF 150
            $table->string('nombre')->nullable(); // Nombre del color
            $table->string('codigo_hex')->nullable(); // Color en HEX
            $table->string('imagen')->nullable(); // Imagen del carrete/muestra
            $table->string('marca')->nullable(); // Marca del hilo
            $table->string('composicion')->nullable(); // Ej: Poliéster, Algodón
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique('referencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_hilos');
    }
};
