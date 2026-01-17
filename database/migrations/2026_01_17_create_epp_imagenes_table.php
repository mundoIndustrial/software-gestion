<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla de imágenes de EPP
     * Almacena referencias a archivos de imágenes en storage/app/public/epp/{codigo_epp}/
     * 
     * Estructura esperada:
     * storage/app/public/epp/
     *  └── EPP-CAB-001/
     *      ├── principal.jpg
     *      ├── lateral.jpg
     *      └── uso.jpg
     */
    public function up(): void
    {
        Schema::create('epp_imagenes', function (Blueprint $table) {
            $table->id();
            
            // Relación con EPP
            $table->unsignedBigInteger('epp_id');
            
            // Nombre del archivo (sin ruta completa)
            // Ej: principal.jpg, lateral.jpg
            $table->string('archivo', 255);
            
            // Indica si es la imagen principal
            $table->boolean('principal')->default(false);
            
            // Orden de visualización
            $table->unsignedInteger('orden')->default(1);
            
            // Timestamps
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('epp_id')
                ->references('id')
                ->on('epps')
                ->onDelete('cascade');
            
            // Índices
            $table->index('epp_id');
            $table->index('principal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epp_imagenes');
    }
};
