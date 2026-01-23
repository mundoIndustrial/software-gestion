<?php

namespace App\Domain\Pedidos\Events;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * LogoPedidoCreado
 * 
 * Se emite cuando se crea un logo para un pedido
 * Contiene datos del logo y referencias al pedido
 */
class LogoPedidoCreado extends DomainEvent
{
    public function __construct(
        private int|string $pedidoId,
        private int|string $logoPedidoId,
        private ?int $logoCotizacionId = null,
        private int $cantidad = 1,
        private ?int $cotizacionId = null,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        parent::__construct($pedidoId, $occurredAt);
    }

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getLogoPedidoId(): int|string
    {
        return $this->logoPedidoId;
    }

    public function getLogoCotizacionId(): ?int
    {
        return $this->logoCotizacionId;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getCotizacionId(): ?int
    {
        return $this->cotizacionId;
    }

    protected function extractEventData(): array
    {
        return [
            'pedido_id' => $this->getPedidoId(),
            'logo_pedido_id' => $this->getLogoPedidoId(),
            'logo_cotizacion_id' => $this->getLogoCotizacionId(),
            'cantidad' => $this->getCantidad(),
            'cotizacion_id' => $this->getCotizacionId(),
        ];
    }
}

