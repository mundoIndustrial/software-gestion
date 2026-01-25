<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

class ObtenerFacturaUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoProduccionRepository
    ) {}

    public function ejecutar(ObtenerFacturaDTO $dto): array
    {
        Log::info('[FACTURA-USECASE] Ejecutando para pedido: ' . $dto->pedidoId);
        
        try {
            // Obtener datos completos de factura desde el repositorio
            // Este método incluye procesos, imágenes, telas, fotos, etc.
            $datos = $this->pedidoProduccionRepository->obtenerDatosFactura((int)$dto->pedidoId);
            
            Log::info('[FACTURA-USECASE] Datos obtenidos correctamente', [
                'pedido_id' => $dto->pedidoId,
                'prendas_count' => count($datos['prendas'] ?? []),
                'procesos_total' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
            ]);

            return $datos;
        } catch (\Exception $e) {
            Log::error('[FACTURA-USECASE] Error obteniendo datos de factura', [
                'pedido_id' => $dto->pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}


