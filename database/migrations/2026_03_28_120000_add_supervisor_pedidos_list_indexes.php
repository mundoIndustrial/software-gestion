<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indices para acelerar el listado principal de supervisor-pedidos.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pedidos_produccion')) {
            return;
        }

        $addVisibleEstadoNumero = !$this->indexExists('pedidos_produccion', 'pedidos_supervisor_visible_estado_numero_idx');
        $addOrdenamiento = !$this->indexExists('pedidos_produccion', 'pedidos_supervisor_ordenamiento_idx');
        $addCreatedAt = !$this->indexExists('pedidos_produccion', 'pedidos_supervisor_created_at_idx');

        Schema::table('pedidos_produccion', function (Blueprint $table) use ($addVisibleEstadoNumero, $addOrdenamiento, $addCreatedAt) {
            if ($addVisibleEstadoNumero) {
                $table->index(
                    ['ocultado_en', 'estado', 'numero_pedido'],
                    'pedidos_supervisor_visible_estado_numero_idx'
                );
            }

            if ($addOrdenamiento) {
                $table->index(
                    ['updated_at', 'numero_pedido'],
                    'pedidos_supervisor_ordenamiento_idx'
                );
            }

            if ($addCreatedAt) {
                $table->index('created_at', 'pedidos_supervisor_created_at_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pedidos_produccion')) {
            return;
        }

        $dropVisibleEstadoNumero = $this->indexExists('pedidos_produccion', 'pedidos_supervisor_visible_estado_numero_idx');
        $dropOrdenamiento = $this->indexExists('pedidos_produccion', 'pedidos_supervisor_ordenamiento_idx');
        $dropCreatedAt = $this->indexExists('pedidos_produccion', 'pedidos_supervisor_created_at_idx');

        Schema::table('pedidos_produccion', function (Blueprint $table) use ($dropVisibleEstadoNumero, $dropOrdenamiento, $dropCreatedAt) {
            if ($dropVisibleEstadoNumero) {
                $table->dropIndex('pedidos_supervisor_visible_estado_numero_idx');
            }

            if ($dropOrdenamiento) {
                $table->dropIndex('pedidos_supervisor_ordenamiento_idx');
            }

            if ($dropCreatedAt) {
                $table->dropIndex('pedidos_supervisor_created_at_idx');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(1) AS total FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result->total ?? 0) > 0;
    }
};
