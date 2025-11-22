<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generos_prenda', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Dama, Caballero, Unisex
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generos_prenda');
    }
};
