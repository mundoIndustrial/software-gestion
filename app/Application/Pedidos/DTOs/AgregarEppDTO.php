<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar EPP (Equipos de Protección Personal) a un pedido
 * 
 * Maneja campos de pedido_epp:
 * - epp_id: referencia a tabla de EPP disponibles
 * - cantidad: nÃºmero de unidades
 * - observaciones: notas especÃ­ficas para este EPP en este pedido
 */
final class AgregarEppDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly int $eppId,
        public readonly int $cantidad,
        public readonly ?string $observaciones = null,
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data): self
    {
        return new self(
            pedidoId: $pedidoId,
            eppId: $data['epp_id'] ?? throw new \InvalidArgumentException('epp_id requerido'),
            cantidad: (int) ($data['cantidad'] ?? throw new \InvalidArgumentException('cantidad requerida')),
            observaciones: $data['observaciones'] ?? null,
        );
    }
}

