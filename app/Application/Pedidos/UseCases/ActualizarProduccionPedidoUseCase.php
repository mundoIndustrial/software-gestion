<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Domain\PedidoProduccion\Agregado\PedidoProduccionAggregate;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Events\Dispatcher;
use Exception;

/**
 * ActualizarProduccionPedidoUseCase
 * 
 * COMPLETADO: Fue refactorizado en FASE 1
 * 
 * Use Case para actualizar un pedido de producción existente
 * 
 * Cambios de FASE 1:
 * - Agregadas dependencias inyectadas (antes faltaban)
 * - Implementada actualización de cliente ✅
 * - Implementada actualización de prendas ✅
 * - Implementada persistencia de cambios ✅
 * - Implementada publicación de eventos ✅
 */
class ActualizarProduccionPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
        private Dispatcher $eventDispatcher
    ) {}

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

            // 3. ✅ ACTUALIZAR CLIENTE SI VIENE EN DTO
            if ($dto->cliente) {
                $pedido->cambiarCliente($dto->cliente);
            }

            // 4. ✅ ACTUALIZAR PRENDAS SI VIENEN EN DTO
            if (!empty($dto->prendas)) {
                $pedido->reemplazarPrendas($dto->prendas);
            }

            // 5. ✅ PERSISTIR CAMBIOS
            $this->pedidoRepository->guardar($pedido);

            // 6. ✅ PUBLICAR DOMAIN EVENTS
            foreach ($pedido->eventos() as $evento) {
                $this->eventDispatcher->dispatch($evento);
            }

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al actualizar pedido: " . $e->getMessage());
        }
    }
}
