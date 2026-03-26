<?php

namespace App\Domain\Pedidos\ReadModels;

final class PedidoNumeroRef
{
    public function __construct(
        public readonly int $pedidoId,
        public readonly int $numeroPedido,
        public readonly ?int $clienteId,
        public readonly ?int $asesorId,
        public readonly string $estado,
    ) {}
}
