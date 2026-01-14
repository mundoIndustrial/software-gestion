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
        // 1. Tabla: tipos_procesos (Catálogo)
        Schema::create('tipos_procesos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique(); // reflectivo, bordado, estampado, dtf, sublimado
            $table->string('slug', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->nullable(); // Código hex para UI (#FF5733)
            $table->string('icono', 100)->nullable(); // Nombre del ícono
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('activo');
        });

        // 2. Tabla: procesos_prenda_detalles
        Schema::create('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->unsignedBigInteger('prenda_pedido_id');
            $table->unsignedBigInteger('tipo_proceso_id');
            
            // Datos del proceso
            $table->json('ubicaciones')->comment('Array: ["Frente", "Espalda", "Manga"]');
            $table->text('observaciones')->nullable();
            $table->json('tallas_dama')->nullable()->comment('Array: ["S", "M", "L"]');
            $table->json('tallas_caballero')->nullable()->comment('Array: ["M", "L", "XL"]');
            
            // Imagen/Archivo
            $table->string('imagen_ruta', 500)->nullable();
            $table->string('nombre_imagen')->nullable();
            $table->string('tipo_mime')->nullable();
            $table->bigInteger('tamaño_imagen')->nullable();
            
            // Estado y tracking
            $table->enum('estado', ['PENDIENTE', 'EN_REVISION', 'APROBADO', 'EN_PRODUCCION', 'COMPLETADO', 'RECHAZADO'])->default('PENDIENTE');
            $table->text('notas_rechazo')->nullable();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->unsignedBigInteger('aprobado_por')->nullable();
            
            // Metadata
            $table->json('datos_adicionales')->nullable()->comment('Para campos flexibles según tipo de proceso');
            
            $table->timestamps();
            $table->softDeletes();

            // Relaciones foráneas
            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');
            
            $table->foreign('tipo_proceso_id')
                ->references('id')
                ->on('tipos_procesos')
                ->onDelete('restrict');
            
            $table->foreign('aprobado_por')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Índices
            $table->unique(['prenda_pedido_id', 'tipo_proceso_id']);
            $table->index('estado');
            $table->index('tipo_proceso_id');
            $table->index('created_at');
            $table->index('aprobado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos_prenda_detalles');
        Schema::dropIfExists('tipos_procesos');
    }
};
