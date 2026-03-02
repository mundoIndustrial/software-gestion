<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hacer nullable el campo cantidad en prenda_pedido_tallas.
 * 
 * En el flujo wizard (colores por talla), las cantidades reales se almacenan
 * en prenda_pedido_talla_colores. El registro padre en prenda_pedido_tallas
 * sirve como contenedor y NO debe tener cantidad (null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->nullable(false)->default(0)->change();
        });
    }
};
