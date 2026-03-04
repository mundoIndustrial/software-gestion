<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega soporte para modo "Por Tallas" en procesos de prendas.
 * 
 * Cambios:
 * 1. pedidos_procesos_prenda_detalles: campo modo_tallas (para_todas / por_tallas)
 * 2. pedidos_procesos_prenda_tallas: ubicaciones y observaciones por talla
 * 3. pedidos_procesos_imagenes: FK opcional a talla específica
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar modo_tallas al detalle del proceso
        Schema::table('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
            $table->enum('modo_tallas', ['para_todas', 'por_tallas'])
                ->default('para_todas')
                ->after('observaciones')
                ->comment('Modo de configuración: para_todas (genérico) o por_tallas (individual)');
        });

        // 2. Agregar ubicaciones y observaciones por talla
        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            $table->json('ubicaciones')->nullable()->after('es_sobremedida')
                ->comment('Ubicaciones específicas para esta talla (modo por_tallas)');
            $table->text('observaciones')->nullable()->after('ubicaciones')
                ->comment('Observaciones específicas para esta talla (modo por_tallas)');
        });

        // 3. Agregar FK de talla a imágenes (para vincular imagen a talla específica)
        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            $table->unsignedBigInteger('proceso_prenda_talla_id')->nullable()->after('proceso_prenda_detalle_id')
                ->comment('Si no es null, la imagen pertenece a esta talla específica (modo por_tallas)');
            $table->foreign('proceso_prenda_talla_id', 'ppi_talla_fk')
                ->references('id')
                ->on('pedidos_procesos_prenda_tallas')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            $table->dropForeign('ppi_talla_fk');
            $table->dropColumn('proceso_prenda_talla_id');
        });

        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            $table->dropColumn(['ubicaciones', 'observaciones']);
        });

        Schema::table('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
            $table->dropColumn('modo_tallas');
        });
    }
};
