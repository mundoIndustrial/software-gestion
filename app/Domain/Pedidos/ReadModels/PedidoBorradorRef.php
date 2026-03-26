<?php

namespace App\Domain\Pedidos\ReadModels;

final class PedidoBorradorRef
{
    public function __construct(
        public readonly int $pedidoId,
        public readonly int $asesorId,
        public readonly ?int $numeroPedido,
        public readonly string $estado,
        public readonly ?string $cliente,
    ) {}
}
