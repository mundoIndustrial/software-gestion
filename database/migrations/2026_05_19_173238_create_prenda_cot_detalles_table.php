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
        Schema::create('prenda_cot_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_cot_id');
            $table->string('disponibilidad')->nullable();
            $table->string('ultima_venta')->nullable();
            $table->timestamps();

            $table->foreign('prenda_cot_id')->references('id')->on('prendas_cot')->onDelete('cascade');
            $table->index('prenda_cot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_cot_detalles');
    }
};
