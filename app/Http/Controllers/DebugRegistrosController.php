<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controller para debugear y monitorear el rendimiento del endpoint /registros
 * Proporciona información detallada sobre:
 * - Queries ejecutadas
 * - Tiempo de ejecución
 * - Tamaño de datos
 * - Cuellos de botella
 */
class DebugRegistrosController extends Controller
{
    public function debugPerformance()
    {
        // Habilitar query logging
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $memory = memory_get_peak_usage(true) / 1024 / 1024;
        
        try {
            // Ejecutar la misma lógica del index original pero con métricas
            $query = PedidoProduccion::query()
                ->with(['asesora', 'prendas' => function($q) {
                    $q->select('id', 'numero_pedido', 'nombre_prenda', 'cantidad', 'descripcion', 'cantidad_talla', 'color_id', 'tela_id', 'tipo_manga_id', 'tipo_broche_id', 'tiene_bolsillos', 'tiene_reflectivo', 'descripcion_variaciones');
                }]);

            // Obtener totales
            $totalOrdenes = $query->count();
            $startTime1 = microtime(true);
            $ordenes = $query->paginate(25);
            $tiempo1 = microtime(true) - $startTime1;

            $queries = DB::getQueryLog();
            $totalTime = microtime(true) - $startTime;
            $currentMemory = memory_get_peak_usage(true) / 1024 / 1024;

            // Análisis detallado
            $analysis = [
                'total_ordenes' => $totalOrdenes,
                'ordenes_en_pagina' => $ordenes->count(),
                'tiempo_total_ms' => round($totalTime * 1000, 2),
                'tiempo_query_ms' => round($tiempo1 * 1000, 2),
                'total_queries' => count($queries),
                'memoria_pico_mb' => round($currentMemory, 2),
                'memoria_inicial_mb' => round($memory, 2),
                'diferencia_memoria_mb' => round($currentMemory - $memory, 2),
                'ordenes_por_segundo' => round($totalOrdenes / ($totalTime > 0 ? $totalTime : 1), 2),
                'ms_por_orden' => round($tiempo1 / ($ordenes->count() > 0 ? $ordenes->count() : 1), 2),
            ];

            // Agrupar queries por tipo
            $querysByType = [];
            foreach ($queries as $query) {
                $sql = $query['query'];
                // Obtener primer verbo SQL
                $verb = strtoupper(preg_match('/^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|SHOW)/', $sql, $m) ? $m[1] : 'OTHER');
                
                if (!isset($querysByType[$verb])) {
                    $querysByType[$verb] = [];
                }
                
                $querysByType[$verb][] = [
                    'query' => $sql,
                    'time_ms' => round($query['time'], 2),
                    'bindings' => $query['bindings']
                ];
            }

            // Top 10 queries más lentas
            $slowestQueries = [];
            foreach ($queries as $query) {
                $slowestQueries[] = [
                    'query' => $query['query'],
                    'time_ms' => round($query['time'], 2),
                    'bindings' => $query['bindings']
                ];
            }
            usort($slowestQueries, function($a, $b) {
                return $b['time_ms'] - $a['time_ms'];
            });
            $slowestQueries = array_slice($slowestQueries, 0, 10);

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
                'queries_by_type' => $querysByType,
                'slowest_queries' => $slowestQueries,
                'total_queries_detail' => count($queries),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Mostrar solo queries sin formateo JSON para análisis en log
     */
    public function listAllQueries()
    {
        DB::enableQueryLog();
        
        $query = PedidoProduccion::query()
            ->with(['asesora', 'prendas' => function($q) {
                $q->select('id', 'numero_pedido', 'nombre_prenda', 'cantidad');
            }])
            ->paginate(25);

        $queries = DB::getQueryLog();
        
        return response()->json($queries);
    }

    /**
     * Analizar tabla_original para identificar ineficiencias
     */
    public function analyzeTable()
    {
        try {
            // Información de la tabla
            $tableStats = DB::select("SELECT 
                TABLE_NAME,
                TABLE_ROWS,
                AVG_ROW_LENGTH,
                DATA_LENGTH,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as 'Size_MB'
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ('tabla_original', 'prendas_pedido', 'procesos_prenda')
            ", [env('DB_DATABASE')]);

            // Índices existentes
            $indices = DB::select("SELECT 
                TABLE_NAME,
                INDEX_NAME,
                COLUMN_NAME,
                SEQ_IN_INDEX
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'tabla_original'
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
            ", [env('DB_DATABASE')]);

            // Columnas de tabla_original
            $columns = DB::select("SELECT 
                COLUMN_NAME,
                DATA_TYPE,
                IS_NULLABLE,
                COLUMN_KEY
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'tabla_original'
            ORDER BY ORDINAL_POSITION
            ", [env('DB_DATABASE')]);

            return response()->json([
                'success' => true,
                'table_stats' => $tableStats,
                'indices' => $indices,
                'columns' => $columns,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recomendar índices basado en columnas usadas en filtros
     */
    public function suggestIndices()
    {
        $recommendations = [
            [
                'table' => 'tabla_original',
                'index_name' => 'idx_estado',
                'columns' => ['estado'],
                'reason' => 'Se usa frecuentemente en filtros (supervisor filtro por "En Ejecución")',
                'sql' => 'ALTER TABLE tabla_original ADD INDEX idx_estado (estado);'
            ],
            [
                'table' => 'tabla_original',
                'index_name' => 'idx_numero_pedido',
                'columns' => ['numero_pedido'],
                'reason' => 'Se usa en búsquedas y LEFT JOINs',
                'sql' => 'ALTER TABLE tabla_original ADD INDEX idx_numero_pedido (numero_pedido);'
            ],
            [
                'table' => 'tabla_original',
                'index_name' => 'idx_cliente_estado',
                'columns' => ['cliente', 'estado'],
                'reason' => 'Se usa combo frecuente en búsquedas',
                'sql' => 'ALTER TABLE tabla_original ADD INDEX idx_cliente_estado (cliente, estado);'
            ],
            [
                'table' => 'procesos_prenda',
                'index_name' => 'idx_numero_pedido_updated',
                'columns' => ['numero_pedido', 'updated_at'],
                'reason' => 'Se usa para obtener último proceso',
                'sql' => 'ALTER TABLE procesos_prenda ADD INDEX idx_numero_pedido_updated (numero_pedido, updated_at DESC);'
            ],
            [
                'table' => 'prendas_pedido',
                'index_name' => 'idx_numero_pedido',
                'columns' => ['numero_pedido'],
                'reason' => 'Se carga con relación with() - FK a pedidos_produccion',
                'sql' => 'ALTER TABLE prendas_pedido ADD INDEX idx_numero_pedido (numero_pedido);'
            ],
        ];

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
        ]);
    }
}
