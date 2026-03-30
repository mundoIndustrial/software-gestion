<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('prendas_pedido', 'tipo_flujo_tallas')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->string('tipo_flujo_tallas', 30)->default('normal');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('prendas_pedido', 'tipo_flujo_tallas')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->dropColumn('tipo_flujo_tallas');
            });
        }
    }
};
