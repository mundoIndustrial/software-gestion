<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OBJETIVO: Corregir la estructura de relaciones
     *
     * ESTRUCTURA CORRECTA:
     * pedidos_produccion (1) ──── (N) prendas_pedido
     *                        ↑
     *                        └─── (N) procesos_prenda (via numero_pedido)
     *
     * CAMBIOS:
     * 1. ELIMINAR numero_pedido de prendas_pedido (si existe) - es redundante
     * 2. MANTENER numero_pedido en procesos_prenda - relación directa con pedidos_produccion
     */
    public function up(): void
    {
        // 1. ELIMINAR COLUMNA numero_pedido DE prendas_pedido (si existe)
        if (Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->dropColumn('numero_pedido');
            });
        }

        // 2. VERIFICAR QUE procesos_prenda TENGA numero_pedido
        // (ya debe estar desde la migración original)
        if (!Schema::hasColumn('procesos_prenda', 'numero_pedido')) {
            Schema::table('procesos_prenda', function (Blueprint $table) {
                $table->unsignedInteger('numero_pedido')->after('id');
                $table->foreign('numero_pedido')
                    ->references('numero_pedido')
                    ->on('pedidos_produccion')
                    ->onDelete('cascade');
                $table->index('numero_pedido');
            });
        }

        // 3. ELIMINAR prenda_pedido_id de procesos_prenda si existe (no lo necesita)
        if (Schema::hasColumn('procesos_prenda', 'prenda_pedido_id')) {
            // Usar DB statement directo para evitar errores de foreign key
            \DB::statement('ALTER TABLE procesos_prenda DROP COLUMN prenda_pedido_id');
        }

        // 4. ASEGURAR QUE prendas_pedido TENGA pedido_produccion_id
        if (!Schema::hasColumn('prendas_pedido', 'pedido_produccion_id')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->foreignId('pedido_produccion_id')
                    ->constrained('pedidos_produccion')
                    ->onDelete('cascade');
                $table->index('pedido_produccion_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios
        if (!Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->string('numero_pedido')->nullable()->after('pedido_produccion_id');
            });
        }

        if (Schema::hasColumn('procesos_prenda', 'numero_pedido')) {
            Schema::table('procesos_prenda', function (Blueprint $table) {
                try {
                    $table->dropForeign(['numero_pedido']);
                } catch (\Exception $e) {
                    // Ignorar
                }
                $table->dropColumn('numero_pedido');
            });
        }
    }
};
