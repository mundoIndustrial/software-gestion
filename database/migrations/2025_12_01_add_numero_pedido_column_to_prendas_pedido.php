<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * OBJETIVO: Agregar columna numero_pedido a prendas_pedido
     * 
     * RAZÓN: El código usa numero_pedido para relacionar prendas con pedidos
     * Esta migración agrega la columna y migra los datos desde pedido_produccion_id
     */
    public function up(): void
    {
        // 1. Agregar columna numero_pedido si no existe
        if (!Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->string('numero_pedido')->nullable()->after('id');
            });
        }

        // 2. Migrar datos desde pedido_produccion_id si existe esa columna
        if (Schema::hasColumn('prendas_pedido', 'pedido_produccion_id')) {
            DB::statement('
                UPDATE prendas_pedido pp
                SET pp.numero_pedido = (
                    SELECT pp2.numero_pedido 
                    FROM pedidos_produccion pp2 
                    WHERE pp2.id = pp.pedido_produccion_id
                )
                WHERE pp.numero_pedido IS NULL 
                AND pp.pedido_produccion_id IS NOT NULL
            ');
        }

        // 3. Crear índice en numero_pedido para mejor performance
        if (!Schema::hasIndex('prendas_pedido', 'prendas_pedido_numero_pedido_index')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->index('numero_pedido');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            // Eliminar índice si existe
            if (Schema::hasIndex('prendas_pedido', 'prendas_pedido_numero_pedido_index')) {
                $table->dropIndex('prendas_pedido_numero_pedido_index');
            }
            
            // Eliminar columna si existe
            if (Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
                $table->dropColumn('numero_pedido');
            }
        });
    }
};
