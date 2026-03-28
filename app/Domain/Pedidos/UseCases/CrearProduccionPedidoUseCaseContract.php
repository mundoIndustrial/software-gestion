<?php

namespace App\Domain\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Domain\Pedidos\Aggregates\PedidoProduccionAggregate;

interface CrearProduccionPedidoUseCaseContract
{
    public function ejecutar(CrearProduccionPedidoDTO $dto): PedidoProduccionAggregate;
}
