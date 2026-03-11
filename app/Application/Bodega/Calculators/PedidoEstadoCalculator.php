<?php

namespace App\Application\Bodega\Calculators;

use App\Application\Bodega\Constants\WarehouseConstants;
use App\Models\BodegaDetallesTalla;

/**
 * Calculadora de estados de pedidos
 * 
 * Centraliza la lógica de cálculo de estados para evitar duplicación
 * de código en múltiples métodos del servicio.
 */
class PedidoEstadoCalculator
{
    /**
     * Calcular el estado de un pedido basado en sus items en bodega
     * 
     * @param string $numeroPedido
     * @return array
     */
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

    /**
     * Obtener total de items del pedido (excluyendo anulados)
     * Solo cuenta items que NO están en estado "Anulada"
     */
    private function obtenerTotalItems(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', '!=', WarehouseConstants::STATE_CANCELLED)
            ->count();
    }

    /**
     * Obtener cantidad de items pendientes
     */
    private function obtenerItemsPendientes(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_PENDING)
            ->count();
    }

    /**
     * Obtener cantidad de items entregados
     */
    private function obtenerItemsEntregados(string $numeroPedido): int
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_DELIVERED)
            ->count();
    }

    /**
     * Verificar si TODOS los items están pendientes
     * 
     * Retorna true SOLO si:
     * - Hay al menos 1 item (total > 0)
     * - TODOS los items no-anulados están en estado "Pendiente"
     * - No hay items en otros estados (En Progreso, etc.)
     */
    private function todosPendientes(int $totalItems, int $itemsPendientes): bool
    {
        return $totalItems > 0 && $totalItems === $itemsPendientes;
    }

    /**
     * Verificar si TODOS los items están entregados
     */
    private function todosEntregados(int $totalItems, int $itemsEntregados): bool
    {
        return $totalItems > 0 && $totalItems === $itemsEntregados;
    }

    /**
     * Verificar si existe al menos un item pendiente
     */
    public function existePendiente(string $numeroPedido): bool
    {
        return BodegaDetallesTalla::where('numero_pedido', $numeroPedido)
            ->where('estado_bodega', WarehouseConstants::STATE_PENDING)
            ->exists();
    }
}
