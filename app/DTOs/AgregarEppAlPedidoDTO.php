<?php

namespace App\DTOs;

/**
 * DTO para agregar EPP a un pedido
 */
class AgregarEppAlPedidoDTO
{
    public function __construct(
        public readonly int $pedidoId,
        public readonly int $eppId,
        public readonly string $talla,
        public readonly int $cantidad,
        public readonly ?string $observaciones = null,
    ) {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1');
        }
    }

    /**
     * Factory method desde request
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            pedidoId: (int)$data['pedido_id'],
            eppId: (int)$data['epp_id'],
            talla: trim($data['talla'] ?? ''),
            cantidad: (int)($data['cantidad'] ?? 1),
            observaciones: trim($data['observaciones'] ?? '') ?: null,
        );
    }
}
