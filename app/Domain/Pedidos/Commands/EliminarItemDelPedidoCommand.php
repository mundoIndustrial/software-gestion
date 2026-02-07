<?php

namespace App\Domain\Pedidos\Commands;

use App\Domain\Shared\Command;

/**
 * Command: EliminarItemDelPedidoCommand
 * 
 * Instrucción para eliminar un item de un pedido
 */
class EliminarItemDelPedidoCommand extends Command
{
    public function __construct(
        public readonly int $itemId,
        public readonly int $pedidoId
    ) {}
}
