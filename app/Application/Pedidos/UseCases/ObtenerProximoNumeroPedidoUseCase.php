<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerProximoNumeroPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;

class ObtenerProximoNumeroPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(ObtenerProximoNumeroPedidoDTO $dto): int
    {
        // Obtener el Ãºltimo nÃºmero de pedido
        $ultimoPedido = $this->pedidoRepository->obtenerUltimoPedido();

        if (!$ultimoPedido) {
            return 1;
        }

        return (int)$ultimoPedido->numero_pedido + 1;
    }
}


