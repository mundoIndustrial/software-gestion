<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Crear tabla prenda_cot_reflectivo
        if (!Schema::hasTable('prenda_cot_reflectivo')) {
            Schema::create('prenda_cot_reflectivo', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cotizacion_id');
                $table->unsignedBigInteger('prenda_cot_id');
                $table->json('variaciones')->nullable()->comment('Variaciones traidas del PASO 2');
                $table->json('ubicaciones')->nullable()->comment('Ubicaciones para reflectivo');
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('cotizacion_id')
                    ->references('id')
                    ->on('cotizaciones')
                    ->onDelete('cascade');
                
                $table->foreign('prenda_cot_id')
                    ->references('id')
                    ->on('prendas_cot')
                    ->onDelete('cascade');
                
                // Índices
                $table->index('cotizacion_id');
                $table->index('prenda_cot_id');
            });
            
            \Log::info(' [Migración] Tabla prenda_cot_reflectivo creada exitosamente');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_cot_reflectivo');
    }
};
