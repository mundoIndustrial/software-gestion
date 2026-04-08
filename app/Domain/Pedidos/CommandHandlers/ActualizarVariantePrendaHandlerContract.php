<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Shared\CQRS\Command;

interface ActualizarVariantePrendaHandlerContract
{
    public function handle(Command $command): mixed;
}

