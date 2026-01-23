<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ConfirmarProduccionPedidoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\PedidoProduccion\Agregado\PedidoProduccionAggregate;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Exception;

/**
 * ConfirmarProduccionPedidoUseCase
 * 
 * Use Case para confirmar un pedido de producción
 * Transición: pendiente → confirmado
 */
class ConfirmarProduccionPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {
    }

    public function ejecutar(ConfirmarProduccionPedidoDTO $dto): PedidoProduccionAggregate
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
