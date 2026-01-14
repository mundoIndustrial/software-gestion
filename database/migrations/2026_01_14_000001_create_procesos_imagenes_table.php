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
        // Tabla: pedidos_procesos_imagenes
        Schema::create('pedidos_procesos_imagenes', function (Blueprint $table) {
            $table->id();
            
            // Relación
            $table->unsignedBigInteger('proceso_prenda_detalle_id');
            
            // Datos de la imagen
            $table->string('ruta', 500);
            $table->string('nombre_original');
            $table->string('tipo_mime');
            $table->bigInteger('tamaño');
            
            // Metadata
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->string('hash_md5', 32)->nullable()->unique();
            
            // Orden
            $table->integer('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            
            // Descripción
            $table->text('descripcion')->nullable();
            
            // Auditoria
            $table->timestamps();
            $table->softDeletes();

            // Relaciones foráneas
            $table->foreign('proceso_prenda_detalle_id')
                ->references('id')
                ->on('pedidos_procesos_prenda_detalles')
                ->onDelete('cascade');

            // Índices
            $table->index('proceso_prenda_detalle_id');
            $table->index('es_principal');
            $table->index('created_at');
            $table->index('hash_md5');
        });

        // Eliminar campos de imagen de pedidos_procesos_prenda_detalles
        Schema::table('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_procesos_prenda_detalles', 'imagen_ruta')) {
                $table->dropColumn('imagen_ruta');
            }
            if (Schema::hasColumn('pedidos_procesos_prenda_detalles', 'nombre_imagen')) {
                $table->dropColumn('nombre_imagen');
            }
            if (Schema::hasColumn('pedidos_procesos_prenda_detalles', 'tipo_mime')) {
                $table->dropColumn('tipo_mime');
            }
            if (Schema::hasColumn('pedidos_procesos_prenda_detalles', 'tamaño_imagen')) {
                $table->dropColumn('tamaño_imagen');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_procesos_imagenes');

        // Restaurar campos en pedidos_procesos_prenda_detalles
        Schema::table('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
            $table->string('imagen_ruta', 500)->nullable()->after('datos_adicionales');
            $table->string('nombre_imagen')->nullable()->after('imagen_ruta');
            $table->string('tipo_mime')->nullable()->after('nombre_imagen');
            $table->bigInteger('tamaño_imagen')->nullable()->after('tipo_mime');
        });
    }
};
