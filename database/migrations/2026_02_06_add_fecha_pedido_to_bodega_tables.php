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
        // Agregar fecha_pedido a bodega_detalles_talla
        if (Schema::hasTable('bodega_detalles_talla')) {
            Schema::table('bodega_detalles_talla', function (Blueprint $table) {
                if (!Schema::hasColumn('bodega_detalles_talla', 'fecha_pedido')) {
                    $table->dateTime('fecha_pedido')->nullable()->after('observaciones_bodega');
                }
            });
        }

        // Agregar fecha_pedido a costura_bodega_detalles
        if (Schema::hasTable('costura_bodega_detalles')) {
            Schema::table('costura_bodega_detalles', function (Blueprint $table) {
                if (!Schema::hasColumn('costura_bodega_detalles', 'fecha_pedido')) {
                    $table->dateTime('fecha_pedido')->nullable()->after('observaciones_bodega');
                }
            });
        }

        // Agregar fecha_pedido a epp_bodega_detalles
        if (Schema::hasTable('epp_bodega_detalles')) {
            Schema::table('epp_bodega_detalles', function (Blueprint $table) {
                if (!Schema::hasColumn('epp_bodega_detalles', 'fecha_pedido')) {
                    $table->dateTime('fecha_pedido')->nullable()->after('observaciones_bodega');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar fecha_pedido de bodega_detalles_talla
        if (Schema::hasTable('bodega_detalles_talla')) {
            Schema::table('bodega_detalles_talla', function (Blueprint $table) {
                if (Schema::hasColumn('bodega_detalles_talla', 'fecha_pedido')) {
                    $table->dropColumn('fecha_pedido');
                }
            });
        }

        // Eliminar fecha_pedido de costura_bodega_detalles
        if (Schema::hasTable('costura_bodega_detalles')) {
            Schema::table('costura_bodega_detalles', function (Blueprint $table) {
                if (Schema::hasColumn('costura_bodega_detalles', 'fecha_pedido')) {
                    $table->dropColumn('fecha_pedido');
                }
            });
        }

        // Eliminar fecha_pedido de epp_bodega_detalles
        if (Schema::hasTable('epp_bodega_detalles')) {
            Schema::table('epp_bodega_detalles', function (Blueprint $table) {
                if (Schema::hasColumn('epp_bodega_detalles', 'fecha_pedido')) {
                    $table->dropColumn('fecha_pedido');
                }
            });
        }
    }
};
