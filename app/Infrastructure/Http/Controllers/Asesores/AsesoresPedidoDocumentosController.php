<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\DTOs\ObtenerRecibosDTO;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\Pedidos\UseCases\ObtenerRecibosUseCase;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

final class AsesoresPedidoDocumentosController extends Controller
{
    public function __construct(
        private readonly ObtenerFacturaUseCase $obtenerFacturaUseCase,
        private readonly ObtenerRecibosUseCase $obtenerRecibosUseCase
    ) {
    }

    public function obtenerDatosFactura($id)
    {
        try {
            $dto = ObtenerFacturaDTO::fromRequest((string) $id);
            $datos = $this->obtenerFacturaUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'data' => $datos,
            ]);
        } catch (\Throwable $e) {
            Log::error('[AsesoresPedidoDocumentosController.obtenerDatosFactura] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error obteniendo datos de la factura.',
            ], 500);
        }
    }

    public function obtenerDatosRecibos($id)
    {
        try {
            $dto = ObtenerRecibosDTO::fromRequest((string) $id);
            $datos = $this->obtenerRecibosUseCase->ejecutar($dto);

            return response()->json($datos);
        } catch (\Throwable $e) {
            Log::error('[AsesoresPedidoDocumentosController.obtenerDatosRecibos] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error obteniendo datos de los recibos.',
            ], 500);
        }
    }
}

