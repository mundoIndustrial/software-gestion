<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use DomainException;

final class EliminarBorradorAsesorUseCase
{
    public function __construct(
        private PedidoProduccionReadRepository $pedidoReadRepository,
        private PedidoRepository $pedidoRepository,
    ) {}

    public function ejecutar(int $pedidoId, int $asesorId): void
    {
        $pedido = $this->pedidoReadRepository->obtenerPorIdYAsesor($pedidoId, $asesorId);

        if ($pedido === null) {
            throw new DomainException('Borrador no encontrado o no autorizado.');
        }

        if ($pedido->estado !== 'Borrador' || $pedido->numeroPedido !== null) {
            throw new DomainException('Solo se pueden eliminar borradores sin numero de pedido.');
        }

        $this->pedidoRepository->eliminar($pedidoId);
    }
}
