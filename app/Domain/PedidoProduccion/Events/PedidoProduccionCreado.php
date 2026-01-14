<?php

namespace App\Domain\PedidoProduccion\Events;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * PedidoProduccionCreado
 * 
 * Se emite cuando se crea un nuevo pedido de producción
 * Contiene toda la información necesaria para notificar del nuevo pedido
 */
class PedidoProduccionCreado extends DomainEvent
{
    public function __construct(
        private int|string $pedidoId,
        private string $numeroPedido,
        private string $cliente,
        private string $formaPago,
        private int $asesoreId,
        private int $cantidadTotal,
        private string $estado,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        parent::__construct($pedidoId, $occurredAt);
    }

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getNumeroPedido(): string
    {
        return $this->numeroPedido;
    }

    public function getCliente(): string
    {
        return $this->cliente;
    }

    public function getFormaPago(): string
    {
        return $this->formaPago;
    }

    public function getAsesoreId(): int
    {
        return $this->asesoreId;
    }

    public function getCantidadTotal(): int
    {
        return $this->cantidadTotal;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    protected function extractEventData(): array
    {
        return [
            'pedido_id' => $this->getPedidoId(),
            'numero_pedido' => $this->getNumeroPedido(),
            'cliente' => $this->getCliente(),
            'forma_pago' => $this->getFormaPago(),
            'asesor_id' => $this->getAsesoreId(),
            'cantidad_total' => $this->getCantidadTotal(),
            'estado' => $this->getEstado(),
        ];
    }
}
