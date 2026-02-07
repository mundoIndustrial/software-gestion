<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Pedidos\Commands\EliminarItemDelPedidoCommand;
use App\Domain\Pedidos\Repositories\ItemPedidoRepository;
use App\Domain\Pedidos\DomainServices\GestorItemsPedidoDomainService;
use App\Domain\Pedidos\Events\ItemEliminadoDelPedido;

/**
 * Command Handler: EliminarItemDelPedidoHandler
 * 
 * Maneja la eliminación de items del pedido
 * Recalcula automáticamente el orden de los items restantes
 * Dispara eventos de dominio
 */
class EliminarItemDelPedidoHandler
{
    public function __construct(
        private ItemPedidoRepository $itemRepository,
        private GestorItemsPedidoDomainService $gestorItems
    ) {}

    public function ejecutar(EliminarItemDelPedidoCommand $comando): void
    {
        // Obtener el item a eliminar
        $item = $this->itemRepository->encontrarPorId($comando->itemId);

        if (!$item) {
            throw new \InvalidArgumentException("Item con ID {$comando->itemId} no encontrado");
        }

        // Validar que pertenece al pedido
        if ($item->pedidoId() !== $comando->pedidoId) {
            throw new \InvalidArgumentException("Item no pertenece al pedido especificado");
        }

        // Obtener todos los items del pedido
        $items = $this->itemRepository->obtenerPorPedido($comando->pedidoId);

        // Eliminar (hace recalc de orden automáticamente)
        $this->gestorItems->eliminarItem($items, $comando->itemId);

        // Persistir todos los items actualizado con nuevo orden
        foreach ($items as $itemActualizado) {
            $this->itemRepository->guardar($itemActualizado);
        }

        // Eliminar de base de datos
        $this->itemRepository->eliminar($comando->itemId);

        // Disparar evento
        $item->registrarEvento(
            new ItemEliminadoDelPedido(
                pedidoId: $comando->pedidoId,
                itemId: $comando->itemId,
                tipo: $item->tipo()->valor(),
                referenciaId: $item->referenciaId()
            )
        );
    }
}
