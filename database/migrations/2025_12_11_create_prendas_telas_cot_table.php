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
        Schema::create('prendas_telas_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_cot_id');
            $table->unsignedBigInteger('tela_id')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->string('referencia')->nullable();
            $table->timestamps();

            // Relaciones
            $table->foreign('prenda_cot_id')->references('id')->on('prendas_cot')->onDelete('cascade');
            $table->foreign('tela_id')->references('id')->on('telas')->onDelete('set null');
            $table->foreign('color_id')->references('id')->on('colores')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prendas_telas_cot');
    }
};
