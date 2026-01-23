<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Domain\PedidoProduccion\Agregado\PedidoProduccionAggregate;
use Exception;

/**
 * AnularProduccionPedidoUseCase
 * 
 * Use Case para anular un pedido de producción
 * Transición: Cualquier estado (excepto completado) → anulado
 */
class AnularProduccionPedidoUseCase
{
    public function __construct()
    {
    }

    public function ejecutar(AnularProduccionPedidoDTO $dto): PedidoProduccionAggregate
    {
        try {
            // 1. TODO: Obtener agregado del repositorio
            // $pedido = $this->pedidoRepository->obtenerPorId($dto->id);
            // if (!$pedido) {
            //     throw new Exception("Pedido no encontrado");
            // }

            // 2. TODO: Anular pedido (lógica encapsulada en agregado)
            // $pedido->anular($dto->razon);

            // 3. TODO: Persistir
            // $this->pedidoRepository->guardar($pedido);

            // 4. TODO: Publicar evento de pedido anulado
            // $this->eventPublisher->publicar(new PedidoAnuladoEvent($pedido));

            // return $pedido;

            throw new Exception("Use Case no implementado aún");

        } catch (Exception $e) {
            throw new Exception("Error al anular pedido: " . $e->getMessage());
        }
    }
}
