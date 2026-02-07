<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Commands\EliminarItemDelPedidoCommand;
use App\Domain\Pedidos\CommandHandlers\EliminarItemDelPedidoHandler;
use App\Domain\Pedidos\Repositories\ItemPedidoRepository;
use App\Domain\Pedidos\DomainServices\GestorItemsPedidoDomainService;

/**
 * Application Service: EliminarItemDelPedidoUseCase
 * 
 * Caso de uso: Eliminar un item de un pedido
 * 
 * Responsabilidades:
 * - Validar que el item existe
 * - Ejecutar eliminación
 * - Retornar lista actualizada y reordenada
 */
class EliminarItemDelPedidoUseCase
{
    public function __construct(
        private EliminarItemDelPedidoHandler $handler,
        private ItemPedidoRepository $itemRepository,
        private GestorItemsPedidoDomainService $gestorItems
    ) {}

    public function ejecutar(int $itemId, int $pedidoId): array
    {
        // Validar que el item existe
        $item = $this->itemRepository->encontrarPorId($itemId);

        if (!$item) {
            throw new \InvalidArgumentException("Item con ID {$itemId} no encontrado");
        }

        if ($item->pedidoId() !== $pedidoId) {
            throw new \InvalidArgumentException("El item no pertenece al pedido especificado");
        }

        // Ejecutar comando
        $this->handler->ejecutar(new EliminarItemDelPedidoCommand($itemId, $pedidoId));

        // Retornar items actualizados del pedido
        $itemsActualizados = $this->itemRepository->obtenerPorPedidoOrdenados($pedidoId);

        return [
            'success' => true,
            'items' => $itemsActualizados,
            'message' => 'Item eliminado correctamente',
            'relacionados_eliminados' => [
                // Si hubiera cascadas, aquí se reportaría
                'procesos' => 0,
                'variantes' => 0
            ]
        ];
    }
}
