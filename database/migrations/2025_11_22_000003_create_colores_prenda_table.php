<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colores_prenda', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('codigo')->nullable(); // Código hexadecimal o código interno
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colores_prenda');
    }
};
