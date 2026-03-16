<?php

namespace App\Application\RegistrosOrdenes\QueryHandlers;

use App\Domain\RegistrosOrdenes\Contracts\RegistroOrdenRepository;
use App\Domain\RegistrosOrdenes\Contracts\DescripcionOrdenService;
use App\Application\RegistrosOrdenes\Contracts\TransformacionOrdenService;
use App\Models\LogoPedido;
use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenStatsService;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerOrdenDetalleQueryHandler
 * 
 * Handler para obtener detalle completo de una orden
 * Maneja: PedidoProduccion y LogoPedido
 * Retorna: Orden transformada con prendas, procesos, etc
 */
class ObtenerOrdenDetalleQueryHandler
{
    public function __construct(
        private RegistroOrdenRepository $repository,
        private DescripcionOrdenService $descripcionService,
        private TransformacionOrdenService $transformacionService,
        private RegistroOrdenStatsService $statsService,
    ) {}

    /**
     * Ejecutar query de detalle
     */
    public function handle($numeroPedido)
    {
        try {
            // 1. Obtener orden por número
            $orden = $this->repository->obtenerPorNumero($numeroPedido);
            
            if (!$orden) {
                throw new \DomainException('Orden no encontrada: ' . $numeroPedido);
            }

            // 2. Transformar para detalle
            $ordenArray = $this->transformacionService->transformarParaDetalle($orden);

            // 3. Construir descripción de prendas
            $ordenArray['descripcion_prendas'] = $this->descripcionService->construirConTallas($orden);

            // 4. Obtener estadísticas
            $stats = $this->statsService->getOrderStats($numeroPedido);
            $ordenArray['total_cantidad'] = $stats['total_cantidad'];
            $ordenArray['total_entregado'] = $stats['total_entregado'];

            Log::info('Detalle de orden obtenido', [
                'numero_pedido' => $numeroPedido,
                'id' => $orden->id,
            ]);

            return $ordenArray;

        } catch (\Exception $e) {
            Log::error('Error en ObtenerOrdenDetalleQueryHandler: ' . $e->getMessage());
            throw $e;
        }
    }
}
