<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // CRÍTICO: bodega_detalles_talla - Búsquedas más frecuentes
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            // Índice simple para búsqueda por número de pedido (usado en loop N+1)
            if (!$this->hasIndex('bodega_detalles_talla', 'idx_numero_pedido')) {
                $table->index('numero_pedido', 'idx_numero_pedido');
            }

            // Índice simple para búsqueda por área (usado en filtros)
            if (!$this->hasIndex('bodega_detalles_talla', 'idx_area')) {
                $table->index('area', 'idx_area');
            }

            // Índice compuesto para búsquedas combinadas numero_pedido + estado
            if (!$this->hasIndex('bodega_detalles_talla', 'idx_numero_estado')) {
                $table->index(['numero_pedido', 'estado_bodega'], 'idx_numero_estado');
            }

            // Índice para empresa (filtros comunes) - usar longitud para TEXT
            // Nota: Si empresa es TEXT, necesita longitud; si es VARCHAR está bien
            // Aquí asumimos que es VARCHAR o que tiene una longitud razonable
            try {
                if (!$this->hasIndex('bodega_detalles_talla', 'idx_empresa')) {
                    DB::statement('ALTER TABLE bodega_detalles_talla ADD INDEX idx_empresa (empresa(191))');
                }
            } catch (\Exception $e) {
                // Si falla por tipo de columna, ignorar
                \Log::warning('No se pudo crear índice en empresa: ' . $e->getMessage());
            }

            // Índice para estado_bodega (filtros)
            if (!$this->hasIndex('bodega_detalles_talla', 'idx_estado')) {
                $table->index('estado_bodega', 'idx_estado');
            }
        });

        // CRÍTICO: pedidos_produccion - Queries por número_pedido muy frecuentes
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Índice para búsqueda por numero_pedido (usado 15+ veces en loop)
            if (!$this->hasIndex('pedidos_produccion', 'idx_numero_pedido')) {
                $table->index('numero_pedido', 'idx_numero_pedido');
            }

            // Índice para filtros por estado
            if (!$this->hasIndex('pedidos_produccion', 'idx_estado')) {
                $table->index('estado', 'idx_estado');
            }
        });

        // ALTA: pedido_epp - Cadenas de homologación recursivas
        Schema::table('pedido_epp', function (Blueprint $table) {
            // Índice para navegar cadena de homologaciones (O(n²) sin índice)
            if (!$this->hasIndex('pedido_epp', 'idx_homologado_de')) {
                $table->index('homologado_de', 'idx_homologado_de');
            }
        });

        // ALTA: pedido_anexos_historial - Subqueries sin índice
        Schema::table('pedido_anexos_historial', function (Blueprint $table) {
            // Índice para joins y subqueries
            if (!$this->hasIndex('pedido_anexos_historial', 'idx_pedido_id')) {
                $table->index('pedido_produccion_id', 'idx_pedido_id');
            }

            // Índice para ORDER BY en subqueries
            if (!$this->hasIndex('pedido_anexos_historial', 'idx_created')) {
                $table->index('created_at', 'idx_created');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_numero_pedido');
            $table->dropIndexIfExists('idx_area');
            $table->dropIndexIfExists('idx_numero_estado');
            $table->dropIndexIfExists('idx_empresa');
            $table->dropIndexIfExists('idx_estado');
        });

        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_numero_pedido');
            $table->dropIndexIfExists('idx_estado');
        });

        Schema::table('pedido_epp', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_homologado_de');
        });

        Schema::table('pedido_anexos_historial', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_pedido_id');
            $table->dropIndexIfExists('idx_created');
        });
    }

    private function hasIndex($table, $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $idx) {
            if ($idx->Key_name === $index) {
                return true;
            }
        }
        return false;
    }
};
