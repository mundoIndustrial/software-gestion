<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('procesos_historial');
    }

    public function down(): void
    {
        Schema::create('procesos_historial', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_pedido');
            $table->string('proceso');
            $table->date('fecha_inicio')->nullable();
            $table->string('encargado')->nullable();
            $table->string('estado_proceso')->nullable();
            $table->timestamps();
        });
    }
};

