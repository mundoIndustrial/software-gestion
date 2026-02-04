<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migración: Agregar índices para optimizar consultas de pedidos
     * 
     * Mejora estimada: 70-80% en tiempo de query
     * Implementa auditoría de rendimiento: 27/01/2026
     * 
     * Nota: Los índices pueden ya existir, esta migración es idempotent
     */
    public function up(): void
    {
        // Verificar si los índices ya existen antes de intentar crearlos
        $indexes = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'pedidos_produccion'
        ", [env('DB_DATABASE')]);
        
        $existingIndexes = collect($indexes)->pluck('INDEX_NAME')->toArray();
        
        Schema::table('pedidos_produccion', function (Blueprint $table) use ($existingIndexes) {
            // Índice para estado (si no existe)
            if (!in_array('pedidos_produccion_estado_index', $existingIndexes)) {
                $table->index('estado');
                echo " Creado índice: estado\n";
            } else {
                echo "⏭️  Índice ya existe: estado\n";
            }

            // Índice compuesto para "mis pedidos" ordenados por fecha (si no existe)
            if (!in_array('pedidos_produccion_asesor_id_created_at_index', $existingIndexes)) {
                $table->index(['asesor_id', 'created_at']);
                echo " Creado índice: asesor_id + created_at\n";
            } else {
                echo "⏭️  Índice ya existe: asesor_id + created_at\n";
            }

            // Índice para búsquedas por número de pedido (si no existe)
            if (!in_array('pedidos_produccion_numero_pedido_index', $existingIndexes)) {
                $table->index('numero_pedido');
                echo " Creado índice: numero_pedido\n";
            } else {
                echo "⏭️  Índice ya existe: numero_pedido\n";
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Intentar eliminar índices (ignorar errores si no existen)
            try {
                $table->dropIndex(['estado']);
            } catch (\Exception $e) {
                // Ya eliminado
            }
            
            try {
                $table->dropIndex(['asesor_id', 'created_at']);
            } catch (\Exception $e) {
                // Ya eliminado
            }
            
            try {
                $table->dropIndex(['numero_pedido']);
            } catch (\Exception $e) {
                // Ya eliminado
            }
        });
    }
};
