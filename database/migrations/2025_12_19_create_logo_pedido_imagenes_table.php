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
        Schema::create('logo_pedido_imagenes', function (Blueprint $table) {
            $table->id();
            
            // Relación con logo_pedidos
            $table->foreignId('logo_pedido_id')
                ->constrained('logo_pedidos')
                ->onDelete('cascade');
            
            // Información de la imagen
            $table->string('nombre_archivo');
            $table->string('url')->nullable();
            $table->string('ruta_original')->nullable();
            $table->string('ruta_webp')->nullable();
            $table->string('tipo_archivo')->default('image/jpeg');
            $table->bigInteger('tamaño_archivo')->nullable();
            
            // Orden de la imagen en la galería
            $table->integer('orden')->default(0);
            
            // Metadata
            $table->timestamps();
            
            // Índices
            $table->index('logo_pedido_id');
            $table->index('orden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_pedido_imagenes');
    }
};
