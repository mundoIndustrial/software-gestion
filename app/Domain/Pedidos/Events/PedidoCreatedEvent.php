<?php

namespace App\Domain\Pedidos\Events;

/**
 * PedidoCreatedEvent
 * 
 * Evento de dominio: Un pedido fue creado
 * Se dispara cuando CrearPedidoCompleteUseCase crea un pedido exitosamente
 */
class PedidoCreatedEvent
{
    public function __construct(
        public int $pedidoId,
        public int $usuarioId,
        public string $estado = 'pendiente',
        public array $metadata = [],
    ) {}

    public function toPayload(): array
    {
        return [
            'pedido_id' => $this->pedidoId,
            'usuario_id' => $this->usuarioId,
            'estado' => $this->estado,
            'timestamp' => now(),
            'metadata' => $this->metadata,
        ];
    }
}
