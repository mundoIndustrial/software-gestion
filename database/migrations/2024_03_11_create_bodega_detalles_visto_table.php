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
        Schema::create('bodega_detalles_visto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bodega_detalle_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            
            // Índices
            $table->unique(['bodega_detalle_id', 'user_id']);
            $table->foreign('bodega_detalle_id')->references('id')->on('bodega_detalles_talla')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bodega_detalles_visto');
    }
};
