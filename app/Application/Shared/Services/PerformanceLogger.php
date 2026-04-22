<?php

namespace App\Application\Shared\Services;

use Illuminate\Support\Facades\Log;

/**
 * PerformanceLogger - Servicio centralizado de logging de performance
 *
 * Responsabilidades:
 * - Medir tiempo de operaciones
 * - Registrar métricas en logs
 * - Rastrear memory usage
 * - Timing detallado por sección
 */
class PerformanceLogger
{
    private static $startTime = null;
    private static $markers = [];
    private static $requestId = null;

    /**
     * Iniciar medición de request
     */
    public static function startRequest($endpoint = '')
    {
        self::$startTime = microtime(true);
        self::$requestId = substr(md5(uniqid()), 0, 8);
        self::$markers = [];

        Log::info('[PERF] 🚀 REQUEST STARTED', [
            'request_id' => self::$requestId,
            'endpoint' => $endpoint,
            'timestamp' => now()->toIso8601String(),
            'memory_initial_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ]);

        return self::$requestId;
    }

    /**
     * Marcar un hito (marker) en la ejecución
     */
    public static function marker($label, $details = [])
    {
        if (!self::$startTime) {
            self::startRequest();
        }

        $currentTime = microtime(true);
        $totalTime = ($currentTime - self::$startTime) * 1000; // en ms
        $lastMarkerTime = 0;

        if (!empty(self::$markers)) {
            $lastMarkerTime = ($currentTime - self::$markers[count(self::$markers) - 1]['timestamp']) * 1000;
        }

        self::$markers[] = [
            'label' => $label,
            'timestamp' => $currentTime,
            'total_ms' => $totalTime,
            'delta_ms' => $lastMarkerTime,
            'details' => $details,
        ];

        Log::info('[PERF] ⏱️ ' . $label, [
            'request_id' => self::$requestId,
            'total_ms' => round($totalTime, 2),
            'delta_ms' => round($lastMarkerTime, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            ...$details,
        ]);
    }

    /**
     * Finalizar medición y mostrar resumen
     */
    public static function endRequest($responseCode = 200)
    {
        if (!self::$startTime) {
            return;
        }

        $totalTime = (microtime(true) - self::$startTime) * 1000; // en ms

        $summary = [
            'request_id' => self::$requestId,
            'status' => $responseCode,
            'total_time_ms' => round($totalTime, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'markers_count' => count(self::$markers),
        ];

        // Agregar desglose de markers
        foreach (self::$markers as $index => $marker) {
            $summary['marker_' . ($index + 1)] = $marker['label'] . ' (' . round($marker['delta_ms'], 2) . 'ms)';
        }

        Log::info('[PERF] ✅ REQUEST FINISHED', $summary);

        // Retornar para incluir en respuesta si es necesario
        return $summary;
    }

    /**
     * Obtener resumen actual
     */
    public static function getSummary()
    {
        if (!self::$startTime) {
            return null;
        }

        $totalTime = (microtime(true) - self::$startTime) * 1000;

        return [
            'request_id' => self::$requestId,
            'total_time_ms' => round($totalTime, 2),
            'markers' => self::$markers,
        ];
    }

    /**
     * Obtener request ID actual
     */
    public static function getRequestId()
    {
        return self::$requestId;
    }
}
