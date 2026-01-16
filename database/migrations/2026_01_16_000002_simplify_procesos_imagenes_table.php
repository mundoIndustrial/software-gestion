<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Simplificar tabla pedidos_procesos_imagenes
     * Solo guardar: ruta_original y ruta_webp
     * Eliminar campos innecesarios
     */
    public function up(): void
    {
        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            // Agregar nuevas columnas si no existen
            if (!Schema::hasColumn('pedidos_procesos_imagenes', 'ruta_original')) {
                $table->string('ruta_original', 500)->nullable()->after('ruta')->comment('Ruta de almacenamiento del archivo original');
            }
            
            if (!Schema::hasColumn('pedidos_procesos_imagenes', 'ruta_webp')) {
                $table->string('ruta_webp', 500)->nullable()->after('ruta_original')->comment('Ruta accesible vía web (public/)');
            }
        });

        // Copiar datos de ruta → ruta_webp si existen
        // La columna 'ruta' actualmente contiene la ruta web
        \DB::statement('UPDATE pedidos_procesos_imagenes SET ruta_webp = ruta WHERE ruta_webp IS NULL');

        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            // Eliminar campos innecesarios
            $columnsToRemove = [];
            
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'nombre_original')) {
                $columnsToRemove[] = 'nombre_original';
            }
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'tipo_mime')) {
                $columnsToRemove[] = 'tipo_mime';
            }
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'tamaño')) {
                $columnsToRemove[] = 'tamaño';
            }
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'ancho')) {
                $columnsToRemove[] = 'ancho';
            }
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'alto')) {
                $columnsToRemove[] = 'alto';
            }
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'hash_md5')) {
                $columnsToRemove[] = 'hash_md5';
            }
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'descripcion')) {
                $columnsToRemove[] = 'descripcion';
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });

        // Cambiar columna 'ruta' por nueva estructura
        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'ruta')) {
                $table->dropColumn('ruta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            // Recrear columnas eliminadas
            $table->string('ruta', 500)->after('proceso_prenda_detalle_id');
            $table->string('nombre_original')->after('ruta');
            $table->string('tipo_mime')->after('nombre_original');
            $table->bigInteger('tamaño')->after('tipo_mime');
            $table->integer('ancho')->nullable()->after('tamaño');
            $table->integer('alto')->nullable()->after('ancho');
            $table->string('hash_md5', 32)->nullable()->unique()->after('alto');
            $table->text('descripcion')->nullable()->after('es_principal');
            
            // Copiar datos de vuelta
            \DB::statement('UPDATE pedidos_procesos_imagenes SET ruta = ruta_webp WHERE ruta_webp IS NOT NULL');
        });

        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'ruta_original')) {
                $table->dropColumn('ruta_original');
            }
            if (Schema::hasColumn('pedidos_procesos_imagenes', 'ruta_webp')) {
                $table->dropColumn('ruta_webp');
            }
        });
    }
};
