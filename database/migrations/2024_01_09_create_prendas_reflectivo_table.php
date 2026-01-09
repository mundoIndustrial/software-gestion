<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crear tabla para prendas tipo REFLECTIVO
     * Una prenda reflectivo tiene:
     * - Múltiples géneros (Dama, Caballero)
     * - Tallas por género (cantidad_talla con estructura género => talla => cantidad)
     * - Ubicaciones del reflectivo
     * - Observaciones por ubicación
     * - Imágenes (fotos)
     */
    public function up(): void
    {
        Schema::create('prendas_reflectivo', function (Blueprint $table) {
            $table->id();
            
            // Relación a prenda_pedido
            $table->foreignId('prenda_pedido_id')
                ->constrained('prendas_pedido')
                ->onDelete('cascade');
            
            // Información básica
            $table->string('nombre_producto', 500)->nullable()->comment('Tipo de prenda (Camiseta, Pantalón, etc.)');
            $table->longText('descripcion')->nullable()->comment('Descripción del reflectivo');
            
            // Género y tallas
            $table->json('generos')->nullable()->comment('Array de géneros seleccionados: ["dama", "caballero"]');
            $table->json('cantidad_talla')->nullable()->comment('Estructura: {genero: {talla: cantidad}} - Ej: {"dama": {"S": 5, "M": 10}, "caballero": {"M": 8}}');
            
            // Ubicaciones del reflectivo
            $table->json('ubicaciones')->nullable()->comment('Array de ubicaciones: [{nombre, observaciones}, ...]');
            
            // Campos adicionales
            $table->text('observaciones_generales')->nullable()->comment('Observaciones generales del reflectivo');
            $table->integer('cantidad_total')->default(0)->comment('Total de prendas para este reflectivo');
            
            // Auditoría
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('prenda_pedido_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prendas_reflectivo');
    }
};
