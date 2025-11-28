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
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (!Schema::hasColumn('materiales_orden_insumos', 'tabla_original_pedido')) {
                $table->string('tabla_original_pedido')->nullable()->after('id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (Schema::hasColumn('materiales_orden_insumos', 'tabla_original_pedido')) {
                $table->dropColumn('tabla_original_pedido');
            }
        });
    }
};
