<?php

namespace App\Domain\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;

interface ActualizarPrendaPedidoUseCaseContract
{
    public function ejecutar(ActualizarPrendaPedidoDTO $dto);
}

