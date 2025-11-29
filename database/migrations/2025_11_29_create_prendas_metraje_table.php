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
        Schema::create('prendas_metraje', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_prenda')->unique();
            $table->decimal('ancho_prenda', 8, 2)->comment('Ancho de la prenda en metros');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prendas_metraje');
    }
};
