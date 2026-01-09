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
        Schema::table('prendas_pedido', function (Blueprint $table) {
            // Agregar campos de observaciones para variaciones
            if (!Schema::hasColumn('prendas_pedido', 'manga_obs')) {
                $table->longText('manga_obs')->nullable()->comment('Observación sobre el tipo de manga');
            }
            if (!Schema::hasColumn('prendas_pedido', 'bolsillos_obs')) {
                $table->longText('bolsillos_obs')->nullable()->comment('Observación sobre bolsillos');
            }
            if (!Schema::hasColumn('prendas_pedido', 'broche_obs')) {
                $table->longText('broche_obs')->nullable()->comment('Observación sobre broche');
            }
            if (!Schema::hasColumn('prendas_pedido', 'reflectivo_obs')) {
                $table->longText('reflectivo_obs')->nullable()->comment('Observación sobre reflectivo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            if (Schema::hasColumn('prendas_pedido', 'manga_obs')) {
                $table->dropColumn('manga_obs');
            }
            if (Schema::hasColumn('prendas_pedido', 'bolsillos_obs')) {
                $table->dropColumn('bolsillos_obs');
            }
            if (Schema::hasColumn('prendas_pedido', 'broche_obs')) {
                $table->dropColumn('broche_obs');
            }
            if (Schema::hasColumn('prendas_pedido', 'reflectivo_obs')) {
                $table->dropColumn('reflectivo_obs');
            }
        });
    }
};
