<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Entities\ItemPedido;
use Illuminate\Support\Collection;

/**
 * Repository Interface: ItemPedidoRepository
 * 
 * Define el contrato para acceder a datos de ItemPedido desde el dominio
 * La implementación estará en Application/Repositories
 */
interface ItemPedidoRepository
{
    /**
     * Guardar un item (crear o actualizar)
     */
    public function guardar(ItemPedido $item): ItemPedido;

    /**
     * Encontrar un item por ID
     */
    public function encontrarPorId(int $id): ?ItemPedido;

    /**
     * Obtener todos los items de un pedido
     */
    public function obtenerPorPedido(int $pedidoId): Collection;

    /**
     * Eliminar un item
     */
    public function eliminar(int $itemId): void;

    /**
     * Obtener items ordenados para respuesta API
     */
    public function obtenerPorPedidoOrdenados(int $pedidoId): array;
}
