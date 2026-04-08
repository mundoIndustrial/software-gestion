<?php

namespace App\Domain\Pedidos\ReadModels;

final class PedidoEppRef
{
    public function __construct(
        public readonly int $pedidoEppId,
        public readonly int $pedidoId,
        public readonly int $eppId,
        public readonly int $cantidad,
        public readonly ?string $observaciones,
        public readonly int $imagenesCount,
    ) {}
}
