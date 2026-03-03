<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega columna 'talla' a pedido_ancho_metraje para permitir
     * registrar ancho y metraje por talla-color en prendas combinadas.
     */
    public function up(): void
    {
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            // Agregar columna talla (nullable para compatibilidad)
            $table->string('talla', 50)->nullable()->after('color');
        });

        // Cambiar constraint unique: (pedido_produccion_id, prenda_pedido_id, color, talla)
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            $table->dropUnique('pedido_ancho_metraje_pedido_prenda_color_unique');
            $table->unique(
                ['pedido_produccion_id', 'prenda_pedido_id', 'color', 'talla'],
                'pedido_ancho_metraje_pedido_prenda_color_talla_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            $table->dropUnique('pedido_ancho_metraje_pedido_prenda_color_talla_unique');
            $table->unique(
                ['pedido_produccion_id', 'prenda_pedido_id', 'color'],
                'pedido_ancho_metraje_pedido_prenda_color_unique'
            );
            $table->dropColumn('talla');
        });
    }
};
