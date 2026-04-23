<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * IdempotencyKeyMiddleware
 *
 * Previene duplicados por múltiples requests idénticos.
 * Solo aplica a POST (creación). PUT es idempotente por naturaleza.
 *
 * Uso:
 * - Cliente envía: X-Idempotency-Key: {UUID}
 * - Middleware verifica si ya procesó esa clave
 * - Si sí: retorna respuesta cached
 * - Si no: deja procesar y cachea el resultado
 */
class IdempotencyKeyMiddleware
{
    /**
     * Duración del cache de idempotencia (24 horas)
     */
    private const CACHE_MINUTES = 1440;

    /**
     * Rutas que requieren idempotencia (solo POST - creación)
     */
    private const PROTECTED_ROUTES = [
        'POST /api/asesores/pedidos/borrador',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 🔧 Solo aplicar a POST en rutas protegidas
        if ($request->getMethod() !== 'POST' || !$this->debeProtegerse($request)) {
            return $next($request);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key');

        // Si no viene clave, generar una (para compatibilidad)
        if (!$idempotencyKey) {
            Log::warning('[IdempotencyKeyMiddleware] Request POST sin X-Idempotency-Key', [
                'path' => $request->path(),
                'user_id' => auth()->id(),
            ]);
            return $next($request);
        }

        $cacheKey = $this->construirClavCache($idempotencyKey);

        // ✅ Ya procesamos esta solicitud antes
        if (Cache::has($cacheKey)) {
            $respuestaCacheada = Cache::get($cacheKey);

            Log::info('[IdempotencyKeyMiddleware] DUPLICADO DETECTADO Y RECHAZADO', [
                'idempotency_key' => $idempotencyKey,
                'user_id' => auth()->id(),
                'resultado_cacheado' => $respuestaCacheada['code'] ?? 'unknown',
            ]);

            return response()->json(
                array_merge($respuestaCacheada, [
                    'idempotency_cached' => true,
                    'message' => 'Esta solicitud ya fue procesada',
                ]),
                $respuestaCacheada['status'] ?? 200
            );
        }

        // 🔄 Procesar request y cachear resultado
        $response = $next($request);

        // Guardar respuesta en cache
        $datosParaCache = [
            'status' => $response->status(),
            'body' => json_decode($response->getContent(), true),
            'code' => json_decode($response->getContent(), true)['code'] ?? 'success',
        ];

        Cache::put($cacheKey, $datosParaCache, now()->addMinutes(self::CACHE_MINUTES));

        Log::info('[IdempotencyKeyMiddleware] Solicitud procesada y cacheada', [
            'idempotency_key' => $idempotencyKey,
            'user_id' => auth()->id(),
            'status' => $response->status(),
        ]);

        return $response;
    }

    /**
     * Verificar si esta ruta debe protegerse con idempotencia
     */
    private function debeProtegerse(Request $request): bool
    {
        $method = $request->getMethod();
        $path = $request->path();

        // POST /api/asesores/pedidos/borrador
        if ($method === 'POST' && str_contains($path, '/asesores/pedidos/borrador') && !str_contains($path, '/actualizar')) {
            return true;
        }

        return false;
    }

    /**
     * Construir clave de cache única por usuario + idempotency key
     */
    private function construirClavCache(string $idempotencyKey): string
    {
        $userId = auth()->id() ?? 'anonymous';
        return "idempotency:{$userId}:{$idempotencyKey}";
    }
}
