<?php

namespace App\Domain\Epp\Repositories;

use Illuminate\Support\Collection;

/**
 * Repositorio para relación Pedido-EPP
 * Encapsula consultas a tabla pedido_epps
 */
interface PedidoEppRepositoryInterface
{
    /**
     * Obtener EPP de un pedido
     *
     * @return Collection
     */
    public function obtenerEppDelPedido(int $pedidoId): Collection;

    /**
     * Agregar EPP a un pedido
     */
    public function agregarEppAlPedido(
        int $pedidoId,
        int $eppId,
        string $talla,
        int $cantidad,
        ?string $observaciones = null
    ): void;

    /**
     * Actualizar EPP en pedido
     */
    public function actualizarEppEnPedido(
        int $pedidoId,
        int $eppId,
        array $datos
    ): void;

    /**
     * Eliminar EPP de un pedido
     */
    public function eliminarEppDelPedido(int $pedidoId, int $eppId): void;

    /**
     * Verificar si un EPP está agregado a un pedido
     */
    public function estaEppEnPedido(int $pedidoId, int $eppId): bool;
}
