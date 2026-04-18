<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'color_reflectivo')) {
                $table->string('color_reflectivo')->nullable()->after('color_costura');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'color_reflectivo')) {
                $table->dropColumn('color_reflectivo');
            }
        });
    }
};
