<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Domain\PedidoProduccion\Agregado\PedidoProduccionAggregate;
use Exception;

/**
 * ActualizarProduccionPedidoUseCase
 * 
 * Use Case para actualizar un pedido de producción existente
 */
class ActualizarProduccionPedidoUseCase
{
    public function __construct()
    {
    }

    public function ejecutar(ActualizarProduccionPedidoDTO $dto): PedidoProduccionAggregate
    {
        try {
            // 1. TODO: Obtener agregado del repositorio
            // $pedido = $this->pedidoRepository->obtenerPorId($dto->id);
            // if (!$pedido) {
            //     throw new Exception("Pedido no encontrado");
            // }

            // 2. TODO: Validar que está en estado pendiente
            // if (!$pedido->estaPendiente()) {
            //     throw new Exception("Solo se pueden actualizar pedidos pendientes");
            // }

            // 3. TODO: Actualizar cliente si viene
            // if ($dto->cliente) {
            //     $pedido->cambiarCliente($dto->cliente);
            // }

            // 4. TODO: Actualizar prendas si vienen
            // if (!empty($dto->prendas)) {
            //     $pedido->reemplazarPrendas($dto->prendas);
            // }

            // 5. TODO: Persistir
            // $this->pedidoRepository->guardar($pedido);

            // return $pedido;

            throw new Exception("Use Case no implementado aún");

        } catch (Exception $e) {
            throw new Exception("Error al actualizar pedido: " . $e->getMessage());
        }
    }
}
