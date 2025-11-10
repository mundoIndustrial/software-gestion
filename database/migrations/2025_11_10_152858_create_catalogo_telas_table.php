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
        Schema::create('catalogo_telas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: TELA LAFAYETTE, DRIL LIVIANO
            $table->string('codigo')->unique()->nullable(); // Código interno
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable(); // Ruta de la imagen
            $table->string('composicion')->nullable(); // Ej: 100% Algodón
            $table->decimal('peso', 8, 2)->nullable(); // Gramos por metro
            $table->decimal('ancho', 8, 2)->nullable(); // Ancho en metros
            $table->json('colores_disponibles')->nullable(); // Array de colores
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_telas');
    }
};
