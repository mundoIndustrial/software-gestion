<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\DTOs\ObtenerRecibosDTO;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\Pedidos\UseCases\ObtenerRecibosUseCase;
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
            $dto = ObtenerFacturaDTO::fromRequest((string) $id);
            $datos = $this->obtenerFacturaUseCase->ejecutar($dto);

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
