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
        Schema::create('pedido_epp_imagenes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pedido_epp_id');
            $table->string('archivo');
            $table->boolean('principal')->default(false)->comment('Si es la imagen principal');
            $table->unsignedInteger('orden')->default(0)->comment('Orden de presentación');
            $table->timestamps();

            // Índices y constraints
            $table->foreign('pedido_epp_id')->references('id')->on('pedido_epp')->onDelete('cascade');
            $table->index('pedido_epp_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_epp_imagenes');
    }
};
