<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerProximoNumeroPedidoDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

class ObtenerProximoNumeroPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(ObtenerProximoNumeroPedidoDTO $dto): int
    {
        // Obtener el último número de pedido
        $ultimoPedido = $this->pedidoRepository->obtenerUltimoPedido();

        if (!$ultimoPedido) {
            return 1;
        }

        return (int)$ultimoPedido->numero_pedido + 1;
    }
}
