<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tallas_costos_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->unsignedBigInteger('prenda_cot_id');
            $table->longText('descripcion')->nullable();
            $table->timestamps();

            $table->index('cotizacion_id');
            $table->index('prenda_cot_id');

            $table->foreign('cotizacion_id')
                ->references('id')
                ->on('cotizaciones')
                ->onDelete('cascade');

            $table->foreign('prenda_cot_id')
                ->references('id')
                ->on('prendas_cot')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallas_costos_cot');
    }
};
