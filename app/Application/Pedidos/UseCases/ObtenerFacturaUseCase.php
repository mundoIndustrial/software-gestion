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
            
            // LOG CRÍTICO: Verificar telas_array en cada prenda
            if (!empty($datos['prendas'])) {
                foreach ($datos['prendas'] as $idx => $prenda) {
                    Log::warning('[FACTURA-USECASE-TELAS] Prenda ' . $idx . ' tiene telas_array', [
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                        'telas_array_count' => count($prenda['telas_array'] ?? []),
                        'telas_array' => $prenda['telas_array'] ?? [],
                        'tela_simple' => $prenda['tela'] ?? null,
                        'color_simple' => $prenda['color'] ?? null,
                        'ref_simple' => $prenda['ref'] ?? null,
                    ]);
                }
            }

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


