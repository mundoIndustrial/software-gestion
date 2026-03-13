<?php

namespace App\Infrastructure\Http\Controllers\Traits;

use Illuminate\Validation\ValidationException;
use Exception;
use Log;

trait HandlesExceptions
{
    /**
     * Maneja excepciones comunes en controladores
     * Retorna una respuesta JSON estructurada
     * 
     * @param Exception $e
     * @param string $context Contexto para logging (ej: "guardar ancho y metraje")
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(Exception $e, string $context = 'operación')
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        }

        // Log para excepciones generales
        Log::error("Error al {$context}: " . $e->getMessage(), [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => "Error al {$context}"
        ], 500);
    }

    /**
     * Maneja excepciones con logging personalizado
     * 
     * @param Exception $e
     * @param string $message Mensaje de error para el usuario
     * @param array $logContext Contexto adicional para logging
     * @param int $statusCode Código HTTP
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleExceptionWithContext(
        Exception $e,
        string $message,
        array $logContext = [],
        int $statusCode = 500
    ) {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $e->errors()
            ], 422);
        }

        Log::error($message . ': ' . $e->getMessage(), array_merge([
            'exception' => get_class($e),
        ], $logContext));

        return response()->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
}
