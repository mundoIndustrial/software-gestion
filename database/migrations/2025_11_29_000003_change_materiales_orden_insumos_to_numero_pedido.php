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
            // Cambiar pedido_produccion_id a numero_pedido
            // Primero, obtener los números de pedido de la tabla pedido_produccion
            // y actualizar la tabla materiales_orden_insumos
            
            // Agregar columna temporal para numero_pedido
            if (!Schema::hasColumn('materiales_orden_insumos', 'numero_pedido_temp')) {
                $table->string('numero_pedido_temp')->nullable()->after('pedido_produccion_id');
            }
        });

        // Copiar datos de pedido_produccion_id a numero_pedido_temp
        \DB::statement('
            UPDATE materiales_orden_insumos moi
            JOIN pedidos_produccion pp ON moi.pedido_produccion_id = pp.id
            SET moi.numero_pedido_temp = pp.numero_pedido
        ');

        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            // Eliminar la clave foránea si existe
            try {
                $table->dropForeign(['pedido_produccion_id']);
            } catch (\Exception $e) {
                // Si no existe, continuar
            }
            
            // Eliminar la columna antigua
            $table->dropColumn('pedido_produccion_id');
        });

        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            // Renombrar columna temporal a numero_pedido
            $table->renameColumn('numero_pedido_temp', 'numero_pedido');
            
            // Hacer la columna no nullable y agregar índice
            $table->string('numero_pedido')->change();
            $table->index('numero_pedido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            // Eliminar índice
            $table->dropIndex(['numero_pedido']);
            
            // Agregar columna temporal
            $table->bigInteger('pedido_produccion_id_temp')->unsigned()->nullable()->after('id');
        });

        // Copiar datos de numero_pedido a pedido_produccion_id_temp
        \DB::statement('
            UPDATE materiales_orden_insumos moi
            JOIN pedidos_produccion pp ON moi.numero_pedido = pp.numero_pedido
            SET moi.pedido_produccion_id_temp = pp.id
        ');

        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            // Eliminar columna numero_pedido
            $table->dropColumn('numero_pedido');
        });

        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            // Renombrar columna temporal
            $table->renameColumn('pedido_produccion_id_temp', 'pedido_produccion_id');
            
            // Agregar clave foránea
            $table->foreign('pedido_produccion_id')
                ->references('id')
                ->on('pedido_produccion')
                ->onDelete('cascade');
        });
    }
};
