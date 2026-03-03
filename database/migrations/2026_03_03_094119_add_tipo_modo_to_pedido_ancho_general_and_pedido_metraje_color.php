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
        // Agregar tipo_modo a pedido_ancho_general
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_ancho_general', 'tipo_modo')) {
                $table->enum('tipo_modo', ['normal', 'color', 'pieza'])->default('normal')->after('ancho');
            }
        });

        // Agregar tipo_modo a pedido_metraje_color
        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_metraje_color', 'tipo_modo')) {
                $table->enum('tipo_modo', ['color', 'pieza'])->default('color')->after('metraje');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_ancho_general', 'tipo_modo')) {
                $table->dropColumn('tipo_modo');
            }
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_metraje_color', 'tipo_modo')) {
                $table->dropColumn('tipo_modo');
            }
        });
    }
};

