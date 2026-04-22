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
        // Índices para subconsultas de conteo de prendas
        if (Schema::hasTable('prendas_pedido')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                if (!$this->indexExists('prendas_pedido', 'prendas_pedido_pedido_produccion_id_index')) {
                    $table->index('pedido_produccion_id');
                }
            });
        }

        // Índices para subconsulta de última actividad
        if (Schema::hasTable('pedido_anexos_historial')) {
            Schema::table('pedido_anexos_historial', function (Blueprint $table) {
                if (!$this->indexExists('pedido_anexos_historial', 'pedido_anexos_historial_pedido_produccion_id_created_at_index')) {
                    $table->index(['pedido_produccion_id', 'created_at']);
                }
            });
        }
    }

    /**
     * Verificar si un índice existe
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM $table");
        foreach ($indexes as $idx) {
            if ($idx->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['ocultado_en']);
            $table->dropIndex(['numero_pedido']);
            $table->dropIndex(['asesor_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['estado', 'ocultado_en']);
        });

        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->dropIndex(['pedido_id']);
        });

        Schema::table('equipos_de_proteccion_personal', function (Blueprint $table) {
            $table->dropIndex(['pedido_id']);
        });

        Schema::table('pedido_anexos_historial', function (Blueprint $table) {
            $table->dropIndex(['pedido_produccion_id', 'created_at']);
        });
    }
};
