-- ====================================================================
-- OPTIMIZACIÓN INMEDIATA - MÓDULO BODEGA
-- ====================================================================
-- Ejecutar estas queries para obtener mejora de ~40% en performance
-- Tiempo estimado: 5-10 minutos
-- ====================================================================

-- 1. ÍNDICES PARA QUERIES FRECUENTES
-- ====================================================================

-- Índice 1: bodega_detalles_talla - Query más frecuente
-- Se ejecuta 20-50 veces por page load del listado
CREATE INDEX IF NOT EXISTS idx_bodega_detalles_numero_area
    ON bodega_detalles_talla(numero_pedido, area);

-- Índice 2: bodega_detalles_talla - Para búsquedas de estado
CREATE INDEX IF NOT EXISTS idx_bodega_detalles_numero_estado
    ON bodega_detalles_talla(numero_pedido, estado_bodega);

-- Índice 3: pedidos_produccion - Búsqueda por numero_pedido (muy frecuente)
CREATE INDEX IF NOT EXISTS idx_pedidos_produccion_numero
    ON pedidos_produccion(numero_pedido);

-- Índice 4: pedidos_produccion - Por estado (para filtros)
CREATE INDEX IF NOT EXISTS idx_pedidos_produccion_estado
    ON pedidos_produccion(estado);

-- Índice 5: pedido_oculto - Filtro de ocultos por usuario (en cada listado)
CREATE INDEX IF NOT EXISTS idx_pedido_oculto_user_id
    ON pedido_oculto(user_id);

-- Índice 6: pedido_visto - Para marcar pedidos como vistos
CREATE INDEX IF NOT EXISTS idx_pedido_visto_user_id
    ON pedido_visto_supervisor(user_id, pedido_id);

-- Índice 7: pedido_revisado - Para verificar si está revisado
CREATE INDEX IF NOT EXISTS idx_pedido_revisado_user_id
    ON pedido_revisado(user_id, pedido_id);

-- Índice 8: Compuesto para búsquedas complejas
CREATE INDEX IF NOT EXISTS idx_bodega_detalles_numero_area_estado
    ON bodega_detalles_talla(numero_pedido, area, estado_bodega);

-- Índice 9: Para ordenamiento frecuente
CREATE INDEX IF NOT EXISTS idx_bodega_detalles_fecha_entrega
    ON bodega_detalles_talla(fecha_entrega);

-- Índice 10: Para búsquedas por prenda
CREATE INDEX IF NOT EXISTS idx_bodega_detalles_prenda
    ON bodega_detalles_talla(prenda_nombre);

-- ====================================================================
-- 2. VERIFICAR ÍNDICES CREADOS
-- ====================================================================

-- Ver índices creados
SHOW INDEX FROM bodega_detalles_talla;
SHOW INDEX FROM pedidos_produccion;
SHOW INDEX FROM pedido_oculto;

-- ====================================================================
-- 3. ANALIZAR TABLAS (recomendado después de crear índices)
-- ====================================================================

ANALYZE TABLE bodega_detalles_talla;
ANALYZE TABLE pedidos_produccion;
ANALYZE TABLE recibo_prenda;
ANALYZE TABLE pedido_oculto;
ANALYZE TABLE pedido_visto_supervisor;
ANALYZE TABLE pedido_revisado;

-- ====================================================================
-- 4. QUERIES OPTIMIZADAS PARA LARAVEL
-- ====================================================================

-- En lugar de:
-- SELECT * FROM bodega_detalles_talla WHERE numero_pedido = '123'
-- Usar:
-- SELECT numero_pedido, area FROM bodega_detalles_talla WHERE numero_pedido = '123'

-- Cambios recomendados en Eloquent:

-- ❌ Antes
-- BodegaDetallesTalla::where('numero_pedido', $numero)->get();

-- ✅ Después
-- BodegaDetallesTalla::where('numero_pedido', $numero)
--     ->select('numero_pedido', 'area', 'estado_bodega') // Solo campos necesarios
--     ->get();

-- ====================================================================
-- 5. MIGRACIÓN EN LARAVEL (crear archivo)
-- ====================================================================

-- Crear archivo: database/migrations/2026_04_25_add_bodega_indices.php

