<?php

namespace App\Support;

use Illuminate\Database\Events\QueryExecuted;

class QueryPerformanceTracker
{
    private array $queries = [];
    private array $queryHashes = [];
    private float $startTime;
    private float $totalSqlTime = 0;
    private array $phases = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    public function recordQuery(QueryExecuted $event): void
    {
        $this->queries[] = [
            'sql' => $event->sql,
            'time' => $event->time,
        ];

        $this->totalSqlTime += $event->time;

        $hash = md5($event->sql . json_encode($event->bindings));
        $this->queryHashes[$hash] = ($this->queryHashes[$hash] ?? 0) + 1;
    }

    public function startPhase(string $name): void
    {
        $this->phases[$name] = [
            'start' => microtime(true),
            'queries' => count($this->queries)
        ];
    }

    public function endPhase(string $name): void
    {
        if (!isset($this->phases[$name])) {
            return;
        }

        $endTime = microtime(true);
        $this->phases[$name]['duration_ms'] = ($endTime - $this->phases[$name]['start']) * 1000;
        $this->phases[$name]['new_queries'] = count($this->queries) - $this->phases[$name]['queries'];
    }

    public function getReport(): array
    {
        $totalTime = (microtime(true) - $this->startTime) * 1000;
        $totalQueries = count($this->queries);
        $slowQueries = count(array_filter($this->queries, fn($q) => $q['time'] > 100));

        $duplicatedCount = 0;
        foreach ($this->queryHashes as $count) {
            if ($count > 1) {
                $duplicatedCount += ($count - 1);
            }
        }

        $phpTime = $totalTime - $this->totalSqlTime;

        return [
            'tiempo_total_ms' => round($totalTime, 2),
            'total_queries' => $totalQueries,
            'queries_lentas_100ms' => $slowQueries,
            'queries_duplicadas' => $duplicatedCount,
            'tiempo_sql_ms' => round($this->totalSqlTime, 2),
            'tiempo_php_ms' => round($phpTime, 2),
            'promedio_por_query_ms' => $totalQueries > 0 ? round($totalTime / $totalQueries, 2) : 0,
            'fases' => $this->phases,
        ];
    }

    public function logCompact(string $endpoint): string
    {
        $report = $this->getReport();

        return sprintf(
            '[%s] tiempo=%dms | queries=%d (lentas=%d, dup=%d) | SQL=%dms | PHP=%dms',
            $endpoint,
            (int) $report['tiempo_total_ms'],
            $report['total_queries'],
            $report['queries_lentas_100ms'],
            $report['queries_duplicadas'],
            (int) $report['tiempo_sql_ms'],
            (int) $report['tiempo_php_ms']
        );
    }

    public function logFull(): void
    {
        $report = $this->getReport();

        \Log::info('[PERF] ' . $this->logCompact('obtenerDetallePedido'), [
            'detalle' => $report,
            'fases' => array_map(fn($f) => [
                'duration_ms' => round($f['duration_ms'] ?? 0, 2),
                'new_queries' => $f['new_queries'] ?? 0,
            ], $this->phases),
        ]);
    }
}
