<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Domain\PedidoProduccion\Agregado\PedidoProduccionAggregate;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Exception;

/**
 * AnularProduccionPedidoUseCase
 * 
 * Use Case para anular un pedido de producción
 * Transición: Cualquier estado (excepto completado) → anulado
 */
class AnularProduccionPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {
    }

    public function ejecutar(AnularProduccionPedidoDTO $dto): PedidoProduccionAggregate
    {
        try {
            // 1. Obtener pedido del repositorio
            $pedido = $this->pedidoRepository->obtenerPorId($dto->id);
            
            if (!$pedido) {
                throw new Exception("Pedido con ID {$dto->id} no encontrado");
            }

            // 2. Anular pedido (validaciones en agregado)
            $pedido->anular($dto->razon);

            // 3. Persistir cambios
            $this->pedidoRepository->guardar($pedido);

            // 4. TODO: Publicar evento de pedido anulado
            // $this->eventPublisher->publicar(new PedidoAnuladoEvent($pedido));

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al anular pedido: " . $e->getMessage());
        }
    }
}