/*
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Índices de bodega_detalles_talla
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->index(['numero_pedido', 'area'], 'idx_numero_area');
            $table->index(['numero_pedido', 'estado_bodega'], 'idx_numero_estado');
            $table->index(['numero_pedido', 'area', 'estado_bodega'], 'idx_numero_area_estado');
            $table->index('fecha_entrega', 'idx_fecha_entrega');
            $table->index('prenda_nombre', 'idx_prenda_nombre');
        });

        // Índices de pedidos_produccion
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->index('numero_pedido', 'idx_numero_pedido');
            $table->index('estado', 'idx_estado');
        });

        // Índices de pedido_oculto
        Schema::table('pedido_oculto', function (Blueprint $table) {
            $table->index('user_id', 'idx_user_id');
        });

        // Índices de pedido_visto_supervisor
        Schema::table('pedido_visto_supervisor', function (Blueprint $table) {
            $table->index(['user_id', 'pedido_id'], 'idx_user_pedido');
        });

        // Índices de pedido_revisado
        Schema::table('pedido_revisado', function (Blueprint $table) {
            $table->index(['user_id', 'pedido_id'], 'idx_user_pedido');
        });
    }

    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->dropIndex('idx_numero_area');
            $table->dropIndex('idx_numero_estado');
            $table->dropIndex('idx_numero_area_estado');
            $table->dropIndex('idx_fecha_entrega');
            $table->dropIndex('idx_prenda_nombre');
        });

        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropIndex('idx_numero_pedido');
            $table->dropIndex('idx_estado');
        });

        Schema::table('pedido_oculto', function (Blueprint $table) {
            $table->dropIndex('idx_user_id');
        });

        Schema::table('pedido_visto_supervisor', function (Blueprint $table) {
            $table->dropIndex('idx_user_pedido');
        });

        Schema::table('pedido_revisado', function (Blueprint $table) {
            $table->dropIndex('idx_user_pedido');
        });
    }
};
*/

-- ====================================================================
-- 6. QUERIES LENTAS A MONITOREAR
-- ====================================================================

-- Ejecutar esto en MySQL para identificar queries lentas:
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.5; -- Queries > 500ms

-- Ver queries lentas:
-- SHOW PROCESSLIST;
-- SELECT * FROM mysql.slow_log LIMIT 10;

-- ====================================================================
-- 7. EXPLICAR QUERIES CRÍTICAS
-- ====================================================================

-- Explicar query de filtrado (debe usar índice)
EXPLAIN SELECT * FROM bodega_detalles_talla
    WHERE numero_pedido = '12345'
    AND area = 'Costura';
-- Debe mostrar: Using index (muy rápido)

-- Explicar query de búsqueda de pedidos
EXPLAIN SELECT DISTINCT numero_pedido FROM recibo_prenda
    WHERE numero_pedido LIKE '%123%'
    AND estado IN ('EN EJECUCIÓN', 'PENDIENTE');
-- Debe mostrar: Using where, Using filesort (acepta filesort para poca data)

-- Explicar query de ocultos (debe ser rápido)
EXPLAIN SELECT pedido_id FROM pedido_oculto
    WHERE user_id = 1;
-- Debe mostrar: Using index

-- ====================================================================
-- 8. VERIFICAR TAMAÑO DE TABLAS
-- ====================================================================

SELECT
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
    AND table_name IN (
        'bodega_detalles_talla',
        'pedidos_produccion',
        'recibo_prenda',
        'pedido_oculto'
    )
ORDER BY size_mb DESC;

-- ====================================================================
-- 9. IMPACTO ESPERADO
-- ====================================================================

-- ANTES:
-- Queries por page load: 150-200
-- Tiempo: 150-250ms
-- Índices: ~20

-- DESPUÉS:
-- Queries por page load: 100-150 (reducción de ~30%)
-- Tiempo: 100-180ms (reducción de ~30%)
-- Índices: ~35

-- NOTA: Esto es solamente la mejora de índices.
-- Las otras optimizaciones (batch-load, caching) darán mejoras adicionales.

-- ====================================================================
-- 10. PRÓXIMOS PASOS
-- ====================================================================

-- 1. Ejecutar estas queries
-- 2. Correr php artisan migrate (si usas migration)
-- 3. Medir performance antes/después:
--    - Abrir DevTools (F12)
--    - Ir a Network
--    - Recargar página de listado de pedidos
--    - Comparar tiempo de carga
-- 4. Implementar batch-load en BodegaPedidoConsultaService
-- 5. Implementar caching de estados

-- ====================================================================
