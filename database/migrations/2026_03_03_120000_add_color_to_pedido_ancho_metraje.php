<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega columna 'color' a pedido_ancho_metraje para permitir
     * registrar ancho y metraje por color en prendas combinadas.
     */
    public function up(): void
    {
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            // Agregar columna color (nullable para compatibilidad con registros existentes)
            $table->string('color', 100)->nullable()->after('prenda_pedido_id');
        });

        // Cambiar constraint unique: (pedido_produccion_id, prenda_pedido_id, color)
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            $table->dropUnique('pedido_ancho_metraje_pedido_prenda_unique');
            $table->unique(
                ['pedido_produccion_id', 'prenda_pedido_id', 'color'],
                'pedido_ancho_metraje_pedido_prenda_color_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            $table->dropUnique('pedido_ancho_metraje_pedido_prenda_color_unique');
            $table->unique(
                ['pedido_produccion_id', 'prenda_pedido_id'],
                'pedido_ancho_metraje_pedido_prenda_unique'
            );
            $table->dropColumn('color');
        });
    }
};
