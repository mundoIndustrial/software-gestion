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
        // Solo crear la tabla si no existe
        if (!Schema::hasTable('logo_cotizacion_tecnica_prendas_fotos')) {
            Schema::create('logo_cotizacion_tecnica_prendas_fotos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('logo_cotizacion_tecnica_prenda_id');
                $table->string('ruta_original', 500);
                $table->string('ruta_webp', 500);
                $table->string('ruta_miniatura', 500);
                $table->integer('orden')->default(0);
                $table->integer('ancho')->nullable();
                $table->integer('alto')->nullable();
                $table->integer('tamaño')->nullable();
                $table->timestamps();

                // Foreign key (nombre corto para MySQL)
                $table->foreign('logo_cotizacion_tecnica_prenda_id', 'fk_foto_prenda')
                    ->references('id')
                    ->on('logo_cotizacion_tecnica_prendas')
                    ->onDelete('cascade');

                // Índices
                $table->index('logo_cotizacion_tecnica_prenda_id');
                $table->index('orden');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_cotizacion_tecnica_prendas_fotos');
    }
};
