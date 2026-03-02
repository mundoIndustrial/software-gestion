<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregar campo observaciones a prenda_pedido_tallas y prenda_pedido_talla_colores.
 * 
 * - Flujo simple: observación por talla se guarda en prenda_pedido_tallas.observaciones
 * - Flujo wizard: observación por color-talla se guarda en prenda_pedido_talla_colores.observaciones
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_pedido_tallas', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('cantidad');
            }
        });

        Schema::table('prenda_pedido_talla_colores', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_pedido_talla_colores', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('cantidad');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_pedido_tallas', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
        });

        Schema::table('prenda_pedido_talla_colores', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_pedido_talla_colores', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
        });
    }
};
