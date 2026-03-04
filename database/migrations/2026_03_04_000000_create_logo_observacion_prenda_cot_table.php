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
        Schema::create('logo_observacion_prenda_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->unsignedBigInteger('prenda_cot_id');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->foreign('prenda_cot_id')->references('id')->on('prendas_cot')->onDelete('cascade');

            $table->unique(['cotizacion_id', 'prenda_cot_id'], 'logo_obs_prenda_cot_unique');
            $table->index(['cotizacion_id', 'prenda_cot_id'], 'logo_obs_prenda_cot_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_observacion_prenda_cot');
    }
};
