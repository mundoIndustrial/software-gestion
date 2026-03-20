<?php

namespace App\Application\SupervisorPedidos\DTOs;

class TogglePedidoVistoRequest
{
    public function __construct(
        private int $pedidoId,
        private int $userId
    ) {}

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
