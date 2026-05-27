<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            $table->string('ancho', 255)->nullable()->change();
            $table->string('metraje', 255)->nullable()->change();
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            $table->string('metraje', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            $table->decimal('ancho', 10, 2)->nullable()->change();
            $table->decimal('metraje', 10, 2)->nullable()->change();
        });

        Schema::table('pedido_metraje_color', function (Blueprint $table) {
            $table->decimal('metraje', 10, 2)->nullable()->change();
        });
    }
};
