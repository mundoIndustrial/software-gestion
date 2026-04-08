<?php

namespace App\Domain\Pedidos\ReadModels;

final class PedidoPrendaRef
{
    public function __construct(
        public readonly int $prendaId,
        public readonly int $pedidoId,
    ) {}
}
