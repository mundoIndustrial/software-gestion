<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Domain\PedidoProduccion\Agregado\PedidoProduccionAggregate;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Exception;

/**
 * ActualizarProduccionPedidoUseCase
 * 
 * Use Case para actualizar un pedido de producción existente
 */
class ActualizarProduccionPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {
    }

    public function ejecutar(ActualizarProduccionPedidoDTO $dto): PedidoProduccionAggregate
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
                    "No se puede actualizar un pedido en estado '{$pedido->getEstado()}'. " .
                    "Solo se pueden actualizar pedidos pendientes."
                );
            }

            // 3. Actualizar cliente si viene en DTO
            if ($dto->cliente) {
                // Nota: Necesitaría método en agregado para cambiar cliente
                // $pedido->cambiarCliente($dto->cliente);
            }

            // 4. Actualizar prendas si vienen en DTO
            if (!empty($dto->prendas)) {
                // Nota: Necesitaría lógica para reemplazar prendas
                // $pedido->reemplazarPrendas($dto->prendas);
            }

            // 5. Persistir cambios
            $this->pedidoRepository->guardar($pedido);

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al actualizar pedido: " . $e->getMessage());
        }
    }
}
