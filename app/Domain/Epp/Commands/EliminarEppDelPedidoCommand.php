<?php

namespace App\Domain\Epp\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * EliminarEppDelPedidoCommand
 * 
 * Command para eliminar un EPP de un pedido
 */
class EliminarEppDelPedidoCommand implements Command
{
    public function __construct(
        private int $pedidoId,
        private int $eppId,
    ) {}

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getEppId(): int
    {
        return $this->eppId;
    }
}
