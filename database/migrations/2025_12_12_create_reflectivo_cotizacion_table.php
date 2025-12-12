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
        Schema::create('reflectivo_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->longText('descripcion');
            $table->string('ubicacion')->nullable();
            $table->json('imagenes')->nullable();
            $table->json('observaciones_generales')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('cotizacion_id')
                ->references('id')
                ->on('cotizaciones')
                ->onDelete('cascade');

            // Indexes
            $table->index('cotizacion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reflectivo_cotizacion');
    }
};
