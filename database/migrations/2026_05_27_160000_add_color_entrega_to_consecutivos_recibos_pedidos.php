<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'color_entrega')) {
                $table->string('color_entrega')->nullable()->after('color_control_calidad');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'color_entrega')) {
                $table->dropColumn('color_entrega');
            }
        });
    }
};
