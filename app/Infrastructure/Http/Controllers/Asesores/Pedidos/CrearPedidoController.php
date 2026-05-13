<?php

namespace App\Infrastructure\Http\Controllers\Asesores\Pedidos;

use App\Application\Pedidos\UseCases\CrearPedidoCompleteUseCase;
use App\Application\Pedidos\UseCases\CrearPedidoInput;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CrearPedidoController extends Controller
{
    private const IDEMPOTENCY_PROCESSING_TTL_SECONDS = 60;
    private const IDEMPOTENCY_RESULT_TTL_SECONDS = 3600; // 1 hora (balance: seguridad vs memoria)
    private const IDEMPOTENCY_WAIT_MAX_ITERATIONS = 20;
    private const IDEMPOTENCY_WAIT_SLEEP_MICROSECONDS = 200000; // 200ms

    public function __construct(
        private CrearPedidoCompleteUseCase $crearPedidoUseCase,
    ) {}

    /**
     * Verificar si un pedido ya fue creado basado en idempotency key
     * Esto previene doble-creación cuando hay errores de conexión
     */
    public function verificarPedidoYaCreado(Request $request): JsonResponse
    {
        try {
            $idempotencyKey = trim((string) $request->header('X-Idempotency-Key', ''));
            $userId = Auth::id() ?? 0;

            if (empty($idempotencyKey)) {
                return response()->json([
                    'existe' => false,
                    'mensaje' => 'Sin idempotency key',
                ], 400);
            }

            $fingerprint = $this->buildIdempotencyFingerprint($request, $userId);
            $baseKey = "pedidos:crear:idempotency:{$fingerprint}";
            $resultKey = "{$baseKey}:result";

            // IMPORTANTE: Usar try-catch por si el cache falla
            try {
                $cachedResult = Cache::get($resultKey);
            } catch (\Exception $e) {
                Log::warning('cache_read_error_verificar', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'fingerprint' => $fingerprint,
                ]);
                // Si cache falla, asumir que no existe (mejor que doble-creación)
                return response()->json([
                    'existe' => false,
                    'mensaje' => 'No se pudo verificar (cache error)',
                ], 200);
            }

            if (is_array($cachedResult)) {
                Log::info('pedido_ya_existe_cache', [
                    'user_id' => $userId,
                    'idempotency_key' => $idempotencyKey,
                    'fingerprint' => $fingerprint,
                    'pedido_id' => $cachedResult['pedido_id'] ?? null,
                    'numero_pedido' => $cachedResult['numero_pedido'] ?? null,
                ]);

                return response()->json([
                    'existe' => true,
                    'pedido_id' => $cachedResult['pedido_id'] ?? null,
                    'numero_pedido' => $cachedResult['numero_pedido'] ?? null,
                    'mensaje' => 'Pedido ya fue creado exitosamente',
                ], 200);
            }

            return response()->json([
                'existe' => false,
                'mensaje' => 'Pedido no encontrado en cache',
            ], 200);
        } catch (\Exception $e) {
            Log::error('verificar_pedido_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'existe' => false,
                'mensaje' => 'Error al verificar pedido',
            ], 500);
        }
    }

    public function crearPedido(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $userId = Auth::id() ?? 0;
        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key', ''));
        $payloadHash = $this->buildPayloadHash($request);
        $fingerprint = $this->buildIdempotencyFingerprint($request, $userId);
        $auditContext = $this->buildAuditContext($request, $userId, $idempotencyKey, $fingerprint, $payloadHash);
        $baseKey = "pedidos:crear:idempotency:{$fingerprint}";
        $processingKey = "{$baseKey}:processing";
        $resultKey = "{$baseKey}:result";

        try {
            Log::info('pedido_create_attempt', $auditContext);

            try {
                $cachedResult = Cache::get($resultKey);
            } catch (\Exception $e) {
                Log::warning('cache_read_error_inicio', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ]);
                $cachedResult = null;
            }

            if (is_array($cachedResult)) {
                Log::warning('pedido_create_replay', array_merge($auditContext, [
                    'replay_source' => 'direct_cache',
                    'pedido_id' => $cachedResult['pedido_id'] ?? null,
                    'numero_pedido' => $cachedResult['numero_pedido'] ?? null,
                ]));

                return response()->json($cachedResult, 200);
            }

            try {
                $processingAcquired = Cache::add(
                    $processingKey,
                    now()->timestamp,
                    now()->addSeconds(self::IDEMPOTENCY_PROCESSING_TTL_SECONDS)
                );
            } catch (\Exception $e) {
                Log::warning('cache_lock_error', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ]);
                // Si el lock falla, permitir proceder (mejor que bloquear)
                $processingAcquired = true;
            }

            if (!$processingAcquired) {
                for ($i = 0; $i < self::IDEMPOTENCY_WAIT_MAX_ITERATIONS; $i++) {
                    usleep(self::IDEMPOTENCY_WAIT_SLEEP_MICROSECONDS);

                    try {
                        $cachedResult = Cache::get($resultKey);
                    } catch (\Exception $e) {
                        $cachedResult = null;
                    }

                    if (is_array($cachedResult)) {
                        Log::warning('pedido_create_replay', array_merge($auditContext, [
                            'replay_source' => 'wait_cache',
                            'pedido_id' => $cachedResult['pedido_id'] ?? null,
                            'numero_pedido' => $cachedResult['numero_pedido'] ?? null,
                            'iteracion' => $i + 1,
                        ]));

                        return response()->json($cachedResult, 200);
                    }
                }

                Log::warning('pedido_create_blocked', array_merge($auditContext, [
                    'reason' => 'in_progress_lock',
                ]));

                return response()->json([
                    'success' => false,
                    'message' => 'Ya estamos procesando este pedido. Espera unos segundos e intenta nuevamente.',
                ], 429);
            }

            $input = CrearPedidoInput::fromRequest($request, $userId);
            $output = $this->crearPedidoUseCase->ejecutar($input);

            $payload = $output->toArray();
            $statusCode = $output->success ? 200 : 500;

            if ($output->success) {
                try {
                    Cache::put(
                        $resultKey,
                        $payload,
                        now()->addSeconds(self::IDEMPOTENCY_RESULT_TTL_SECONDS)
                    );
                } catch (\Exception $cacheError) {
                    // Si cache falla, el pedido ya se creó (en BD)
                    // Log el error pero devuelve éxito al cliente
                    Log::warning('cache_write_error_pedido_creado', [
                        'error' => $cacheError->getMessage(),
                        'pedido_id' => $output->pedido_id,
                        'user_id' => $userId,
                        'fingerprint' => $fingerprint,
                    ]);
                    // El pedido se creó pero el cache falló
                    // Devolvemos éxito porque el pedido está en BD
                }
            }

            Log::info('pedido_create_result', array_merge($auditContext, [
                'success' => $output->success,
                'pedido_id' => $output->pedido_id ?? null,
                'numero_pedido' => $output->numero_pedido ?? null,
                'status_code' => $statusCode,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]));

            return response()->json($payload, $statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('pedido_create_result', array_merge($auditContext, [
                'success' => false,
                'status_code' => 422,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'errors' => $e->errors(),
                'reason' => 'validation_error',
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Datos invalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('pedido_create_result', array_merge($auditContext, [
                'success' => false,
                'status_code' => 500,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reason' => 'exception',
            ]));

            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 500);
        } finally {
            Cache::forget($processingKey);
        }
    }

    private function buildAuditContext(
        Request $request,
        int $userId,
        string $idempotencyKey,
        string $fingerprint,
        string $payloadHash
    ): array {
        return [
            'user_id' => $userId,
            'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
            'request_fingerprint' => $fingerprint,
            'payload_hash' => $payloadHash,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => optional($request->route())->getName(),
            'url' => $request->fullUrl(),
        ];
    }

    private function buildPayloadHash(Request $request): string
    {
        $pedidoPayload = $this->normalizePedidoPayload($request->input('pedido'));
        $filesMeta = $this->extractFilesMeta($request->allFiles());

        return hash('sha256', json_encode([
            'pedido' => $pedidoPayload,
            'files' => $filesMeta,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function buildIdempotencyFingerprint(Request $request, int $userId): string
    {
        $requestKey = trim((string) $request->header('X-Idempotency-Key', ''));
        if ($requestKey !== '') {
            return hash('sha256', "user:{$userId}|request_key:{$requestKey}");
        }

        $pedidoPayload = $this->normalizePedidoPayload($request->input('pedido'));
        $filesMeta = $this->extractFilesMeta($request->allFiles());

        $base = [
            'user_id' => $userId,
            'route' => 'api/asesores/pedidos/crear',
            'pedido' => $pedidoPayload,
            'files' => $filesMeta,
        ];

        return hash('sha256', json_encode($base, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function normalizePedidoPayload(mixed $pedido): mixed
    {
        if (is_string($pedido)) {
            $decoded = json_decode($pedido, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->ksortRecursive($decoded);
            }

            return trim($pedido);
        }

        if (is_array($pedido)) {
            return $this->ksortRecursive($pedido);
        }

        return $pedido;
    }

    private function ksortRecursive(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if ($this->isAssoc($value)) {
            ksort($value);
        }

        foreach ($value as $key => $child) {
            $value[$key] = $this->ksortRecursive($child);
        }

        return $value;
    }

    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function extractFilesMeta(array $files, string $prefix = ''): array
    {
        $result = [];

        foreach ($files as $key => $fileOrArray) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($fileOrArray)) {
                $result = array_merge($result, $this->extractFilesMeta($fileOrArray, $path));
                continue;
            }

            if ($fileOrArray instanceof \Illuminate\Http\UploadedFile) {
                $result[$path] = [
                    'name' => $fileOrArray->getClientOriginalName(),
                    'size' => $fileOrArray->getSize(),
                    'mime' => $fileOrArray->getMimeType(),
                ];
            }
        }

        ksort($result);

        return $result;
    }
}
