<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * QueryOptimizerService
 * 
 * Audita queries en desarrollo para detectar N+1 problems
 * y bottlenecks en BD.
 */
class QueryOptimizerService
{
    private static $queriesExecutadas = [];
    private static $ejecucionesActiva = false;

    /**
     * Iniciar auditoría de queries
     */
    public static function iniciarAuditoria(): void
    {
        if (!config('app.debug')) {
            return;
        }

        self::$queriesExecutadas = [];
        self::$ejecucionesActiva = true;

        DB::listen(function ($query) {
            if (self::$ejecucionesActiva) {
                self::$queriesExecutadas[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'tiempo_total' => array_sum(
                        array_column(self::$queriesExecutadas, 'time')
                    ) + $query->time
                ];
            }
        });
    }

    /**
     * Finalizar y reportar auditoría
     */
    public static function finalizarYReportar(string $contexto = ''): void
    {
        if (!config('app.debug') || !self::$ejecucionesActiva) {
            return;
        }

        self::$ejecucionesActiva = false;

        $totalQueries = count(self::$queriesExecutadas);
        $tiempoTotal = array_sum(array_column(self::$queriesExecutadas, 'time'));

        \Log::debug('QueryOptimizer Report', [
            'contexto' => $contexto,
            'total_queries' => $totalQueries,
            'tiempo_total_ms' => round($tiempoTotal, 2),
            'queries' => self::$queriesExecutadas
        ]);

        // Alerta si hay demasiadas queries
        if ($totalQueries > 20) {
            \Log::warning('Posible N+1 problem detectado', [
                'contexto' => $contexto,
                'cantidad_queries' => $totalQueries,
                'queries' => array_map(fn($q) => $q['sql'], self::$queriesExecutadas)
            ]);
        }

        // Alerta si queries lentas
        $queriesLentas = array_filter(
            self::$queriesExecutadas,
            fn($q) => $q['time'] > 100 // más de 100ms
        );

        if (!empty($queriesLentas)) {
            \Log::warning('Queries lentas detectadas', [
                'contexto' => $contexto,
                'queries_lentas' => array_map(
                    fn($q) => ['sql' => $q['sql'], 'time_ms' => round($q['time'], 2)],
                    $queriesLentas
                )
            ]);
        }
    }

    /**
     * Obtener reporte de queries
     */
    public static function obtenerReporte(): array
    {
        return [
            'total' => count(self::$queriesExecutadas),
            'tiempo_total_ms' => array_sum(array_column(self::$queriesExecutadas, 'time')),
            'queries' => self::$queriesExecutadas
        ];
    }
}
