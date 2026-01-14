<?php

namespace App\Domain\PedidoProduccion\Events;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * PedidoProduccionCompletado
 * 
 * Se emite cuando un pedido de producción ha completado todo su ciclo
 * Contiene datos finales del pedido (cantidad total, estado final, etc.)
 */
class PedidoProduccionCompletado extends DomainEvent
{
    public function __construct(
        private int|string $pedidoId,
        private string $numeroPedido,
        private int $cantidadTotal,
        private int $totalPrendas,
        private string $estadoFinal,
        private ?DateTimeImmutable $fechaCompletado = null,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        parent::__construct($pedidoId, $occurredAt);
        $this->fechaCompletado = $fechaCompletado ?? new DateTimeImmutable();
    }

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getNumeroPedido(): string
    {
        return $this->numeroPedido;
    }

    public function getCantidadTotal(): int
    {
        return $this->cantidadTotal;
    }

    public function getTotalPrendas(): int
    {
        return $this->totalPrendas;
    }

    public function getEstadoFinal(): string
    {
        return $this->estadoFinal;
    }

    public function getFechaCompletado(): DateTimeImmutable
    {
        return $this->fechaCompletado;
    }

    protected function extractEventData(): array
    {
        return [
            'pedido_id' => $this->getPedidoId(),
            'numero_pedido' => $this->getNumeroPedido(),
            'cantidad_total' => $this->getCantidadTotal(),
            'total_prendas' => $this->getTotalPrendas(),
            'estado_final' => $this->getEstadoFinal(),
            'fecha_completado' => $this->getFechaCompletado()->toIso8601String(),
        ];
    }
}
