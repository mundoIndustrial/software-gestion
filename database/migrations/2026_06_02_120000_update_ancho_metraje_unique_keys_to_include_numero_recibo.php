<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            $table->dropUnique('unique_ancho_prenda');
            $table->unique(
                ['pedido_produccion_id', 'prenda_pedido_id', 'numero_recibo'],
                'unique_ancho_prenda_recibo'
            );
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            $table->dropUnique('unique_metraje_color');
            $table->unique(
                ['pedido_produccion_id', 'prenda_pedido_id', 'color', 'numero_recibo'],
                'unique_metraje_color_recibo'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            $table->dropUnique('unique_ancho_prenda_recibo');
            $table->unique(['pedido_produccion_id', 'prenda_pedido_id'], 'unique_ancho_prenda');
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            $table->dropUnique('unique_metraje_color_recibo');
            $table->unique(['pedido_produccion_id', 'prenda_pedido_id', 'color'], 'unique_metraje_color');
        });
    }
};
