<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Middleware para logging de operaciones CQRS
 * Registra información detallada de Commands y Queries para debugging y auditoría
 */
class CQRSLoggingMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Registrar inicio de la request
        $requestId = uniqid('req_', true);
        Log::info("CQRS Request Iniciada", [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => Carbon::now()->toDateTimeString()
        ]);

        // Ejecutar la request
        $response = $next($request);

        // Calcular tiempo de ejecución
        $executionTime = round((microtime(true) - $startTime) * 1000, 2); // en ms

        // Registrar fin de la request
        Log::info("CQRS Request Completada", [
            'request_id' => $requestId,
            'status_code' => $response->getStatusCode(),
            'execution_time_ms' => $executionTime,
            'timestamp' => Carbon::now()->toDateTimeString()
        ]);

        // Alertar si el tiempo de ejecución es muy alto
        if ($executionTime > 5000) { // 5 segundos
            Log::warning("CQRS Request Lenta Detectada", [
                'request_id' => $requestId,
                'execution_time_ms' => $executionTime,
                'url' => $request->fullUrl()
            ]);
        }

        return $response;
    }
}
