<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_areas_logo_pedido', function (Blueprint $table) {
            // First, drop the foreign key constraint that depends on the unique constraint
            $table->dropForeign('prenda_areas_logo_pedido_proceso_prenda_detalle_id_foreign');
            
            // Then drop the old unique constraint on just proceso_prenda_detalle_id
            $table->dropUnique('prenda_areas_logo_pedido_proceso_prenda_detalle_id_unique');
            
            // Add pedido_parcial_id column if it doesn't exist
            if (!Schema::hasColumn('prenda_areas_logo_pedido', 'pedido_parcial_id')) {
                $table->unsignedBigInteger('pedido_parcial_id')->nullable();
            }

            // Create a new composite unique constraint that includes pedido_parcial_id
            // This allows multiple records per process but unique per (process + pedido_parcial_id) combination
            $table->unique(['proceso_prenda_detalle_id', 'pedido_parcial_id'], 'prenda_areas_logo_unique_processo_pedido_parcial');
            
            // Recreate the foreign key constraint
            $table->foreign('proceso_prenda_detalle_id')
                ->references('id')
                ->on('pedidos_procesos_prenda_detalles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('prenda_areas_logo_pedido', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign('prenda_areas_logo_pedido_proceso_prenda_detalle_id_foreign');
            
            // Drop the new composite unique constraint
            $table->dropUnique('prenda_areas_logo_unique_processo_pedido_parcial');

            // Restore the old unique constraint
            $table->unique('proceso_prenda_detalle_id');
            
            // Recreate the original foreign key
            $table->foreign('proceso_prenda_detalle_id')
                ->references('id')
                ->on('pedidos_procesos_prenda_detalles')
                ->onDelete('cascade');
            
            // Drop pedido_parcial_id column if it was added
            if (Schema::hasColumn('prenda_areas_logo_pedido', 'pedido_parcial_id')) {
                $table->dropColumn('pedido_parcial_id');
            }
        });
    }
};
