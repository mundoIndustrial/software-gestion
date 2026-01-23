<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ConfirmarProduccionPedidoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Agregado\PedidosAggregate;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Exception;

/**
 * ConfirmarProduccionPedidoUseCase
 * 
 * Use Case para confirmar un pedido de producciÃ³n
 * TransiciÃ³n: pendiente â†’ confirmado
 */
class ConfirmarProduccionPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {
    }

    public function ejecutar(ConfirmarProduccionPedidoDTO $dto): PedidosAggregate
    {
        try {
            $pedido = $this->validarPedidoExiste($dto->id, $this->pedidoRepository);
            $this->validarEstadoPermitido($pedido, 'PENDIENTE');
            $this->validarTienePrendas($pedido);

            $pedido->confirmar();
            $this->pedidoRepository->guardar($pedido);

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al confirmar pedido: " . $e->getMessage());
        }
    }
}


