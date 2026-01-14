<?php

namespace App\Domain\PedidoProduccion\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * EliminarPedidoCommand
 * 
 * Command para eliminar un pedido (soft delete)
 * 
 * @param int|string $pedidoId ID del pedido a eliminar
 * @param string $razon Razón de la eliminación
 */
class EliminarPedidoCommand implements Command
{
    public function __construct(
        private int|string $pedidoId,
        private string $razon,
    ) {}

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getRazon(): string
    {
        return $this->razon;
    }
}
