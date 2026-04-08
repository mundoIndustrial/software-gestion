<?php

namespace App\Domain\Pedidos\Events;

/**
 * PedidoValidatedEvent
 * 
 * Evento de dominio: Un pedido fue validado
 * Se dispara cuando ValidarPedidoUseCase valida exitosamente un pedido
 */
class PedidoValidatedEvent
{
    public function __construct(
        public int $pedidoId,
        public int $usuarioId,
        public array $validacionesPasadas = [],
        public array $metadata = [],
    ) {}

    public function toPayload(): array
    {
        return [
            'pedido_id' => $this->pedidoId,
            'usuario_id' => $this->usuarioId,
            'validaciones_pasadas' => $this->validacionesPasadas,
            'timestamp' => now(),
            'metadata' => $this->metadata,
        ];
    }
}
