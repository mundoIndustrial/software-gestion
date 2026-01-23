<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ConfirmarProduccionPedidoDTO;
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
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {
    }

    public function ejecutar(ConfirmarProduccionPedidoDTO $dto): PedidoProduccionAggregate
    {
        try {
            // 1. Obtener pedido del repositorio
            $pedido = $this->pedidoRepository->obtenerPorId($dto->id);
            
            if (!$pedido) {
                throw new Exception("Pedido con ID {$dto->id} no encontrado");
            }

            // 2. Validar que está en estado pendiente
            if (!$pedido->estaPendiente()) {
                throw new Exception(
                    "No se puede confirmar un pedido en estado '{$pedido->getEstado()}'. " .
                    "Solo se pueden confirmar pedidos pendientes."
                );
            }

            // 3. Validar que tiene prendas
            if ($pedido->getCantidadPrendas() === 0) {
                throw new Exception("No se puede confirmar un pedido sin prendas");
            }

            // 4. Confirmar el pedido (lógica en agregado)
            $pedido->confirmar();

            // 5. Persistir cambios
            $this->pedidoRepository->guardar($pedido);

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al confirmar pedido: " . $e->getMessage());
        }
    }
}
