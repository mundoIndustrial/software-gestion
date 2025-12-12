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
        Schema::create('reflectivo_fotos_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reflectivo_cotizacion_id');
            $table->string('ruta_original')->nullable();
            $table->string('ruta_webp')->nullable();
            $table->integer('orden')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('reflectivo_cotizacion_id')
                ->references('id')
                ->on('reflectivo_cotizacion')
                ->onDelete('cascade');

            // Indexes
            $table->index('reflectivo_cotizacion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reflectivo_fotos_cotizacion');
    }
};
