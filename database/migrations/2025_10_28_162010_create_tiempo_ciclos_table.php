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
        Schema::create('tiempo_ciclos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tela_id')->constrained('telas')->onDelete('cascade');
            $table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');
            $table->decimal('tiempo_ciclo', 8, 2);
            $table->timestamps();
            $table->unique(['tela_id', 'maquina_id']); // Ensure one tiempo_ciclo per tela-maquina pair
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiempo_ciclos');
    }
};
