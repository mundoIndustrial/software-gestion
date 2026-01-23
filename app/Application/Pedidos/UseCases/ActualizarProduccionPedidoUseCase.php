<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Domain\Pedidos\Agregado\PedidosAggregate;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Events\Dispatcher;
use Exception;

/**
 * ActualizarProduccionPedidoUseCase
 * 
 * COMPLETADO: Fue refactorizado en FASE 1
 * 
 * Use Case para actualizar un pedido de producciÃ³n existente
 * 
 * Cambios de FASE 1:
 * - Agregadas dependencias inyectadas (antes faltaban)
 * - Implementada actualizaciÃ³n de cliente âœ…
 * - Implementada actualizaciÃ³n de prendas âœ…
 * - Implementada persistencia de cambios âœ…
 * - Implementada publicaciÃ³n de eventos âœ…
 */
class ActualizarProduccionPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository,
        private Dispatcher $eventDispatcher
    ) {}

    public function ejecutar(ActualizarProduccionPedidoDTO $dto): PedidosAggregate
    {
        try {
            // 1. Obtener pedido del repositorio
            $pedido = $this->pedidoRepository->obtenerPorId($dto->id);
            
            if (!$pedido) {
                throw new Exception("Pedido con ID {$dto->id} no encontrado");
            }

            // 2. Validar que estÃ¡ en estado pendiente
            if (!$pedido->estaPendiente()) {
                throw new Exception(
                    "No se puede actualizar un pedido en estado '{$pedido->getEstado()}'. " .
                    "Solo se pueden actualizar pedidos pendientes."
                );
            }

            // 3. âœ… ACTUALIZAR CLIENTE SI VIENE EN DTO
            if ($dto->cliente) {
                $pedido->cambiarCliente($dto->cliente);
            }

            // 4. âœ… ACTUALIZAR PRENDAS SI VIENEN EN DTO
            if (!empty($dto->prendas)) {
                $pedido->reemplazarPrendas($dto->prendas);
            }

            // 5. âœ… PERSISTIR CAMBIOS
            $this->pedidoRepository->guardar($pedido);

            // 6. âœ… PUBLICAR DOMAIN EVENTS
            foreach ($pedido->eventos() as $evento) {
                $this->eventDispatcher->dispatch($evento);
            }

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al actualizar pedido: " . $e->getMessage());
        }
    }
}


