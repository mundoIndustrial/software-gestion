<?php

namespace App\Domain\Pedidos\DomainServices;

use App\Domain\Pedidos\Entities\ItemPedido;
use App\Domain\Pedidos\ValueObjects\OrdenItem;
use App\Domain\Pedidos\ValueObjects\TipoItem;
use Illuminate\Support\Collection;

/**
 * Domain Service: GestorItemsPedidoDomainService
 * 
 * Orquesta la lógica de negocio para gestión de items en un pedido
 * 
 * Responsabilidades:
 * - Agregación de items con validación
 * - Eliminación de items con recálculo de orden
 * - Reordenamiento de items
 * - Asegurar invariantes de orden
 */
class GestorItemsPedidoDomainService
{
    /**
     * Agregar un item al final del pedido
     * 
     * @throws \InvalidArgumentException si el item es inválido
     */
    public function agregarItemAlFinal(
        Collection $items,
        ItemPedido $nuevoItem
    ): Collection {
        // Validar que el nuevo item no tenga ID (es nuevo)
        if ($nuevoItem->id() !== null) {
            throw new \InvalidArgumentException('No se puede agregar un item que ya existe en base de datos');
        }

        // Calcular la siguiente posición
        $proximaPosicion = $this->calcularProximaPosicion($items);

        // Crear el item con la posición correcta
        $itemConOrden = new ItemPedido(
            id: null,
            pedidoId: $nuevoItem->pedidoId(),
            referenciaId: $nuevoItem->referenciaId(),
            tipo: $nuevoItem->tipo(),
            orden: $proximaPosicion,
            nombre: $nuevoItem->nombre(),
            descripcion: $nuevoItem->descripcion(),
            datosPresentacion: $nuevoItem->datosPresentacion()
        );

        // Agregar y retornar
        return $items->push($itemConOrden);
    }

    /**
     * Eliminar un item por su ID y reordenar los restantes
     * 
     * @throws \InvalidArgumentException si el item no existe
     */
    public function eliminarItem(Collection &$items, int $itemId): void
    {
        // Buscar el item
        $indice = $items->search(
            fn(ItemPedido $item) => $item->id() === $itemId,
            strict: true
        );

        if ($indice === false) {
            throw new \InvalidArgumentException("Item con ID {$itemId} no encontrado en el pedido");
        }

        // Eliminar
        $items->forget($indice);

        // Reordenar los items restantes
        $this->reconstruirOrden($items);
    }

    /**
     * Cambiar la posición de un item
     */
    public function cambiarPosicion(Collection $items, int $itemId, int $nuevaPosicion): void
    {
        $item = $items->first(fn(ItemPedido $i) => $i->id() === $itemId);

        if (!$item) {
            throw new \InvalidArgumentException("Item con ID {$itemId} no encontrado");
        }

        if ($nuevaPosicion < 1 || $nuevaPosicion > $items->count()) {
            throw new \InvalidArgumentException(
                "Posición inválida: {$nuevaPosicion}. Debe estar entre 1 y {$items->count()}"
            );
        }

        // Reordenar: el item va a la nueva posición
        $items = $items->filter(fn(ItemPedido $i) => $i->id() !== $itemId);
        
        $itemsArray = $items->values()->toArray();
        array_splice($itemsArray, $nuevaPosicion - 1, 0, [$item]);
        
        $items->replace($itemsArray);

        // Reconstruir orden de todos
        $this->reconstruirOrden($items);
    }

    /**
     * Obtener items en el orden correcto (para API)
     */
    public function obtenerItemsOrdenados(Collection $items): array
    {
        return $items
            ->sortBy(fn(ItemPedido $item) => $item->orden()->valor())
            ->map(fn(ItemPedido $item) => $item->aArray())
            ->values()
            ->toArray();
    }

    /**
     * Validar que los items tienen orden consistente
     */
    public function validarOrden(Collection $items): bool
    {
        $ordenes = $items
            ->map(fn(ItemPedido $item) => $item->orden()->valor())
            ->sort()
            ->values()
            ->toArray();

        // Debe ser 1, 2, 3, ..., N
        for ($i = 0; $i < count($ordenes); $i++) {
            if ($ordenes[$i] !== $i + 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcular la siguiente posición disponible
     */
    public function calcularProximaPosicion(Collection $items): OrdenItem
    {
        if ($items->isEmpty()) {
            return OrdenItem::primera();
        }

        $maxOrden = $items
            ->map(fn(ItemPedido $item) => $item->orden()->valor())
            ->max();

        return OrdenItem::desde($maxOrden + 1);
    }

    /**
     * Reconstruir la secuencia de orden después de eliminar o reordenar
     */
    private function reconstruirOrden(Collection &$items): void
    {
        $items
            ->sortBy(fn(ItemPedido $item) => $item->orden()->valor())
            ->each(function (ItemPedido $item, int $indice) {
                $item->cambiarOrden(OrdenItem::desde($indice + 1));
            });
    }
}
