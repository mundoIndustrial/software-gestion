<?php

namespace App\Http\Controllers\Bodega;

use App\Http\Controllers\Controller;
use App\Application\Bodega\CQRS\CQRSManager;
use App\Domain\Bodega\EventSourcing\EventStoreInterface;
use App\Models\PedidoEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Controller para dashboard de monitoreo CQRS y Event Sourcing
 */
class CQRSDashboardController extends Controller
{
    public function __construct(
        private CQRSManager $cqrsManager,
        private EventStoreInterface $eventStore
    ) {}

    /**
     * Dashboard principal de CQRS
     */
    public function index()
    {
        return view('bodega.cqrs-dashboard');
    }

    /**
     * Obtener métricas en tiempo real
     */
    public function metrics(Request $request): JsonResponse
    {
        try {
            $periodo = $request->input('periodo', '24h'); // 1h, 24h, 7d, 30d
            
            $metrics = [
                'cqrs_stats' => $this->cqrsManager->getStats(),
                'event_sourcing_stats' => $this->getEventSourcingStats($periodo),
                'performance_metrics' => $this->getPerformanceMetrics($periodo),
                'system_health' => $this->getSystemHealth(),
                'recent_events' => $this->getRecentEvents(10),
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'generated_at' => now()->toDateTimeString(),
                'period' => $periodo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métricas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de Event Sourcing
     */
    private function getEventSourcingStats(string $periodo): array
    {
        $date = $this->getDateFromPeriod($periodo);
        
        $totalEvents = PedidoEvent::where('occurred_at', '>=', $date)->count();
        $uniqueAggregates = PedidoEvent::where('occurred_at', '>=', $date)
            ->distinct('aggregate_id')
            ->count('aggregate_id');
        
        $eventTypes = PedidoEvent::where('occurred_at', '>=', $date)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderBy('count', 'desc')
            ->get();

        $eventsPorHora = PedidoEvent::where('occurred_at', '>=', $date)
            ->selectRaw('DATE_FORMAT(occurred_at, "%Y-%m-%d %H:00:00") as hora, COUNT(*) as count')
            ->groupBy('hora')
            ->orderBy('hora', 'desc')
            ->limit(24)
            ->get();

        return [
            'total_events' => $totalEvents,
            'unique_aggregates' => $uniqueAggregates,
            'events_per_aggregate' => $uniqueAggregates > 0 ? round($totalEvents / $uniqueAggregates, 2) : 0,
            'event_types' => $eventTypes->toArray(),
            'events_by_hour' => $eventsPorHora->toArray(),
            'period' => $periodo
        ];
    }

    /**
     * Obtener métricas de performance
     */
    private function getPerformanceMetrics(string $periodo): array
    {
        $date = $this->getDateFromPeriod($periodo);
        
        // Tiempo promedio de queries (simulado - en producción vendría de logs)
        $avgQueryTime = $this->getAverageQueryTime($date);
        
        // Tasa de éxito de commands
        $commandSuccessRate = $this->getCommandSuccessRate($date);
        
        // Uso de cache
        $cacheHitRate = $this->getCacheHitRate();
        
        return [
            'avg_query_time_ms' => $avgQueryTime,
            'command_success_rate' => $commandSuccessRate,
            'cache_hit_rate' => $cacheHitRate,
            'total_queries' => $this->getTotalQueries($date),
            'total_commands' => $this->getTotalCommands($date),
            'slow_queries' => $this->getSlowQueries($date, 1000), // > 1 segundo
            'period' => $periodo
        ];
    }

    /**
     * Obtener salud del sistema
     */
    private function getSystemHealth(): array
    {
        return [
            'database_connection' => $this->checkDatabaseConnection(),
            'event_store_size' => $this->getEventStoreSize(),
            'cache_status' => $this->getCacheStatus(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
        ];
    }

    /**
     * Obtener eventos recientes
     */
    private function getRecentEvents(int $limit): array
    {
        return PedidoEvent::with('metadata')
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'aggregate_id' => $event->aggregate_id,
                    'event_type' => $event->getEventShortNameAttribute(),
                    'version' => $event->version,
                    'occurred_at' => $event->occurred_at->toDateTimeString(),
                    'es_importante' => $event->esEventoImportante(),
                    'usuario' => $event->getUsuarioAttribute(),
                    'ip' => $event->getIpAttribute(),
                ];
            })
            ->toArray();
    }

    /**
     * Obtener eventos por tipo
     */
    public function eventsByType(Request $request): JsonResponse
    {
        try {
            $eventType = $request->input('event_type');
            $fromDate = $request->input('from_date') ? new Carbon($request->input('from_date')) : null;
            
            if (!$eventType) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tipo de evento es requerido'
                ], 400);
            }

