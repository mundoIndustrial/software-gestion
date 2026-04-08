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
        // Columnas ancho, alto, tamano ya no existentes en prenda_fotos_cot
        // Este modelo ya fue limpiado en app/Models/PrendaFotoCot.php
        // No se realiza drop porque las columnas no existen en la tabla
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_fotos_cot', function (Blueprint $table) {
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamano')->nullable();
        });
    }
};
