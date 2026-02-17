<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos_fechas_llegada', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recibo_id')->unique();
            $table->dateTime('fecha_llegada')->nullable();
            $table->timestamps();

            $table->foreign('recibo_id')
                ->references('id')
                ->on('consecutivos_recibos_pedidos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos_fechas_llegada');
    }
};
