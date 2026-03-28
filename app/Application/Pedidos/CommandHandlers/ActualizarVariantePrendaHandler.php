<?php

namespace App\Application\Pedidos\CommandHandlers;

use App\Domain\Pedidos\CommandHandlers\ActualizarVariantePrendaHandlerContract;
use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;

class ActualizarVariantePrendaHandler implements CommandHandler
{
    public function __construct(private readonly ActualizarVariantePrendaHandlerContract $handler)
    {
    }

    public function handle(Command $command): mixed
    {
        return $this->handler->handle($command);
    }
}
