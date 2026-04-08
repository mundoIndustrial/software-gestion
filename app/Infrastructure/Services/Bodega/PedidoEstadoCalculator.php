<?php

namespace App\Infrastructure\Services\Bodega;

use App\Application\Bodega\Constants\WarehouseConstants;
use App\Domain\Bodega\Services\PedidoEstadoCalculatorContract;
use App\Models\BodegaDetallesTalla;

class PedidoEstadoCalculator implements PedidoEstadoCalculatorContract
{
    public function calcular(string $numeroPedido): array
    {
        $totalItems = $this->obtenerTotalItems($numeroPedido);
        $itemsPendientes = $this->obtenerItemsPendientes($numeroPedido);
        $itemsEntregados = $this->obtenerItemsEntregados($numeroPedido);

        return [
            'total_items' => $totalItems,
            'items_pendientes' => $itemsPendientes,
            'items_entregados' => $itemsEntregados,
            'tiene_pendientes' => $itemsPendientes > 0,
            'todos_pendientes' => $this->todosPendientes($totalItems, $itemsPendientes),
            'todos_entregados' => $this->todosEntregados($totalItems, $itemsEntregados),
        ];
    }

    public function existePendiente(string $numeroPedido): bool
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_PENDING)
            ->exists();
    }

    private function obtenerTotalItems(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', '!=', WarehouseConstants::STATE_CANCELLED)
            ->count();
    }

    private function obtenerItemsPendientes(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_PENDING)
            ->count();
    }

    private function obtenerItemsEntregados(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_DELIVERED)
            ->count();
    }

    private function todosPendientes(int $totalItems, int $itemsPendientes): bool
    {
        return $totalItems > 0 && $totalItems === $itemsPendientes;
    }

    private function todosEntregados(int $totalItems, int $itemsEntregados): bool
    {
        return $totalItems > 0 && $totalItems === $itemsEntregados;
    }
}

