<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ConfirmarProduccionPedidoDTO;
use App\Application\Pedidos\Exceptions\ConfirmarProduccionPedidoException;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\Repositories\PedidoRepository;

/**
 * ConfirmarProduccionPedidoUseCase
 * Use Case para confirmar un pedido de producción
 * Transición: pendiente  confirmado
 */
class ConfirmarProduccionPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {
    }

    public function ejecutar(ConfirmarProduccionPedidoDTO $dto): PedidoAggregate
    {
        try {
            $pedido = $this->validarPedidoExiste($dto->id, $this->pedidoRepository);
            $this->validarEstadoPermitido($pedido, 'PENDIENTE');
            $this->validarTienePrendas($pedido);

            $pedido->confirmar();
            $this->pedidoRepository->guardar($pedido);

            return $pedido;

        } catch (\DomainException|\InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ConfirmarProduccionPedidoException::fromThrowable($e);
        }
    }
}

