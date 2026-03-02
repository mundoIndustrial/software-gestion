<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hacer cantidad nullable en pedidos_procesos_prenda_tallas.
 * 
 * En flujo wizard las cantidades reales están en pedidos_procesos_prenda_talla_colores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->nullable(false)->default(0)->change();
        });
    }
};
