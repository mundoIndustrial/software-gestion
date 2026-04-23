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
        Schema::create('system_errors', function (Blueprint $table) {
            $table->id();

            // Error básico
            $table->string('tipo'); // ERROR_IMAGEN, ERROR_RED, ERROR_VALIDACION, etc
            $table->text('mensaje');
            $table->text('detalles')->nullable(); // JSON con detalles adicionales

            // Contexto
            $table->string('origen')->nullable(); // 'image-upload', 'api', 'validation', 'general'
            $table->string('url_pagina')->nullable(); // URL donde ocurrió el error
            $table->string('navegador')->nullable(); // User agent

            // Relaciones
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();

            // Rastreo
            $table->timestamp('ocurrido_en');
            $table->timestamps();

            // Indexes para queries rápidas
            $table->index('tipo');
            $table->index('origen');
            $table->index('usuario_id');
            $table->index('pedido_id');
            $table->index('ocurrido_en');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_errors');
    }
};
