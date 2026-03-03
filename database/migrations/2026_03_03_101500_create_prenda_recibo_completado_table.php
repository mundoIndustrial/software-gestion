<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_recibo_completado', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_recibo');
            $table->unsignedBigInteger('numero_recibo');
            $table->string('area', 50);
            $table->string('nombre_operario', 255);
            $table->timestamp('fecha_completado');

            $table->unique(['id_recibo', 'area']);
            $table->index(['area', 'numero_recibo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_recibo_completado');
    }
};
