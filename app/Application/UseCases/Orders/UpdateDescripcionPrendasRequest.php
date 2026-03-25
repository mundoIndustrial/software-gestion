<?php

namespace App\Application\UseCases\Orders;

final class UpdateDescripcionPrendasRequest
{
    public function __construct(
        public readonly string $pedido,
        public readonly string $descripcion,
        public readonly int $userId,
    ) {}
}
