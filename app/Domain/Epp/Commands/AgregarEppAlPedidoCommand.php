<?php

namespace App\Domain\Epp\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * AgregarEppAlPedidoCommand
 * 
 * Command para agregar un EPP a un pedido
 */
class AgregarEppAlPedidoCommand implements Command
{
    public function __construct(
        private int $pedidoId,
        private int $eppId,
        private string $talla,
        private int $cantidad,
        private ?string $observaciones = null,
    ) {}

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getEppId(): int
    {
        return $this->eppId;
    }

    public function getTalla(): string
    {
        return $this->talla;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }
}
