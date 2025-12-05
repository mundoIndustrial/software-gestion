<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cambiar todos los campos DATE a DATETIME para capturar hora completa
     * Se ejecuta después de identificar campos problemáticos con check-db-fields.php
     */
    public function up(): void
    {
        // Tabla: tabla_original_bodega
        if (Schema::hasTable('tabla_original_bodega')) {
            Schema::table('tabla_original_bodega', function (Blueprint $table) {
                $table->dateTime('fecha_de_creacion_de_orden')->nullable()->change();
                $table->dateTime('control_de_calidad')->nullable()->change();
                $table->dateTime('entrega')->nullable()->change();
                $table->dateTime('despacho')->nullable()->change();
            });
        }

        // Tabla: cotizaciones
        if (Schema::hasTable('cotizaciones')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->dateTime('fecha_envio')->nullable()->change();
            });
        }

        // Tabla: registros_por_orden_bodega
        if (Schema::hasTable('registros_por_orden_bodega')) {
            Schema::table('registros_por_orden_bodega', function (Blueprint $table) {
                $table->dateTime('fecha_completado')->nullable()->change();
            });
        }

        // Tabla: entregas_pedido_costura
        if (Schema::hasTable('entregas_pedido_costura')) {
            Schema::table('entregas_pedido_costura', function (Blueprint $table) {
                $table->dateTime('fecha_entrega')->change();
            });
        }

        // Tabla: entregas_bodega_costura
        if (Schema::hasTable('entregas_bodega_costura')) {
            Schema::table('entregas_bodega_costura', function (Blueprint $table) {
                $table->dateTime('fecha_entrega')->change();
            });
        }

        // Tabla: entrega_pedido_corte
        if (Schema::hasTable('entrega_pedido_corte')) {
            Schema::table('entrega_pedido_corte', function (Blueprint $table) {
                $table->dateTime('fecha_entrega')->change();
            });
        }

        // Tabla: entrega_bodega_corte
        if (Schema::hasTable('entrega_bodega_corte')) {
            Schema::table('entrega_bodega_corte', function (Blueprint $table) {
                $table->dateTime('fecha_entrega')->change();
            });
        }

        // Tabla: registro_piso_produccion
        if (Schema::hasTable('registro_piso_produccion')) {
            Schema::table('registro_piso_produccion', function (Blueprint $table) {
                $table->dateTime('fecha')->nullable()->change();
            });
        }

        // Tabla: registro_piso_polo
        if (Schema::hasTable('registro_piso_polo')) {
            Schema::table('registro_piso_polo', function (Blueprint $table) {
                $table->dateTime('fecha')->nullable()->change();
            });
        }

        // Tabla: registro_piso_corte
        if (Schema::hasTable('registro_piso_corte')) {
            Schema::table('registro_piso_corte', function (Blueprint $table) {
                $table->dateTime('fecha')->nullable()->change();
            });
        }

        // Tabla: reportes
        if (Schema::hasTable('reportes')) {
            Schema::table('reportes', function (Blueprint $table) {
                $table->dateTime('fecha_inicio')->nullable()->change();
                $table->dateTime('fecha_fin')->nullable()->change();
            });
        }

        // Tabla: materiales_orden_insumos
        if (Schema::hasTable('materiales_orden_insumos')) {
            Schema::table('materiales_orden_insumos', function (Blueprint $table) {
                $table->dateTime('fecha_llegada')->nullable()->change();
                $table->dateTime('fecha_orden')->nullable()->change();
                $table->dateTime('fecha_pago')->nullable()->change();
                $table->dateTime('fecha_despacho')->nullable()->change();
                $table->dateTime('fecha_pedido')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversión: cambiar DATETIME de vuelta a DATE (si es necesario)
        // Nota: Esto podría perder datos de hora, usar con cuidado

        // Tabla: tabla_original_bodega
        if (Schema::hasTable('tabla_original_bodega')) {
            Schema::table('tabla_original_bodega', function (Blueprint $table) {
                $table->date('fecha_de_creacion_de_orden')->nullable()->change();
                $table->date('control_de_calidad')->nullable()->change();
                $table->date('entrega')->nullable()->change();
                $table->date('despacho')->nullable()->change();
            });
        }

        // Tabla: cotizaciones
        if (Schema::hasTable('cotizaciones')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->date('fecha_envio')->nullable()->change();
            });
        }

        // Tabla: registros_por_orden_bodega
        if (Schema::hasTable('registros_por_orden_bodega')) {
            Schema::table('registros_por_orden_bodega', function (Blueprint $table) {
                $table->date('fecha_completado')->nullable()->change();
            });
        }

        // Tabla: entregas_pedido_costura
        if (Schema::hasTable('entregas_pedido_costura')) {
            Schema::table('entregas_pedido_costura', function (Blueprint $table) {
                $table->date('fecha_entrega')->change();
            });
        }

        // Tabla: entregas_bodega_costura
        if (Schema::hasTable('entregas_bodega_costura')) {
            Schema::table('entregas_bodega_costura', function (Blueprint $table) {
                $table->date('fecha_entrega')->change();
            });
        }

        // Tabla: entrega_pedido_corte
        if (Schema::hasTable('entrega_pedido_corte')) {
            Schema::table('entrega_pedido_corte', function (Blueprint $table) {
                $table->date('fecha_entrega')->change();
            });
        }

        // Tabla: entrega_bodega_corte
        if (Schema::hasTable('entrega_bodega_corte')) {
            Schema::table('entrega_bodega_corte', function (Blueprint $table) {
                $table->date('fecha_entrega')->change();
            });
        }

        // Tabla: registro_piso_produccion
        if (Schema::hasTable('registro_piso_produccion')) {
            Schema::table('registro_piso_produccion', function (Blueprint $table) {
                $table->date('fecha')->nullable()->change();
            });
        }

        // Tabla: registro_piso_polo
        if (Schema::hasTable('registro_piso_polo')) {
            Schema::table('registro_piso_polo', function (Blueprint $table) {
                $table->date('fecha')->nullable()->change();
            });
        }

        // Tabla: registro_piso_corte
        if (Schema::hasTable('registro_piso_corte')) {
            Schema::table('registro_piso_corte', function (Blueprint $table) {
                $table->date('fecha')->nullable()->change();
            });
        }

        // Tabla: reportes
        if (Schema::hasTable('reportes')) {
            Schema::table('reportes', function (Blueprint $table) {
                $table->date('fecha_inicio')->nullable()->change();
                $table->date('fecha_fin')->nullable()->change();
            });
        }

        // Tabla: materiales_orden_insumos
        if (Schema::hasTable('materiales_orden_insumos')) {
            Schema::table('materiales_orden_insumos', function (Blueprint $table) {
                $table->date('fecha_llegada')->nullable()->change();
                $table->date('fecha_orden')->nullable()->change();
                $table->date('fecha_pago')->nullable()->change();
                $table->date('fecha_despacho')->nullable()->change();
                $table->date('fecha_pedido')->nullable()->change();
            });
        }
    }
};
