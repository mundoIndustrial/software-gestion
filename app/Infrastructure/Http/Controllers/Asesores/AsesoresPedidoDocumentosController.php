<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\DTOs\ObtenerRecibosDTO;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\Pedidos\UseCases\ObtenerRecibosUseCase;
use App\Models\PedidoProduccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

final class AsesoresPedidoDocumentosController extends Controller
{
    public function __construct(
        private readonly ObtenerFacturaUseCase $obtenerFacturaUseCase,
        private readonly ObtenerRecibosUseCase $obtenerRecibosUseCase
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status = 500): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    public function obtenerDatosFactura(int|string $id): JsonResponse
    {
        try {
            Log::info('[EPP-DEBUG][FACTURA-DATOS] Request', [
                'pedido_id' => $id,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
                'roles' => method_exists(auth()->user(), 'getRoleNames')
                    ? auth()->user()?->getRoleNames()?->toArray()
                    : [],
            ]);

            $dto = ObtenerFacturaDTO::fromRequest((string) $id);
            $datos = $this->obtenerFacturaUseCase->ejecutar($dto);

            // Hardening: algunos payloads de factura-datos no incluyen estado.
            // En ese caso, leer el estado real de pedidos_produccion para que frontend no use fallback incorrecto.
            if (!array_key_exists('estado', $datos) || $datos['estado'] === null || trim((string) $datos['estado']) === '') {
                $estadoReal = PedidoProduccion::query()
                    ->where('id', (int) $id)
                    ->value('estado');

                if ($estadoReal !== null && trim((string) $estadoReal) !== '') {
                    $datos['estado'] = $estadoReal;
                }
            }

            Log::info('[EPP-DEBUG][FACTURA-DATOS] Response', [
                'pedido_id' => $id,
                'estado' => $datos['estado'] ?? null,
                'numero_pedido' => $datos['numero_pedido'] ?? ($datos['numero'] ?? null),
                'epps_count' => is_array($datos['epps'] ?? null) ? count($datos['epps']) : null,
                'epps_transformados_count' => is_array($datos['epps_transformados'] ?? null) ? count($datos['epps_transformados']) : null,
                'keys' => array_keys($datos),
            ]);

            return $this->json([
                'success' => true,
                'data' => $datos,
            ]);
        } catch (\Throwable $e) {
            Log::error('[AsesoresPedidoDocumentosController.obtenerDatosFactura] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error obteniendo datos de la factura.', 500);
        }
    }

    public function obtenerDatosRecibos(int|string $id): JsonResponse
    {
        try {
            $dto = ObtenerRecibosDTO::fromRequest((string) $id);
            $datos = $this->obtenerRecibosUseCase->ejecutar($dto);

            return $this->json([
                'success' => true,
                'data' => $datos,
            ]);
        } catch (\Throwable $e) {
            Log::error('[AsesoresPedidoDocumentosController.obtenerDatosRecibos] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error obteniendo datos de los recibos.', 500);
        }
    }
}
