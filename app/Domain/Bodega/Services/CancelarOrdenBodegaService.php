<?php

namespace App\Domain\Bodega\Services;

use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use App\Domain\Bodega\Repositories\OrdenBodegaRepositoryInterface;

/**
 * Application Service: Cancelar Orden en Bodega
 */
final class CancelarOrdenBodegaService
{
    public function __construct(
        private OrdenBodegaRepositoryInterface $repository
    ) {}

    public function ejecutar(int $numeroPedido): void
    {
        $orden = $this->repository->obtener(NumeroPedidoBodega::desde($numeroPedido));

        if (!$orden) {
            throw new \InvalidArgumentException(
                "Orden {$numeroPedido} no encontrada"
            );
        }

        $orden->cancelar();

        $this->repository->actualizar($orden);
    }
}
