<?php

namespace App\Application\Pedidos\CommandHandlers;

use App\Domain\Pedidos\CommandHandlers\CrearPedidoCompletoHandlerContract;
use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;

class CrearPedidoCompletoHandler implements CommandHandler
{
    public function __construct(private readonly CrearPedidoCompletoHandlerContract $handler)
    {
    }

    public function handle(Command $command): mixed
    {
        return $this->handler->handle($command);
    }
}
