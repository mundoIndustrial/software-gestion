<?php

namespace App\Application\SupervisorPedidos\DTOs;

class CancelReceiptRequest
{
    public function __construct(
        private int $pedidoId,
        private int $prendaId,
        private ?string $notes = null
    ) {}

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getPrendaId(): int
    {
        return $this->prendaId;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
}
