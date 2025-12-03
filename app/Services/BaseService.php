<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Base Service Class
 * 
 * Clase base para todos los servicios.
 * Proporciona métodos comunes de logging y manejo de errores.
 */
abstract class BaseService
{
    /**
     * Log de información
     */
    protected function log($message, $data = [])
    {
        Log::info("Service: " . static::class, [
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Log de error
     */
    protected function logError($message, $exception = null)
    {
        Log::error("Service: " . static::class, [
            'message' => $message,
            'exception' => $exception ? $exception->getMessage() : null,
            'trace' => $exception ? $exception->getTraceAsString() : null,
        ]);
    }

    /**
     * Log de advertencia
     */
    protected function logWarning($message, $data = [])
    {
        Log::warning("Service: " . static::class, [
            'message' => $message,
            'data' => $data,
        ]);
    }
}
