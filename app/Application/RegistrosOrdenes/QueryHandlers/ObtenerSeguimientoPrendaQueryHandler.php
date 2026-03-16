<?php

namespace App\Application\RegistrosOrdenes\QueryHandlers;

use App\Domain\RegistrosOrdenes\Contracts\RegistroOrdenRepository;
use App\Domain\RegistrosOrdenes\Contracts\SeguimientoOrdenService;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerSeguimientoPrendaQueryHandler
 * 
 * Handler para obtener seguimiento detallado por prenda
 * Retorna: Procesos, consecutivos, fechas por prenda
 */
class ObtenerSeguimientoPrendaQueryHandler
{
    public function __construct(
        private RegistroOrdenRepository $repository,
        private SeguimientoOrdenService $seguimientoService,
    ) {}

    /**
     * Ejecutar query de seguimiento
     */
    public function handle($numeroPedido)
    {
        try {
            // 1. Obtener orden
            $orden = $this->repository->obtenerPorNumero($numeroPedido);
            
            if (!$orden) {
                throw new \DomainException('Orden no encontrada: ' . $numeroPedido);
            }

            // 2. Obtener seguimiento por prenda
            $seguimiento = $this->seguimientoService->obtenerSeguimientoPorPrenda($orden->id);

            Log::info('Seguimiento por prenda obtenido', [
                'numero_pedido' => $numeroPedido,
                'total_prendas' => count($seguimiento),
            ]);

            return [
                'success' => true,
                'numero_pedido' => $numeroPedido,
                'prendas' => $seguimiento,
            ];

        } catch (\Exception $e) {
            Log::error('Error en ObtenerSeguimientoPrendaQueryHandler: ' . $e->getMessage());
            throw $e;
        }
    }
}
