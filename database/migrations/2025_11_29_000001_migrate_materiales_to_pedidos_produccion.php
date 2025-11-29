<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columna pedido_produccion_id a materiales_orden_insumos
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (!Schema::hasColumn('materiales_orden_insumos', 'pedido_produccion_id')) {
                $table->unsignedBigInteger('pedido_produccion_id')->nullable()->after('id');
                $table->foreign('pedido_produccion_id')
                    ->references('id')
                    ->on('pedidos_produccion')
                    ->onDelete('cascade');
            }
        });

        // 2. Migrar datos de tabla_original_pedido a pedido_produccion_id
        // Buscar coincidencias entre numero_pedido en pedidos_produccion y tabla_original_pedido en materiales
        if (Schema::hasColumn('materiales_orden_insumos', 'tabla_original_pedido')) {
            $materiales = DB::table('materiales_orden_insumos')->whereNotNull('tabla_original_pedido')->get();
            
            foreach ($materiales as $material) {
                // Buscar el pedido_produccion que corresponde a este numero_pedido
                $pedido = DB::table('pedidos_produccion')
                    ->where('numero_pedido', $material->tabla_original_pedido)
                    ->first();
                
                if ($pedido) {
                    DB::table('materiales_orden_insumos')
                        ->where('id', $material->id)
                        ->update(['pedido_produccion_id' => $pedido->id]);
                }
            }

            // 3. Eliminar la columna tabla_original_pedido
            Schema::table('materiales_orden_insumos', function (Blueprint $table) {
                $table->dropColumn('tabla_original_pedido');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Agregar de vuelta tabla_original_pedido
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (!Schema::hasColumn('materiales_orden_insumos', 'tabla_original_pedido')) {
                $table->unsignedInteger('tabla_original_pedido')->nullable()->after('id')->index();
            }
        });

        // 2. Migrar datos de vuelta
        $materiales = DB::table('materiales_orden_insumos')->whereNotNull('pedido_produccion_id')->get();
        
        foreach ($materiales as $material) {
            $pedido = DB::table('pedidos_produccion')
                ->where('id', $material->pedido_produccion_id)
                ->first();
            
            if ($pedido) {
                DB::table('materiales_orden_insumos')
                    ->where('id', $material->id)
                    ->update(['tabla_original_pedido' => $pedido->numero_pedido]);
            }
        }

        // 3. Agregar foreign key antigua
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (Schema::hasColumn('materiales_orden_insumos', 'tabla_original_pedido')) {
                $table->foreign('tabla_original_pedido')
                    ->references('pedido')
                    ->on('tabla_original')
                    ->onDelete('cascade');
            }
        });

        // 4. Eliminar pedido_produccion_id
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            if (Schema::hasColumn('materiales_orden_insumos', 'pedido_produccion_id')) {
                try {
                    $table->dropForeign(['pedido_produccion_id']);
                } catch (\Exception $e) {
                    // Si no existe la foreign key, continuar
                }
                $table->dropColumn('pedido_produccion_id');
            }
        });
    }
};
