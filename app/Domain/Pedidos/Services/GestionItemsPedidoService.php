<?php

namespace App\Domain\Pedidos\Services;

use App\Application\DTOs\ItemPedidoDTO;
use Illuminate\Support\Collection;

class GestionItemsPedidoService
{
    private Collection $items;

    public function __construct()
    {
        $this->items = collect();
    }

    public function agregarItem(ItemPedidoDTO $itemDTO): void
    {
        $this->items->push($itemDTO);
    }

    public function eliminarItem(int $index): void
    {
        $this->items->forget($index);
        $this->items = $this->items->values();
    }

    public function obtenerItems(): Collection
    {
        return $this->items;
    }

    public function obtenerItemsArray(): array
    {
        return $this->items->map(fn(ItemPedidoDTO $item) => $item->toArray())->toArray();
    }

    public function tieneItems(): bool
    {
        return $this->items->count() > 0;
    }

    public function limpiar(): void
    {
        $this->items = collect();
    }

    public function contar(): int
    {
        return $this->items->count();
    }

    public function validar(): array
    {
        $errores = [];

        if (!$this->tieneItems()) {
            $errores[] = 'Debe agregar al menos un Ã­tem al pedido';
        }

        foreach ($this->items as $index => $item) {
            $itemNum = $index + 1;
            if (empty($item->prenda)) {
                $errores[] = "Ãtem {$itemNum}: Prenda no especificada";
            }

            if (empty($item->tallas)) {
                $errores[] = "Ãtem {$itemNum}: Debe seleccionar al menos una talla";
            }
        }

        return $errores;
    }
}

