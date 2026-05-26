<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            $table->unsignedBigInteger('pedido_produccion_id')->nullable()->change();
            $table->unsignedBigInteger('prenda_pedido_id')->nullable()->change();
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            $table->unsignedBigInteger('pedido_produccion_id')->nullable()->change();
            $table->unsignedBigInteger('prenda_pedido_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            $table->unsignedBigInteger('pedido_produccion_id')->nullable(false)->change();
            $table->unsignedBigInteger('prenda_pedido_id')->nullable(false)->change();
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            $table->unsignedBigInteger('pedido_produccion_id')->nullable(false)->change();
            $table->unsignedBigInteger('prenda_pedido_id')->nullable(false)->change();
        });
    }
};

