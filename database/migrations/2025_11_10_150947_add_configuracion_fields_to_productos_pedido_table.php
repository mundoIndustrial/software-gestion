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
        Schema::table('productos_pedido', function (Blueprint $table) {
            // Categoría y Tipo
            $table->string('categoria_prenda')->nullable()->after('nombre_producto');
            $table->string('tipo_prenda')->nullable()->after('categoria_prenda');
            
            // Configuraciones
            $table->string('configuracion_cuello')->nullable()->after('tipo_prenda');
            $table->string('configuracion_bolsillos')->nullable()->after('configuracion_cuello');
            $table->string('configuracion_puños')->nullable()->after('configuracion_bolsillos');
            $table->string('configuracion_cierre')->nullable()->after('configuracion_puños');
            
            // Configuraciones complejas (JSON)
            $table->json('configuracion_reflectivos')->nullable()->after('configuracion_cierre');
            $table->json('configuracion_bordados')->nullable()->after('configuracion_reflectivos');
            $table->json('caracteristicas_especiales')->nullable()->after('configuracion_bordados');
            $table->json('tallas_cantidades')->nullable()->after('caracteristicas_especiales');
            
            // Información adicional
            $table->integer('ciclos')->nullable()->after('tallas_cantidades');
            $table->string('origen')->nullable()->after('ciclos'); // De Bodega, Nuevo, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos_pedido', function (Blueprint $table) {
            $table->dropColumn([
                'categoria_prenda',
                'tipo_prenda',
                'configuracion_cuello',
                'configuracion_bolsillos',
                'configuracion_puños',
                'configuracion_cierre',
                'configuracion_reflectivos',
                'configuracion_bordados',
                'caracteristicas_especiales',
                'tallas_cantidades',
                'ciclos',
                'origen'
            ]);
        });
    }
};
