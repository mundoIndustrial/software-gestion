<?php

namespace App\Domain\Epp\Repositories;

use App\Models\PedidoEpp;
use Illuminate\Support\Collection;

/**
 * Implementación de Repositorio Pedido-EPP
 */
class PedidoEppRepository implements PedidoEppRepositoryInterface
{
    /**
     * Obtener EPP de un pedido
     */
    public function obtenerEppDelPedido(int $pedidoId): Collection
    {
        return PedidoEpp::where('pedido_id', $pedidoId)
            ->with('epp.imagenes')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Agregar EPP a un pedido
     */
    public function agregarEppAlPedido(
        int $pedidoId,
        int $eppId,
        string $talla,
        int $cantidad,
        ?string $observaciones = null
    ): void {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1');
        }

        // Usar firstOrCreate para evitar duplicados por el unique index
        PedidoEpp::firstOrCreate(
            [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
            ],
            [
                'talla' => $talla,
                'cantidad' => $cantidad,
                'observaciones' => $observaciones,
            ]
        );
    }

    /**
     * Actualizar EPP en pedido
     */
    public function actualizarEppEnPedido(
        int $pedidoId,
        int $eppId,
        array $datos
    ): void {
        PedidoEpp::where('pedido_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->update($datos);
    }

    /**
     * Eliminar EPP de un pedido
     */
    public function eliminarEppDelPedido(int $pedidoId, int $eppId): void
    {
        PedidoEpp::where('pedido_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->delete();
    }

    /**
     * Verificar si un EPP está agregado a un pedido
     */
    public function estaEppEnPedido(int $pedidoId, int $eppId): bool
    {
        return PedidoEpp::where('pedido_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->exists();
    }
}
