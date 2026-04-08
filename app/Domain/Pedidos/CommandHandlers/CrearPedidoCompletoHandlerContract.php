<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Shared\CQRS\Command;

interface CrearPedidoCompletoHandlerContract
{
    public function handle(Command $command): mixed;
}

