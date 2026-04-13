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
    private const IDEMPOTENCY_RESULT_TTL_SECONDS = 300;
    private const IDEMPOTENCY_WAIT_MAX_ITERATIONS = 20;
    private const IDEMPOTENCY_WAIT_SLEEP_MICROSECONDS = 200000; // 200ms

    public function __construct(
        private CrearPedidoCompleteUseCase $crearPedidoUseCase,
    ) {}

    public function crearPedido(Request $request): JsonResponse
    {
        $userId = Auth::id() ?? 0;
        $fingerprint = $this->buildIdempotencyFingerprint($request, $userId);
        $baseKey = "pedidos:crear:idempotency:{$fingerprint}";
        $processingKey = "{$baseKey}:processing";
        $resultKey = "{$baseKey}:result";

        try {
            $cachedResult = Cache::get($resultKey);
            if (is_array($cachedResult)) {
                Log::warning('[CrearPedidoController::crearPedido] Replay idempotente (cache directa)', [
                    'usuario_id' => $userId,
                    'pedido_id' => $cachedResult['pedido_id'] ?? null,
                    'fingerprint' => $fingerprint,
                ]);

                return response()->json($cachedResult, 200);
            }

            $processingAcquired = Cache::add(
                $processingKey,
                now()->timestamp,
                now()->addSeconds(self::IDEMPOTENCY_PROCESSING_TTL_SECONDS)
            );

            if (!$processingAcquired) {
                for ($i = 0; $i < self::IDEMPOTENCY_WAIT_MAX_ITERATIONS; $i++) {
                    usleep(self::IDEMPOTENCY_WAIT_SLEEP_MICROSECONDS);

                    $cachedResult = Cache::get($resultKey);
                    if (is_array($cachedResult)) {
                        Log::warning('[CrearPedidoController::crearPedido] Replay idempotente (espera)', [
                            'usuario_id' => $userId,
                            'pedido_id' => $cachedResult['pedido_id'] ?? null,
                            'fingerprint' => $fingerprint,
                            'iteracion' => $i + 1,
                        ]);

                        return response()->json($cachedResult, 200);
                    }
                }

                Log::warning('[CrearPedidoController::crearPedido] Solicitud duplicada en curso', [
                    'usuario_id' => $userId,
                    'fingerprint' => $fingerprint,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Ya estamos procesando este pedido. Espera unos segundos e intenta nuevamente.',
                ], 429);
            }

            Log::info('[CrearPedidoController::crearPedido] Iniciado', [
                'usuario_id' => $userId,
                'fingerprint' => $fingerprint,
            ]);

            $input = CrearPedidoInput::fromRequest($request, $userId);
            $output = $this->crearPedidoUseCase->ejecutar($input);

            $payload = $output->toArray();
            $statusCode = $output->success ? 200 : 500;

            if ($output->success) {
                Cache::put(
                    $resultKey,
                    $payload,
                    now()->addSeconds(self::IDEMPOTENCY_RESULT_TTL_SECONDS)
                );
            }

            Log::info('[CrearPedidoController::crearPedido] Completado', [
                'usuario_id' => $userId,
                'success' => $output->success,
                'pedido_id' => $output->pedido_id ?? null,
                'fingerprint' => $fingerprint,
            ]);

            return response()->json($payload, $statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CrearPedidoController::crearPedido] Validacion fallida', [
                'usuario_id' => $userId,
                'errors' => $e->errors(),
                'fingerprint' => $fingerprint,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos invalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[CrearPedidoController::crearPedido] Error', [
                'usuario_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'fingerprint' => $fingerprint,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 500);
        } finally {
            Cache::forget($processingKey);
        }
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
