<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'color_control_calidad')) {
                $table->string('color_control_calidad')->nullable()->after('color_costura');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'color_control_calidad')) {
                $table->dropColumn('color_control_calidad');
            }
        });
    }
};

