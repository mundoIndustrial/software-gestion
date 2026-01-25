<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerRecibosDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

class ObtenerRecibosUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoProduccionRepository
    ) {}

    public function ejecutar(ObtenerRecibosDTO $dto): array
    {
        Log::info('[RECIBOS-USECASE] Ejecutando para pedido: ' . $dto->pedidoId);
        
        try {
            // Obtener datos completos de recibos desde el repositorio
            // Este método incluye procesos, imágenes, telas, fotos, etc.
            $datos = $this->pedidoProduccionRepository->obtenerDatosRecibos((int)$dto->pedidoId);
            
            Log::info('[RECIBOS-USECASE] Datos obtenidos correctamente', [
                'pedido_id' => $dto->pedidoId,
                'prendas_count' => count($datos['prendas'] ?? []),
                'procesos_total' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
            ]);

            return $datos;
        } catch (\Exception $e) {
            Log::error('[RECIBOS-USECASE] Error obteniendo datos de recibos', [
                'pedido_id' => $dto->pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}


