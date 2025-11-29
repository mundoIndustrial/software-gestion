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
            // Agregar columna numero_pedido si no existe
            if (!Schema::hasColumn('materiales_orden_insumos', 'numero_pedido')) {
                $table->string('numero_pedido')->nullable()->after('pedido_produccion_id')->index();
            }
            
            // Eliminar columna tabla_original_pedido si existe
            if (Schema::hasColumn('materiales_orden_insumos', 'tabla_original_pedido')) {
                $table->dropColumn('tabla_original_pedido');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (Schema::hasColumn('materiales_orden_insumos', 'numero_pedido')) {
                $table->dropColumn('numero_pedido');
            }
        });
    }
};