            $events = $this->eventStore->getEventsByType($eventType, $fromDate);

            return response()->json([
                'success' => true,
                'data' => $events,
                'event_type' => $eventType,
                'from_date' => $fromDate?->toDateTimeString(),
                'total' => count($events)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener eventos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar eventos antiguos
     */
    public function cleanupEvents(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 30);
            $beforeDate = Carbon::now()->subDays($days);
            
            $deletedCount = $this->eventStore->cleanupOldEvents($beforeDate);

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deletedCount} eventos anteriores a {$beforeDate->toDateTimeString()}",
                'deleted_count' => $deletedCount,
                'cleanup_date' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar eventos: ' . $e->getMessage()
            ], 500);
        }
    }

    // Métodos auxiliares
    private function getDateFromPeriod(string $periodo): Carbon
    {
        return match($periodo) {
            '1h' => Carbon::now()->subHour(),
            '24h' => Carbon::now()->subDay(),
            '7d' => Carbon::now()->subWeek(),
            '30d' => Carbon::now()->subMonth(),
            default => Carbon::now()->subDay(),
        };
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getEventStoreSize(): array
    {
        $count = PedidoEvent::count();
        $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_name = 'pedido_events'")[0]->size_mb ?? 0;
        
        return [
            'total_events' => $count,
            'size_mb' => $size,
        ];
    }

    private function getCacheStatus(): array
    {
        // Simulado - en producción verificaría el cache real
        return [
            'status' => 'active',
            'hit_rate' => 85.5,
            'total_keys' => 1234,
            'memory_usage' => '45.2MB'
        ];
    }

    private function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return [
            'current' => round($memoryUsage / 1024 / 1024, 2) . 'MB',
            'limit' => $memoryLimit,
            'percentage' => round(($memoryUsage / $this->parseMemoryLimit($memoryLimit)) * 100, 2)
        ];
    }

    private function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        return [
            'total' => round($total / 1024 / 1024 / 1024, 2) . 'GB',
            'used' => round($used / 1024 / 1024 / 1024, 2) . 'GB',
            'free' => round($free / 1024 / 1024 / 1024, 2) . 'GB',
            'percentage' => round(($used / $total) * 100, 2)
        ];
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = strtolower($limit);
        $multiplier = 1;
        
        if (str_ends_with($limit, 'g')) {
            $multiplier = 1024 * 1024 * 1024;
        } elseif (str_ends_with($limit, 'm')) {
            $multiplier = 1024 * 1024;
        } elseif (str_ends_with($limit, 'k')) {
            $multiplier = 1024;
        }
        
        return (int) $limit * $multiplier;
    }

    // Métodos simulados para métricas (en producción vendrían de logs reales)
    private function getAverageQueryTime(\DateTime $date): float
    {
        return 125.5; // ms
    }

    private function getCommandSuccessRate(\DateTime $date): float
    {
        return 98.7; // %
    }

    private function getCacheHitRate(): float
    {
        return 85.5; // %
    }

    private function getTotalQueries(\DateTime $date): int
    {
        return 15420;
    }

    private function getTotalCommands(\DateTime $date): int
    {
        return 3245;
    }

    private function getSlowQueries(\DateTime $date, int $thresholdMs): int
    {
        return 12;
    }
}
