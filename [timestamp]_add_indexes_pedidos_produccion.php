<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migración: Agregar índices para optimizar consultas de pedidos
     * 
     * Índices agregados:
     * 1. estado - Para filtrar pedidos por estado
     * 2. asesor_id + created_at - Para consultas "mis pedidos" ordenados por fecha
     * 
     * Mejora estimada: 70-80% en tiempo de query
     */
    public function up(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Índice simple para filtros por estado
            // SELECT * FROM pedidos_produccion WHERE estado = 'En Ejecución'
            if (!$this->hasIndex('pedidos_produccion', 'estado')) {
                $table->index('estado');
            }

            // Índice compuesto para "mis pedidos" ordenados por fecha
            // SELECT * FROM pedidos_produccion 
            // WHERE asesor_id = ? 
            // ORDER BY created_at DESC
            if (!$this->hasIndex('pedidos_produccion', ['asesor_id', 'created_at'])) {
                $table->index(['asesor_id', 'created_at']);
            }

            // Índice opcional: para búsquedas por número de pedido
            // SELECT * FROM pedidos_produccion WHERE numero_pedido LIKE '%123%'
            if (!$this->hasIndex('pedidos_produccion', 'numero_pedido')) {
                $table->index('numero_pedido');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['asesor_id', 'created_at']);
            $table->dropIndex(['numero_pedido']);
        });
    }

    /**
     * Helper: Verificar si un índice existe
     */
    private function hasIndex($table, $columns): bool
    {
        $indexName = is_array($columns) 
            ? $table . '_' . implode('_', $columns) . '_index'
            : $table . '_' . $columns . '_index';

        $indexes = \DB::select(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [env('DB_DATABASE'), $table, $indexName]
        );

        return !empty($indexes);
    }
};
