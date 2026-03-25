<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\BodegaNota\Repositories\BodegaNotaRepositoryInterface;

class ObtenerNotasPedidoUseCase
{
    public function __construct(
        private BodegaNotaRepositoryInterface $bodegaNotaRepository
    ) {}

    public function ejecutar(string $numeroPedido)
    {
        return $this->bodegaNotaRepository->obtenerNotasPorNumeroPedido($numeroPedido);
    }
}
