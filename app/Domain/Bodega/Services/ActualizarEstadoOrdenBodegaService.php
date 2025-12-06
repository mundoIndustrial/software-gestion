<?php

namespace App\Domain\Bodega\Services;

use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use App\Domain\Bodega\ValueObjects\EstadoBodega;
use App\Domain\Bodega\Repositories\OrdenBodegaRepositoryInterface;

/**
 * Application Service: Actualizar Estado de Orden en Bodega
 */
final class ActualizarEstadoOrdenBodegaService
{
    public function __construct(
        private OrdenBodegaRepositoryInterface $repository
    ) {}

    public function ejecutar(int $numeroPedido, string $nuevoEstado): void
    {
        $orden = $this->repository->obtener(NumeroPedidoBodega::desde($numeroPedido));

        if (!$orden) {
            throw new \InvalidArgumentException(
                "Orden {$numeroPedido} no encontrada"
            );
        }

        $estadoNuevo = EstadoBodega::desde($nuevoEstado);
        $orden->cambiarEstado($estadoNuevo);

        $this->repository->actualizar($orden);
    }
}
