<?php

namespace App\Application\UseCases\CarteraPedidos;

use Illuminate\Support\Facades\Log;

class ObtenerDatosFacturaUseCase
{
    /**
     * Ejecutar caso de uso
     */
    public function execute(int $pedidoId): array
    {
        try {
            Log::info('[CARTERA-FACTURA] ObtenerDatosFacturaUseCase iniciado', [
                'pedido_id' => $pedidoId
            ]);

            $service = app(\App\Application\Services\Asesores\ObtenerDatosFacturaService::class);
            $datos = $service->obtener($pedidoId);

            Log::info('[CARTERA-FACTURA] Datos obtenidos correctamente', [
                'pedido_id' => $pedidoId,
                'prendas_count' => count($datos['prendas'] ?? []),
                'epps_count' => count($datos['epps'] ?? [])
            ]);

            return [
                'success' => true,
                'data' => $datos
            ];
        } catch (\Exception $e) {
            Log::error('[CARTERA-FACTURA] Error en ObtenerDatosFacturaUseCase: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ];
        }
    }
}
