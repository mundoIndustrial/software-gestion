<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregar campos referencia e imagen_ruta a prenda_pedido_talla_colores.
 * 
 * - referencia: Referencia del color asignada en el wizard
 * - imagen_ruta: Ruta de la imagen asociada al color (almacenada en storage/public)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_pedido_talla_colores', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_pedido_talla_colores', 'referencia')) {
                $table->string('referencia', 255)->nullable()->after('cantidad')
                    ->comment('Referencia del color asignada en wizard');
            }
            if (!Schema::hasColumn('prenda_pedido_talla_colores', 'imagen_ruta')) {
                $table->string('imagen_ruta', 500)->nullable()->after('referencia')
                    ->comment('Ruta de imagen del color en storage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_pedido_talla_colores', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_pedido_talla_colores', 'referencia')) {
                $table->dropColumn('referencia');
            }
            if (Schema::hasColumn('prenda_pedido_talla_colores', 'imagen_ruta')) {
                $table->dropColumn('imagen_ruta');
            }
        });
    }
};
